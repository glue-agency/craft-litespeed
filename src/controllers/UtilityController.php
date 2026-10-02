<?php

namespace GlueAgency\LiteSpeed\controllers;

use Craft;
use craft\web\Controller;
use GlueAgency\LiteSpeed\LiteSpeed;
use GlueAgency\LiteSpeed\utilities\CacheUtility;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * The LiteSpeed utility’s live check.
 */
class UtilityController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function beforeAction($action): bool
    {
        if (! parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();

        if (! Craft::$app->getUtilities()->checkAuthorization(CacheUtility::class)) {
            throw new ForbiddenHttpException('User is not authorized to use the LiteSpeed utility.');
        }

        return true;
    }

    public function actionCheck(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $result = LiteSpeed::getInstance()->check->check(
            (string) $this->request->getRequiredBodyParam('url'),
            (string) $this->request->getBodyParam('variant', ''),
        );

        return $this->asJson([
            'status'  => $result['status']->value,
            'color'   => $result['status']->color(),
            'message' => $result['message'],
        ]);
    }
}
