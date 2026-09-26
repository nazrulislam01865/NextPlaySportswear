<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'logo_path',
])]
class StorefrontBrandingSetting extends Model
{
    public function logoUrl(): ?string
    {
        return PublicMedia::url($this->logo_path);
    }
}
