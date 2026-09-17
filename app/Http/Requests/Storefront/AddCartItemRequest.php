<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Concerns\HasCartItemRules;
use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    use HasCartItemRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->cartItemRules();
    }
}
