<?php

namespace GlueAgency\LiteSpeed\utilities;

use Craft;
use craft\base\Utility;
use GlueAgency\LiteSpeed\enums\Permission;
use GlueAgency\LiteSpeed\LiteSpeed;

/**
 * Utilities → LiteSpeed: how many pages the plugin handed to LiteSpeed, and a live check per URL.
 */
class CacheUtility extends Utility
{
    protected const SEARCH_LIMIT = 50;

    public static function displayName(): string
    {
        return Craft::t('litespeed', 'LiteSpeed');
    }

    public static function id(): string
    {
        return 'litespeed';
    }

    public static function icon(): ?string
    {
        return '@litespeed/icon-mask.svg';
    }

    public static function contentHtml(): string
    {
        $tracker = LiteSpeed::getInstance()->tracker;
        $query = trim((string) Craft::$app->getRequest()->getQueryParam('q', ''));

        return Craft::$app->getView()->renderTemplate('litespeed/_utility', [
            'sites'    => Craft::$app->getSites()->getAllSites(),
            'stats'    => $tracker->stats(),
            'query'    => $query,
            'pages'    => $query === '' ? [] : $tracker->search($query, self::SEARCH_LIMIT),
            'limit'    => self::SEARCH_LIMIT,
            'canPurge' => Craft::$app->getUser()->checkPermission(Permission::ACCESS_PLUGIN->value),
        ]);
    }
}
