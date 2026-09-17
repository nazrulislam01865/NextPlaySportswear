<?php

use App\Exceptions\CheckoutApiConflictException;
use App\Support\Api\RequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\ApiRequestId::class);
        $middleware->validateCsrfTokens(except: ['webhooks/stripe']);

        // Bind every authenticated customer browser session to the user's
        // security version. A password reset increments that version, so all
        // previously issued customer sessions are rejected on their next web
        // request regardless of whether sessions are stored in Redis, SQL or
        // files.
        $middleware->web(append: [
            \App\Http\Middleware\EnforceCustomerSessionVersion::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin/*')
            ? route('admin.login')
            : route('login'));
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\HideAdminRoutesFromStorefrontUsers::class,
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'admin.hidden' => \App\Http\Middleware\HideAdminRoutesFromStorefrontUsers::class,
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

        $apiError = static function (Request $request, string $message, int $status, array $extra = []) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            $requestId = RequestId::ensure($request);

            return response()
                ->json(array_merge([
                    'message' => $message,
                ], $extra, [
                    'request_id' => $requestId,
                ]), $status)
                ->header(RequestId::HEADER, $requestId);
        };

        $messageForStatus = static fn (int $status): string => match ($status) {
            400 => 'Bad request.',
            401 => 'Unauthenticated.',
            403 => 'Forbidden.',
            404 => 'Resource not found.',
            405 => 'Method not allowed.',
            409 => 'Conflict.',
            422 => 'Validation failed.',
            429 => 'Too many requests.',
            default => $status >= 500 ? 'Server error.' : 'Request failed.',
        };

        $exceptions->render(function (CheckoutApiConflictException $exception, Request $request) use ($apiError) {
            return $apiError($request, $exception->getMessage(), 409, [
                'code' => $exception->errorCode,
                'data' => $exception->context === [] ? (object) [] : $exception->context,
            ]);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Validation failed.', 422, [
                'errors' => $exception->errors(),
            ]);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Unauthenticated.', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Forbidden.', 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Resource not found.', 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Resource not found.', 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Method not allowed.', 405);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($apiError, $messageForStatus) {
            $status = $exception->getStatusCode();

            return $apiError($request, $messageForStatus($status), $status);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) use ($apiError) {
            return $apiError($request, 'Server error.', 500);
        });
    })->create();
