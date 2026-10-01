<?php

namespace GlueAgency\LiteSpeed\jobs;

use Craft;
use craft\queue\BaseJob;
use GlueAgency\LiteSpeed\LiteSpeed;

/**
 * Sends the parts of a purge that didn't fit on the response that caused it.
 */
class PurgeJob extends BaseJob
{
    /**
     * LiteSpeed tags to purge, already prefixed.
     *
     * @var string[]
     */
    public array $tags = [];

    public function execute($queue): void
    {
        $purge = LiteSpeed::getInstance()->purge;
        $purge->queue($this->tags);
        $purge->relay();
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('litespeed', 'Purging the LiteSpeed cache');
    }
}
