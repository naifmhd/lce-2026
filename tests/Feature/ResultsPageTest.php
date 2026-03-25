<?php

use App\Jobs\GenerateRaceProjection;
use App\Models\BoxRaceResult;
use App\Models\BoxResult;
use App\Models\Candidate;
use App\Models\CandidateVote;
use App\Models\ElectionRace;
use App\Models\User;
use App\Models\VoterRecord;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from results page', function () {
    $this->get(route('results.index'))->assertRedirect(route('login'));
});

test('users without results role cannot access results page', function () {
    $user = User::factory()->withRoles(['call-center'])->create();

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertForbidden();
});

test('admin can view results page', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Results/Index')
                ->has('races')
                ->has('candidates')
                ->has('raceStats')
                ->has('availableBoxes')
                ->has('islandSummary')
        );
});

test('users with results role can view results page', function () {
    $user = User::factory()->withRoles(['results'])->create();

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Results/Index'));
});

test('users without results role cannot store box results', function () {
    $user = User::factory()->withRoles(['call-center'])->create();
    $race = ElectionRace::factory()->create(['type' => 'mayor']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($user)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 0,
            'races' => [['race_id' => $race->id, 'votes' => [$candidate->id => 5]]],
        ])
        ->assertForbidden();
});

test('results page includes available boxes from voter records', function () {
    $admin = User::factory()->create();
    VoterRecord::factory()->create(['registered_box' => 'Box A', 'dhaairaa' => 'B9-1']);
    VoterRecord::factory()->create(['registered_box' => 'Box B', 'dhaairaa' => 'B9-2']);

    $this->actingAs($admin)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Results/Index')
                ->has('availableBoxes', fn (AssertableInertia $boxes) => $boxes->where('0.registered_box', 'Box A')
                    ->where('0.dhaairas.0', 'B9-1')
                    ->etc()
                )
        );
});

test('admin can store box results', function () {
    $admin = User::factory()->create();

    $race = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $candidateA = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);
    $candidateB = Candidate::factory()->create(['affiliation' => 'PNC', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 2,
            'races' => [
                [
                    'race_id' => $race->id,
                    'votes' => [
                        $candidateA->id => 45,
                        $candidateB->id => 30,
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('results.index'));

    expect(BoxResult::where('registered_box', 'Box 1')->value('invalid_votes'))->toBe(2);

    $boxRaceResult = BoxRaceResult::where('registered_box', 'Box 1')->where('race_id', $race->id)->first();
    expect($boxRaceResult)->not->toBeNull();

    expect(
        CandidateVote::where('box_race_result_id', $boxRaceResult->id)
            ->where('candidate_id', $candidateA->id)
            ->value('votes'),
    )->toBe(45);
});

test('storing box results upserts existing data', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'mayor']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $existing = BoxRaceResult::create(['registered_box' => 'Box 1', 'race_id' => $race->id, 'invalid_votes' => 0]);
    CandidateVote::create(['box_race_result_id' => $existing->id, 'candidate_id' => $candidate->id, 'votes' => 10]);
    BoxResult::create(['registered_box' => 'Box 1', 'invalid_votes' => 1]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 5,
            'races' => [
                ['race_id' => $race->id, 'votes' => [$candidate->id => 50]],
            ],
        ])
        ->assertRedirect();

    expect(BoxRaceResult::where('registered_box', 'Box 1')->count())->toBe(1);
    expect(BoxResult::where('registered_box', 'Box 1')->value('invalid_votes'))->toBe(5);

    expect(
        CandidateVote::where('box_race_result_id', $existing->id)
            ->where('candidate_id', $candidate->id)
            ->value('votes'),
    )->toBe(50);
});

test('race stats reflect entered votes', function () {
    $admin = User::factory()->create();

    $race = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $candidateMdp = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);
    $candidatePnc = Candidate::factory()->create(['affiliation' => 'PNC', 'race_id' => $race->id]);

    $brr = BoxRaceResult::create(['registered_box' => 'Box 1', 'race_id' => $race->id, 'invalid_votes' => 0]);
    CandidateVote::create(['box_race_result_id' => $brr->id, 'candidate_id' => $candidateMdp->id, 'votes' => 60]);
    CandidateVote::create(['box_race_result_id' => $brr->id, 'candidate_id' => $candidatePnc->id, 'votes' => 40]);

    $this->actingAs($admin)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Results/Index')
                ->where("raceStats.{$race->id}.votes_per_party.MDP", 60)
                ->where("raceStats.{$race->id}.votes_per_party.PNC", 40)
                ->where("raceStats.{$race->id}.total_voted", 100)
                ->where("raceStats.{$race->id}.mdp_vs_pnc", 20)
        );
});

test('box results validation requires registered box, invalid votes, and races', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('results.store-box'), [])
        ->assertSessionHasErrors(['registered_box', 'invalid_votes', 'races']);
});

test('storing box results dispatches projection job when api key is configured', function () {
    Queue::fake();
    Config::set('services.anthropic.key', 'test-key');

    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 0,
            'races' => [
                ['race_id' => $race->id, 'votes' => [$candidate->id => 10]],
            ],
        ])
        ->assertRedirect();

    Queue::assertPushed(GenerateRaceProjection::class, fn ($job) => in_array($race->id, $job->raceIds, true));
});

test('storing box results does not dispatch projection job when api key is not set', function () {
    Queue::fake();
    Config::set('services.anthropic.key', null);

    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'mayor']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 0,
            'races' => [
                ['race_id' => $race->id, 'votes' => [$candidate->id => 5]],
            ],
        ])
        ->assertRedirect();

    Queue::assertNotPushed(GenerateRaceProjection::class);
});

test('projection job updates race with winner from anthropic api', function () {
    $race = ElectionRace::factory()->create(['type' => 'mayor']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => "[{\"race_id\":{$race->id},\"projected_winner\":\"MDP\",\"confidence\":\"high\",\"reasoning\":\"MDP leads significantly.\"}]"],
            ],
        ], 200),
    ]);

    (new GenerateRaceProjection([$race->id]))->handle();

    $race->refresh();
    expect($race->projected_winner)->toBe('MDP')
        ->and($race->projection_confidence)->toBe('high')
        ->and($race->projection_reasoning)->toBe('MDP leads significantly.')
        ->and($race->projection_updated_at)->not->toBeNull();
});
