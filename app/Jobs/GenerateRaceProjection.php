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

        Log::info('GenerateRaceProjection: sending prompt', [
            'race_ids' => $this->raceIds,
            'prompt' => $prompt,
        ]);

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 4096,
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
            Log::warning('Invalid batch projection response', ['text' => $text, 'json_error' => json_last_error_msg(), 'race_ids' => $this->raceIds]);

            return;
        }

        Log::info('GenerateRaceProjection: response', [
            'race_ids' => $this->raceIds,
            'results' => $results,
        ]);

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

        $countedBoxNames = $boxRaceResults->pluck('registered_box')->unique()->all();
        $countedBoxes = count($countedBoxNames);
        $totalBoxes = $race->isIslandWide()
            ? VoterRecord::query()->whereNotNull('registered_box')->distinct('registered_box')->count('registered_box')
            : VoterRecord::query()->where('dhaairaa', $race->dhaaira)->whereNotNull('registered_box')->distinct('registered_box')->count('registered_box');

        $votersInCountedBoxes = $countedBoxes > 0
            ? VoterRecord::query()
                ->when(! $race->isIslandWide(), fn ($q) => $q->where('dhaairaa', $race->dhaaira))
                ->whereIn('registered_box', $countedBoxNames)
                ->count()
            : 0;

        $weightedCompletionPct = $eligible > 0
            ? round($votersInCountedBoxes / $eligible * 100, 1)
            : 0;

        $votedInUncountedBoxes = VoterRecord::query()
            ->when(! $race->isIslandWide(), fn ($q) => $q->where('dhaairaa', $race->dhaaira))
            ->where('vote_status', 'voted')
            ->when($countedBoxes > 0, fn ($q) => $q->whereNotIn('registered_box', $countedBoxNames))
            ->count();

        $totalKnownVoted = VoterRecord::query()
            ->when(! $race->isIslandWide(), fn ($q) => $q->where('dhaairaa', $race->dhaaira))
            ->where('vote_status', 'voted')
            ->count();

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
            'voters_in_counted_boxes' => $votersInCountedBoxes,
            'weighted_completion_pct' => $weightedCompletionPct,
            'voted_in_uncounted_boxes' => $votedInUncountedBoxes,
            'total_known_voted' => $totalKnownVoted,
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
            $coveragePct = $s['eligible'] > 0 ? round($s['total_voted'] / $s['eligible'] * 100, 1) : 0;
            $avgVotesPerBox = $s['counted_boxes'] > 0 ? round($s['total_voted'] / $s['counted_boxes']) : 0;
            $uncountedBoxes = max(0, $s['total_boxes'] - $s['counted_boxes']);
            $estimatedRemaining = $avgVotesPerBox * $uncountedBoxes;
            $lead = abs($s['mdp_votes'] - $s['pnc_votes']);
            $leader = $s['mdp_votes'] >= $s['pnc_votes'] ? 'MDP' : 'PNC';
            $scope = $race->dhaaira !== null ? "Constituency: {$race->dhaaira}" : 'Island-wide';

            return implode("\n", [
                "--- Race: {$race->name} (race_id: {$race->id}) ---",
                $scope,
                "Boxes counted: {$s['counted_boxes']}/{$s['total_boxes']} ({$boxesPct}% of boxes) | Votes counted: {$s['total_voted']}/{$s['eligible']} eligible ({$coveragePct}% coverage)",
                "MDP: {$s['mdp_votes']}, PNC: {$s['pnc_votes']} | Current lead: {$lead} in favour of {$leader}",
                "Estimated votes remaining: ~{$estimatedRemaining} (avg {$avgVotesPerBox}/box × {$uncountedBoxes} uncounted boxes)",
                "Pledges — MDP: {$s['mdp_estimate']}, PNC: {$s['pnc_estimate']}",
            ]);
        })->implode("\n\n");

        $instructions = <<<'PROMPT'
You are an election analyst projecting winners from partial results.

## Output
Return a JSON array only (no markdown, no explanation):
[{"race_id": <id>, "projected_winner": "MDP"|"PNC"|"Too Close"|"Too Early", "confidence": "high"|"medium"|"low", "reasoning": "<2 sentences max>"}]

---

## Rules — follow in order, stop at first match

**RULE 1 — Threshold check**
If votes counted ÷ eligible voters < 50%:
→ projected_winner = "Too Early", confidence = "low", STOP.

**RULE 2 — Swing check**
If estimated votes remaining ≥ current vote lead:
→ projected_winner = "Too Close", STOP.

**RULE 3 — Project winner**
If even all estimated remaining votes going to the trailer cannot close the gap:
→ projected_winner = "MDP" or "PNC"

**RULE 4 — Confidence**
| Coverage | Remaining < 25% of lead | Pledges align | Confidence |
|---|---|---|---|
| 50–70% | Yes | Yes or absent | medium |
| 50–70% | Yes | No (conflict) | low |
| >70% | Yes | Yes or absent | high |
| >70% | Yes | No (conflict) | medium |

If pledge data is absent or zero, treat as neutral.
If pledges conflict with vote lead, drop confidence one level.

---

## Reasoning template
- Too Early: "[X]% of eligible voters covered; insufficient votes to project."
- Too Close: "[X]% coverage; [leader] leads by [N] but ~[remaining] estimated votes remaining could swing it."
- Winner: "[X]% coverage; [leader] leads by [N] and ~[remaining] estimated remaining votes cannot close the gap."
PROMPT;

        return $instructions."\n\n".$sections;
    }
}
