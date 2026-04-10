<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        ActivityLogService::log(
            'auth.logout',
            'Logout dari sistem.',
            $event->user,
            $event->user
        );
    }
}
