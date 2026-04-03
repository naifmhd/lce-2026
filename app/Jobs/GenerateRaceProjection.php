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

        $pledgeData = $this->buildPledgeEstimate($race);

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
            'mdp_pledged_voted' => $pledgeData['mdp_pledged_voted'],
            'pnc_pledged_voted' => $pledgeData['pnc_pledged_voted'],
            'mdp_pledged_not_voted' => $pledgeData['mdp_pledged_not_voted'],
            'pnc_pledged_not_voted' => $pledgeData['pnc_pledged_not_voted'],
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
        if ($race->type === 'referendum') {
            return ['mdp_pledged_voted' => 0, 'pnc_pledged_voted' => 0, 'mdp_pledged_not_voted' => 0, 'pnc_pledged_not_voted' => 0];
        }

        $field = $race->type;
        $query = Pledge::query();

        if (! $race->isIslandWide()) {
            $query->whereHas('voter', fn ($q) => $q->where('dhaairaa', $race->dhaaira));
        }

        $totalMdp = (clone $query)->where($field, 'MDP')->count();
        $totalPnc = (clone $query)->where($field, 'PNC')->count();

        $votedQuery = (clone $query)
            ->whereHas('voter', fn ($q) => $q->whereRaw("LOWER(TRIM(vote_status)) = 'voted'"));

        $mdpVoted = (clone $votedQuery)->where($field, 'MDP')->count();
        $pncVoted = (clone $votedQuery)->where($field, 'PNC')->count();

        return [
            'mdp_pledged_voted' => $mdpVoted,
            'pnc_pledged_voted' => $pncVoted,
            'mdp_pledged_not_voted' => $totalMdp - $mdpVoted,
            'pnc_pledged_not_voted' => $totalPnc - $pncVoted,
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
            $knownVotedPct = $s['eligible'] > 0 ? round($s['total_known_voted'] / $s['eligible'] * 100, 1) : 0;
            $countedOfVotedPct = $s['total_known_voted'] > 0 ? round($s['total_voted'] / $s['total_known_voted'] * 100, 1) : 0;
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
                "MDP: {$s['mdp_votes']}, PNC: {$s['pnc_votes']} | Lead: {$lead} → {$leader}",
                "Estimated remaining (box-based): ~{$estimatedRemaining} (avg {$avgVotesPerBox}/box × {$uncountedBoxes} uncounted boxes)",
                "Voter tracker: {$s['total_known_voted']}/{$s['eligible']} known voted ({$knownVotedPct}%) | {$s['voted_in_uncounted_boxes']} voted in uncounted boxes",
                "Pledges (already voted): MDP {$s['mdp_pledged_voted']}, PNC {$s['pnc_pledged_voted']}",
                "Pledges (outstanding, not yet voted): MDP {$s['mdp_pledged_not_voted']}, PNC {$s['pnc_pledged_not_voted']}",
            ]);
        })->implode("\n\n");

        $instructions = <<<'PROMPT'
You are an election analyst projecting winners from partial results.

## Output
Return a JSON array only (no markdown, no explanation):
[{"race_id": <id>, "projected_winner": "MDP"|"PNC"|"Too Close"|"Too Early", "confidence": "high"|"medium"|"low", "reasoning": "<2 sentences max>"}]

---

## Data glossary
- **Votes counted**: actual ballot tallies from returned boxes
- **Known voted**: voters with vote_status='voted' (real-time field — may lead box-result entry)
- **Voted in uncounted boxes**: known-voted people whose box results are not yet entered — reliable lower bound on remaining countable votes
- **Pledges (already voted)**: supporters who pledged a party AND have already voted — should roughly match vote shares in counted boxes
- **Pledges (outstanding)**: supporters who pledged but haven't voted yet — expected future votes still to arrive

---

## Rules — follow in order, stop at first match

**RULE 1 — Threshold check**
If votes counted ÷ eligible voters < 50%:
→ projected_winner = "Too Early", confidence = "low", STOP.

**RULE 2 — Swing check**
Compute: best_remaining = max(estimated_remaining_box_based, voted_in_uncounted_boxes)
Also add outstanding pledges for the current trailer party.
If best_remaining + outstanding_pledges_for_trailer ≥ current vote lead:
→ projected_winner = "Too Close", STOP.

**RULE 3 — Project winner**
If (best_remaining + outstanding_pledges_for_trailer) < current vote lead:
→ projected_winner = "MDP" or "PNC"

**RULE 4 — Confidence**
Primary: vote lead vs best_remaining.
Secondary: pledge alignment — voted_pledges direction should match vote lead direction.

| Coverage | Remaining < 25% of lead | Pledges align | Confidence |
|---|---|---|---|
| 50–70% | Yes | Yes or absent | medium |
| 50–70% | Yes | No (conflict) | low |
| >70% | Yes | Yes or absent | high |
| >70% | Yes | No (conflict) | medium |

- "Pledges align" = voted-pledges leader matches vote leader AND outstanding pledges for trailer < 50% of lead.
- If pledge data is absent or zero, treat as neutral (no penalty or bonus).
- If outstanding pledges for the trailer exceed 50% of the lead, drop confidence one level.

---

## Reasoning template
- Too Early: "[X]% of eligible voters covered; insufficient data to project."
- Too Close: "[X]% coverage; [leader] leads by [N] but ~[best_remaining] votes remain ([voted_in_uncounted] in uncounted boxes)."
- Winner: "[X]% coverage; [leader] leads by [N] with ~[best_remaining] remaining votes; pledge data [confirms / does not conflict with] the lead."
PROMPT;

        return $instructions."\n\n".$sections;
    }
}
