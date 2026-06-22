<?php

namespace App\Rules;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafePublicUrl implements ValidationRule
{
    public function __construct(private readonly bool $allowContactSchemes = false)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && ! PublicUrl::isAllowed($value, $this->allowContactSchemes)) {
            $fail('The :attribute must be a valid HTTP/HTTPS URL, a safe site-relative path, or an anchor.');
        }
    }
}
