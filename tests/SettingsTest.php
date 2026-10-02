<?php

namespace GlueAgency\LiteSpeed\Tests;

use GlueAgency\LiteSpeed\models\Settings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testItNormalisesWhatTheCpPosts(): void
    {
        $normalized = Settings::normalize([
            'enabled'        => '1',
            'tagPrefix'      => '  ',
            'ttl'            => '3600',
            'statusTtls'     => [['status' => '404', 'ttl' => '600']],
            'excludeUris'    => '',
            'varyCookies'    => [['name' => 'customer_group'], ['name' => '']],
            'loggedInCookie' => ' _lscache_vary ',
        ]);

        $this->assertSame([
            'enabled'        => true,
            'tagPrefix'      => null,
            'ttl'            => 3600,
            'statusTtls'     => [404 => 600],
            'excludeUris'    => [],
            'varyCookies'    => ['customer_group'],
            'loggedInCookie' => '_lscache_vary',
        ], $normalized);
    }

    public function testABlankLifetimeKeepsTheCurrentOne(): void
    {
        $this->assertSame([], Settings::normalize(['ttl' => '']));
    }

    public function testConfigFileValuesPassThrough(): void
    {
        $values = [
            'enabled'      => '$LITESPEED_ENABLED',
            'tagPrefix'    => 'aquaduin',
            'ttl'          => 86400,
            'statusTtls'   => [404 => 3600],
            'excludeUris'  => ['^nl/zoeken'],
            'varyCookies'  => ['customer_group'],
            'varyLoggedIn' => false,
            'loopbackUrls' => ['https://www.example.com/nl/'],
        ];

        $this->assertSame($values, Settings::normalize($values));
    }

    public function testADisabledLightswitchPostsFalse(): void
    {
        $this->assertSame(['enabled' => false], Settings::normalize(['enabled' => '0']));
    }

    public static function cookieNames(): array
    {
        return [
            'lscache default' => ['_lscache_vary', true],
            'site cookie'     => ['customer_group', true],
            'dashes and dots' => ['my-site.vary', true],
            'space'           => ['customer group', false],
            'equals'          => ['a=b', false],
            'semicolon'       => ['a;b', false],
            'comma'           => ['a,b', false],
            'empty'           => ['', false],
        ];
    }

    #[DataProvider('cookieNames')]
    public function testItValidatesCookieNames(string $name, bool $valid): void
    {
        $this->assertSame($valid, Settings::isValidCookieName($name));
    }

    public function testExcludePatternsAreCaseInsensitiveAndAllowTheDelimiter(): void
    {
        $this->assertSame(1, preg_match(Settings::excludePattern('^NL/zoeken'), 'nl/zoeken/resultaten'));
        $this->assertSame(1, preg_match(Settings::excludePattern('a~b'), 'a~b'));
    }

    public function testItRejectsAPatternThatDoesNotCompile(): void
    {
        $this->assertTrue(Settings::isValidExcludePattern('^nl/(zoeken|search)'));
        $this->assertFalse(Settings::isValidExcludePattern('^nl/(zoeken'));
    }

    public function testTheTagPrefixAvoidsLiteSpeedsReservedWords(): void
    {
        $this->assertTrue(Settings::isValidTagPrefix('aquaduin2'));
        $this->assertFalse(Settings::isValidTagPrefix('Public'));
        $this->assertFalse(Settings::isValidTagPrefix('private'));
        $this->assertFalse(Settings::isValidTagPrefix('aqua_duin'));
    }
}
