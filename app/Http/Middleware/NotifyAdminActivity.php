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
        $routeModel = $this->firstRouteModel($request);
        $beforeAttributes = $routeModel?->getAttributes() ?? [];
        $response = $next($request);

        if ($this->shouldNotify($request, $response)) {
            try {
                $routeName = (string) $request->route()?->getName();
                $resourceInfo = $this->resourceInfo($request, $routeName);
                $action = $this->actionFor($request, $routeName);

                app(AdminNotificationService::class)->adminActivity(
                    $action,
                    $resourceInfo['label'],
                    $resourceInfo['name'],
                    Auth::guard('admin')->user(),
                    $this->targetUrl($request, $response),
                    [
                        'resource_id' => $resourceInfo['id'],
                        'resource_code' => $resourceInfo['code'],
                        'route_name' => $routeName,
                        'request_method' => $request->method(),
                        'change_details' => $action === 'updated'
                            ? $this->modelChangeDetails($routeModel, $beforeAttributes)
                            : [],
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
            // Remote-area imports arrive in many 1,000-row chunks. Logging each network
            // chunk would create dozens of duplicate audit notifications; only the final
            // successful import endpoint is allowed to generate the activity event.
            'admin.rural-area-surcharges.import.start',
            'admin.rural-area-surcharges.import.chunk',
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

        if (Str::endsWith($name, '.suspend')) {
            return 'suspended';
        }

        if (Str::endsWith($name, '.reactivate')) {
            return 'reactivated';
        }

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
            'rural-area-surcharges.' => 'Remote area surcharge',
            'payment-methods.' => 'Payment method',
            'shipping-methods.' => 'Shipping method',
            'faqs.' => 'FAQ',
            'homepage-slides.' => 'Homepage slide',
            'role-matrix.roles.' => 'Admin role',
            'role-matrix.' => 'Role matrix',
            'attributes.' => 'Catalog attribute',
            'categories.' => 'Category',
            'menus.' => 'Navigation menu',
            'coupons.' => 'Coupon',
            'customers.' => 'Customer account',
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

    /**
     * Build a small, safe attribute diff for generic admin update notifications.
     * Product updates use their richer dedicated catalog change tracker instead.
     *
     * @param array<string, mixed> $before
     * @return array<int, string>
     */
    private function modelChangeDetails(?Model $model, array $before): array
    {
        if (! $model || $before === []) {
            return [];
        }

        $fresh = $model->fresh();
        if (! $fresh) {
            return [];
        }

        $after = $fresh->getAttributes();
        $currency = trim((string) ($after['currency'] ?? $before['currency'] ?? ''));
        $details = [];

        foreach ($after as $field => $newValue) {
            if (! array_key_exists($field, $before) || $this->skipAuditAttribute($field)) {
                continue;
            }

            $oldValue = $before[$field];
            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $label = Str::of($field)->replace('_', ' ')->headline()->toString();
            $details[] = $this->formatAuditChange($field, $label, $oldValue, $newValue, $currency);

            if (count($details) >= 8) {
                break;
            }
        }

        return array_values(array_unique(array_filter($details)));
    }

    private function skipAuditAttribute(string $field): bool
    {
        if (in_array($field, [
            'id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by',
            'remember_token', 'email_verified_at', 'last_login_at', 'last_login_ip',
        ], true)) {
            return true;
        }

        return Str::contains(Str::lower($field), [
            'password', 'token', 'secret', 'credential', 'private_key', 'api_key', 'signature',
        ]);
    }

    private function formatAuditChange(string $field, string $label, mixed $oldValue, mixed $newValue, string $currency): string
    {
        if ($this->isLongAuditValue($oldValue) || $this->isLongAuditValue($newValue)) {
            return 'Updated '.Str::lower($label);
        }

        $old = $this->auditDisplayValue($field, $oldValue, $currency);
        $new = $this->auditDisplayValue($field, $newValue, $currency);

        return 'Updated '.Str::lower($label).' from '.$old.' to '.$new;
    }

    private function isLongAuditValue(mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        $text = trim((string) $value);

        return mb_strlen($text) > 70 || Str::contains($text, ['<p', '<div', '<table', '{', '[']);
    }

    private function auditDisplayValue(string $field, mixed $value, string $currency): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        if (Str::startsWith($field, 'is_') || Str::startsWith($field, 'has_') || in_array($field, ['active', 'enabled'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Yes' : 'No';
        }

        if (Str::contains($field, ['price', 'amount', 'cost', 'total']) && is_numeric($value)) {
            return trim(($currency !== '' ? $currency.' ' : '').number_format((float) $value, 2, '.', ','));
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : 'Not set';
    }
}
