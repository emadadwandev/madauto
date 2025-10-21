<?php

namespace App\Listeners;

use App\Events\Auth\Authenticated;
use App\Services\UserActivityService;

class LogUserLogin
{
    public function handle(Authenticated $event): void
    {
        // Only log actual logins, not every authenticated request
        if (request()->isMethod('POST') && request()->is('/login')) {
            UserActivityService::logLogin($event->user);
        }
        
        // Also update last_login_at timestamp
        $event->user->update(['last_login_at' => now()]);
    }
}
