<?php

namespace GlueAgency\LiteSpeed\enums;

use Psr\Http\Message\ResponseInterface;

/**
 * What LiteSpeed answered to a HEAD request for a page. A HEAD request never stores the page itself.
 */
enum CacheStatus: string
{
    case CACHED = 'cached';
    case CACHEABLE = 'cacheable';
    case NOT_CACHEABLE = 'not-cacheable';
    case REDIRECT = 'redirect';
    case ERROR = 'error';

    public static function fromResponse(ResponseInterface $response): self
    {
        if (str_starts_with(strtolower($response->getHeaderLine(Header::CACHE->value)), 'hit')) {
            return self::CACHED;
        }

        $status = $response->getStatusCode();

        if ($status >= 300 && $status < 400) {
            return self::REDIRECT;
        }

        $cacheControl = strtolower($response->getHeaderLine(Header::CACHE_CONTROL->value));

        if (str_starts_with($cacheControl, 'public')) {
            return self::CACHEABLE;
        }

        if (str_starts_with($cacheControl, 'no-cache')) {
            return self::NOT_CACHEABLE;
        }

        return self::ERROR;
    }

    /**
     * The colour of the CP status indicator.
     */
    public function color(): string
    {
        return match ($this) {
            self::CACHED        => 'green',
            self::CACHEABLE     => 'orange',
            self::NOT_CACHEABLE => 'gray',
            self::REDIRECT      => 'blue',
            self::ERROR         => 'red',
        };
    }
}
