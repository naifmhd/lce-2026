<?php

use App\Models\User;
use App\Models\VoterRecord;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from voters page', function () {
    $response = $this->get(route('voters.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view voters page with 15 records per page', function () {
    $user = User::factory()->create();

    VoterRecord::factory()
        ->count(18)
        ->sequence(fn (Sequence $sequence) => [
            'list_number' => $sequence->index + 1,
            'name' => 'Voter '.($sequence->index + 1),
        ])
        ->create();

    $response = $this->actingAs($user)->get(route('voters.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Voters/Index')
            ->where('voters.total', 18)
            ->has('voters.data', 15)
            ->where('voters.current_page', 1)
    );
});

test('voters page supports search and filters and returns selected voter details', function () {
    $user = User::factory()->create();

    $matchingVoter = VoterRecord::factory()->create([
        'list_number' => 1,
        'id_card_number' => 'A999999',
        'name' => 'Special Voter',
        'mobile' => '9991111',
        'address' => 'Special Address',
        'dhaairaa' => 'Special Dhaairaa',
        'majilis_con' => 'Special Constituency',
    ]);
    $matchingVoter->pledge()->create([
        'mayor' => 'Yes',
        'raeesa' => 'No',
        'council' => 'Yes',
        'wdc' => 'No',
    ]);

    VoterRecord::factory()->count(5)->create([
        'dhaairaa' => 'Another Dhaairaa',
        'majilis_con' => 'Another Constituency',
    ]);

    $response = $this->actingAs($user)->get(route('voters.index', [
        'search' => 'A999999',
        'dhaairaa' => 'Special Dhaairaa',
        'majilis_con' => 'Special Constituency',
        'selected' => $matchingVoter->id,
    ]));

    $response->assertOk();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Voters/Index')
            ->where('voters.total', 1)
            ->has('voters.data', 1)
            ->where('voters.data.0.id', $matchingVoter->id)
            ->where('voters.data.0.pledge.mayor', 'Yes')
            ->where('voters.data.0.pledge.raeesa', 'No')
            ->where('selectedVoter.id', $matchingVoter->id)
            ->where('selectedVoter.name', 'Special Voter')
            ->where('selectedVoter.pledge.mayor', 'Yes')
            ->where('selectedVoter.pledge.raeesa', 'No')
    );
});

test('voter details can be updated from voters modal', function () {
    $user = User::factory()->create();

    $voter = VoterRecord::factory()->create([
        'mobile' => '7770000',
        're_reg_travel' => 'Old travel',
        'comments' => 'Old comments',
    ]);

    $voter->pledge()->create([
        'mayor' => 'PNC',
        'raeesa' => 'PNC',
        'council' => 'PNC',
        'wdc' => 'PNC',
    ]);

    $response = $this->actingAs($user)->patch(route('voters.update', [
        'voter' => $voter->id,
        'search' => 'abc',
        'page' => 2,
    ]), [
        'mobile' => '7999999',
        're_reg_travel' => 'New travel',
        'comments' => 'Updated from modal',
        'pledge' => [
            'mayor' => 'MDP',
            'raeesa' => 'UN',
            'council' => 'NOT VOTING',
            'wdc' => 'PNC',
        ],
    ]);

    $response->assertRedirect(route('voters.index', [
        'search' => 'abc',
        'page' => 2,
        'selected' => $voter->id,
    ]));

    $this->assertDatabaseHas('voter_records', [
        'id' => $voter->id,
        'mobile' => '7999999',
        're_reg_travel' => 'New travel',
        'comments' => 'Updated from modal',
    ]);

    $this->assertDatabaseHas('pledge', [
        'voter_id' => $voter->id,
        'mayor' => 'MDP',
        'raeesa' => 'UN',
        'council' => 'NOT VOTING',
        'wdc' => 'PNC',
    ]);
});
