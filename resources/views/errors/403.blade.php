@if(auth('admin')->check())
    @php($fallbackUrl = \App\Support\AdminRbac::firstAllowedRoute(auth('admin')->user()) ?? route('home'))
    <x-layouts.admin
        title="Access Denied"
        eyebrow="Security"
        subtitle="Your role does not include access to this admin section."
        :storefront-url="route('home')"
    >
        <div class="mx-auto max-w-3xl">
            <x-admin.section-card
                title="You don’t have permission to access this section"
                description="NextPlay protects each admin module with role-based permissions. Your account is signed in, but this action is outside the permissions assigned to your role."
            >
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    If this access is required for your work, ask a Super Admin to update your Role Matrix permissions. No data was changed by this request.
                </div>

                <div class="responsive-actions mt-5 [&_.btn]:w-full sm:[&_.btn]:w-auto">
                    <a class="btn btn-red" href="{{ $fallbackUrl }}">Go to an available section</a>
                    <a class="btn btn-white" href="{{ route('home') }}" target="_blank" rel="noopener">View storefront ↗</a>
                </div>
            </x-admin.section-card>
        </div>
    </x-layouts.admin>
@else
    @include('errors.error', [
        'code' => '403',
        'status' => 'Access denied',
        'title' => 'You Don’t Have Access',
        'message' => 'You do not have permission to open this page or perform this action.',
        'showSupport' => false,
        'showShop' => false,
    ])
@endif
