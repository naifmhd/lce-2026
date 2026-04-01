<?php

use App\Models\Pledge;
use App\Models\User;
use App\Models\VoterRecord;
use Inertia\Testing\AssertableInertia;

test('cc-dhaaira-1 sees B9-1 voters with MDP council pledge', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'MDP', 'wdc' => 'PNC']);

    $other = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $other->id, 'council' => 'PNC', 'wdc' => 'PNC']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 1)
            ->where('voters.data.0.id', $voter->id)
        );
});

test('cc-dhaaira-1 sees B9-1 voters with MDP wdc pledge', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'PNC', 'wdc' => 'MDP']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 1));
});

test('cc-dhaaira-1 does not see B9-1 voters with non-MDP pledges', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'PNC', 'wdc' => 'PNC']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 0));
});

test('cc-dhaaira-1 does not see B9-2 voters even with MDP pledge', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-2', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'MDP', 'wdc' => 'MDP']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 0));
});

test('cc-dhaaira-1 does not see voters with no pledge record', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 0));
});

test('cc-mayor sees all-dhaairaa voters with MDP mayor pledge', function () {
    $user = User::factory()->withRoles(['cc-mayor'])->create();

    $voter1 = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter1->id, 'mayor' => 'MDP']);

    $voter2 = VoterRecord::factory()->create(['dhaairaa' => 'B9-3', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter2->id, 'mayor' => 'MDP']);

    $voter3 = VoterRecord::factory()->create(['dhaairaa' => 'B9-2', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter3->id, 'mayor' => 'PNC']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 2));
});

test('cc-raeesa sees all-dhaairaa voters with MDP raeesa pledge', function () {
    $user = User::factory()->withRoles(['cc-raeesa'])->create();

    $voter1 = VoterRecord::factory()->create(['dhaairaa' => 'B9-4', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter1->id, 'raeesa' => 'MDP']);

    $voter2 = VoterRecord::factory()->create(['dhaairaa' => 'B9-5', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter2->id, 'raeesa' => 'PNC']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 1));
});

test('admin sees all voters regardless of pledge', function () {
    $admin = User::factory()->create();

    $voter1 = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter1->id, 'council' => 'PNC']);

    $voter2 = VoterRecord::factory()->create(['dhaairaa' => 'B9-2', 'vote_status' => null]);

    $this->actingAs($admin)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 2));
});

test('cc-dhaaira-1 gets 403 when updating remark for out-of-scope voter', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-2']);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'MDP']);

    $this->actingAs($user)
        ->patch(route('call-center.update-remark', $voter), ['cc_remarks' => 'Should fail'])
        ->assertForbidden();
});

test('user with cc-dhaaira-1 and cc-dhaaira-2 sees MDP voters from both dhaairaa', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1', 'cc-dhaaira-2'])->create();

    $voter1 = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter1->id, 'council' => 'MDP', 'wdc' => 'PNC']);

    $voter2 = VoterRecord::factory()->create(['dhaairaa' => 'B9-2', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter2->id, 'council' => 'PNC', 'wdc' => 'MDP']);

    $voter3 = VoterRecord::factory()->create(['dhaairaa' => 'B9-3', 'vote_status' => null]);
    Pledge::factory()->create(['voter_id' => $voter3->id, 'council' => 'MDP']);

    $this->actingAs($user)
        ->get(route('call-center.index', ['include_voted' => '1']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('voters.data', 2));
});

test('cc-dhaaira-1 can update remark for in-scope voter', function () {
    $user = User::factory()->withRoles(['cc-dhaaira-1'])->create();

    $voter = VoterRecord::factory()->create(['dhaairaa' => 'B9-1', 'cc_remarks' => null]);
    Pledge::factory()->create(['voter_id' => $voter->id, 'council' => 'MDP']);

    $this->actingAs($user)
        ->patch(route('call-center.update-remark', $voter), ['cc_remarks' => 'Called'])
        ->assertRedirect();

    expect($voter->fresh()->cc_remarks)->toBe('Called');
});
