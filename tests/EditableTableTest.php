<?php

namespace GlueAgency\LiteSpeed\Tests;

use GlueAgency\LiteSpeed\helpers\EditableTable;
use PHPUnit\Framework\TestCase;

class EditableTableTest extends TestCase
{
    public function testItReadsOneColumnFromPostedRows(): void
    {
        $rows = [
            0      => ['url' => ' https://example.com/a '],
            'new1' => ['url' => 'https://example.com/b'],
        ];

        $this->assertSame(['https://example.com/a', 'https://example.com/b'], EditableTable::column($rows, 'url'));
    }

    public function testItDropsBlankAndDuplicateRows(): void
    {
        $rows = [['url' => ''], ['url' => '  '], ['url' => '/a'], ['url' => '/a'], ['other' => 'x']];

        $this->assertSame(['/a'], EditableTable::column($rows, 'url'));
    }

    public function testAPlainListFromAConfigFileIsKept(): void
    {
        $this->assertSame(['customer_group', 'theme'], EditableTable::column(['customer_group', 'theme'], 'name'));
    }

    /**
     * An editable table without rows posts an empty string rather than an array.
     */
    public function testAnEmptyTableIsAnEmptyList(): void
    {
        $this->assertSame([], EditableTable::column('', 'url'));
        $this->assertSame([], EditableTable::integerMap('', 'status', 'ttl'));
    }

    public function testItBuildsAnIntegerMapFromTwoColumns(): void
    {
        $rows = [
            ['status' => '404', 'ttl' => '3600'],
            ['status' => '410', 'ttl' => '86400'],
            ['status' => '', 'ttl' => '60'],
            ['status' => '500', 'ttl' => ''],
        ];

        $this->assertSame([404 => 3600, 410 => 86400], EditableTable::integerMap($rows, 'status', 'ttl'));
    }

    public function testAnIntegerMapFromAConfigFileIsKept(): void
    {
        $this->assertSame([404 => 3600, 500 => 600], EditableTable::integerMap([404 => 3600, 500 => 600], 'status', 'ttl'));
    }
}
