<?php

use App\Enums\UserRole;
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

    $response = $this->actingAs($user)->get(route('home'));

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

test('dhaairaa scoped user sees scoped stats only', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1->value])->create();

    $allowed = VoterRecord::factory()->create([
        'dhaairaa' => 'B9-1',
        'vote_status' => 'VOTED',
    ]);
    $allowed->pledge()->create([
        'mayor' => 'PNC',
    ]);

    $blocked = VoterRecord::factory()->create([
        'dhaairaa' => 'B9-2',
        'vote_status' => 'NOT VOTED',
    ]);
    $blocked->pledge()->create([
        'mayor' => 'MDP',
    ]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('summary.total_voters', 1)
            ->where('overallPledgeCounts.PNC', 1)
            ->where('overallPledgeCounts.MDP', 0)
            ->where('statusCounts.0.label', 'VOTED')
            ->where('statusCounts.0.count', 1),
    );
});

test('full access role sees global stats', function () {
    $user = User::factory()->withRoles([UserRole::Island->value])->create();

    VoterRecord::factory()->create(['dhaairaa' => 'B9-1']);
    VoterRecord::factory()->create(['dhaairaa' => 'B9-2']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('summary.total_voters', 2),
    );
});
