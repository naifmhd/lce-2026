<?php

use App\Models\User;
use App\Models\VoterRecord;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from call center page', function () {
    $this->get(route('call-center.index'))->assertRedirect(route('login'));
});

test('users without call-center role cannot access call center page', function () {
    $user = User::factory()->withRoles(['zeroday'])->create();

    $this->actingAs($user)
        ->get(route('call-center.index'))
        ->assertForbidden();
});

test('admin can access call center page', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('call-center.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('CallCenter/Index'));
});

test('users with cc-dhaaira-1 role can access call center page', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $this->actingAs($user)
        ->get(route('call-center.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('CallCenter/Index'));
});

test('call center page shows not-voted voters without search', function () {
    $user = User::factory()->create();
    VoterRecord::factory()->count(3)->create(['vote_status' => null]);
    VoterRecord::factory()->create(['vote_status' => 'voted']);

    $this->actingAs($user)
        ->get(route('call-center.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('CallCenter/Index')
                ->has('voters.data', 3)
                ->where('filters.cc_filter', '')
        );
});

test('call center page excludes voted voters', function () {
    $user = User::factory()->create();
    VoterRecord::factory()->create(['name' => 'Not Voted', 'vote_status' => null]);
    VoterRecord::factory()->create(['name' => 'Already Voted', 'vote_status' => 'voted']);

    $this->actingAs($user)
        ->get(route('call-center.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Not Voted')
        );
});

test('cc_filter filled returns only voters with cc_remarks', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['cc_remarks' => 'Called, interested']);
    VoterRecord::factory()->create(['cc_remarks' => null]);
    VoterRecord::factory()->create(['cc_remarks' => null]);

    $this->actingAs($user)
        ->get(route('call-center.index', ['cc_filter' => 'filled']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 1)
                ->where('filters.cc_filter', 'filled')
        );
});

test('cc_filter blank returns only voters without cc_remarks', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['cc_remarks' => 'Has a remark']);
    VoterRecord::factory()->create(['cc_remarks' => null]);
    VoterRecord::factory()->create(['cc_remarks' => null]);

    $this->actingAs($user)
        ->get(route('call-center.index', ['cc_filter' => 'blank']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data', 2)
                ->where('filters.cc_filter', 'blank')
        );
});

test('call center voter data includes cc_remarks field', function () {
    $user = User::factory()->create();

    VoterRecord::factory()->create(['cc_remarks' => 'Test remark']);

    $this->actingAs($user)
        ->get(route('call-center.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('voters.data.0', fn (AssertableInertia $voter) => $voter
                    ->hasAll(['id', 'list_number', 'id_card_number', 'name', 'address', 'mobile', 'registered_box', 'agent', 'vote_status', 'cc_remarks', 'photo_url'])
                )
        );
});

test('admin can update cc_remarks', function () {
    $user = User::factory()->create();
    $voter = VoterRecord::factory()->create(['cc_remarks' => null]);

    $this->actingAs($user)
        ->patch(route('call-center.update-remark', $voter), ['cc_remarks' => 'Called, will vote'])
        ->assertRedirect();

    expect($voter->fresh()->cc_remarks)->toBe('Called, will vote');
});

test('cc_remarks can be cleared', function () {
    $user = User::factory()->create();
    $voter = VoterRecord::factory()->create(['cc_remarks' => 'Old remark']);

    $this->actingAs($user)
        ->patch(route('call-center.update-remark', $voter), ['cc_remarks' => null])
        ->assertRedirect();

    expect($voter->fresh()->cc_remarks)->toBeNull();
});

test('users without call-center role cannot update cc_remarks', function () {
    $user = User::factory()->withRoles(['zeroday'])->create();
    $voter = VoterRecord::factory()->create(['cc_remarks' => null]);

    $this->actingAs($user)
        ->patch(route('call-center.update-remark', $voter), ['cc_remarks' => 'Should not save'])
        ->assertForbidden();

    expect($voter->fresh()->cc_remarks)->toBeNull();
});

test('guests cannot update cc_remarks', function () {
    $voter = VoterRecord::factory()->create(['cc_remarks' => null]);

    $this->patch(route('call-center.update-remark', $voter), ['cc_remarks' => 'Should not save'])
        ->assertRedirect(route('login'));

    expect($voter->fresh()->cc_remarks)->toBeNull();
});
