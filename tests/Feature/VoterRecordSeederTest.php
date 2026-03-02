<?php

use App\Models\Pledge;
use App\Models\VoterRecord;
use Database\Seeders\VoterRecordSeeder;
use Illuminate\Support\Facades\Storage;

test('voter record seeder imports first sheet rows including photos', function () {
    Storage::fake('public');

    $this->seed(VoterRecordSeeder::class);

    $voterCount = VoterRecord::query()->count();
    $pledgeCount = Pledge::query()->count();

    expect($voterCount)->toBeGreaterThan(0);
    expect($pledgeCount)->toBe($voterCount);

    $recordWithPhoto = VoterRecord::query()
        ->whereNotNull('photo_path')
        ->first();

    expect($recordWithPhoto)->not->toBeNull();
    expect($recordWithPhoto?->name)->not->toBeNull();
    expect($recordWithPhoto?->getAttributes())->toHaveKey('agent');
    Storage::disk('public')->assertExists((string) $recordWithPhoto?->photo_path);
    expect($recordWithPhoto?->pledge)->not->toBeNull();
});
