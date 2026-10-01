<?php

namespace GlueAgency\LiteSpeed\controllers;

use Craft;
use craft\web\Controller;
use GlueAgency\LiteSpeed\LiteSpeed;
use GlueAgency\LiteSpeed\models\Settings;
use yii\web\Response;

/**
 * The plugin settings, as a screen of the LiteSpeed section.
 */
class SettingsController extends Controller
{
    public function beforeAction($action): bool
    {
        if (! parent::beforeAction($action)) {
            return false;
        }

        $this->requireAdmin();

        return true;
    }

    public function actionEdit(?Settings $settings = null): Response
    {
        $plugin = LiteSpeed::getInstance();

        return $this->asCpScreen()
            ->title(Craft::t('litespeed', 'Settings'))
            ->addCrumb(Craft::t('litespeed', 'LiteSpeed'), 'litespeed')
            ->selectedSubnavItem('settings')
            ->action('litespeed/settings/save')
            ->redirectUrl('litespeed/settings')
            ->tabs([
                'general' => ['label' => Craft::t('litespeed', 'General'), 'url' => '#general'],
                'caching' => ['label' => Craft::t('litespeed', 'Caching'), 'url' => '#caching'],
                'vary'    => ['label' => Craft::t('litespeed', 'Vary'), 'url' => '#vary'],
                'purging' => ['label' => Craft::t('litespeed', 'Purging'), 'url' => '#purging'],
            ])
            ->contentTemplate('litespeed/settings', [
                'settings'  => $settings ?? $plugin->getSettings(),
                'overrides' => array_keys(Craft::$app->getConfig()->getConfigFromFile($plugin->handle)),
            ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $plugin = LiteSpeed::getInstance();

        if (! Craft::$app->getPlugins()->savePluginSettings($plugin, $this->request->getBodyParam('settings', []))) {
            return $this->asModelFailure($plugin->getSettings(), Craft::t('litespeed', 'Couldn’t save the settings.'), 'settings');
        }

        return $this->asSuccess(Craft::t('litespeed', 'Settings saved.'));
    }
}
