<?php

namespace GlueAgency\LiteSpeed\migrations;

use craft\db\Migration;
use craft\db\Table as CraftTable;
use GlueAgency\LiteSpeed\db\Table;

/**
 * Adds the record of the pages handed to LiteSpeed, for installs from before it existed.
 */
class m261002_100000_create_page_tables extends Migration
{
    public function safeUp(): bool
    {
        if (! $this->db->tableExists(Table::PAGES)) {
            $this->createTable(Table::PAGES, [
                'id'          => $this->primaryKey(),
                'siteId'      => $this->integer()->notNull(),
                'url'         => $this->text()->notNull(),
                'variant'     => $this->string()->notNull()->defaultValue(''),
                'key'         => $this->char(32)->notNull(),
                'expiryDate'  => $this->dateTime()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
            ]);

            $this->createIndex(null, Table::PAGES, ['key'], true);
            $this->createIndex(null, Table::PAGES, ['expiryDate']);
            $this->addForeignKey(null, Table::PAGES, ['siteId'], CraftTable::SITES, ['id'], 'CASCADE');
        }

        if (! $this->db->tableExists(Table::PAGETAGS)) {
            $this->createTable(Table::PAGETAGS, [
                'pageId' => $this->integer()->notNull(),
                'tag'    => $this->string()->notNull(),
                'PRIMARY KEY([[pageId]], [[tag]])',
            ]);

            $this->createIndex(null, Table::PAGETAGS, ['tag']);
            $this->addForeignKey(null, Table::PAGETAGS, ['pageId'], Table::PAGES, ['id'], 'CASCADE');
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::PAGETAGS);
        $this->dropTableIfExists(Table::PAGES);

        return true;
    }
}
