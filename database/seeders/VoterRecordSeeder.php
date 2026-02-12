<?php

namespace Database\Seeders;

use App\Models\Pledge;
use App\Models\VoterRecord;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class VoterRecordSeeder extends Seeder
{
    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const DRAWING_NAMESPACE = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';

    private const DRAWING_MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const OFFICE_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @var array<string, string>
     */
    private array $columnToFieldMap = [
        'A' => 'list_number',
        'B' => 'id_card_number',
        'D' => 'name',
        'E' => 'sex',
        'F' => 'mobile',
        'G' => 'dob',
        'H' => 'age',
        'I' => 'island',
        'J' => 'majilis_con',
        'K' => 'address',
        'L' => 'dhaairaa',
        'M' => 'mayor',
        'N' => 'raeesa',
        'O' => 'council',
        'P' => 'wdc',
        'Q' => 're_reg_travel',
        'R' => 'comments',
        'S' => 'vote_status',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $excelPath = storage_path('app/DATA.xlsx');

        if (! file_exists($excelPath)) {
            throw new \RuntimeException("Excel file was not found at {$excelPath}");
        }

        $zip = new ZipArchive;

        if ($zip->open($excelPath) !== true) {
            throw new \RuntimeException("Unable to open Excel file at {$excelPath}");
        }

        $sharedStrings = $this->parseSharedStrings($zip);
        $rowToImagePath = $this->parseRowToImagePathMap($zip);
        $rows = $this->parseRows($zip);

        Storage::disk('public')->deleteDirectory('voter-record-photos');
        Storage::disk('public')->makeDirectory('voter-record-photos');

        $now = now();
        $records = [];
        $pledgesByRecordIndex = [];

        foreach ($rows as $rowNumber => $rowValues) {
            if ($rowNumber === 1) {
                continue;
            }

            $record = $this->buildRecord($rowValues, $rowNumber, $sharedStrings);

            if ($record === null) {
                continue;
            }

            $record['photo_path'] = $this->extractAndStorePhotoPath($zip, $rowToImagePath[$rowNumber] ?? null, $record['id_card_number'], $rowNumber);
            $record['created_at'] = $now;
            $record['updated_at'] = $now;

            $pledgesByRecordIndex[] = [
                'mayor' => $record['mayor'] ?? null,
                'raeesa' => $record['raeesa'] ?? null,
                'council' => $record['council'] ?? null,
                'wdc' => $record['wdc'] ?? null,
            ];

            unset(
                $record['mayor'],
                $record['raeesa'],
                $record['council'],
                $record['wdc']
            );

            $records[] = $record;
        }

        $zip->close();

        if ($records === []) {
            return;
        }

        Pledge::query()->delete();
        VoterRecord::query()->delete();

        foreach ($records as $index => $record) {
            $voter = VoterRecord::query()->create($record);
            $pledgeValues = $pledgesByRecordIndex[$index] ?? null;

            if ($pledgeValues === null) {
                continue;
            }

            $voter->pledge()->create([
                'mayor' => $pledgeValues['mayor'],
                'raeesa' => $pledgeValues['raeesa'],
                'council' => $pledgeValues['council'],
                'wdc' => $pledgeValues['wdc'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', self::SPREADSHEET_NAMESPACE);

        $sharedStrings = [];
        $stringItems = $xpath->query('//s:si');

        if ($stringItems === false) {
            return $sharedStrings;
        }

        foreach ($stringItems as $stringItem) {
            $textNodes = $xpath->query('.//s:t', $stringItem);
            $value = '';

            if ($textNodes !== false) {
                foreach ($textNodes as $textNode) {
                    $value .= $textNode->textContent;
                }
            }

            $sharedStrings[] = trim($value);
        }

        return $sharedStrings;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseRows(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($xml === false) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', self::SPREADSHEET_NAMESPACE);

        $rowNodes = $xpath->query('//s:sheetData/s:row');

        if ($rowNodes === false) {
            return [];
        }

        $rows = [];

        foreach ($rowNodes as $rowNode) {
            if (! $rowNode instanceof DOMElement) {
                continue;
            }

            $rowNumber = (int) $rowNode->getAttribute('r');

            if ($rowNumber < 1) {
                continue;
            }

            $cells = [];
            $cellNodes = $xpath->query('s:c', $rowNode);

            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cellNode) {
                if (! $cellNode instanceof DOMElement) {
                    continue;
                }

                $reference = $cellNode->getAttribute('r');
                $column = preg_replace('/\d+/', '', $reference);

                if (! is_string($column) || $column === '') {
                    continue;
                }

                $valueNode = $xpath->query('s:v', $cellNode)->item(0);
                $cells[$column] = trim($valueNode?->textContent ?? '');
            }

            $rows[$rowNumber] = $cells;
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $rowValues
     * @param  array<int, string>  $sharedStrings
     * @return array<string, int|string|null>|null
     */
    private function buildRecord(array $rowValues, int $rowNumber, array $sharedStrings): ?array
    {
        $record = [];

        foreach ($this->columnToFieldMap as $column => $field) {
            $rawValue = $rowValues[$column] ?? '';
            $resolvedValue = $this->resolveRawValue($rowValues, $column, $rawValue, $sharedStrings);

            if ($field === 'list_number') {
                $record[$field] = $this->toInteger($resolvedValue);

                continue;
            }

            if ($field === 'dob') {
                $record[$field] = $this->toExcelDate($resolvedValue);

                continue;
            }

            if ($field === 'age') {
                $record[$field] = $this->toInteger($resolvedValue);

                continue;
            }

            if ($field === 'mobile') {
                $record[$field] = $this->normalizeMobile($resolvedValue);

                continue;
            }

            $record[$field] = $this->normalizeText($resolvedValue);
        }

        if (($record['list_number'] ?? null) === null) {
            $record['list_number'] = $rowNumber - 1;
        }

        if (($record['id_card_number'] ?? null) === null && ($record['name'] ?? null) === null) {
            return null;
        }

        return $record;
    }

    /**
     * @param  array<string, string>  $rowValues
     * @param  array<int, string>  $sharedStrings
     */
    private function resolveRawValue(array $rowValues, string $column, string $rawValue, array $sharedStrings): string
    {
        if (! isset($rowValues[$column])) {
            return '';
        }

        if ($rawValue === '') {
            return '';
        }

        $sharedColumns = ['B', 'D', 'E', 'F', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];

        if (in_array($column, $sharedColumns, true) && ctype_digit($rawValue) && isset($sharedStrings[(int) $rawValue])) {
            return $sharedStrings[(int) $rawValue];
        }

        return $rawValue;
    }

    private function normalizeText(string $value): ?string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function normalizeMobile(string $value): ?string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return $normalized;
        }

        $floatValue = (float) $normalized;

        if ((int) $floatValue === $floatValue) {
            return (string) (int) $floatValue;
        }

        return rtrim(rtrim((string) $floatValue, '0'), '.');
    }

    private function toInteger(string $value): ?int
    {
        $normalized = trim($value);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized);
    }

    private function toExcelDate(string $value): ?string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        if (is_numeric($normalized)) {
            $excelDays = (int) floor((float) $normalized);
            $date = new \DateTimeImmutable('1899-12-30');
            $date = $date->modify("+{$excelDays} days");

            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($normalized);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @return array<int, string>
     */
    private function parseRowToImagePathMap(ZipArchive $zip): array
    {
        $relationships = $this->parseDrawingRelationships($zip);
        $drawingXml = $zip->getFromName('xl/drawings/drawing1.xml');

        if ($drawingXml === false) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadXML($drawingXml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xdr', self::DRAWING_NAMESPACE);
        $xpath->registerNamespace('a', self::DRAWING_MAIN_NAMESPACE);

        $anchors = $xpath->query('//xdr:oneCellAnchor');

        if ($anchors === false) {
            return [];
        }

        $rowToImagePath = [];

        foreach ($anchors as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $rowNode = $xpath->query('xdr:from/xdr:row', $anchor)->item(0);
            $blipNode = $xpath->query('xdr:pic/xdr:blipFill/a:blip', $anchor)->item(0);

            if (! $rowNode instanceof DOMElement || ! $blipNode instanceof DOMElement) {
                continue;
            }

            $relationshipId = $blipNode->getAttributeNS(self::OFFICE_RELATIONSHIP_NAMESPACE, 'embed');

            if ($relationshipId === '' || ! isset($relationships[$relationshipId])) {
                continue;
            }

            $sheetRow = ((int) $rowNode->textContent) + 1;
            $rowToImagePath[$sheetRow] = $relationships[$relationshipId];
        }

        return $rowToImagePath;
    }

    /**
     * @return array<string, string>
     */
    private function parseDrawingRelationships(ZipArchive $zip): array
    {
        $relsXml = $zip->getFromName('xl/drawings/_rels/drawing1.xml.rels');

        if ($relsXml === false) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadXML($relsXml);

        $relationships = [];
        $relationshipNodes = $document->getElementsByTagName('Relationship');

        foreach ($relationshipNodes as $relationshipNode) {
            if (! $relationshipNode instanceof DOMElement) {
                continue;
            }

            $id = $relationshipNode->getAttribute('Id');
            $target = $relationshipNode->getAttribute('Target');

            if ($id === '' || $target === '') {
                continue;
            }

            $relationships[$id] = $this->normalizeMediaTargetPath($target);
        }

        return $relationships;
    }

    private function normalizeMediaTargetPath(string $target): string
    {
        $normalized = ltrim(str_replace('../', '', $target), '/');

        if (str_starts_with($normalized, 'xl/')) {
            return $normalized;
        }

        return 'xl/'.$normalized;
    }

    private function extractAndStorePhotoPath(ZipArchive $zip, ?string $zipImagePath, ?string $idCardNumber, int $rowNumber): ?string
    {
        if ($zipImagePath === null) {
            return null;
        }

        $imageBytes = $zip->getFromName($zipImagePath);

        if ($imageBytes === false) {
            return null;
        }

        $extension = strtolower(pathinfo($zipImagePath, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : 'jpg';

        $safeId = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $idCardNumber);
        $safeId = is_string($safeId) && $safeId !== '' ? $safeId : "row-{$rowNumber}";
        $fileName = "voter-record-photos/{$safeId}.{$extension}";

        Storage::disk('public')->put($fileName, $imageBytes);

        return $fileName;
    }
}
