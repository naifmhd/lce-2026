<?php

namespace App\Listeners;

use App\Models\UserSession;
use Illuminate\Auth\Events\Login;

class EnforceSessionLimit
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $lifetime = (int) config('session.lifetime');
        $limit = 2;

        // Purge stale registry entries for this user first
        UserSession::where('user_id', $user->id)
            ->where('last_activity_at', '<', now()->subMinutes($lifetime))
            ->delete();

        $activeSessions = UserSession::where('user_id', $user->id)
            ->orderBy('last_activity_at')
            ->get();

        $overflow = $activeSessions->count() - $limit + 1;

        if ($overflow > 0) {
            $behavior = config('auth.concurrent_session_limit_behavior', 'evict');

            if ($behavior === 'evict') {
                $activeSessions->take($overflow)->each(function (UserSession $record): void {
                    session()->getHandler()->destroy($record->session_id);
                    $record->delete();
                });
            }
        }

        $request = request();

        UserSession::updateOrCreate(
            ['session_id' => session()->getId()],
            [
                'user_id' => $user->id,
                'ip_address' => $request->header('CF-Connecting-IP') ?? $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity_at' => now(),
            ]
        );
    }
}
