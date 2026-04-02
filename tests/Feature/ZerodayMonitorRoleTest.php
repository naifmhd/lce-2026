<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VoterRecord;

test('zeroday role no longer exists in UserRole keys', function () {
    expect(UserRole::keys())->not->toContain('zeroday');
});

test('monitor role user can access zeroday page', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1'])->create();

    $this->actingAs($user)
        ->get(route('zeroday.index'))
        ->assertOk();
});

test('monitor role user only sees voters from their registered box', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1'])->create();

    VoterRecord::factory()->create([
        'list_number' => 1,
        'name' => 'Allowed Voter',
        'registered_box' => 'Kulhudhuffushi Uthuru-1',
    ]);
    VoterRecord::factory()->create([
        'list_number' => 2,
        'name' => 'Blocked Voter',
        'registered_box' => 'Kulhudhuffushi Uthuru-2',
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Voter']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Allowed Voter')
        );
});

test('monitor greater male role sees voters from all four greater male boxes', function () {
    $user = User::factory()->withRoles(['monitor-greater-male'])->create();

    $boxes = [
        "HDH. Atoll, Male'-4",
        "Hulhumale' Phase1, Ehenihen-18",
        "Hulhumale' Phase2, Ehenihen-3",
        "Vilimale', Ehenihen-4",
    ];

    foreach ($boxes as $i => $box) {
        VoterRecord::factory()->create([
            'list_number' => $i + 1,
            'name' => "Voter {$i}",
            'registered_box' => $box,
        ]);
    }

    VoterRecord::factory()->create([
        'list_number' => 99,
        'name' => 'Out Of Scope Voter',
        'registered_box' => 'Kulhudhuffushi Uthuru-1',
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Voter']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->has('voters.data', 4)
        );
});

test('monitor other role sees voters in dhaaira not in known box list', function () {
    $user = User::factory()->withRoles(['monitor-other-1'])->create();

    VoterRecord::factory()->create([
        'list_number' => 1,
        'name' => 'Other Box Voter',
        'dhaairaa' => 'B9-1',
        'registered_box' => 'Some Other Box',
    ]);

    VoterRecord::factory()->create([
        'list_number' => 2,
        'name' => 'Known Box Voter',
        'dhaairaa' => 'B9-1',
        'registered_box' => 'Kulhudhuffushi Uthuru-1',
    ]);

    VoterRecord::factory()->create([
        'list_number' => 3,
        'name' => 'Wrong Dhaaira Voter',
        'dhaairaa' => 'B9-2',
        'registered_box' => 'Some Other Box',
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Voter']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('voters.data', 1)
                ->where('voters.data.0.name', 'Other Box Voter')
        );
});

test('user with two monitor roles sees union of both boxes', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1', 'monitor-uthuru-2'])->create();

    VoterRecord::factory()->create([
        'list_number' => 1,
        'name' => 'Box One Voter',
        'registered_box' => 'Kulhudhuffushi Uthuru-1',
    ]);
    VoterRecord::factory()->create([
        'list_number' => 2,
        'name' => 'Box Two Voter',
        'registered_box' => 'Kulhudhuffushi Uthuru-2',
    ]);
    VoterRecord::factory()->create([
        'list_number' => 3,
        'name' => 'Other Box Voter',
        'registered_box' => 'Kulhudhuffushi Medhu Uthuru-1',
    ]);

    $this->actingAs($user)
        ->get(route('zeroday.index', ['search' => 'Voter']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->has('voters.data', 2)
        );
});

test('monitor role user cannot mark voted a voter outside their box', function () {
    $user = User::factory()->withRoles(['monitor-uthuru-1'])->create();
    $voter = VoterRecord::factory()->create([
        'vote_status' => null,
        'registered_box' => 'Kulhudhuffushi Uthuru-2',
    ]);

    $this->actingAs($user)
        ->patch(route('zeroday.mark-voted', $voter))
        ->assertForbidden();

    expect($voter->fresh()->vote_status)->toBeNull();
});

test('user without monitor role cannot access zeroday page', function () {
    $user = User::factory()->withRoles(['dhaaira-1-council'])->create();

    $this->actingAs($user)
        ->get(route('zeroday.index'))
        ->assertForbidden();
});
