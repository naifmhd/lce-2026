<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\VoterRecord;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    use AppliesVoterRoleScope;

    /**
     * @var array<int, string>
     */
    private const PLEDGE_OPTIONS = ['MDP', 'PNC', 'UN', 'NOT VOTING'];

    /**
     * @var array<int, string>
     */
    private const ROLE_FIELDS = ['council', 'wdc', 'raeesa', 'mayor'];

    /**
     * @var array<int, string>
     */
    private const ROLE_BREAKDOWN_BUCKETS = ['PNC', 'MDP', 'UN', 'NOT VOTING', 'Blank'];

    /**
     * @var array<string, string>
     */
    private const DHAAIRAA_LABELS = [
        'B9-1' => 'Dhaaira 1',
        'B9-2' => 'Dhaaira 2',
        'B9-3' => 'Dhaaira 3',
        'B9-4' => 'Dhaaira 4',
        'B9-5' => 'Dhaaira 5',
        'B9-6' => 'Dhaaira 6',
    ];

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
        $isFullAccess = array_intersect($roleKeys, [UserRole::Admin->value]) !== [];
        // Admin, Mayor and Raeesa implicitly have candidates access (they are the candidates)
        $isFullAccessRole = $user?->hasFullVoterAccess() ?? false;

        $permissionService = app(RolePermissionService::class);
        $canViewCandidates = ($user?->canViewVoters() ?? false) || $permissionService->userHasPermission($user, Permission::Candidates);
        $canViewCouncil = $canViewCandidates && ($isFullAccess || $permissionService->userHasPermission($user, Permission::CouncilPledge));
        $canViewWdc = $canViewCandidates && ($isFullAccess || $permissionService->userHasPermission($user, Permission::WdcPledge));
        $canViewRaeesa = $canViewCandidates && ($isFullAccess || in_array(UserRole::Raeesa->value, $roleKeys, true) || $permissionService->userHasPermission($user, Permission::RaeesaPledge));
        $canViewMayor = $canViewCandidates && ($isFullAccess || in_array(UserRole::Mayor->value, $roleKeys, true) || $permissionService->userHasPermission($user, Permission::MayorPledge));

        $hasCouncilRole = $canViewCandidates && ($isFullAccess || collect($roleKeys)->contains(fn ($r) => str_ends_with($r, '-council')));
        $hasWdcRole = $canViewCandidates && ($isFullAccess || collect($roleKeys)->contains(fn ($r) => str_ends_with($r, '-wdc')));
        $hasRaeesaRole = $canViewCandidates && ($isFullAccess || in_array(UserRole::Raeesa->value, $roleKeys, true));
        $hasMayorRole = $canViewCandidates && ($isFullAccess || in_array(UserRole::Mayor->value, $roleKeys, true));

        $cardVisibility = [
            // showOverall* is restricted to the role's own candidate (Raeesa/Mayor/Admin only)
            'showOverallRaeesaTotal' => $hasRaeesaRole && $canViewRaeesa,
            'showOverallMayorTotal' => $hasMayorRole && $canViewMayor,
            'showCouncilByDhaairaa' => $canViewCouncil,
            'showWdcByDhaairaa' => $canViewWdc,
            'showRaeesaByDhaairaa' => $canViewRaeesa,
            'showMayorByDhaairaa' => $canViewMayor,
        ];

        $distributionVisibility = [
            'showCouncilDistribution' => $hasCouncilRole,
            'showWdcDistribution' => $hasWdcRole,
            'showRaeesaDistribution' => $hasRaeesaRole,
            'showMayorDistribution' => $hasMayorRole,
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

        $votersByDhaairaaGroup = $voters->groupBy(fn (VoterRecord $voter) => $this->normalizeBucket($voter->dhaairaa));

        $zerodayStats = [];

        if ($hasMayorRole) {
            $zerodayStats[] = ['name' => 'Mayor'] + $this->computeZerodayRow($voters, 'mayor');
        }

        if ($hasRaeesaRole) {
            $zerodayStats[] = ['name' => 'Raeesa'] + $this->computeZerodayRow($voters, 'raeesa');
        }

        if ($hasCouncilRole) {
            foreach (self::DHAAIRAA_LABELS as $code => $label) {
                $group = $votersByDhaairaaGroup->get($code, collect());

                if ($group->count() > 0) {
                    $zerodayStats[] = ['name' => "{$label} Council"] + $this->computeZerodayRow($group, 'council');
                }
            }
        }

        if ($hasWdcRole) {
            foreach (self::DHAAIRAA_LABELS as $code => $label) {
                $group = $votersByDhaairaaGroup->get($code, collect());

                if ($group->count() > 0) {
                    $zerodayStats[] = ['name' => "{$label} WDC"] + $this->computeZerodayRow($group, 'wdc');
                }
            }
        }

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
            'distributionVisibility' => $distributionVisibility,
            'statusCounts' => $statusCounts,
            'zerodayStats' => $zerodayStats,
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

    /**
     * @param  Collection<int, VoterRecord>  $voters
     * @return array{name?: string, pledgedVoted: array<string, int>, pledgedNotVoted: array<string, int>, totalVoted: int, totalEligible: int}
     */
    private function computeZerodayRow(Collection $voters, string $pledgeField): array
    {
        $pledgedVoted = array_fill_keys(self::PLEDGE_OPTIONS, 0);
        $pledgedNotVoted = array_fill_keys(self::PLEDGE_OPTIONS, 0);
        $totalVoted = 0;

        foreach ($voters as $voter) {
            $voted = strtolower(trim((string) $voter->vote_status)) === 'voted';

            if ($voted) {
                $totalVoted++;
            }

            $pledgeValue = $voter->pledge?->{$pledgeField};

            if (is_string($pledgeValue) && in_array($pledgeValue, self::PLEDGE_OPTIONS, true)) {
                if ($voted) {
                    $pledgedVoted[$pledgeValue]++;
                } else {
                    $pledgedNotVoted[$pledgeValue]++;
                }
            }
        }

        return [
            'pledgedVoted' => $pledgedVoted,
            'pledgedNotVoted' => $pledgedNotVoted,
            'totalVoted' => $totalVoted,
            'totalEligible' => $voters->count(),
        ];
    }
}
