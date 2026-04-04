<?php

use App\Models\UserSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->hourly();

Schedule::call(function (): void {
    $lifetime = (int) config('session.lifetime');
    UserSession::where('last_activity_at', '<', now()->subMinutes($lifetime))->delete();
})->daily()->name('purge-stale-user-sessions');
