<?php

namespace App\Services\Sentry;

use App\Domains\Orders\Models\Order;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry's before_send hook: removes codes, OTPs, passwords, tokens and the Authorization and Cookie headers from the
 * request (body, query string, URL, headers, cookies), the extra data, the contexts and the breadcrumbs, at any depth,
 * and masks the tracking token in /track/{token} URLs. Everything else is kept.
 */
class ScrubSensitiveEventData
{
    /**
     * Lower-case field and header names that never leave the server.
     */
    private const array SENSITIVE_KEYS = [
        'code',
        'otp',
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'authorization',
        'cookie',
    ];

    /**
     * Breadcrumb metadata that holds a URL.
     */
    private const array URL_KEYS = ['url', 'http.url'];

    public static function beforeSend(Event $event, ?EventHint $hint = null): Event
    {
        $event->setRequest(self::scrubRequest($event->getRequest()));
        $event->setExtra(self::scrubArray($event->getExtra()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, self::scrubArray($context));
        }

        $event->setBreadcrumb(array_map(self::scrubBreadcrumb(...), $event->getBreadcrumbs()));

        return $event;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private static function scrubRequest(array $request): array
    {
        // Parsed from the Cookie header, so they go with it.
        unset($request['cookies']);

        if (isset($request['query_string']) && is_string($request['query_string'])) {
            $request['query_string'] = self::scrubQueryString($request['query_string']);
        }

        if (isset($request['url']) && is_string($request['url'])) {
            $request['url'] = self::scrubUrl($request['url']);
        }

        if (isset($request['data']) && is_string($request['data'])) {
            $decoded = json_decode($request['data'], true);
            $request['data'] = is_array($decoded) ? self::scrubArray($decoded) : $request['data'];
        }

        return self::scrubArray($request);
    }

    private static function scrubBreadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        foreach ($breadcrumb->getMetadata() as $name => $value) {
            if (self::isSensitive($name)) {
                $breadcrumb = $breadcrumb->withoutMetadata($name);
            } elseif (is_array($value)) {
                $breadcrumb = $breadcrumb->withMetadata($name, self::scrubArray($value));
            } elseif ($name === 'http.query' && is_string($value)) {
                $breadcrumb = $breadcrumb->withMetadata($name, self::scrubQueryString($value));
            } elseif (in_array($name, self::URL_KEYS, true) && is_string($value)) {
                $breadcrumb = $breadcrumb->withMetadata($name, self::scrubUrl($value));
            }
        }

        return $breadcrumb;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function scrubArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = self::scrubArray($value);
            }
        }

        return $data;
    }

    /**
     * Masks the tracking token in the path and drops the sensitive query pairs.
     */
    private static function scrubUrl(string $url): string
    {
        $url = preg_replace('#(/track/)[A-Za-z0-9]{'.Order::TRACKING_TOKEN_LENGTH.'}(?![A-Za-z0-9])#', '$1[masked]', $url) ?? $url;

        if (! str_contains($url, '?')) {
            return $url;
        }

        [$path, $query] = explode('?', $url, 2);

        return rtrim($path.'?'.self::scrubQueryString($query), '?');
    }

    /**
     * Drops the sensitive pairs and keeps the others exactly as sent; "user[password]" counts as "password".
     */
    private static function scrubQueryString(string $query): string
    {
        $pairs = array_filter(explode('&', $query), function (string $pair): bool {
            $nameParts = preg_split('/[\[\]]+/', urldecode(explode('=', $pair, 2)[0]), flags: PREG_SPLIT_NO_EMPTY) ?: [];

            return array_filter($nameParts, self::isSensitive(...)) === [];
        });

        return implode('&', $pairs);
    }

    private static function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), self::SENSITIVE_KEYS, true);
    }
}
