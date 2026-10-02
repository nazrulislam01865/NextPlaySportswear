<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardProgramSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RewardProgramController extends Controller
{
    public function edit(): View
    {
        return view('admin.rewards.edit', [
            'settings' => RewardProgramSetting::current(),
            'canManageRewards' => (bool) auth('admin')->user()?->canAdmin('customers.manage'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'default_progress_target' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'default_reward_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'referral_enabled' => ['nullable', 'boolean'],
            'referral_friend_reward_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'referral_referrer_reward_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'referral_minimum_order' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $settings = RewardProgramSetting::current();
        $settings->update([
            'is_active' => $request->boolean('is_active'),
            'default_progress_target' => round((float) $validated['default_progress_target'], 2),
            'default_reward_amount' => round((float) $validated['default_reward_amount'], 2),
            'referral_enabled' => $request->boolean('referral_enabled'),
            'referral_friend_reward_amount' => round((float) $validated['referral_friend_reward_amount'], 2),
            'referral_referrer_reward_amount' => round((float) $validated['referral_referrer_reward_amount'], 2),
            'referral_minimum_order' => round((float) $validated['referral_minimum_order'], 2),
            'updated_by' => $request->user('admin')?->id,
        ]);

        return redirect()->route('admin.reward-program.edit')->with('status', 'Rewards programme settings updated.');
    }
}
