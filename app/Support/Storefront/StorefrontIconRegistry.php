<?php

namespace App\Support\Storefront;

class StorefrontIconRegistry
{
    /** @return array<string, string> */
    public static function utility(): array
    {
        return [
            '' => 'No icon',
            'package' => 'Package / Track order',
            'delivery' => 'Delivery & returns',
            'headphones' => 'Support / Headphones',
            'phone' => 'Phone',
            'mail' => 'Email',
            'map-pin' => 'Location',
            'clock' => 'Clock',
            'help-circle' => 'Help',
            'info' => 'Information',
            'external-link' => 'External link',
            'tag' => 'Tag / Offer',
            'gift' => 'Gift',
            'search' => 'Search',
            'user' => 'Account',
            'heart' => 'Wishlist',
            'shopping-cart' => 'Cart',
            'truck' => 'Truck',
            'shield-check' => 'Shield / Secure',
            'file-text' => 'Document',
        ];
    }

    /** @return array<string, string> */
    public static function menu(): array
    {
        return self::utility();
    }

    /** @return array<string, string> */
    public static function social(): array
    {
        return [
            'youtube' => 'YouTube',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
            'linkedin' => 'LinkedIn',
            'x' => 'X / Twitter',
            'pinterest' => 'Pinterest',
            'website' => 'Website',
        ];
    }

    /** @return array<int, string> */
    public static function utilityKeys(): array
    {
        return array_keys(self::utility());
    }

    /** @return array<int, string> */
    public static function menuKeys(): array
    {
        return array_keys(self::menu());
    }

    /** @return array<int, string> */
    public static function socialKeys(): array
    {
        return array_keys(self::social());
    }
}
