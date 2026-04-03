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

test('dhaaira council role can access results page', function () {
    $user = User::factory()->withRoles(['dhaaira-1-council'])->create();

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Results/Index')
            ->where('canEnterResults', false)
        );
});

test('dhaaira council role only sees council races for their dhaaira', function () {
    $user = User::factory()->withRoles(['dhaaira-1-council'])->create();

    $allowed = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $other = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-2']);
    $mayor = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('races', 1)
            ->where('races.0.id', $allowed->id)
        );
});

test('dhaaira wdc role only sees wdc races for their dhaaira', function () {
    $user = User::factory()->withRoles(['dhaaira-2-wdc'])->create();

    $allowed = ElectionRace::factory()->create(['type' => 'wdc', 'dhaaira' => 'B9-2']);
    ElectionRace::factory()->create(['type' => 'wdc', 'dhaaira' => 'B9-3']);
    ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-2']);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('races', 1)
            ->where('races.0.id', $allowed->id)
        );
});

test('mayor role only sees mayor race', function () {
    $user = User::factory()->withRoles(['mayor'])->create();

    $mayor = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);
    ElectionRace::factory()->create(['type' => 'raeesa', 'dhaaira' => null]);
    ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('races', 1)
            ->where('races.0.id', $mayor->id)
            ->where('canEnterResults', false)
        );
});

test('raeesa role only sees raeesa race', function () {
    $user = User::factory()->withRoles(['raeesa'])->create();

    $raeesa = ElectionRace::factory()->create(['type' => 'raeesa', 'dhaaira' => null]);
    ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('races', 1)
            ->where('races.0.id', $raeesa->id)
        );
});

test('results role sees all races and can enter results', function () {
    $user = User::factory()->withRoles(['results'])->create();

    ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);
    ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    ElectionRace::factory()->create(['type' => 'wdc', 'dhaaira' => 'B9-2']);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('races', 3)
            ->where('canEnterResults', true)
        );
});

test('viewer role cannot store box results', function () {
    $user = User::factory()->withRoles(['dhaaira-1-council'])->create();
    $race = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($user)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => ['local_council' => 0, 'wdc' => 0, 'referendum' => 0],
            'races' => [['race_id' => $race->id, 'votes' => [$candidate->id => 5]]],
        ])
        ->assertForbidden();
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
            'invalid_votes' => ['local_council' => 0, 'wdc' => 0, 'referendum' => 0],
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
            'invalid_votes' => ['local_council' => 2, 'wdc' => 0, 'referendum' => 0],
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

    expect(
        BoxResult::where('registered_box', 'Box 1')->where('election_type', 'local_council')->value('invalid_votes'),
    )->toBe(2);

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
    BoxResult::create(['registered_box' => 'Box 1', 'election_type' => 'local_council', 'invalid_votes' => 1]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => ['local_council' => 5, 'wdc' => 0, 'referendum' => 0],
            'races' => [
                ['race_id' => $race->id, 'votes' => [$candidate->id => 50]],
            ],
        ])
        ->assertRedirect();

    expect(BoxRaceResult::where('registered_box', 'Box 1')->count())->toBe(1);
    expect(
        BoxResult::where('registered_box', 'Box 1')->where('election_type', 'local_council')->value('invalid_votes'),
    )->toBe(5);

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
            'invalid_votes' => ['local_council' => 0, 'wdc' => 0, 'referendum' => 0],
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
            'invalid_votes' => ['local_council' => 0, 'wdc' => 0, 'referendum' => 0],
            'races' => [
                ['race_id' => $race->id, 'votes' => [$candidate->id => 5]],
            ],
        ])
        ->assertRedirect();

    Queue::assertNotPushed(GenerateRaceProjection::class);
});

