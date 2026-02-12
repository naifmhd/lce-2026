<?php

use App\Models\VoterRecord;
use Database\Seeders\VoterRecordSeeder;
use Illuminate\Support\Facades\Storage;

test('voter record seeder imports first sheet rows including photos', function () {
    Storage::fake('public');

    $this->seed(VoterRecordSeeder::class);

    expect(VoterRecord::query()->count())->toBe(244);

    $recordWithPhoto = VoterRecord::query()
        ->whereNotNull('photo_path')
        ->first();

    expect($recordWithPhoto)->not->toBeNull();
    expect($recordWithPhoto?->name)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $recordWithPhoto?->photo_path);
});
