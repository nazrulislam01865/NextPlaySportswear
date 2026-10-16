<?php

namespace App\Providers;

use App\Contracts\EmailService;
use App\Services\Email\CentralEmailService;
use App\Services\Email\E2aTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('e2a', static fn (array $config): E2aTransport => new E2aTransport(
            apiKey: trim((string) ($config['api_key'] ?? '')),
            sender: trim((string) ($config['sender'] ?? '')),
            apiUrl: trim((string) ($config['api_url'] ?? 'https://api.e2a.dev/v1')),
            timeout: (int) ($config['timeout'] ?? 20),
        ));
    }

    public function register(): void
    {
        $this->app->singleton(
            EmailService::class,
            CentralEmailService::class
        );
    }
}
