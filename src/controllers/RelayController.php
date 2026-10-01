<?php

namespace GlueAgency\LiteSpeed\controllers;

use Craft;
use craft\web\Controller;
use GlueAgency\LiteSpeed\LiteSpeed;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Receives purges from console commands and queue jobs, so LiteSpeed sees them on a response of its own.
 */
class RelayController extends Controller
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    public function actionIndex(): Response
    {
        $this->requirePostRequest();

        $purge = LiteSpeed::getInstance()->purge;
        $payload = $purge->readRelayPayload((string) $this->request->getRequiredBodyParam('payload'));

        if ($payload === null) {
            throw new BadRequestHttpException('Invalid purge payload.');
        }

        $purge->queue($payload['tags'], $payload['everything']);

        $response = Craft::$app->getResponse();
        $response->setStatusCode(204);

        return $response;
    }
}
