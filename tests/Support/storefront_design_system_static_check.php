<?php

$root = dirname(__DIR__, 2);
$task = $argv[1] ?? 'all';
$failures = [];

$read = static function (string $path) use ($root): string {
    $full = $root.'/'.$path;
    if (!is_file($full)) {
        return '';
    }
    return (string) file_get_contents($full);
};
$contains = static function (string $haystack, string $needle, string $label) use (&$failures): void {
    if (!str_contains($haystack, $needle)) $failures[] = $label.' missing: '.$needle;
};
$notContains = static function (string $haystack, string $needle, string $label) use (&$failures): void {
    if (str_contains($haystack, $needle)) $failures[] = $label.' still contains: '.$needle;
};
$exists = static function (string $path) use ($root, &$failures): void {
    if (!is_file($root.'/'.$path)) $failures[] = 'missing file: '.$path;
};

$theme = $read('resources/css/storefront-theme.css');
$storefront = $read('resources/css/storefront.css');
$pagination = $read('resources/css/pagination.css');
$fontAssets = $read('resources/views/components/storefront/font-assets.blade.php');
$layout = $read('resources/views/components/layouts/storefront.blade.php');
$tailwind = $read('tailwind.config.js');

if ($task === '1' || $task === 'all') {
    $contains($theme, '--np-color-primary: #061F44;', 'theme');
    $contains($theme, '--np-color-secondary: #CF5D38;', 'theme');
    $contains($theme, '--np-color-orange: #CF5D38;', 'theme');
    $contains($theme, '--np-font-body: "Expose"', 'theme');
    $contains($theme, '--np-title-1-size: 48px;', 'theme');
    $contains($theme, '--np-body-1-size: 18px;', 'theme');
    $contains($theme, '--np-cta-all-caps-regular-size: 16px;', 'theme');
    $notContains($fontAssets, 'fonts.googleapis.com', 'font assets');
    $contains($layout, 'content="#061F44"', 'storefront layout');
    foreach (['Regular','Medium','Bold','Black'] as $weight) {
        $exists("resources/fonts/expose/Expose-{$weight}.woff2");
    }
}

if ($task === '2' || $task === 'all') {
    $contains($tailwind, "red: themeColor('--np-color-secondary')", 'tailwind');
    $contains($tailwind, "navy: themeColor('--np-color-primary')", 'tailwind');
    $contains($storefront, '.btn-primary,', 'buttons');
    $contains($storefront, '.btn-secondary,', 'buttons');
    $contains($storefront, 'background: var(--np-color-primary);', 'buttons');
    $contains($storefront, 'background: var(--np-color-secondary);', 'buttons');
    $contains($storefront, 'font-size: var(--np-cta-all-caps-regular-size);', 'buttons');
    $contains($storefront, '.home-page a:not(.btn)', 'button cascade');
    $contains($storefront, '.bulk-quote-page a:not(.btn)', 'button cascade');
}

if ($task === '3' || $task === 'all') {
    $notContains($storefront, 'Inter', 'storefront css');
    $notContains($storefront, 'Oswald', 'storefront css');
    $notContains(strtolower($pagination), '#15345d', 'pagination');
    $notContains(strtolower($pagination), '#0d2545', 'pagination');
    $contains($storefront, 'font-size: var(--np-title-2-size);', 'section heading typography');
    $contains($pagination, 'color: var(--np-color-primary);', 'pagination brand');
}

