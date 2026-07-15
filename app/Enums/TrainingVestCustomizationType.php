<?php

namespace App\Enums;

enum TrainingVestCustomizationType: string
{
    case Color = 'color';
    case Fabric = 'fabric';
    case Size = 'size';
    case VestType = 'vest_type';

    public function label(): string
    {
        return match ($this) {
            self::Color => 'Color',
            self::Fabric => 'Fabric',
            self::Size => 'Size',
            self::VestType => 'Vest Type',
        };
    }

    public function group(): string
    {
        return 'training_vest';
    }

    public function groupLabel(): string
    {
        return 'Training Vest Customization';
    }

    public function groupNumber(): string
    {
        return '1.14';
    }

    public function menuNumber(): string
    {
        $index = collect(self::menuTypes())
            ->search(fn (self $type): bool => $type === $this);

        return $this->groupNumber().'.'.(((int) $index) + 1);
    }

    public function usesColorValue(): bool
    {
        return $this === self::Color;
    }

    public function usesDescription(): bool
    {
        return $this === self::Fabric;
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Color => 'Example: Safety Orange',
            self::Fabric => 'Example: Breathable Mesh Polyester',
            self::Size => 'Example: Adult Medium',
            self::VestType => 'Example: Scrimmage Training Vest',
        };
    }

    public function helpText(): string
    {
        return match ($this) {
            self::Color => 'Add training vest colors with exact color values. These colors are stored separately from jersey, jacket, hoodie, and other customization colors.',
            self::Fabric => 'Add training vest fabric choices with useful details such as mesh, lightweight polyester, breathable fabric, or reversible material.',
            self::Size => 'Add training vest-specific size values such as youth, adult, or numbered training bib sizes.',
            self::VestType => 'Add training vest type choices such as scrimmage vest, reversible vest, numbered vest, or goalkeeper training vest.',
        };
    }

    public function imageTitle(): string
    {
        return match ($this) {
            self::Color => 'Optional color swatch',
            self::Fabric => 'Optional fabric texture image',
            self::Size => 'Optional size reference image',
            self::VestType => 'Optional vest type image',
        };
    }

    public function imageDescription(): string
    {
        return match ($this) {
            self::Color => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
            self::Fabric => 'Optional. Add a texture or material close-up for this training vest fabric.',
            self::Size => 'Optional. Add only when a visual size reference helps the admin or customer.',
            self::VestType => 'Optional. Add only when the training vest type needs a visual preview.',
        };
    }

    public function imageCta(): string
    {
        return match ($this) {
            self::Color => 'Choose swatch image',
            self::Fabric => 'Choose fabric image',
            self::Size => 'Choose size image',
            self::VestType => 'Choose vest type image',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function masterDataOptions(): array
    {
        return self::options();
    }

    /** @return array<int, self> */
    public static function menuTypes(): array
    {
        return [
            self::Color,
            self::Fabric,
            self::Size,
            self::VestType,
        ];
    }

    /** @return array<string, array{number: string, label: string, types: array<int, self>}> */
    public static function menuGroups(): array
    {
        return [
            'training_vest' => [
                'number' => '1.14',
                'label' => 'Training Vest Customization',
                'types' => self::menuTypes(),
            ],
        ];
    }
}
