<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Ensure Laravel runtime directories exist
|--------------------------------------------------------------------------
|
| Empty directories are commonly omitted when a project is copied or zipped.
| File-backed sessions, cache, compiled views and logs must exist before the
| framework handles a request. Creating them here keeps local, CI and fresh
| deployments self-healing without routing any request through extra code.
|
*/
$runtimeDirectories = [
    dirname(__DIR__).'/storage/framework/cache/data',
    dirname(__DIR__).'/storage/framework/sessions',
    dirname(__DIR__).'/storage/framework/testing',
    dirname(__DIR__).'/storage/framework/views',
    dirname(__DIR__).'/storage/logs',
    dirname(__DIR__).'/bootstrap/cache',
];

foreach ($runtimeDirectories as $runtimeDirectory) {
    if (! is_dir($runtimeDirectory) && ! mkdir($runtimeDirectory, 0775, true) && ! is_dir($runtimeDirectory)) {
        throw new RuntimeException(sprintf('Unable to create Laravel runtime directory: %s', $runtimeDirectory));
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin/*')
            ? route('admin.login')
            : route('login'));
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
            'order.manager' => \App\Http\Middleware\EnsureOrderManager::class,
            'admin.permission' => \App\Http\Middleware\EnsureAdminPermission::class,
            'admin.activity' => \App\Http\Middleware\NotifyAdminActivity::class,
            'not.admin' => \App\Http\Middleware\RedirectAdminFromCustomerArea::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
