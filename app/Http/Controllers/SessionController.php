<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(): Response
    {
        $sessions = UserSession::with('user:id,name,email')
            ->active()
            ->latest('last_activity_at')
            ->paginate(20)
            ->through(fn (UserSession $s) => [
                'id' => $s->id,
                'user_name' => $s->user?->name,
                'user_email' => $s->user?->email,
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity_at' => $s->last_activity_at?->diffForHumans(),
                'created_at' => $s->created_at?->format('d M Y H:i'),
                'is_current' => $s->session_id === session()->getId(),
            ]);

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    public function destroy(UserSession $session): RedirectResponse
    {
        session()->getHandler()->destroy($session->session_id);
        $session->delete();

        return redirect()->back();
    }
}