test('projection job computes voter-weighted completion and uncounted vote tracking', function () {
    $race = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);
    $candidateMdp = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    // 3 voters in Box A (counted), 2 voters in Box B (uncounted)
    VoterRecord::factory()->count(3)->create(['registered_box' => 'Box A', 'dhaairaa' => 'B9-1', 'vote_status' => 'voted']);
    VoterRecord::factory()->count(2)->create(['registered_box' => 'Box B', 'dhaairaa' => 'B9-1', 'vote_status' => 'voted']);

    // Only Box A has box results entered
    $brr = BoxRaceResult::create(['registered_box' => 'Box A', 'race_id' => $race->id, 'invalid_votes' => 0]);
    CandidateVote::create(['box_race_result_id' => $brr->id, 'candidate_id' => $candidateMdp->id, 'votes' => 3]);

    $capturedPrompt = null;
    Http::fake([
        'api.anthropic.com/*' => function ($request) use ($race, &$capturedPrompt) {
            $capturedPrompt = $request->body();

            return Http::response([
                'content' => [['type' => 'text', 'text' => "[{\"race_id\":{$race->id},\"projected_winner\":\"MDP\",\"confidence\":\"low\",\"reasoning\":\"Early signs.\"}]"]],
            ], 200);
        },
    ]);

    (new GenerateRaceProjection([$race->id]))->handle();

    // Box A: 3 votes counted, eligible = 5, coverage = 60%
    // avg 3 votes/box × 1 uncounted box → ~3 estimated remaining
    $decoded = json_decode($capturedPrompt, true);
    $promptContent = $decoded['messages'][0]['content'] ?? '';
    expect($promptContent)->toContain('Votes counted: 3/5 eligible (60% coverage)')
        ->and($promptContent)->toContain('Estimated votes remaining: ~3');
});

test('projection job updates race with winner from anthropic api', function () {
    $race = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);

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

test('estimate_per_party counts all pledges regardless of vote status', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);

    $votedVoter = VoterRecord::factory()->create(['vote_status' => 'voted']);
    $votedVoter->pledge()->create(['mayor' => 'MDP']);

    $notVotedVoter = VoterRecord::factory()->create(['vote_status' => null]);
    $notVotedVoter->pledge()->create(['mayor' => 'MDP']);

    $this->actingAs($admin)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("raceStats.{$race->id}.estimate_per_party.MDP", 2)
            ->where("raceStats.{$race->id}.estimate_per_party.PNC", 0)
        );
});

test('referendum race is visible to admin and results-role users', function () {
    $admin = User::factory()->create();
    $resultsUser = User::factory()->withRoles(['results'])->create();

    ElectionRace::factory()->create(['type' => 'referendum', 'dhaaira' => null]);

    foreach ([$admin, $resultsUser] as $user) {
        $this->actingAs($user)
            ->get(route('results.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('races', 1));
    }
});

test('referendum race is not visible to dhaaira-scoped roles', function () {
    $user = User::factory()->withRoles(['dhaaira-1-council'])->create();

    ElectionRace::factory()->create(['type' => 'referendum', 'dhaaira' => null]);

    $this->actingAs($user)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('races', 0));
});

test('storing box results creates separate BoxResult rows per election type', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'council', 'dhaaira' => 'B9-1']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => ['local_council' => 3, 'wdc' => 1, 'referendum' => 0],
            'races' => [['race_id' => $race->id, 'votes' => [$candidate->id => 10]]],
        ])
        ->assertRedirect();

    expect(BoxResult::where('registered_box', 'Box 1')->count())->toBe(3);
    expect(BoxResult::where('registered_box', 'Box 1')->where('election_type', 'local_council')->value('invalid_votes'))->toBe(3);
    expect(BoxResult::where('registered_box', 'Box 1')->where('election_type', 'wdc')->value('invalid_votes'))->toBe(1);
    expect(BoxResult::where('registered_box', 'Box 1')->where('election_type', 'referendum')->value('invalid_votes'))->toBe(0);
});

test('island summary sums invalid votes across all election types', function () {
    $admin = User::factory()->create();
    $mayorRace = ElectionRace::factory()->create(['type' => 'mayor', 'dhaaira' => null]);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $mayorRace->id]);

    BoxResult::create(['registered_box' => 'Box 1', 'election_type' => 'local_council', 'invalid_votes' => 3]);
    BoxResult::create(['registered_box' => 'Box 1', 'election_type' => 'wdc', 'invalid_votes' => 2]);
    BoxResult::create(['registered_box' => 'Box 1', 'election_type' => 'referendum', 'invalid_votes' => 1]);

    $this->actingAs($admin)
        ->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('islandSummary.invalid_votes', 6)
        );
});

test('invalid_votes must be an array', function () {
    $admin = User::factory()->create();
    $race = ElectionRace::factory()->create(['type' => 'mayor']);
    $candidate = Candidate::factory()->create(['affiliation' => 'MDP', 'race_id' => $race->id]);

    $this->actingAs($admin)
        ->post(route('results.store-box'), [
            'registered_box' => 'Box 1',
            'invalid_votes' => 0,
            'races' => [['race_id' => $race->id, 'votes' => [$candidate->id => 5]]],
        ])
        ->assertSessionHasErrors(['invalid_votes']);
});
