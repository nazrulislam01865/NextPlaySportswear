<?php

namespace App\Support;

final class PromotionBannerPlacement
{
    public const SALE_TOP = 'sale_top';
    public const SALE_MIDDLE = 'sale_middle';
    public const ALL_PRODUCTS_TOP = 'all_products_top';
    public const ALL_PRODUCTS_MIDDLE = 'all_products_middle';
    public const CATEGORY_TOP = 'category_top';

    public const ALL = [
        self::SALE_TOP,
        self::SALE_MIDDLE,
        self::ALL_PRODUCTS_TOP,
        self::ALL_PRODUCTS_MIDDLE,
        self::CATEGORY_TOP,
    ];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::SALE_TOP => 'Sale page — top',
            self::SALE_MIDDLE => 'Sale page — middle of product list',
            self::ALL_PRODUCTS_TOP => 'All Products page — top',
            self::ALL_PRODUCTS_MIDDLE => 'All Products page — middle of product list',
            self::CATEGORY_TOP => 'Category page — top',
        ];
    }

    private function __construct()
    {
    }
}
