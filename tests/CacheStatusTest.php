<?php

namespace GlueAgency\LiteSpeed\Tests;

use GlueAgency\LiteSpeed\enums\CacheStatus;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The answers below are what LiteSpeed sent to HEAD requests on staging.
 */
class CacheStatusTest extends TestCase
{
    public static function responses(): array
    {
        $cached = ['x-litespeed-cache' => 'hit', 'x-litespeed-cache-control' => 'public,max-age=86400'];
        $missed = ['x-litespeed-cache' => 'miss', 'x-litespeed-cache-control' => 'public,max-age=86400'];

        return [
            'cached page'                 => [200, $cached, CacheStatus::CACHED],
            'cached page, other casing'   => [200, ['X-LiteSpeed-Cache' => 'HIT'], CacheStatus::CACHED],
            'cached 404'                  => [404, ['x-litespeed-cache' => 'hit'], CacheStatus::CACHED],
            'not cached yet'              => [200, ['x-litespeed-cache-control' => 'public,max-age=86362'], CacheStatus::CACHEABLE],
            'not cached yet after a miss' => [200, $missed, CacheStatus::CACHEABLE],
            'cacheable 404'               => [404, ['x-litespeed-cache-control' => 'public,max-age=3600'], CacheStatus::CACHEABLE],
            'never cached'                => [200, ['x-litespeed-cache-control' => 'no-cache'], CacheStatus::NOT_CACHEABLE],
            'server error'                => [500, ['x-litespeed-cache-control' => 'no-cache'], CacheStatus::NOT_CACHEABLE],
            'redirect'                    => [301, ['location' => 'https://example.com/nl', 'x-litespeed-cache-control' => 'no-cache'], CacheStatus::REDIRECT],
            'found'                       => [302, ['location' => '/nl'], CacheStatus::REDIRECT],
            'no headers'                  => [200, [], CacheStatus::ERROR],
            'basic auth'                  => [401, [], CacheStatus::ERROR],
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('responses')]
    public function testItReadsLiteSpeedsAnswer(int $status, array $headers, CacheStatus $expected): void
    {
        $this->assertSame($expected, CacheStatus::fromResponse(new Response($status, $headers)));
    }
}
