<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\BoxResultRequest;
use App\Jobs\GenerateRaceProjection;
use App\Models\BoxRaceResult;
use App\Models\BoxResult;
use App\Models\Candidate;
use App\Models\ElectionRace;
use App\Models\Pledge;
use App\Models\User;
use App\Models\VoterRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ResultsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canEnterResults = $user->isAdmin() || $user->hasRole(UserRole::Results->value);

        $races = $this->scopeRacesForUser(ElectionRace::query()->orderBy('sort_order'), $user)->get();

        $candidates = Candidate::query()
            ->with('race:id,name,dhaaira,type,sort_order')
            ->whereIn('race_id', $races->pluck('id'))
            ->get();

        $boxRaceResults = BoxRaceResult::query()
            ->with('candidateVotes.candidate:id,name,affiliation,race_id')
            ->whereIn('race_id', $races->pluck('id'))
            ->get();

        $availableBoxes = $canEnterResults ? $this->buildAvailableBoxes() : [];

        $raceStats = $this->buildRaceStats($races, $candidates, $boxRaceResults, $this->buildAvailableBoxes());

        $islandSummary = $this->buildIslandSummary($races, $raceStats, $this->buildAvailableBoxes());

        $existingBoxData = [];
        if ($canEnterResults) {
            $boxResultsByBox = BoxResult::all()->groupBy('registered_box');
            $existingBoxData = $boxRaceResults
                ->groupBy('registered_box')
                ->map(fn (Collection $results, string $box) => [
                    'invalid_votes' => ($boxResultsByBox->get($box) ?? collect())
                        ->pluck('invalid_votes', 'election_type')
                        ->all(),
                    'races' => $results->mapWithKeys(fn (BoxRaceResult $brr) => [
                        $brr->race_id => $brr->candidateVotes
                            ->mapWithKeys(fn ($cv) => [$cv->candidate_id => $cv->votes])
                            ->all(),
                    ])->all(),
                ])
                ->all();
        }

        $electionTypes = [
            ['key' => 'local_council', 'label' => 'Local Council Election', 'raceTypes' => ['council', 'mayor']],
            ['key' => 'wdc',           'label' => 'WDC Election',           'raceTypes' => ['wdc', 'raeesa']],
            ['key' => 'referendum',    'label' => 'Public Referendum 2026', 'raceTypes' => ['referendum']],
        ];

        return Inertia::render('Results/Index', [
            'canEnterResults' => $canEnterResults,
            'electionTypes' => $electionTypes,
            'races' => $races->map(fn (ElectionRace $race) => [
                'id' => $race->id,
                'name' => $race->name,
                'dhaaira' => $race->dhaaira,
                'type' => $race->type,
                'sort_order' => $race->sort_order,
                'projected_winner' => $race->projected_winner,
                'projection_confidence' => $race->projection_confidence,
                'projection_reasoning' => $race->projection_reasoning,
                'projection_updated_at' => $race->projection_updated_at?->toISOString(),
            ]),
            'candidates' => $candidates->map(fn (Candidate $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'affiliation' => $candidate->affiliation,
                'race_id' => $candidate->race_id,
            ]),
            'raceStats' => $raceStats,
            'availableBoxes' => $availableBoxes,
            'islandSummary' => $islandSummary,
            'existingBoxData' => $existingBoxData,
        ]);
    }

    private function scopeRacesForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->hasRole(UserRole::Results->value)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            foreach ($user->roleKeys() as $roleKey) {
                $dhaaira = UserRole::dhaairaaCodeForRole($roleKey);
                if ($dhaaira !== null) {
                    $type = str_ends_with($roleKey, '-council') ? 'council' : 'wdc';
                    $q->orWhere(fn (Builder $sub) => $sub->where('type', $type)->where('dhaaira', $dhaaira));
                }
            }

            if ($user->hasRole(UserRole::Mayor->value)) {
                $q->orWhere('type', 'mayor');
            }

            if ($user->hasRole(UserRole::Raeesa->value)) {
                $q->orWhere('type', 'raeesa');
            }
        });
    }

    public function storeBoxResult(BoxResultRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->hasRole(UserRole::Results->value)) {
            abort(403);
        }

        $validated = $request->validated();
        $registeredBox = $validated['registered_box'];

        foreach ($validated['invalid_votes'] as $electionType => $count) {
            BoxResult::updateOrCreate(
                ['registered_box' => $registeredBox, 'election_type' => $electionType],
                ['invalid_votes' => $count],
            );
        }

        foreach ($validated['races'] as $raceData) {
            $boxRaceResult = BoxRaceResult::updateOrCreate(
                ['registered_box' => $registeredBox, 'race_id' => $raceData['race_id']],
                ['invalid_votes' => 0],
            );

            foreach ($raceData['votes'] as $candidateId => $votes) {
                $boxRaceResult->candidateVotes()->updateOrCreate(
                    ['candidate_id' => (int) $candidateId],
                    ['votes' => $votes],
                );
            }
        }

        if (config('services.anthropic.key')) {
            $raceIds = collect($validated['races'])->pluck('race_id')->unique()->values()->all();
            GenerateRaceProjection::dispatch($raceIds);
        }

        return redirect()->route('results.index');
    }

    /**
     * @return array<int, array{registered_box: string, dhaairas: array<int, string>}>
     */
    private function buildAvailableBoxes(): array
    {
        return VoterRecord::query()
            ->whereNotNull('registered_box')
            ->select('registered_box', 'dhaairaa')
            ->distinct()
            ->orderBy('registered_box')
            ->get()
            ->groupBy('registered_box')
            ->map(fn ($rows, string $box) => [
                'registered_box' => $box,
                'dhaairas' => $rows->pluck('dhaairaa')->filter()->unique()->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ElectionRace>  $races
     * @param  Collection<int, Candidate>  $candidates
     * @param  Collection<int, BoxRaceResult>  $boxRaceResults
     * @param  array<int, array{registered_box: string, dhaairas: array<int, string>}>  $availableBoxes
     * @return array<int, array<string, mixed>>
     */
    private function buildRaceStats(
        Collection $races,
        Collection $candidates,
        Collection $boxRaceResults,
        array $availableBoxes,
    ): array {
        $allBoxDhaairas = collect($availableBoxes)->keyBy('registered_box');

        return $races->map(function (ElectionRace $race) use ($boxRaceResults, $allBoxDhaairas): array {
            $eligible = $race->isIslandWide()
                ? VoterRecord::query()->count()
                : VoterRecord::query()->where('dhaairaa', $race->dhaaira)->count();

            $resultsForRace = $boxRaceResults->where('race_id', $race->id);

            $totalVoted = $resultsForRace->sum(function (BoxRaceResult $brr): int {
                return $brr->candidateVotes->sum('votes');
            });

            $votesPerParty = ['MDP' => 0, 'PNC' => 0, ];
            foreach ($resultsForRace as $brr) {
                foreach ($brr->candidateVotes as $cv) {
                    $affiliation = $cv->candidate?->affiliation;
                    if ($affiliation !== null && isset($votesPerParty[$affiliation])) {
                        $votesPerParty[$affiliation] += $cv->votes;
                    }
                }
            }

            $estimatePerParty = $this->buildPledgeEstimate($race);

            $countedBoxes = $resultsForRace->pluck('registered_box')->unique()->count();

            $totalBoxes = $race->isIslandWide()
                ? $allBoxDhaairas->count()
                : $allBoxDhaairas->filter(
                    fn ($b) => in_array($race->dhaaira, $b['dhaairas'], true)
                )->count();

            $turnoutPct = $eligible > 0 ? round($totalVoted / $eligible * 100, 1) : 0;
            $votesCountedPct = $eligible > 0 ? round($totalVoted / $eligible * 100, 1) : 0;

            return [
                'race_id' => $race->id,
                'eligible' => $eligible,
                'total_voted' => $totalVoted,
                'votes_per_party' => $votesPerParty,
                'estimate_per_party' => $estimatePerParty,
                'difference' => [
                    'MDP' => $votesPerParty['MDP'] - ($estimatePerParty['MDP'] ?? 0),
                    'PNC' => $votesPerParty['PNC'] - ($estimatePerParty['PNC'] ?? 0),
                ],
                'mdp_vs_pnc' => $votesPerParty['MDP'] - $votesPerParty['PNC'],
                'turnout_pct' => $turnoutPct,
                'votes_counted_pct' => $votesCountedPct,
                'total_boxes' => $totalBoxes,
                'counted_boxes' => $countedBoxes,
                'uncounted_boxes' => max(0, $totalBoxes - $countedBoxes),
                'votes_remaining' => max(0, $eligible - $totalVoted),
            ];
        })->keyBy('race_id')->all();
    }

    /**
     * @return array<string, int>
     */
    private function buildPledgeEstimate(ElectionRace $race): array
    {
        if ($race->type === 'referendum') {
            return [];
        }

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
     * @param  Collection<int, ElectionRace>  $races
     * @param  array<int, array<string, mixed>>  $raceStats
     * @param  array<int, array{registered_box: string, dhaairas: array<int, string>}>  $availableBoxes
     * @return array<string, mixed>
     */
    private function buildIslandSummary(Collection $races, array $raceStats, array $availableBoxes): array
    {
        $totalBoxes = count($availableBoxes);
        $totalEligible = VoterRecord::query()->count();

        $islandWideRaces = $races->filter(fn (ElectionRace $r) => $r->isIslandWide());
        $mayorRace = $islandWideRaces->firstWhere('type', 'mayor');

        $countedBoxes = $mayorRace !== null
            ? ($raceStats[$mayorRace->id]['counted_boxes'] ?? 0)
            : 0;

        $validVotes = $mayorRace !== null
            ? ($raceStats[$mayorRace->id]['total_voted'] ?? 0)
            : 0;

        $invalidVotes = BoxResult::sum('invalid_votes');
        $totalVoted = $validVotes + $invalidVotes;
        $turnoutPct = $totalEligible > 0 ? round($totalVoted / $totalEligible * 100, 1) : 0;

        return [
            'total_boxes' => $totalBoxes,
            'counted_boxes' => $countedBoxes,
            'uncounted_boxes' => max(0, $totalBoxes - $countedBoxes),
            'total_eligible' => $totalEligible,
            'valid_votes' => $validVotes,
            'invalid_votes' => $invalidVotes,
            'total_voted' => $totalVoted,
            'turnout_pct' => $turnoutPct,
            'votes_remaining' => max(0, $totalEligible - $totalVoted),
        ];
    }
}
