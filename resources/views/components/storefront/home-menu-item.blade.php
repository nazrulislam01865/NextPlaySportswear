@props(['item', 'depth' => 0, 'align' => 'left'])

{{--
    Backward-compatible alias kept for older homepage code.
    The actual desktop menu markup lives in components/storefront/menu/desktop-item.blade.php
    so one CSS/Blade update changes the menu everywhere.
--}}
<x-storefront.menu.desktop-item :item="$item" :align="$align" />
