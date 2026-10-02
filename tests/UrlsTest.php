<?php

namespace GlueAgency\LiteSpeed\Tests;

use GlueAgency\LiteSpeed\helpers\Urls;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlsTest extends TestCase
{
    public static function urls(): array
    {
        return [
            'absolute'               => [' https://example.com/nl/faq?page=2 ', 'https://example.com/nl/faq?page=2'],
            'http'                   => ['http://example.com/nl', 'http://example.com/nl'],
            'path'                   => ['/nl/faq', 'https://aquaduin.test/nl/faq'],
            'protocol-relative'      => ['//example.com/nl', 'https://example.com/nl'],
            'no scheme'              => ['example.com/nl', 'https://example.com/nl'],
            'uppercase scheme'       => ['HTTPS://example.com', 'HTTPS://example.com'],
            'empty'                  => ['  ', null],
            'path without an origin' => ['/nl/faq', null, ''],
        ];
    }

    #[DataProvider('urls')]
    public function testItResolvesWhatEditorsType(string $url, ?string $expected, string $origin = 'https://aquaduin.test/'): void
    {
        $this->assertSame($expected, Urls::absolute($url, $origin));
    }
}
