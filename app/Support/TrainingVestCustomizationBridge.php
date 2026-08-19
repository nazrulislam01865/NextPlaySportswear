<?php

namespace App\Support;

use App\Enums\JerseyCustomizationType;
use App\Enums\TrainingVestCustomizationType;

final class TrainingVestCustomizationBridge
{
    public static function sharedType(TrainingVestCustomizationType|string $type): JerseyCustomizationType
    {
        $value = $type instanceof TrainingVestCustomizationType ? $type->value : $type;

        return match ($value) {
            TrainingVestCustomizationType::Color->value => JerseyCustomizationType::TrainingVestColorOption,
            TrainingVestCustomizationType::Fabric->value => JerseyCustomizationType::TrainingVestFabricOption,
            TrainingVestCustomizationType::Size->value => JerseyCustomizationType::TrainingVestSizeOption,
            TrainingVestCustomizationType::VestType->value => JerseyCustomizationType::TrainingVestVestTypeOption,
            default => throw new \InvalidArgumentException("Unsupported Training Vest customization type [{$value}]."),
        };
    }

    public static function sizeMirrorSlug(int $groupId, string $code): string
    {
        return 'tvg-'.$groupId.'-'.strtolower(trim($code));
    }

    public static function sizeMirrorPrefix(int $groupId): string
    {
        return 'tvg-'.$groupId.'-';
    }
}
