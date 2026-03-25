<?php

namespace App\Jobs;

use App\Models\BoxRaceResult;
use App\Models\ElectionRace;
use App\Models\Pledge;
use App\Models\VoterRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateRaceProjection implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $raceId) {}

    public function handle(): void
    {
        $race = ElectionRace::find($this->raceId);

        if ($race === null) {
            return;
        }

        $stats = $this->computeStats($race);
        $prompt = $this->buildPrompt($race, $stats);

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('Anthropic API call failed for race '.$race->id, ['status' => $response->status()]);

            return;
        }

        $text = $response->json('content.0.text', '');
        $data = json_decode($text, true);

        if (! is_array($data) || ! isset($data['projected_winner'])) {
            Log::warning('Invalid projection response for race '.$race->id, ['text' => $text]);

            return;
        }

        $race->update([
            'projected_winner' => $data['projected_winner'],
            'projection_confidence' => $data['confidence'] ?? null,
            'projection_reasoning' => $data['reasoning'] ?? null,
            'projection_updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeStats(ElectionRace $race): array
    {
        $eligible = $race->isIslandWide()
            ? VoterRecord::query()->count()
            : VoterRecord::query()->where('dhaairaa', $race->dhaaira)->count();

        $boxRaceResults = BoxRaceResult::query()
            ->where('race_id', $race->id)
            ->with('candidateVotes.candidate:id,affiliation')
            ->get();

        $totalVoted = $boxRaceResults->sum(fn ($brr) => $brr->candidateVotes->sum('votes') + $brr->invalid_votes);
        $invalidVotes = $boxRaceResults->sum('invalid_votes');

        $votesPerParty = ['MDP' => 0, 'PNC' => 0];
        foreach ($boxRaceResults as $brr) {
            foreach ($brr->candidateVotes as $cv) {
                $aff = $cv->candidate?->affiliation;
                if ($aff !== null && isset($votesPerParty[$aff])) {
                    $votesPerParty[$aff] += $cv->votes;
                }
            }
        }

        $estimate = $this->buildPledgeEstimate($race);

        $countedBoxes = $boxRaceResults->pluck('registered_box')->unique()->count();
        $totalBoxes = $race->isIslandWide()
            ? VoterRecord::query()->whereNotNull('registered_box')->distinct('registered_box')->count('registered_box')
            : VoterRecord::query()->where('dhaairaa', $race->dhaaira)->whereNotNull('registered_box')->distinct('registered_box')->count('registered_box');

        $turnoutPct = $eligible > 0 ? round($totalVoted / $eligible * 100, 1) : 0;

        return [
            'eligible' => $eligible,
            'total_voted' => $totalVoted,
            'invalid_votes' => $invalidVotes,
            'mdp_votes' => $votesPerParty['MDP'],
            'pnc_votes' => $votesPerParty['PNC'],
            'mdp_estimate' => $estimate['MDP'],
            'pnc_estimate' => $estimate['PNC'],
            'mdp_diff' => $votesPerParty['MDP'] - $estimate['MDP'],
            'pnc_diff' => $votesPerParty['PNC'] - $estimate['PNC'],
            'turnout_pct' => $turnoutPct,
            'votes_remaining' => max(0, $eligible - $totalVoted),
            'counted_boxes' => $countedBoxes,
            'total_boxes' => $totalBoxes,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function buildPledgeEstimate(ElectionRace $race): array
    {
        $field = $race->type;
        $query = Pledge::query();

        if (! $race->isIslandWide()) {
            $query->whereHas('voter', fn ($q) => $q->where('dhaairaa', $race->dhaaira));
        }

        return [
            'MDP' => (clone $query)->where($field, 'MDP')->count(),
            'PNC' => (clone $query)->where($field, 'PNC')->count(),
        ];
    }

    private function buildPrompt(ElectionRace $race, array $stats): string
    {
        $boxesPct = $stats['total_boxes'] > 0
            ? round($stats['counted_boxes'] / $stats['total_boxes'] * 100, 1)
            : 0;

        $lines = [
            'You are analyzing partial election results to project a winner.',
            '',
            "Race: {$race->name}",
        ];

        if ($race->dhaaira !== null) {
            $lines[] = "Constituency: {$race->dhaaira}";
        }

        $lines = array_merge($lines, [
            '',
            "Results ({$stats['counted_boxes']}/{$stats['total_boxes']} boxes counted, {$boxesPct}% of boxes):",
            "- Eligible voters: {$stats['eligible']}",
            "- Votes counted: {$stats['total_voted']} ({$stats['turnout_pct']}% turnout so far)",
            "- MDP votes: {$stats['mdp_votes']}",
            "- PNC votes: {$stats['pnc_votes']}",
            "- Invalid votes: {$stats['invalid_votes']}",
            "- Uncounted votes remaining: ~{$stats['votes_remaining']}",
            '',
            'Pledge estimates (canvassing data):',
            "- MDP pledges: {$stats['mdp_estimate']}",
            "- PNC pledges: {$stats['pnc_estimate']}",
            "- MDP pledge vs actual: {$stats['mdp_diff']} (positive means ahead of estimate)",
            "- PNC pledge vs actual: {$stats['pnc_diff']}",
            '',
            'Respond with JSON only (no markdown):',
            '{"projected_winner": "MDP" or "PNC" or "Too Close", "confidence": "high" or "medium" or "low", "reasoning": "1-2 sentences"}',
        ]);

        return implode("\n", $lines);
    }
}
