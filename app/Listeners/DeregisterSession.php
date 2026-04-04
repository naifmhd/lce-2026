<?php

namespace App\Listeners;

use App\Models\UserSession;
use Illuminate\Auth\Events\Logout;

class DeregisterSession
{
    public function handle(Logout $event): void
    {
        UserSession::where('session_id', session()->getId())->delete();
    }
}
