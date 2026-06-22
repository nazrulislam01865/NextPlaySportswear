<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Blade and other view engines will search these paths for templates.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Do not use realpath() here. It returns false when the directory has not
    | been created yet, which makes Artisan commands such as view:clear and
    | optimize:clear fail with "View path not found". The service provider
    | creates this directory safely during application boot.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH') ?: storage_path('framework/views'),

];
