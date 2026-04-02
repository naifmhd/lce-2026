<?php

use App\Models\User;
use App\Models\VoterRecord;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from zeroday page', function () {
    $this->get(route('zeroday.index'))->assertRedirect(route('login'));
});

test('users without zeroday role cannot access zeroday page', function () {
    $user = User::factory()->withRoles(['call-center'])->create();

    $this->actingAs($user)
        ->get(route('zeroday.index'))
        ->assertForbidden();
});

test('users without zeroday role cannot mark a voter as voted', function () {
    $user = User::factory()->withRoles(['call-center'])->create();
    $voter = VoterRecord::factory()->create(['vote_status' => null]);

    $this->actingAs($user)
        ->patch(route('zeroday.mark-voted', $voter))
        ->assertForbidden();

    expect($voter->fresh()->vote_status)->toBeNull();
});

test('users with a monitor role can access zeroday page', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1'])->create();

    $this->actingAs($user)
        ->get(route('zeroday.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Zeroday/Index'));
});

test('users with a monitor role can mark a voter in their box as voted', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1'])->create();
    $voter = VoterRecord::factory()->create([
        'vote_status' => null,
        'registered_box' => 'Kulhudhuffushi Uthuru-1',
    ]);

    $this->actingAs($user)
        ->patch(route('zeroday.mark-voted', $voter))
        ->assertRedirect();

    expect($voter->fresh()->vote_status)->toBe('voted');
});

test('zeroday page returns null voters when no search is provided', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('zeroday.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Zeroday/Index')
                ->where('voters', null)
                ->where('filters.search', '')
        );
});

test('zeroday page returns matching voters when searched', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create([
        'list_number' => 1,
        'name' => 'Ali Ahmed',
        'id_card_number' => 'A123456',
    ]);
    VoterRecord::factory()->create([
        'list_number' => 2,
        'name' => 'Other Person',
        'id_card_number' => 'B999999',
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Ali']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Zeroday/Index')
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Ali Ahmed')
                ->where('filters.search', 'Ali')
        );
});

test('zeroday page voter data only includes expected fields', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create([
        'list_number' => 1,
        'name' => 'Test Voter',
        'id_card_number' => 'T111111',
        'address' => 'Some Address',
        'mobile' => '7001234',
        'registered_box' => 'Box 1',
        'vote_status' => null,
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Test Voter']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Zeroday/Index')
                ->has('voters.data', 1)
                ->has('voters.data.0', fn (AssertableInertia $voter) => $voter
                    ->hasAll(['id', 'list_number', 'id_card_number', 'name', 'address', 'mobile', 'registered_box', 'vote_status', 'photo_url'])
                    ->missing('agent')
                    ->missing('dhaairaa')
                )
        );
});

test('guests cannot mark a voter as voted', function () {
    $voter = VoterRecord::factory()->create(['vote_status' => null]);

    $this->patch(route('zeroday.mark-voted', $voter))
        ->assertRedirect(route('login'));

    expect($voter->fresh()->vote_status)->toBeNull();
});

test('authenticated user can mark a voter as voted', function () {
    $user = User::factory()->create();
    $voter = VoterRecord::factory()->create(['vote_status' => null]);

    $this->actingAs($user)
        ->patch(route('zeroday.mark-voted', $voter))
        ->assertRedirect();

    expect($voter->fresh()->vote_status)->toBe('voted');
});

test('zeroday excludes voted voters by default when searching', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['name' => 'Ali Ahmed', 'vote_status' => null]);
    VoterRecord::factory()->create(['name' => 'Ali Voted', 'vote_status' => 'voted']);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Ali']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Ali Ahmed')
                ->where('filters.include_voted', false)
        );
});

test('zeroday comma-separated search filters by all terms', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['name' => 'Ahmed Ali', 'address' => 'Dhoores']);
    VoterRecord::factory()->create(['name' => 'Ahmed Ali', 'address' => 'Other Island']);
    VoterRecord::factory()->create(['name' => 'Other Person', 'address' => 'Dhoores']);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Ahmed Ali,Dhoores']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Ahmed Ali')
                ->where('voters.data.0.address', 'Dhoores')
        );
});

test('zeroday includes voted voters when include_voted is set', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['name' => 'Ali Ahmed', 'vote_status' => null]);
    VoterRecord::factory()->create(['name' => 'Ali Voted', 'vote_status' => 'voted']);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Ali', 'include_voted' => '1']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 2)
                ->where('filters.include_voted', true)
        );
});
