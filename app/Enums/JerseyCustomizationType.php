<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum JerseyCustomizationType: string
{
    case NeckAndCollar = 'neck_and_collar';
    case Fabric = 'fabric';
    case Color = 'color';
    case SleevesAndCuffs = 'sleeves_and_cuffs';
    case JerseyStyle = 'jersey_style';

    public function label(): string
    {
        return match ($this) {
            self::NeckAndCollar => 'Neck and Collar',
            self::Fabric => 'Fabric',
            self::Color => 'Color',
            self::SleevesAndCuffs => 'Sleeves and Cuffs',
            self::JerseyStyle => 'Jersey Style',
        };
    }

    public function productCode(): string
    {
        return Str::slug($this->label());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
