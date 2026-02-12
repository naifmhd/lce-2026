<?php

use App\Models\User;
use App\Models\VoterRecord;
use Inertia\Testing\AssertableInertia;

test('stats page shows grouped pledge counts and summary statistics', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $dhaairaaA = VoterRecord::factory()->create([
        'dhaairaa' => 'A',
        'sex' => 'M',
        'vote_status' => 'VOTED',
        'photo_path' => 'voter-record-photos/a.jpg',
    ]);
    $dhaairaaA->pledge()->create([
        'mayor' => 'PNC',
        'raeesa' => 'MDP',
        'council' => 'UN',
        'wdc' => 'NOT VOTING',
    ]);

    $dhaairaaB = VoterRecord::factory()->create([
        'dhaairaa' => 'B',
        'sex' => 'F',
        'vote_status' => 'NOT VOTED',
        'photo_path' => null,
    ]);
    $dhaairaaB->pledge()->create([
        'mayor' => 'PNC',
        'raeesa' => null,
        'council' => null,
        'wdc' => null,
    ]);

    $response = $this->actingAs($user)->get(route('stats.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Stats/Index')
            ->where('summary.total_voters', 2)
            ->where('summary.voters_with_photo', 1)
            ->where('summary.voters_with_any_pledge', 2)
            ->where('summary.total_pledge_entries', 5)
            ->where('overallPledgeCounts.PNC', 2)
            ->where('overallPledgeCounts.MDP', 1)
            ->where('overallPledgeCounts.UN', 1)
            ->where('overallPledgeCounts.NOT VOTING', 1)
            ->has('pledgeByDhaairaa', 2)
            ->where('pledgeByDhaairaa.0.dhaairaa', 'A')
            ->where('pledgeByDhaairaa.0.pledge_counts.PNC', 1)
            ->where('pledgeByDhaairaa.0.pledge_counts.MDP', 1)
            ->where('pledgeByDhaairaa.0.pledge_counts.UN', 1)
            ->where('pledgeByDhaairaa.0.pledge_counts.NOT VOTING', 1)
            ->where('pledgeByDhaairaa.1.dhaairaa', 'B')
            ->where('pledgeByDhaairaa.1.pledge_counts.PNC', 1)
    );
});
