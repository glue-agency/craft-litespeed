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
        $plugin = LiteSpeed::getInstance();
        $query = trim((string) Craft::$app->getRequest()->getQueryParam('q', ''));

        return Craft::$app->getView()->renderTemplate('litespeed/_utility', [
            'sites'    => Craft::$app->getSites()->getAllSites(),
            'stats'    => $plugin->tracker->stats(),
            'query'    => $query,
            'check'    => static::isPageReference($query) ? static::check($query) : null,
            'pages'    => $query === '' ? [] : $plugin->tracker->search($query, self::SEARCH_LIMIT),
            'limit'    => self::SEARCH_LIMIT,
            'canPurge' => Craft::$app->getUser()->checkPermission(Permission::ACCESS_PLUGIN->value),
        ]);
    }

    /**
     * A full URL or a path is checked live; anything else only searches the record.
     */
    protected static function isPageReference(string $query): bool
    {
        return str_starts_with($query, '/') || preg_match('~^https?://~i', $query) === 1;
    }

    /**
     * @return array{color: string, message: string}
     */
    protected static function check(string $url): array
    {
        $result = LiteSpeed::getInstance()->check->check($url);

        return [
            'color'   => $result['status']->color(),
            'message' => $result['message'],
        ];
    }
}
