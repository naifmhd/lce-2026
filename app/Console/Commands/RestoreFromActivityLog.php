<?php

namespace App\Console\Commands;

use App\Models\Pledge;
use App\Models\VoterRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreFromActivityLog extends Command
{
    protected $signature = 'restore:from-activity-log
                            {--type=all : Which model to restore: voter, pledge, or all}
                            {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Restore voter_records and pledge data from activity_log entries';

    /** @var array<string, int> */
    private array $stats = [
        'restored' => 0,
        'skipped' => 0,
        'not_found' => 0,
    ];

    public function handle(): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if (! in_array($type, ['all', 'voter', 'pledge'])) {
            $this->error('Invalid --type. Use: voter, pledge, or all.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        } else {
            $this->warn('This will overwrite current pledge/voter field values with the last known state from activity_log.');
            if (! $this->confirm('Continue?')) {
                return self::SUCCESS;
            }
        }

        if (in_array($type, ['all', 'pledge'])) {
            $this->restorePledges($dryRun);
        }

        if (in_array($type, ['all', 'voter'])) {
            $this->restoreVoterRecords($dryRun);
        }

        $this->newLine();
        $this->table(['Restored', 'Skipped (already match)', 'Not found'], [
            [$this->stats['restored'], $this->stats['skipped'], $this->stats['not_found']],
        ]);

        return self::SUCCESS;
    }

    private function restorePledges(bool $dryRun): void
    {
        $this->info('Processing pledge activity logs...');

        $trackedFields = ['mayor', 'raeesa', 'council', 'wdc'];
        $lastKnown = $this->buildLastKnownState('pledge', $trackedFields);

        $bar = $this->output->createProgressBar(count($lastKnown));
        $bar->start();

        foreach ($lastKnown as $pledgeId => $fields) {
            $pledge = Pledge::query()->find($pledgeId);

            if (! $pledge) {
                $this->stats['not_found']++;
                $bar->advance();

                continue;
            }

            $diff = $this->diffFields($pledge->getAttributes(), $fields, $trackedFields);

            if (empty($diff)) {
                $this->stats['skipped']++;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                Pledge::query()->whereKey($pledgeId)->update($diff);
            }

            $this->stats['restored']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function restoreVoterRecords(bool $dryRun): void
    {
        $this->info('Processing voter activity logs...');

        $trackedFields = ['agent', 'registered_box', 'mobile', 're_reg_travel', 'comments'];
        $lastKnown = $this->buildLastKnownState('voter', $trackedFields);

        $bar = $this->output->createProgressBar(count($lastKnown));
        $bar->start();

        foreach ($lastKnown as $voterId => $fields) {
            $voter = VoterRecord::query()->find($voterId);

            if (! $voter) {
                $this->stats['not_found']++;
                $bar->advance();

                continue;
            }

            $diff = $this->diffFields($voter->getAttributes(), $fields, $trackedFields);

            if (empty($diff)) {
                $this->stats['skipped']++;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                VoterRecord::query()->whereKey($voterId)->update($diff);
            }

            $this->stats['restored']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Build a map of subject_id → last known field values by replaying activity_log in chronological order.
     *
     * @param  list<string>  $trackedFields
     * @return array<int, array<string, mixed>>
     */
    private function buildLastKnownState(string $logName, array $trackedFields): array
    {
        $lastKnown = [];

        DB::table('activity_log')
            ->where('log_name', $logName)
            ->orderBy('created_at')
            ->select(['subject_id', 'properties'])
            ->each(function (object $row) use (&$lastKnown, $trackedFields): void {
                $props = json_decode($row->properties, true);
                $attributes = $props['attributes'] ?? [];

                foreach ($trackedFields as $field) {
                    if (array_key_exists($field, $attributes)) {
                        $lastKnown[(int) $row->subject_id][$field] = $attributes[$field];
                    }
                }
            });

        return $lastKnown;
    }

    /**
     * Return only fields whose last-known value differs from the current record value.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $lastKnown
     * @param  list<string>  $trackedFields
     * @return array<string, mixed>
     */
    private function diffFields(array $current, array $lastKnown, array $trackedFields): array
    {
        $diff = [];

        foreach ($trackedFields as $field) {
            if (! array_key_exists($field, $lastKnown)) {
                continue;
            }

            $currentValue = $current[$field] ?? null;
            $knownValue = $lastKnown[$field];

            // Normalize empty string to null for comparison
            if ($currentValue === '') {
                $currentValue = null;
            }
            if ($knownValue === '') {
                $knownValue = null;
            }

            if ($currentValue !== $knownValue) {
                $diff[$field] = $lastKnown[$field];
            }
        }

        return $diff;
    }
}
