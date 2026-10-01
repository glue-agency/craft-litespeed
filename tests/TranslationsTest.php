<?php

namespace GlueAgency\LiteSpeed\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TranslationsTest extends TestCase
{
    protected const SRC = __DIR__ . '/../src';

    /**
     * @return array<string, array{string}>
     */
    public static function languages(): array
    {
        return [
            'Dutch'  => ['nl'],
            'French' => ['fr'],
            'German' => ['de'],
        ];
    }

    #[DataProvider('languages')]
    public function testEveryStringIsTranslated(string $language): void
    {
        $missing = array_diff(self::sourceStrings(), array_keys(self::translations($language)));

        $this->assertSame([], array_values($missing));
    }

    #[DataProvider('languages')]
    public function testNoTranslationIsLeftOver(string $language): void
    {
        $stale = array_diff(array_keys(self::translations($language)), self::sourceStrings());

        $this->assertSame([], array_values($stale));
    }

    #[DataProvider('languages')]
    public function testTranslationsKeepTheirPlaceholders(string $language): void
    {
        foreach (self::translations($language) as $source => $translation) {
            preg_match_all('/\{(\w+)/', $source, $expected);
            preg_match_all('/\{(\w+)/', $translation, $actual);

            $this->assertSame(array_unique($expected[1]), array_unique($actual[1]), $source);
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function translations(string $language): array
    {
        return require self::SRC . "/translations/{$language}/litespeed.php";
    }

    /**
     * @return string[]
     */
    protected static function sourceStrings(): array
    {
        $patterns = [
            'php'  => "/Craft::t\\('litespeed',\\s*'((?:[^'\\\\]|\\\\.)*)'/",
            'twig' => "/'((?:[^'\\\\]|\\\\.)*)'\\s*\\|\\s*t\\('litespeed'/",
        ];

        $strings = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::SRC));

        foreach ($files as $file) {
            $extension = $file->getExtension();

            if (! isset($patterns[$extension]) || str_contains($file->getPathname(), '/translations/')) {
                continue;
            }

            preg_match_all($patterns[$extension], file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $string) {
                $strings[] = stripcslashes($string);
            }
        }

        return array_values(array_unique($strings));
    }
}
