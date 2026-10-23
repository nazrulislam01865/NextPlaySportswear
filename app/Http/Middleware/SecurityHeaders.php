<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add browser security headers without changing application behavior.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', $request->is('bulk-quote/track', 'bulk-quote/track/*') ? 'no-referrer' : 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=(), payment=(self)');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('X-Download-Options', 'noopen');

        // Production assets are local. The explicit external hosts below are
        // limited to local scripts/fonts and HTTPS product imagery selected by administrators.
        if (app()->environment('production')) {
            // Google's checkbox loads external scripts and frames only on this form.
            $recaptchaPage = $request->isMethod('GET')
                && $request->is('bulk-quote')
                && filled(config('services.recaptcha.site_key'))
                && filled(config('services.recaptcha.secret_key'));

            $policy = [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'".($recaptchaPage ? ' https://www.google.com https://www.gstatic.com' : ''),
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' data: https://fonts.gstatic.com",
                "img-src 'self' data: blob: https:",
                "connect-src 'self'".($recaptchaPage ? ' https://www.google.com https://recaptcha.google.com' : ''),
                "media-src 'self'",
                "worker-src 'self' blob:",
                'upgrade-insecure-requests',
            ];

            if ($recaptchaPage) {
                $policy[] = 'frame-src https://www.google.com https://recaptcha.google.com';
            }

            if (! $headers->has('Content-Security-Policy') && ! $request->routeIs('quote.guest.file', 'admin.bulk-quotes.file')) {
                $headers->set('Content-Security-Policy', implode('; ', $policy));
            }

            if ($request->isSecure()) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }
        }

        return $response;
    }
}
