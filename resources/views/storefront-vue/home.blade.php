<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0d2545">

    <title>{{ $siteName }}</title>

    <link rel="preconnect" href="https://api.fontshare.com">
    <link href="https://api.fontshare.com/v2/css?f[]=expose@400,500,700,900&display=swap" rel="stylesheet">

    @vite('resources/js/storefront/main.ts')
</head>
<body>
    <div id="nextplay-storefront"></div>
    <noscript>This storefront requires JavaScript to display the new homepage.</noscript>
</body>
</html>
