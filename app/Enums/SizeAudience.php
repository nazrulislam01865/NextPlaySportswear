<?php

namespace App\Enums;

enum SizeAudience: string
{
    case Male = 'male';
    case Female = 'female';
    case Youth = 'youth';
    case Kids = 'kids';
    case Unisex = 'unisex';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Youth => 'Young / Youth',
            self::Kids => 'Kids',
            self::Unisex => 'Unisex',
            self::Custom => 'Custom',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $audience): array => [$audience->value => $audience->label()]
        )->all();
    }
}
