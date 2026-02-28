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
            ->where('summary.male_count', 1)
            ->where('summary.female_count', 1)
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
            ->where('roleCountsByDhaairaa.0.dhaairaa', 'A')
            ->where('roleCountsByDhaairaa.0.total_voters', 1)
            ->where('roleCountsByDhaairaa.0.roles.council.UN', 1)
            ->where('roleCountsByDhaairaa.0.roles.wdc.NOT VOTING', 1)
            ->where('roleCountsByDhaairaa.0.roles.raeesa.MDP', 1)
            ->where('roleCountsByDhaairaa.0.roles.mayor.PNC', 1)
            ->where('roleCountsByDhaairaa.1.dhaairaa', 'B')
            ->where('roleCountsByDhaairaa.1.total_voters', 1)
            ->where('roleCountsByDhaairaa.1.roles.council.Blank', 1)
            ->where('roleCountsByDhaairaa.1.roles.wdc.Blank', 1)
            ->where('roleCountsByDhaairaa.1.roles.raeesa.Blank', 1)
            ->where('roleCountsByDhaairaa.1.roles.mayor.PNC', 1)
            ->where('overallRoleTotals.raeesa.MDP', 1)
            ->where('overallRoleTotals.raeesa.Blank', 1)
            ->where('overallRoleTotals.mayor.PNC', 2)
            ->where('overallRoleTotals.mayor.Blank', 0)
            ->where('cardVisibility.showOverallRaeesaTotal', true)
            ->where('cardVisibility.showOverallMayorTotal', true)
            ->where('cardVisibility.showCouncilByDhaairaa', true)
            ->where('cardVisibility.showWdcByDhaairaa', true)
            ->where('cardVisibility.showRaeesaByDhaairaa', true)
            ->where('cardVisibility.showMayorByDhaairaa', true)
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
            ->where('roleCountsByDhaairaa.0.dhaairaa', 'B9-1')
            ->where('roleCountsByDhaairaa.0.roles.council.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.wdc.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.raeesa.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.mayor.PNC', 1)
            ->where('overallRoleTotals.raeesa.Blank', 1)
            ->where('overallRoleTotals.mayor.PNC', 1)
            ->where('cardVisibility.showOverallRaeesaTotal', false)
            ->where('cardVisibility.showOverallMayorTotal', false)
            ->where('cardVisibility.showCouncilByDhaairaa', true)
            ->where('cardVisibility.showWdcByDhaairaa', true)
            ->where('cardVisibility.showRaeesaByDhaairaa', true)
            ->where('cardVisibility.showMayorByDhaairaa', true)
            ->where('statusCounts.0.label', 'voted')
            ->where('statusCounts.0.count', 1),
    );
});

test('full access role sees global stats', function () {
    $user = User::factory()->withRoles([UserRole::Admin->value])->create();

    VoterRecord::factory()->create(['dhaairaa' => 'B9-1']);
    VoterRecord::factory()->create(['dhaairaa' => 'B9-2']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('summary.total_voters', 2)
            ->where('overallRoleTotals.raeesa.Blank', 2)
            ->where('overallRoleTotals.mayor.Blank', 2)
            ->where('cardVisibility.showOverallRaeesaTotal', true)
            ->where('cardVisibility.showOverallMayorTotal', true)
            ->where('cardVisibility.showCouncilByDhaairaa', true)
            ->where('cardVisibility.showWdcByDhaairaa', true)
            ->where('cardVisibility.showRaeesaByDhaairaa', true)
            ->where('cardVisibility.showMayorByDhaairaa', true),
    );
});

test('blank handling includes voters without pledge row', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create([
        'dhaairaa' => 'B9-6',
    ]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('roleCountsByDhaairaa.0.dhaairaa', 'B9-6')
            ->where('roleCountsByDhaairaa.0.roles.council.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.wdc.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.raeesa.Blank', 1)
            ->where('roleCountsByDhaairaa.0.roles.mayor.Blank', 1)
            ->where('overallRoleTotals.raeesa.Blank', 1)
            ->where('overallRoleTotals.mayor.Blank', 1)
            ->where('cardVisibility.showOverallRaeesaTotal', true)
            ->where('cardVisibility.showOverallMayorTotal', true)
            ->where('cardVisibility.showCouncilByDhaairaa', true)
            ->where('cardVisibility.showWdcByDhaairaa', true)
            ->where('cardVisibility.showRaeesaByDhaairaa', true)
            ->where('cardVisibility.showMayorByDhaairaa', true),
    );
});

test('mayor sees mayor total and not raeesa total card visibility', function () {
    $user = User::factory()->withRoles([UserRole::Mayor->value])->create();

    VoterRecord::factory()->create(['dhaairaa' => 'B9-1']);
    VoterRecord::factory()->create(['dhaairaa' => 'B9-2']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('summary.total_voters', 2)
            ->where('cardVisibility.showOverallRaeesaTotal', false)
            ->where('cardVisibility.showOverallMayorTotal', true)
            ->where('cardVisibility.showCouncilByDhaairaa', false)
            ->where('cardVisibility.showWdcByDhaairaa', false)
            ->where('cardVisibility.showRaeesaByDhaairaa', false)
            ->where('cardVisibility.showMayorByDhaairaa', true),
    );
});

test('raeesa sees raeesa total and not mayor total card visibility', function () {
    $user = User::factory()->withRoles([UserRole::Raeesa->value])->create();

    VoterRecord::factory()->create(['dhaairaa' => 'B9-1']);
    VoterRecord::factory()->create(['dhaairaa' => 'B9-2']);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('summary.total_voters', 2)
            ->where('cardVisibility.showOverallRaeesaTotal', true)
            ->where('cardVisibility.showOverallMayorTotal', false)
            ->where('cardVisibility.showCouncilByDhaairaa', false)
            ->where('cardVisibility.showWdcByDhaairaa', false)
            ->where('cardVisibility.showRaeesaByDhaairaa', true)
            ->where('cardVisibility.showMayorByDhaairaa', false),
    );
});
