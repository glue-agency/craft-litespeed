<?php

namespace GlueAgency\LiteSpeed\helpers;

use Craft;

/**
 * Turns what editors type into absolute URLs.
 */
class Urls
{
    /**
     * Returns the absolute URL, or null when there's no host to be found. A path resolves against `$origin`, and a
     * URL without a scheme gets `https://`.
     */
    public static function absolute(string $url, string $origin): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        } elseif (str_starts_with($url, '/')) {
            $url = rtrim($origin, '/') . $url;
        } elseif (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        if (empty(parse_url($url, PHP_URL_HOST))) {
            return null;
        }

        return $url;
    }

    /**
     * The scheme and host a path is resolved against: the current site's on a site request, the primary site's
     * otherwise.
     */
    public static function defaultOrigin(): string
    {
        $request = Craft::$app->getRequest();

        if (! $request->getIsConsoleRequest() && $request->getIsSiteRequest()) {
            return $request->getHostInfo();
        }

        $parts = parse_url(Craft::$app->getSites()->getPrimarySite()->getBaseUrl() ?? '');

        if (empty($parts['host'])) {
            return '';
        }

        return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
