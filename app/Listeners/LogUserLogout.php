<?php

namespace App\Listeners;

use App\Events\Auth\Logout;
use App\Services\UserActivityService;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        UserActivityService::logLogout($event->user);
    }
}
