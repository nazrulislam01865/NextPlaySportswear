<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AdminPagination
{
    /** @var array<int, int> */
    public const OPTIONS = [10, 15, 20, 25, 30, 40, 60, 100];

    public static function resolve(Request $request, int $default = 20): int
    {
        $requested = (int) $request->query('per_page', $default);

        return in_array($requested, self::OPTIONS, true) ? $requested : $default;
    }
}
