<?php

use App\Models\Pledge;
use App\Models\VoterRecord;
use Illuminate\Support\Facades\DB;

function insertActivityLog(string $logName, int $subjectId, array $attributes, array $old = []): void
{
    DB::table('activity_log')->insert([
        'log_name' => $logName,
        'description' => $logName.' updated',
        'subject_type' => $logName === 'pledge' ? Pledge::class : VoterRecord::class,
        'subject_id' => $subjectId,
        'event' => 'updated',
        'properties' => json_encode(['old' => $old, 'attributes' => $attributes]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('restores pledge fields from activity log', function () {
    $voter = VoterRecord::factory()->create();
    $pledge = Pledge::factory()->create([
        'voter_id' => $voter->id,
        'mayor' => null,
        'raeesa' => null,
        'council' => null,
        'wdc' => null,
    ]);

    insertActivityLog('pledge', $pledge->id, [
        'mayor' => 'MDP',
        'raeesa' => 'MDP',
        'council' => 'PNC',
        'wdc' => 'MDP',
    ]);

    $this->artisan('restore:from-activity-log --type=pledge')
        ->expectsConfirmation('Continue?', 'yes')
        ->assertSuccessful();

    $pledge->refresh();
    expect($pledge->mayor)->toBe('MDP')
        ->and($pledge->raeesa)->toBe('MDP')
        ->and($pledge->council)->toBe('PNC')
        ->and($pledge->wdc)->toBe('MDP');
});

test('skips pledge when values already match activity log', function () {
    $voter = VoterRecord::factory()->create();
    $pledge = Pledge::factory()->create([
        'voter_id' => $voter->id,
        'mayor' => 'MDP',
        'raeesa' => 'MDP',
        'council' => 'MDP',
        'wdc' => 'MDP',
    ]);

    insertActivityLog('pledge', $pledge->id, [
        'mayor' => 'MDP',
        'raeesa' => 'MDP',
        'council' => 'MDP',
        'wdc' => 'MDP',
    ]);

    $this->artisan('restore:from-activity-log --type=pledge')
        ->expectsConfirmation('Continue?', 'yes')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped');
});

test('restores voter record tracked fields from activity log', function () {
    $voter = VoterRecord::factory()->create([
        'agent' => null,
        'mobile' => null,
        're_reg_travel' => null,
        'comments' => null,
    ]);

    insertActivityLog('voter', $voter->id, ['mobile' => '7654321']);
    insertActivityLog('voter', $voter->id, ['agent' => 'SAUDY', 're_reg_travel' => 'travel']);

    $this->artisan('restore:from-activity-log --type=voter')
        ->expectsConfirmation('Continue?', 'yes')
        ->assertSuccessful();

    $voter->refresh();
    expect($voter->mobile)->toBe('7654321')
        ->and($voter->agent)->toBe('SAUDY')
        ->and($voter->re_reg_travel)->toBe('travel');
});

test('dry run does not modify any records', function () {
    $voter = VoterRecord::factory()->create(['agent' => null]);

    insertActivityLog('voter', $voter->id, ['agent' => 'DRYTEST']);

    $this->artisan('restore:from-activity-log --type=voter --dry-run')
        ->assertSuccessful();

    $voter->refresh();
    expect($voter->agent)->toBeNull();
});

test('--type=pledge only processes pledges and not voter records', function () {
    $voter = VoterRecord::factory()->create(['agent' => null]);
    $pledge = Pledge::factory()->create([
        'voter_id' => $voter->id,
        'mayor' => null,
    ]);

    insertActivityLog('voter', $voter->id, ['agent' => 'SHOULD_NOT_RESTORE']);
    insertActivityLog('pledge', $pledge->id, ['mayor' => 'MDP', 'raeesa' => 'MDP', 'council' => 'MDP', 'wdc' => 'MDP']);

    $this->artisan('restore:from-activity-log --type=pledge')
        ->expectsConfirmation('Continue?', 'yes')
        ->assertSuccessful();

    $voter->refresh();
    $pledge->refresh();

    expect($voter->agent)->toBeNull()
        ->and($pledge->mayor)->toBe('MDP');
});

test('uses last activity log entry when multiple updates exist for same record', function () {
    $voter = VoterRecord::factory()->create(['mobile' => null]);

    insertActivityLog('voter', $voter->id, ['mobile' => '7000000'], ['mobile' => null]);
    insertActivityLog('voter', $voter->id, ['mobile' => '7999999'], ['mobile' => '7000000']);

    $this->artisan('restore:from-activity-log --type=voter')
        ->expectsConfirmation('Continue?', 'yes')
        ->assertSuccessful();

    $voter->refresh();
    expect($voter->mobile)->toBe('7999999');
});
