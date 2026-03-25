<?php

use App\Models\Candidate;
use App\Models\ElectionRace;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from candidates page', function () {
    $this->get(route('candidates.index'))->assertRedirect(route('login'));
});

test('non-admin users cannot access candidates page', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)
        ->get(route('candidates.index'))
        ->assertForbidden();
});

test('admin can view candidates page', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['name' => 'Dhaaira 1 Council', 'type' => 'council', 'dhaaira' => 'B9-1']);
    Candidate::factory()->create(['name' => 'Ahmed Ali', 'affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->get(route('candidates.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Candidates/Index')
                ->has('candidates', 1)
                ->where('candidates.0.name', 'Ahmed Ali')
                ->where('candidates.0.affiliation', 'MDP')
                ->where('candidates.0.race_name', 'Dhaaira 1 Council')
                ->has('races')
        );
});

test('admin can create a candidate', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'council']);

    $this->actingAs($admin)
        ->post(route('candidates.store'), [
            'name' => 'New Candidate',
            'affiliation' => 'PNC',
            'race_id' => $race->id,
        ])
        ->assertRedirect(route('candidates.index'));

    expect(Candidate::where('name', 'New Candidate')->where('affiliation', 'PNC')->exists())->toBeTrue();
});

test('candidate store validates required fields', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('candidates.store'), [])
        ->assertSessionHasErrors(['name', 'affiliation', 'race_id']);
});

test('candidate affiliation must be MDP or PNC', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create();

    $this->actingAs($admin)
        ->post(route('candidates.store'), [
            'name' => 'Test',
            'affiliation' => 'JP',
            'race_id' => $race->id,
        ])
        ->assertSessionHasErrors(['affiliation']);
});

test('admin can update a candidate', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'council']);
    $candidate = Candidate::factory()->create(['name' => 'Old Name', 'affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->patch(route('candidates.update', $candidate), [
            'name' => 'Updated Name',
            'affiliation' => 'PNC',
            'race_id' => $race->id,
        ])
        ->assertRedirect(route('candidates.index'));

    expect($candidate->fresh()->name)->toBe('Updated Name')
        ->and($candidate->fresh()->affiliation)->toBe('PNC');
});

test('admin can delete a candidate', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create();
    $candidate = Candidate::factory()->create(['race_id' => $race->id]);

    $this->actingAs($admin)
        ->delete(route('candidates.destroy', $candidate))
        ->assertRedirect(route('candidates.index'));

    expect(Candidate::find($candidate->id))->toBeNull();
});
