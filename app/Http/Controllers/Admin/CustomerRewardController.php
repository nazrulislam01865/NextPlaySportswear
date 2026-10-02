<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Rewards\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerRewardController extends Controller
{
    public function __construct(private readonly RewardService $rewards)
    {
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $validated = $request->validate([
            'progress_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'progress_target_override' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'reward_amount_override' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $this->rewards->updateProfile($customer, $validated, $request->user('admin'));

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Customer reward progress settings updated.');
    }

    public function store(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->rewards->grant(
            $customer,
            (float) $validated['amount'],
            $request->user('admin'),
            (string) $validated['reason'],
        );

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Reward granted to '.$customer->name.'.');
    }
}
