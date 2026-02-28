<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Enums\UserRole;
use App\Models\VoterRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    use AppliesVoterRoleScope;

    /**
     * @var array<int, string>
     */
    private const PLEDGE_OPTIONS = ['PNC', 'MDP', 'UN', 'NOT VOTING'];

    /**
     * @var array<int, string>
     */
    private const ROLE_FIELDS = ['council', 'wdc', 'raeesa', 'mayor'];

    /**
     * @var array<int, string>
     */
    private const ROLE_BREAKDOWN_BUCKETS = ['PNC', 'MDP', 'UN', 'NOT VOTING', 'Blank'];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $voters = $this->applyVoterRoleScope(VoterRecord::query(), $request->user())
            ->with('pledge:id,voter_id,mayor,raeesa,council,wdc')
            ->get([
                'id',
                'dhaairaa',
                'sex',
                'vote_status',
                'photo_path',
            ]);

        $votersByDhaairaa = $voters
            ->groupBy(fn (VoterRecord $voter) => $this->normalizeBucket($voter->dhaairaa))
            ->map(function ($group, string $dhaairaa): array {
                $pledgeCounts = $this->emptyPledgeCounts();

                foreach ($group as $voter) {
                    foreach (['mayor', 'raeesa', 'council', 'wdc'] as $field) {
                        $value = $voter->pledge?->{$field};

                        if (is_string($value) && isset($pledgeCounts[$value])) {
                            $pledgeCounts[$value]++;
                        }
                    }
                }

                return [
                    'dhaairaa' => $dhaairaa,
                    'total_voters' => $group->count(),
                    'total_pledges' => array_sum($pledgeCounts),
                    'pledge_counts' => $pledgeCounts,
                ];
            })
            ->sortByDesc('total_voters')
            ->values();

        $overallPledgeCounts = $this->emptyPledgeCounts();

        foreach ($voters as $voter) {
            foreach (self::ROLE_FIELDS as $field) {
                $value = $voter->pledge?->{$field};

                if (is_string($value) && isset($overallPledgeCounts[$value])) {
                    $overallPledgeCounts[$value]++;
                }
            }
        }

        $roleCountsByDhaairaa = $voters
            ->groupBy(fn (VoterRecord $voter) => $this->normalizeBucket($voter->dhaairaa))
            ->map(function ($group, string $dhaairaa): array {
                $roles = [
                    'council' => $this->emptyRoleBreakdownCounts(),
                    'wdc' => $this->emptyRoleBreakdownCounts(),
                    'raeesa' => $this->emptyRoleBreakdownCounts(),
                    'mayor' => $this->emptyRoleBreakdownCounts(),
                ];

                foreach ($group as $voter) {
                    foreach (self::ROLE_FIELDS as $roleField) {
                        $roles[$roleField][$this->resolveRoleBreakdownBucket($voter->pledge?->{$roleField})]++;
                    }
                }

                return [
                    'dhaairaa' => $dhaairaa,
                    'total_voters' => $group->count(),
                    'roles' => $roles,
                ];
            })
            ->sortBy('dhaairaa', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $overallRoleTotals = [
            'raeesa' => $this->emptyRoleBreakdownCounts(),
            'mayor' => $this->emptyRoleBreakdownCounts(),
        ];

        foreach ($voters as $voter) {
            foreach (['raeesa', 'mayor'] as $roleField) {
                $overallRoleTotals[$roleField][$this->resolveRoleBreakdownBucket($voter->pledge?->{$roleField})]++;
            }
        }

        $roleKeys = $user?->roleKeys() ?? [];
        $showAllTotals = in_array(UserRole::Admin->value, $roleKeys, true) || in_array(UserRole::CallCenter->value, $roleKeys, true);
        $showAllRoleCards = $showAllTotals || ! in_array(UserRole::Mayor->value, $roleKeys, true) && ! in_array(UserRole::Raeesa->value, $roleKeys, true);
        $showMayorCard = $showAllTotals || in_array(UserRole::Mayor->value, $roleKeys, true);
        $showRaeesaCard = $showAllTotals || in_array(UserRole::Raeesa->value, $roleKeys, true);
        $cardVisibility = [
            'showOverallRaeesaTotal' => $showAllTotals || in_array(UserRole::Raeesa->value, $roleKeys, true),
            'showOverallMayorTotal' => $showAllTotals || in_array(UserRole::Mayor->value, $roleKeys, true),
            'showCouncilByDhaairaa' => $showAllRoleCards,
            'showWdcByDhaairaa' => $showAllRoleCards,
            'showRaeesaByDhaairaa' => $showAllRoleCards || $showRaeesaCard,
            'showMayorByDhaairaa' => $showAllRoleCards || $showMayorCard,
        ];

        $statusCounts = $voters
            ->groupBy(fn (VoterRecord $voter) => $this->normalizeLowerBucket($voter->vote_status))
            ->map(fn ($group, string $status): array => [
                'label' => $status,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $maleCount = $voters->filter(function (VoterRecord $voter): bool {
            $normalizedSex = strtoupper(trim((string) $voter->sex));

            return in_array($normalizedSex, ['M', 'MALE'], true);
        })->count();

        $femaleCount = $voters->filter(function (VoterRecord $voter): bool {
            $normalizedSex = strtoupper(trim((string) $voter->sex));

            return in_array($normalizedSex, ['F', 'FEMALE'], true);
        })->count();

        $withAnyPledge = $voters->filter(function (VoterRecord $voter): bool {
            $pledge = $voter->pledge;

            if ($pledge === null) {
                return false;
            }

            return $pledge->mayor !== null
                || $pledge->raeesa !== null
                || $pledge->council !== null
                || $pledge->wdc !== null;
        })->count();

        return Inertia::render('Stats/Index', [
            'summary' => [
                'total_voters' => $voters->count(),
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'voters_with_any_pledge' => $withAnyPledge,
                'total_pledge_entries' => array_sum($overallPledgeCounts),
            ],
            'pledgeOptions' => self::PLEDGE_OPTIONS,
            'pledgeByDhaairaa' => $votersByDhaairaa,
            'overallPledgeCounts' => $overallPledgeCounts,
            'roleCountsByDhaairaa' => $roleCountsByDhaairaa,
            'overallRoleTotals' => $overallRoleTotals,
            'cardVisibility' => $cardVisibility,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function emptyPledgeCounts(): array
    {
        return array_fill_keys(self::PLEDGE_OPTIONS, 0);
    }

    /**
     * @return array<string, int>
     */
    private function emptyRoleBreakdownCounts(): array
    {
        return array_fill_keys(self::ROLE_BREAKDOWN_BUCKETS, 0);
    }

    private function resolveRoleBreakdownBucket(mixed $value): string
    {
        if (! is_string($value)) {
            return 'Blank';
        }

        $normalizedValue = trim($value);

        if ($normalizedValue === '') {
            return 'Blank';
        }

        return in_array($normalizedValue, self::PLEDGE_OPTIONS, true) ? $normalizedValue : 'Blank';
    }

    private function normalizeBucket(?string $value): string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? 'Unspecified' : $normalized;
    }

    private function normalizeLowerBucket(?string $value): string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? 'unspecified' : strtolower($normalized);
    }
}
