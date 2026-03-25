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

    /** @param array<int, int> $raceIds */
    public function __construct(public readonly array $raceIds) {}

    public function handle(): void
    {
        $races = ElectionRace::whereIn('id', $this->raceIds)->get();

        if ($races->isEmpty()) {
            return;
        }

        $raceStats = $races->mapWithKeys(fn (ElectionRace $race) => [
            $race->id => $this->computeStats($race),
        ]);

        $prompt = $this->buildPrompt($races, $raceStats->all());

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('Anthropic API call failed', ['status' => $response->status(), 'race_ids' => $this->raceIds]);

            return;
        }

        $text = trim($response->json('content.0.text', ''));
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $results = json_decode(trim($text), true);

        if (! is_array($results)) {
            Log::warning('Invalid batch projection response', ['text' => $text, 'race_ids' => $this->raceIds]);

            return;
        }

        foreach ($results as $result) {
            if (! isset($result['race_id'], $result['projected_winner'])) {
                continue;
            }

            ElectionRace::where('id', $result['race_id'])->update([
                'projected_winner' => $result['projected_winner'],
                'projection_confidence' => $result['confidence'] ?? null,
                'projection_reasoning' => $result['reasoning'] ?? null,
                'projection_updated_at' => now(),
            ]);
        }
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

        $totalVoted = $boxRaceResults->sum(fn ($brr) => $brr->candidateVotes->sum('votes'));

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

    /**
     * @param  \Illuminate\Support\Collection<int, ElectionRace>  $races
     * @param  array<int, array<string, mixed>>  $raceStats
     */
    private function buildPrompt(\Illuminate\Support\Collection $races, array $raceStats): string
    {
        $sections = $races->map(function (ElectionRace $race) use ($raceStats): string {
            $s = $raceStats[$race->id];
            $boxesPct = $s['total_boxes'] > 0 ? round($s['counted_boxes'] / $s['total_boxes'] * 100, 1) : 0;
            $scope = $race->dhaaira !== null ? "Constituency: {$race->dhaaira}" : 'Island-wide';

            return implode("\n", [
                "--- Race: {$race->name} (race_id: {$race->id}) ---",
                $scope,
                "Boxes counted: {$s['counted_boxes']}/{$s['total_boxes']} ({$boxesPct}%) | Eligible: {$s['eligible']} | Turnout so far: {$s['turnout_pct']}%",
                "Votes — MDP: {$s['mdp_votes']}, PNC: {$s['pnc_votes']} | Remaining: ~{$s['votes_remaining']}",
                "Pledges (canvassing) — MDP: {$s['mdp_estimate']}, PNC: {$s['pnc_estimate']} | Actual vs pledge — MDP: {$s['mdp_diff']}, PNC: {$s['pnc_diff']}",
            ]);
        })->implode("\n\n");

        $instructions = <<<'PROMPT'
You are an election analyst projecting winners from partial results. Be appropriately cautious — small early samples are unreliable.

CONFIDENCE CALIBRATION (based on % of boxes counted):
- < 10% counted  → confidence MUST be "low"; only call a winner if pledge lead is overwhelming (>3:1 ratio)
- 10–30% counted → confidence at most "medium"; "high" only if vote lead exceeds 20% of eligible AND aligns with pledges
- 30–60% counted → "medium" or "high" based on vote lead size and pledge alignment
- > 60% counted  → "high" allowed if the lead is mathematically difficult to close

PLEDGE DATA WEIGHTING:
- Pledge totals represent canvassed voter intent and are more reliable than early vote counts
- When < 15% boxes counted: base the projection primarily on pledges, not current votes
- When votes and pledges point opposite directions: lean toward pledges, lower confidence by one level, and note the discrepancy in reasoning
- When votes and pledges agree: confidence may be one level higher than counting % alone suggests

REASONING (1 sentence):
- Lead with the most reliable signal (pledges when early, votes when substantial counts are in)
- Always mention the % of boxes counted so the reader understands reliability
- When < 25% counted use hedged language: "early signs favour", "pledges suggest", "insufficient data to call"
- Do NOT use phrases like "unlikely to be overcome", "dominant", or "clear winner" when < 50% is counted

WHEN TO USE "Too Close":
- Pledge counts within 20% of each other AND < 30% boxes counted
- Vote lead < 10% of total votes cast AND pledges within 15% of each other
- Any race where the data does not support a directional call

Return a JSON array only (no markdown):
[{"race_id": <id>, "projected_winner": "MDP" or "PNC" or "Too Close", "confidence": "high" or "medium" or "low", "reasoning": "1 sentence"}, ...]
PROMPT;

        return $instructions."\n\n".$sections;
    }
}
