<?php

namespace GlueAgency\LiteSpeed\helpers;

/**
 * Describes a page the way the plugin records it after handing it to LiteSpeed.
 */
class Pages
{
    /**
     * The cookie values a page was cached for, as `name=value` pairs sorted by name. Cookies the request didn't
     * carry are left out.
     *
     * @param string[] $names
     * @param array<string, string> $cookies
     */
    public static function variant(array $names, array $cookies): string
    {
        $pairs = [];

        foreach (array_unique($names) as $name) {
            if (! isset($cookies[$name])) {
                continue;
            }

            $pairs[$name] = $name . '=' . $cookies[$name];
        }

        ksort($pairs, SORT_STRING);

        return implode(';', $pairs);
    }

    /**
     * The `Cookie` request header that asks LiteSpeed for a variant.
     */
    public static function cookieHeader(string $variant): string
    {
        return implode('; ', array_filter(explode(';', $variant)));
    }

    public static function key(string $url, string $variant): string
    {
        return md5($url . "\n" . $variant);
    }

    /**
     * Returns the max-age of an `X-LiteSpeed-Cache-Control` value that lets LiteSpeed store the page, or null.
     */
    public static function maxAge(string $cacheControl): ?int
    {
        $directives = array_map(fn(string $directive) => strtolower(trim($directive)), explode(',', $cacheControl));

        if (! in_array('public', $directives, true) || in_array('no-cache', $directives, true)) {
            return null;
        }

        foreach ($directives as $directive) {
            if (! preg_match('/^max-age=(\d+)$/', $directive, $match)) {
                continue;
            }

            if ((int) $match[1] <= 0) {
                return null;
            }

            return (int) $match[1];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public static function tags(string $header): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $header)))));
    }
}
