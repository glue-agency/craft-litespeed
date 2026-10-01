<?php

namespace GlueAgency\LiteSpeed\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use GlueAgency\LiteSpeed\LiteSpeed;
use yii\console\ExitCode;

/**
 * Purges the LiteSpeed cache.
 */
class PurgeController extends Controller
{
    /**
     * @var bool Empty the entire LiteSpeed cache, including other apps on the same vhost.
     */
    public bool $everything = false;

    /**
     * @var bool Purge every page below the given URLs as well.
     */
    public bool $subpages = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'all') {
            $options[] = 'everything';
        }

        if ($actionID === 'urls') {
            $options[] = 'subpages';
        }

        return $options;
    }

    public function beforeAction($action): bool
    {
        if (! LiteSpeed::getInstance()->getSettings()->isEnabled()) {
            $this->stderr('The LiteSpeed plugin is disabled, nothing to purge.' . PHP_EOL, Console::FG_YELLOW);

            return false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Purges every page this site cached. Run it after a deploy: new templates don't invalidate anything.
     */
    public function actionAll(): int
    {
        $purge = LiteSpeed::getInstance()->purge;

        if ($this->everything) {
            $purge->purgeEverything();
        } else {
            $purge->purgeAll();
        }

        return $this->relay();
    }

    /**
     * Purges the given URLs.
     *
     * @param string ...$urls
     */
    public function actionUrls(string ...$urls): int
    {
        if (empty($urls)) {
            $this->stderr('Pass at least one URL.' . PHP_EOL, Console::FG_RED);

            return ExitCode::USAGE;
        }

        LiteSpeed::getInstance()->purge->purgeUrls($urls, $this->subpages);

        return $this->relay();
    }

    /**
     * Purges the given custom tags.
     *
     * @param string ...$tags
     */
    public function actionTags(string ...$tags): int
    {
        if (empty($tags)) {
            $this->stderr('Pass at least one tag.' . PHP_EOL, Console::FG_RED);

            return ExitCode::USAGE;
        }

        LiteSpeed::getInstance()->purge->purgeTags($tags);

        return $this->relay();
    }

    protected function relay(): int
    {
        if (LiteSpeed::getInstance()->purge->relay()) {
            $this->stdout('Purge sent to LiteSpeed.' . PHP_EOL, Console::FG_GREEN);

            return ExitCode::OK;
        }

        $this->stderr('Couldn’t reach the site, see the log. The purge goes out with the next web request instead.' . PHP_EOL, Console::FG_YELLOW);

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
