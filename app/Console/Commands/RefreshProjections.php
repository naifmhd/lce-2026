<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRaceProjection;
use App\Models\BoxRaceResult;
use Illuminate\Console\Command;

class RefreshProjections extends Command
{
    protected $signature = 'projections:refresh';

    protected $description = 'Regenerate AI projections for all races with box results entered';

    public function handle(): void
    {
        if (! config('services.anthropic.key')) {
            $this->error('ANTHROPIC_API_KEY is not configured.');

            return;
        }

        $raceIds = BoxRaceResult::query()->distinct()->pluck('race_id')->all();

        if (empty($raceIds)) {
            $this->info('No box results found. Nothing to do.');

            return;
        }

        GenerateRaceProjection::dispatch($raceIds);

        $this->info('Dispatched projection job for '.count($raceIds).' race(s).');
    }
}
