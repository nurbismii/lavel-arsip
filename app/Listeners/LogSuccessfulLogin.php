<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        ActivityLogService::log(
            'auth.login',
            'Login ke sistem.',
            $event->user,
            $event->user
        );
    }
}