if ($task === '4' || $task === 'all') {
    $roots = [
        'resources/views/components/storefront',
        'resources/views/storefront',
        'resources/views/errors',
    ];
    $forbidden = ['fonts.googleapis.com', 'Inter', 'Oswald', '#e91d33', '#c9182b', '#15345d', '#0d2545', '#2467b7'];
    foreach ($roots as $relative) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$relative, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;
            $content = (string) file_get_contents($file->getPathname());
            // Decorative testimonial avatar arrays are intentionally outside brand UI chrome.
            if (str_contains($file->getPathname(), '/testimonials.blade.php')) {
                $content = preg_replace('/\$colors\s*=\s*\[[^;]+;/s', '', $content) ?? $content;
            }
            foreach ($forbidden as $needle) {
                $found = in_array($needle, ['Inter', 'Oswald'], true)
                    ? (bool) preg_match('/\b'.preg_quote($needle, '/').'\b/', $content)
                    : stripos($content, $needle) !== false;
                if ($found) {
                    $failures[] = str_replace($root.'/', '', $file->getPathname()).' contains forbidden storefront identity: '.$needle;
                }
            }
        }
    }
}


if ($task === 'brand-audit' || $task === 'all') {
    $legacyBrandHexes = [
        '#ed102b', '#f1122e', '#ff233c', '#d81e35', '#c80d24', '#d80e27',
        '#ef2028', '#ef233c', '#c91832', '#b20c20', '#2f67c7', '#285caa',
        '#0b63ce', '#0e4f9f', '#061e40', '#062042', '#071a35', '#08182f',
        '#081d3f', '#061744', '#2f6fbd', '#00c2ff', '#ff334d', '#ff4058', '#ef102b', '#f21e25',
        '#1d4ed8', '#225a9d', '#075fad', '#08213e', '#123866', '#0a2b50', '#0d2f57', '#071f3e',
        '#123b6b', '#0d2d55', '#08264e', '#061c3d', '#071935', '#092d61', '#061d43', '#112d4d',
        '#082a58', '#041833', '#102642', '#071426', '#081735', '#111b31', '#22334c', '#294775',
        '#18253d', '#111f38', '#142038', '#1a2941', '#1b2940', '#17243b', '#112039',
        '#06152c', '#020916', '#0d1a32', '#ffccd3', '#7fa6d5', '#be123c',
    ];
    foreach ($legacyBrandHexes as $hex) {
        if (stripos($storefront, $hex) !== false) {
            $failures[] = 'storefront css still contains legacy brand hex: '.$hex;
        }
    }
    $legacyBrandRgb = [
        'rgba(233, 29, 51,', 'rgba(237, 16, 43,', 'rgba(36, 103, 183,',
        'rgba(37, 99, 235,', 'rgba(59, 130, 246,', 'rgba(11,99,206,',
    ];
    foreach ($legacyBrandRgb as $rgb) {
        if (stripos($storefront, $rgb) !== false) {
            $failures[] = 'storefront css still contains legacy brand rgba: '.$rgb;
        }
    }
}

if ($task === '5' || $task === 'all') {
    $manifestPath = $root.'/public/build/manifest.json';
    if (!is_file($manifestPath)) {
        $failures[] = 'compiled manifest missing';
    } else {
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $entry = $manifest['resources/css/storefront.css']['file'] ?? null;
        if (!$entry || !is_file($root.'/public/build/'.$entry)) {
            $failures[] = 'compiled storefront css missing from manifest';
        } else {
            $compiled = (string) file_get_contents($root.'/public/build/'.$entry);
            foreach (['Expose', '#061f44', '#cf5d38', '.btn-primary'] as $needle) {
                if (stripos($compiled, $needle) === false) $failures[] = 'compiled css missing: '.$needle;
            }
            foreach (['Inter', 'Oswald'] as $needle) {
                if (preg_match('/\b'.preg_quote($needle, '/').'\b/', $compiled)) {
                    $failures[] = 'compiled css still contains font family: '.$needle;
                }
            }
            foreach (['#e91d33', '#c9182b', '#15345d', '#0d2545', '#2467b7'] as $legacyColor) {
                if (stripos($compiled, $legacyColor) !== false) {
                    $failures[] = 'compiled css still contains legacy brand color: '.$legacyColor;
                }
            }
        }
    }
}

if ($failures) {
    fwrite(STDERR, "FAIL\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "PASS {$task}\n";
