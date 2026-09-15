<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Exceptions;

use Sentry\Event;
use Sentry\EventHint;

class SentryEventScrubber
{
    public const FILTERED = '[Filtered]';

    private const SENSITIVE_NAMES = [
        'token',
        '_token',
        'access_token',
        'api_key',
        'hash',
        'signature',
        'code',
        'key',
        'secret',
        'password',
        'password_confirmation',
        'current_password',
        'email',
    ];

    public static function handle(Event $event, ?EventHint $hint = null): Event
    {
        $request = $event->getRequest();

        if ($request === []) {
            return $event;
        }

        return $event->setRequest(self::scrub($request, self::currentRouteParameters()));
    }

    public static function scrub(array $request, array $routeParameters = []): array
    {
        if (isset($request['url']) && is_string($request['url'])) {
            $request['url'] = self::scrubUrl($request['url'], $routeParameters);
        }

        if (isset($request['query_string']) && is_string($request['query_string'])) {
            $request['query_string'] = self::scrubQuery($request['query_string']);
        }

        return $request;
    }

    private static function scrubUrl(string $url, array $routeParameters): string
    {
        foreach ($routeParameters as $name => $value) {
            if (is_scalar($value) && self::isSensitive((string) $name)) {
                $url = str_replace([rawurlencode((string) $value), (string) $value], self::FILTERED, $url);
            }
        }

        [$path, $query] = array_pad(explode('?', $url, 2), 2, null);

        return $query === null ? $path : $path.'?'.self::scrubQuery($query);
    }

    private static function scrubQuery(string $query): string
    {
        $pairs = array_map(static function (string $pair): string {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, null);

            if ($value === null || ! self::isSensitive(urldecode($name))) {
                return $pair;
            }

            return $name.'='.self::FILTERED;
        }, explode('&', $query));

        return implode('&', $pairs);
    }

    private static function isSensitive(string $name): bool
    {
        return in_array(strtolower($name), self::SENSITIVE_NAMES, true);
    }

    private static function currentRouteParameters(): array
    {
        try {
            return request()->route()?->parameters() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
