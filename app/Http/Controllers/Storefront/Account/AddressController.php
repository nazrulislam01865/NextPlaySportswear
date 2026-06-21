<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\StoreAddressRequest;
use App\Models\CustomerAddress;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function __construct(private readonly CustomerAccountService $accountService)
    {
    }

    public function index(Request $request): View
    {
        return view('storefront.account.addresses', [
            'seo' => $this->seo(),
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
            'addressBook' => $this->accountService->addressBook($request->user()),
            'states' => $this->accountService->usStates(),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->cleanAddressData($request->validated());
        $makeDefault = $request->boolean('is_default') || $user->customerAddresses()->doesntExist();

        if ($makeDefault) {
            $user->customerAddresses()->where('type', $data['type'])->update(['is_default' => false]);
        }

        $user->customerAddresses()->create($data + ['is_default' => $makeDefault]);

        return back()->with('status', 'Address saved successfully.');
    }

    public function makeDefault(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $request->user()->customerAddresses()
            ->where('type', $address->type)
            ->update(['is_default' => false]);

        $address->forceFill(['is_default' => true])->save();

        return back()->with('status', 'Default address updated.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->delete();

        return back()->with('status', 'Address removed from your account.');
    }

    /**
     * @return array<string, string>
     */
    private function seo(): array
    {
        return [
            'title' => 'Saved Addresses | NextPlay Sportswear',
            'description' => 'Manage saved shipping and billing addresses for faster NextPlay checkout.',
            'robots' => 'noindex, nofollow',
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function cleanAddressData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = Str::of($value)->stripTags()->trim()->toString();
            }
        }

        return $data;
    }
}
