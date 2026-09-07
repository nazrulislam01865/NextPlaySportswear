<?php

namespace App\Providers;

use App\Contracts\EmailService;
use App\Services\Email\CentralEmailService;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            EmailService::class,
            CentralEmailService::class
        );
    }
}
