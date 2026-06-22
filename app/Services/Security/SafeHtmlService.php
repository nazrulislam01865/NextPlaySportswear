<?php

namespace App\Services\Security;

use App\Support\PublicUrl;

class SafeHtmlService
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><h2><h3><h4><ul><ol><li><blockquote><a><span><div><table><thead><tbody><tr><th><td><hr><code><pre>';

    public function sanitize(?string $html): ?string
    {
        if (! filled($html)) {
            return null;
        }

        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link|base)[^>]*>.*?<\/\1\s*>/is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Remove event handlers, inline style, data URLs and other executable attributes.
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+(src|formaction|xlink:href)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function (array $matches): string {
            $attributes = $matches[1] ?? '';
            $safeHref = null;

            if (preg_match('/\s+href\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $href)) {
                $url = html_entity_decode(
                    trim((string) ($href[1] ?? $href[2] ?? $href[3] ?? '')),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
                if (PublicUrl::isAllowed($url, allowContactSchemes: true)) {
                    $safeHref = $url;
                }
            }

            $attributes = preg_replace('/\s+href\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes) ?? '';
            $attributes = preg_replace('/\s+(?!title\b|target\b|rel\b|class\b)[a-z0-9:-]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes) ?? '';
            $attributes = preg_replace('/\s+target\s*=\s*(?:"(?!_blank")[^"]*"|\'(?!_blank\')[^\']*\'|(?!_blank\b)[^\s>]+)/i', '', $attributes) ?? '';

            if ($safeHref !== null) {
                $attributes .= ' href="'.e($safeHref).'"';
            }

            if (preg_match('/\s+target\s*=\s*("|\')_blank\1/i', $attributes)) {
                $attributes = preg_replace('/\s+rel\s*=\s*("|\')[^"\']*\1/i', '', $attributes) ?? '';
                $attributes .= ' rel="noopener noreferrer"';
            }

            return '<a'.$attributes.'>';
        }, $html) ?? '';

        // Keep only safe attributes on non-link tags.
        $html = preg_replace_callback('/<(?!a\b)([a-z0-9]+)\b([^>]*)>/i', function (array $matches): string {
            $tag = strtolower($matches[1]);
            $attributes = $matches[2] ?? '';
            $allowed = ['class'];
            if (in_array($tag, ['th', 'td'], true)) {
                $allowed[] = 'colspan';
                $allowed[] = 'rowspan';
            }
            $pattern = '/\s+(?!'.implode('\b|', $allowed).'\b)[a-z0-9:-]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';
            $attributes = preg_replace($pattern, '', $attributes) ?? '';

            return '<'.$tag.$attributes.'>';
        }, $html) ?? '';

        return trim($html) ?: null;
    }
}
