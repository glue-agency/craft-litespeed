<?php

namespace GlueAgency\LiteSpeed\enums;

use Craft;

/**
 * Backed values are what Craft stores per user group: renaming one silently revokes it.
 */
enum Permission: string
{
    case ACCESS_PLUGIN = 'accessPlugin-litespeed';
    case PURGE_TAGS = 'litespeed:purgeTags';

    public function label(): string
    {
        return match ($this) {
            self::ACCESS_PLUGIN => Craft::t('litespeed', 'Access LiteSpeed'),
            self::PURGE_TAGS    => Craft::t('litespeed', 'Purge tags'),
        };
    }
}
