<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'products_banner_path',
    'products_banner_color',
])]
class CatalogPageSetting extends Model
{
    public function productsBannerUrl(): ?string
    {
        return PublicMedia::url($this->products_banner_path);
    }
}
