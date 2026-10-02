<?php

namespace GlueAgency\LiteSpeed\Tests;

use GlueAgency\LiteSpeed\helpers\Pages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PagesTest extends TestCase
{
    public function testAVariantListsTheVaryCookiesSortedByName(): void
    {
        $cookies = [
            'theme'          => 'dark',
            'customer_group' => 'b2b',
            'CraftSessionId' => 'abc',
        ];

        $this->assertSame('customer_group=b2b;theme=dark', Pages::variant(['theme', 'customer_group'], $cookies));
    }

    public function testAVaryCookieTheRequestLacksIsLeftOut(): void
    {
        $this->assertSame('theme=dark', Pages::variant(['customer_group', 'theme', 'theme'], ['theme' => 'dark']));
    }

    public function testAPageWithoutVaryCookiesHasNoVariant(): void
    {
        $this->assertSame('', Pages::variant([], ['theme' => 'dark']));
        $this->assertSame('', Pages::variant(['theme'], []));
    }

    public function testAnEmptyCookieValueIsStillAVariant(): void
    {
        $this->assertSame('theme=', Pages::variant(['theme'], ['theme' => '']));
    }

    public function testTheCookieHeaderSendsTheVariantBack(): void
    {
        $this->assertSame('customer_group=b2b; theme=dark', Pages::cookieHeader('customer_group=b2b;theme=dark'));
        $this->assertSame('', Pages::cookieHeader(''));
    }

    public function testEachVariantOfAUrlHasItsOwnKey(): void
    {
        $url = 'https://example.com/nl?page=2';

        $this->assertSame(Pages::key($url, ''), Pages::key($url, ''));
        $this->assertNotSame(Pages::key($url, ''), Pages::key($url, 'theme=dark'));
        $this->assertNotSame(Pages::key($url . 'theme=dark', ''), Pages::key($url, 'theme=dark'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Pages::key($url, ''));
    }

    public static function cacheControls(): array
    {
        return [
            'plugin header'      => ['public,max-age=86400', 86400],
            'shortened by Craft' => ['public,max-age=86362', 86362],
            'spaces and case'    => [' Public, Max-Age=600 ', 600],
            'max-age first'      => ['max-age=60,public', 60],
            'zero'               => ['public,max-age=0', null],
            'no max-age'         => ['public', null],
            'no-cache'           => ['no-cache', null],
            'private'            => ['private,max-age=600', null],
            'public no-cache'    => ['public,no-cache,max-age=600', null],
            'empty'              => ['', null],
        ];
    }

    #[DataProvider('cacheControls')]
    public function testItReadsTheMaxAgeLiteSpeedStoresAPageFor(string $cacheControl, ?int $expected): void
    {
        $this->assertSame($expected, Pages::maxAge($cacheControl));
    }

    public function testItSplitsTheTagHeader(): void
    {
        $this->assertSame(['ab12cd', 'ab12cd_id.1', 'ab12cd_E'], Pages::tags('ab12cd, ab12cd_id.1,,ab12cd_E,ab12cd'));
        $this->assertSame([], Pages::tags(''));
    }
}
