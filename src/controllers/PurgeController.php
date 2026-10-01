<?php

namespace GlueAgency\LiteSpeed\controllers;

use Craft;
use craft\web\Controller;
use GlueAgency\LiteSpeed\enums\Permission;
use GlueAgency\LiteSpeed\helpers\EditableTable;
use GlueAgency\LiteSpeed\LiteSpeed;
use yii\web\Response;

/**
 * The CP purge screens.
 */
class PurgeController extends Controller
{
    public function beforeAction($action): bool
    {
        if (! parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requirePermission(Permission::ACCESS_PLUGIN->value);

        if (! LiteSpeed::getInstance()->getSettings()->isEnabled()) {
            $this->setFailFlash(Craft::t('litespeed', 'The LiteSpeed plugin is disabled, nothing was purged.'));
            $this->redirectToPostedUrl();

            return false;
        }

        return true;
    }

    public function actionUrl(): ?Response
    {
        $this->requirePostRequest();

        $urls = EditableTable::column($this->request->getBodyParam('urls'), 'url');
        $subpages = (bool) $this->request->getBodyParam('subpages');

        if (empty($urls)) {
            return $this->asFailure(Craft::t('litespeed', 'Enter a URL to purge.'));
        }

        LiteSpeed::getInstance()->purge->purgeUrls($urls, $subpages);

        $message = $subpages
            ? Craft::t('litespeed', '{url} and its sub-pages purged.', ['url' => implode(', ', $urls)])
            : Craft::t('litespeed', '{url} purged.', ['url' => implode(', ', $urls)]);

        return $this->asSuccess($message);
    }

    public function actionTags(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission(Permission::PURGE_TAGS->value);

        $tags = EditableTable::column($this->request->getBodyParam('tags'), 'tag');

        if (empty($tags)) {
            return $this->asFailure(Craft::t('litespeed', 'Enter at least one tag to purge.'));
        }

        LiteSpeed::getInstance()->purge->purgeTags($tags);

        return $this->asSuccess(Craft::t('litespeed', '{count, plural, =1{# tag} other{# tags}} purged.', ['count' => count($tags)]));
    }
}
