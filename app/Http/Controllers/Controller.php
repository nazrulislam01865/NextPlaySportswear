<?php

namespace App\Http\Controllers;

use App\Support\AdminPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function adminPerPage(int $default = 20): int
    {
        return AdminPagination::resolve(request(), $default);
    }
}
