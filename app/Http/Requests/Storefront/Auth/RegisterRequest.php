<?php

namespace App\Http\Requests\Storefront\Auth;

use App\Services\Referrals\ReferralOfferService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $referralRegistration = is_array($this->session()->get(ReferralOfferService::SESSION_KEY));
        $firstName = trim((string) $this->input('first_name'));
        $lastName = trim((string) $this->input('last_name'));
        $name = trim((string) $this->input('name'));

        if ($referralRegistration && ($firstName !== '' || $lastName !== '')) {
            $name = trim($firstName.' '.$lastName);
        }

        $merge = [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower(trim((string) $this->input('email'))),
        ];

        // The approved referral prototype has one password field. Confirmation
        // remains part of normal registration, while referral registration uses
        // the same strong server-side password validation without adding a field
        // that is not present in the approved design.
        if ($referralRegistration) {
            $merge['password_confirmation'] = (string) $this->input('password');
        }

        $this->merge($merge);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $referralRegistration = is_array($this->session()->get(ReferralOfferService::SESSION_KEY));
        $password = Password::min(8)->letters()->numbers();

        if ($referralRegistration) {
            $password = $password->symbols();
        }

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'first_name' => [Rule::requiredIf($referralRegistration), 'nullable', 'string', 'min:2', 'max:60'],
            'last_name' => [Rule::requiredIf($referralRegistration), 'nullable', 'string', 'min:2', 'max:60'],
            // Verification proves ownership/deliverability, so registration
            // avoids network-dependent DNS validation on the request path.
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', $password],
            'website' => ['nullable', 'max:0'],
            'terms' => ['accepted'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the Terms & Conditions and Privacy Policy before creating an account.',
            'website.max' => 'The registration request could not be accepted.',
            'email.unique' => 'A customer account already exists with this email address. Please sign in or reset your password.',
        ];
    }
}
