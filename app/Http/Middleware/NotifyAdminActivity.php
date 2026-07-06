<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminNotificationService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NotifyAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldNotify($request, $response)) {
            try {
                $routeName = (string) $request->route()?->getName();
                $resourceInfo = $this->resourceInfo($request, $routeName);

                app(AdminNotificationService::class)->adminActivity(
                    $this->actionFor($request, $routeName),
                    $resourceInfo['label'],
                    $resourceInfo['name'],
                    Auth::guard('admin')->user(),
                    $this->targetUrl($request, $response),
                    [
                        'resource_id' => $resourceInfo['id'],
                        'resource_code' => $resourceInfo['code'],
                        'route_name' => $routeName,
                        'request_method' => $request->method(),
                    ]
                );
            } catch (Throwable $exception) {
                Log::warning('NextPlay admin activity notification failed.', [
                    'route' => $request->route()?->getName(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $response;
    }

    private function shouldNotify(Request $request, Response $response): bool
    {
        $routeName = (string) $request->route()?->getName();

        if (! Str::startsWith($routeName, 'admin.')) {
            return false;
        }

        if (! $request->isMethod('POST') && ! $request->isMethod('PUT') && ! $request->isMethod('PATCH') && ! $request->isMethod('DELETE')) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return false;
        }

        if (! Auth::guard('admin')->check()) {
            return false;
        }

        foreach ([
            'admin.login',
            'admin.login.store',
            'admin.logout',
            'admin.notifications.',
            'admin.products.',
        ] as $excluded) {
            if ($routeName === $excluded || Str::startsWith($routeName, $excluded)) {
                return false;
            }
        }

        return true;
    }

    private function actionFor(Request $request, string $routeName): string
    {
        $name = Str::after($routeName, 'admin.');

        if ($request->isMethod('DELETE') || Str::endsWith($name, '.destroy')) {
            return 'deleted';
        }

        if (Str::contains($name, '.duplicate')) {
            return 'duplicated';
        }

        if (Str::contains($name, '.import')) {
            return 'imported';
        }

        if (Str::contains($name, ['.update', '.toggle', '.bulk', '.sync-legacy', '.ordering.update', '.default']) || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            return 'updated';
        }

        return 'created';
    }

    /** @return array{label: string, name: string, id: mixed, code: string} */
    private function resourceInfo(Request $request, string $routeName): array
    {
        $model = $this->firstRouteModel($request);
        $label = $this->resourceLabel($routeName, $model);
        $name = $this->modelDisplayName($model) ?: $this->requestDisplayName($request);

        return [
            'label' => $label,
            'name' => $name,
            'id' => $model?->getKey(),
            'code' => $model ? $this->modelCode($model) : $this->requestCode($request),
        ];
    }

    private function firstRouteModel(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }

    private function resourceLabel(string $routeName, ?Model $model = null): string
    {
        $name = Str::after($routeName, 'admin.');

        $map = [
            'orders.shipments.' => 'Order shipment',
            'orders.requests.' => 'Order change request',
            'orders.downloads.' => 'Order download',
            'categories.products.' => 'Category product assignment',
            'categories.ordering' => 'Category ordering',
            'jersey-customization-options.' => 'Customization option',
            'size-option-groups.' => 'Size option group',
            'rural-area-surcharges.' => 'Rural area surcharge',
            'payment-methods.' => 'Payment method',
            'shipping-methods.' => 'Shipping method',
            'homepage-slides.' => 'Homepage slide',
            'role-matrix.roles.' => 'Admin role',
            'role-matrix.' => 'Role matrix',
            'attributes.' => 'Catalog attribute',
            'categories.' => 'Category',
            'menus.' => 'Navigation menu',
            'coupons.' => 'Coupon',
            'users.' => 'Admin user',
            'orders.' => 'Order',
            'returns.' => 'Return request',
        ];

        foreach ($map as $prefix => $label) {
            if (Str::startsWith($name, $prefix)) {
                return $label;
            }
        }

        if ($model) {
            return Str::of(class_basename($model))->headline()->toString();
        }

        return Str::of(Str::before($name, '.'))->replace(['-', '_'], ' ')->headline()->singular()->toString() ?: 'Admin record';
    }

    private function modelDisplayName(?Model $model): string
    {
        if (! $model) {
            return '';
        }

        foreach (['name', 'title', 'label', 'menu_label', 'code', 'sku', 'email', 'order_number', 'tracking_number', 'slug'] as $attribute) {
            $value = trim((string) ($model->getAttribute($attribute) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }

    private function modelCode(Model $model): string
    {
        foreach (['code', 'sku', 'slug', 'email', 'order_number', 'tracking_number'] as $attribute) {
            $value = trim((string) ($model->getAttribute($attribute) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function requestDisplayName(Request $request): string
    {
        foreach (['name', 'title', 'label', 'menu_label', 'code', 'sku', 'email', 'order_number', 'tracking_number', 'slug'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function requestCode(Request $request): string
    {
        foreach (['code', 'sku', 'slug', 'email', 'order_number', 'tracking_number'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function targetUrl(Request $request, Response $response): string
    {
        if ($response instanceof RedirectResponse) {
            $location = (string) $response->headers->get('Location', '');
            if ($location !== '') {
                return $location;
            }
        }

        $routeName = (string) $request->route()?->getName();
        if ($routeName !== '' && Route::has($routeName) && ! $request->isMethod('DELETE')) {
            return $request->fullUrl();
        }

        return url()->previous() ?: route('admin.dashboard');
    }
}
