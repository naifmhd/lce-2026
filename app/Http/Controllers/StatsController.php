<?php

namespace App\Http\Controllers;

use App\Models\VoterRecord;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const PLEDGE_OPTIONS = ['PNC', 'MDP', 'UN', 'NOT VOTING'];

    public function index(): Response
    {
        $voters = VoterRecord::query()
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
            foreach (['mayor', 'raeesa', 'council', 'wdc'] as $field) {
                $value = $voter->pledge?->{$field};

                if (is_string($value) && isset($overallPledgeCounts[$value])) {
                    $overallPledgeCounts[$value]++;
                }
            }
        }

        $statusCounts = $voters
            ->groupBy(fn (VoterRecord $voter) => $this->normalizeBucket($voter->vote_status))
            ->map(fn ($group, string $status): array => [
                'label' => $status,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $sexCounts = $voters
            ->groupBy(fn (VoterRecord $voter) => $this->normalizeBucket($voter->sex))
            ->map(fn ($group, string $sex): array => [
                'label' => $sex,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

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
                'voters_with_photo' => $voters->whereNotNull('photo_path')->count(),
                'voters_with_any_pledge' => $withAnyPledge,
                'total_pledge_entries' => array_sum($overallPledgeCounts),
            ],
            'pledgeOptions' => self::PLEDGE_OPTIONS,
            'pledgeByDhaairaa' => $votersByDhaairaa,
            'overallPledgeCounts' => $overallPledgeCounts,
            'statusCounts' => $statusCounts,
            'sexCounts' => $sexCounts,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function emptyPledgeCounts(): array
    {
        return array_fill_keys(self::PLEDGE_OPTIONS, 0);
    }

    private function normalizeBucket(?string $value): string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? 'Unspecified' : $normalized;
    }
}
