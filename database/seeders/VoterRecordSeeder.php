<?php

namespace Database\Seeders;

use App\Models\Pledge;
use App\Models\VoterRecord;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class VoterRecordSeeder extends Seeder
{
    private const FORCE_REBUILD_PHOTOS = true;

    private const IMPORT_CHUNK_SIZE = 500;

    private const TARGET_SHEET_NAME = 'list';

    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const DRAWING_NAMESPACE = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';

    private const DRAWING_MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const OFFICE_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

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
        'I' => 'registered_box',
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
        Schema::disableForeignKeyConstraints();

        try {
            Pledge::query()->truncate();
            VoterRecord::query()->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $excelPath = storage_path('app/DATA.xlsx');

        if (! file_exists($excelPath)) {
            throw new \RuntimeException("Excel file was not found at {$excelPath}");
        }

        $zip = new ZipArchive;

        if ($zip->open($excelPath) !== true) {
            throw new \RuntimeException("Unable to open Excel file at {$excelPath}");
        }

        $worksheetPath = $this->resolveWorksheetPath($zip, self::TARGET_SHEET_NAME);
        $drawingPath = $this->resolveWorksheetDrawingPath($zip, $worksheetPath);
        $sharedStrings = $this->parseSharedStrings($zip);
        $rowToImagePath = $this->parseRowToImagePathMap($zip, $drawingPath);
        $rows = $this->parseRows($zip, $worksheetPath);

        $shouldRebuildPhotos = self::FORCE_REBUILD_PHOTOS || $this->isPhotoDirectoryEmpty();

        if ($shouldRebuildPhotos) {
            Storage::disk('public')->deleteDirectory('voter-record-photos');
            Storage::disk('public')->makeDirectory('voter-record-photos');
        }

        $now = now();
        $pendingChunk = [];
        $usedListNumbers = [];
        $skippedRows = 0;
        $importedRows = 0;
        $processableRows = max(0, count($rows) - 1);

        if ($this->command !== null) {
            $this->command->getOutput()->writeln(
                'Importing voters from sheet ['.self::TARGET_SHEET_NAME.'] in chunks of '.self::IMPORT_CHUNK_SIZE.'...'
            );
            $this->command->getOutput()->progressStart($processableRows);
        }

        foreach ($rows as $rowNumber => $rowValues) {
            if ($rowNumber === 1) {
                continue;
            }

            $record = $this->buildRecord($rowValues, $rowNumber, $sharedStrings);

            if ($record === null) {
                $skippedRows++;

                if ($this->command !== null) {
                    $this->command->getOutput()->progressAdvance();
                }

                continue;
            }

            $record['list_number'] = $this->resolveUniqueListNumber(
                $record['list_number'],
                $rowNumber,
                $usedListNumbers
            );
            $record['photo_path'] = $this->extractAndStorePhotoPath(
                $zip,
                $rowToImagePath[$rowNumber] ?? null,
                $record['id_card_number'],
                $rowNumber,
                $shouldRebuildPhotos
            );
            $record['created_at'] = $now;
            $record['updated_at'] = $now;

            $pledgeValues = [
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

            $pendingChunk[] = [
                'record' => $record,
                'pledge' => $pledgeValues,
            ];

            if (count($pendingChunk) >= self::IMPORT_CHUNK_SIZE) {
                $importedRows += $this->persistChunk($pendingChunk, $now);
                $pendingChunk = [];
            }

            if ($this->command !== null) {
                $this->command->getOutput()->progressAdvance();
            }
        }

        $zip->close();

        if ($pendingChunk !== []) {
            $importedRows += $this->persistChunk($pendingChunk, $now);
        }

        if ($this->command !== null) {
            $this->command->getOutput()->progressFinish();
            $this->command->newLine();
            $this->command->getOutput()->writeln("Imported rows: {$importedRows}");
            $this->command->getOutput()->writeln("Skipped rows: {$skippedRows}");
        }

        if ($importedRows === 0) {
            return;
        }
    }

    /**
     * @param  array<int, array{record: array<string, int|string|null>, pledge: array{mayor: string|null, raeesa: string|null, council: string|null, wdc: string|null}}>  $chunk
     */
    private function persistChunk(array $chunk, \DateTimeInterface $now): int
    {
        $importedRows = 0;

        foreach ($chunk as $item) {
            $voter = VoterRecord::query()->create($item['record']);
            $pledgeValues = $item['pledge'];

            $voter->pledge()->create([
                'mayor' => $pledgeValues['mayor'],
                'raeesa' => $pledgeValues['raeesa'],
                'council' => $pledgeValues['council'],
                'wdc' => $pledgeValues['wdc'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $importedRows++;
        }

        return $importedRows;
    }

    /**
     * @param  array<int, true>  $usedListNumbers
     */
    private function resolveUniqueListNumber(int $candidateListNumber, int $rowNumber, array &$usedListNumbers): int
    {
        $resolvedListNumber = $candidateListNumber;

        if (isset($usedListNumbers[$resolvedListNumber])) {
            $resolvedListNumber = max(1, $rowNumber - 1);

            while (isset($usedListNumbers[$resolvedListNumber])) {
                $resolvedListNumber++;
            }
        }

        $usedListNumbers[$resolvedListNumber] = true;

        return $resolvedListNumber;
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
    private function parseRows(ZipArchive $zip, string $worksheetPath): array
    {
        $xml = $zip->getFromName($worksheetPath);

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
    private function parseRowToImagePathMap(ZipArchive $zip, ?string $drawingPath): array
    {
        if ($drawingPath === null) {
            return [];
        }

        $relationships = $this->parseDrawingRelationships($zip, $drawingPath);
        $drawingXml = $zip->getFromName($drawingPath);

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
    private function parseDrawingRelationships(ZipArchive $zip, string $drawingPath): array
    {
        $drawingDir = dirname($drawingPath);
        $drawingFile = basename($drawingPath);
        $relsPath = $drawingDir.'/_rels/'.$drawingFile.'.rels';
        $relsXml = $zip->getFromName($relsPath);

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

    private function resolveWorksheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $workbookRelsXml === false) {
            throw new \RuntimeException('Workbook metadata files are missing in the Excel archive.');
        }

        $workbookDocument = new DOMDocument;
        $workbookDocument->loadXML($workbookXml);

        $workbookXpath = new DOMXPath($workbookDocument);
        $workbookXpath->registerNamespace('s', self::SPREADSHEET_NAMESPACE);

        $targetRelationshipId = null;
        $sheetNodes = $workbookXpath->query('//s:sheets/s:sheet');

        if ($sheetNodes !== false) {
            foreach ($sheetNodes as $sheetNode) {
                if (! $sheetNode instanceof DOMElement) {
                    continue;
                }

                if ($sheetNode->getAttribute('name') !== $sheetName) {
                    continue;
                }

                $targetRelationshipId = $sheetNode->getAttributeNS(self::OFFICE_RELATIONSHIP_NAMESPACE, 'id');
                break;
            }
        }

        if ($targetRelationshipId === null || $targetRelationshipId === '') {
            throw new \RuntimeException("Worksheet [{$sheetName}] was not found in workbook.");
        }

        $workbookRelsDocument = new DOMDocument;
        $workbookRelsDocument->loadXML($workbookRelsXml);

        $workbookRelsXpath = new DOMXPath($workbookRelsDocument);
        $workbookRelsXpath->registerNamespace('r', self::PACKAGE_RELATIONSHIP_NAMESPACE);
        $relationshipNodes = $workbookRelsXpath->query('//r:Relationship');

        if ($relationshipNodes === false) {
            throw new \RuntimeException('Workbook relationships are invalid.');
        }

        foreach ($relationshipNodes as $relationshipNode) {
            if (! $relationshipNode instanceof DOMElement) {
                continue;
            }

            if ($relationshipNode->getAttribute('Id') !== $targetRelationshipId) {
                continue;
            }

            $target = trim($relationshipNode->getAttribute('Target'));

            if ($target === '') {
                break;
            }

            return $this->normalizeZipPath('xl/'.$target);
        }

        throw new \RuntimeException("Worksheet relationship [{$targetRelationshipId}] could not be resolved.");
    }

    private function resolveWorksheetDrawingPath(ZipArchive $zip, string $worksheetPath): ?string
    {
        $worksheetFile = basename($worksheetPath);
        $worksheetDir = dirname($worksheetPath);
        $relsPath = $worksheetDir.'/_rels/'.$worksheetFile.'.rels';
        $relsXml = $zip->getFromName($relsPath);

        if ($relsXml === false) {
            return null;
        }

        $document = new DOMDocument;
        $document->loadXML($relsXml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('r', self::PACKAGE_RELATIONSHIP_NAMESPACE);
        $relationshipNodes = $xpath->query('//r:Relationship');

        if ($relationshipNodes === false) {
            return null;
        }

        foreach ($relationshipNodes as $relationshipNode) {
            if (! $relationshipNode instanceof DOMElement) {
                continue;
            }

            $type = $relationshipNode->getAttribute('Type');

            if (! str_ends_with($type, '/drawing')) {
                continue;
            }

            $target = trim($relationshipNode->getAttribute('Target'));

            if ($target === '') {
                continue;
            }

            return $this->normalizeZipPath($worksheetDir.'/'.$target);
        }

        return null;
    }

    private function normalizeZipPath(string $path): string
    {
        $segments = explode('/', str_replace('\\', '/', $path));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($normalized);

                continue;
            }

            $normalized[] = $segment;
        }

        return implode('/', $normalized);
    }

    private function extractAndStorePhotoPath(
        ZipArchive $zip,
        ?string $zipImagePath,
        ?string $idCardNumber,
        int $rowNumber,
        bool $shouldRebuildPhotos
    ): ?string {
        if ($zipImagePath === null) {
            return null;
        }

        $safeId = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $idCardNumber);
        $safeId = is_string($safeId) && $safeId !== '' ? $safeId : "row-{$rowNumber}";
        $originalExtension = strtolower(pathinfo($zipImagePath, PATHINFO_EXTENSION));
        $originalExtension = $originalExtension !== '' ? $originalExtension : 'jpg';
        $fileName = "voter-record-photos/{$safeId}.{$originalExtension}";
        $optimizedFileName = "voter-record-photos/{$safeId}.jpg";

        if (! $shouldRebuildPhotos) {
            if (Storage::disk('public')->exists($optimizedFileName)) {
                return $optimizedFileName;
            }

            if (Storage::disk('public')->exists($fileName)) {
                return $fileName;
            }
        }

        $imageBytes = $zip->getFromName($zipImagePath);

        if ($imageBytes === false) {
            return null;
        }

        $optimizedImageBytes = $this->optimizeImageBytes($imageBytes);

        if ($optimizedImageBytes !== null) {
            Storage::disk('public')->put($optimizedFileName, $optimizedImageBytes);

            return $optimizedFileName;
        }

        Storage::disk('public')->put($fileName, $imageBytes);

        return $fileName;
    }

    private function isPhotoDirectoryEmpty(): bool
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('voter-record-photos')) {
            return true;
        }

        return $disk->files('voter-record-photos') === [];
    }

    private function optimizeImageBytes(string $imageBytes): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $sourceImage = @imagecreatefromstring($imageBytes);

        if ($sourceImage === false) {
            return null;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($sourceImage);

            return null;
        }

        $maxWidth = 320;
        $targetWidth = min($width, $maxWidth);
        $targetHeight = (int) round(($targetWidth / $width) * $height);

        $targetImage = imagecreatetruecolor($targetWidth, max(1, $targetHeight));

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            max(1, $targetHeight),
            $width,
            $height
        );

        ob_start();
        imagejpeg($targetImage, null, 78);
        $optimized = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (! is_string($optimized) || $optimized === '') {
            return null;
        }

        return $optimized;
    }
}
