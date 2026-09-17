<?php

namespace App\Support\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RequestId
{
    public const ATTRIBUTE = 'api_request_id';
    public const HEADER = 'X-Request-ID';

    private function __construct()
    {
    }

    public static function from(Request $request): string
    {
        return (string) $request->attributes->get(self::ATTRIBUTE, '');
    }

    public static function ensure(Request $request): string
    {
        $existing = self::from($request);
        if ($existing !== '') {
            return $existing;
        }

        $candidate = trim((string) $request->headers->get(self::HEADER, ''));
        $requestId = $candidate !== '' && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $candidate) === 1
            ? $candidate
            : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        return $requestId;
    }
}
