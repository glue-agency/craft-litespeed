<?php

namespace GlueAgency\LiteSpeed\web\twig;

use GlueAgency\LiteSpeed\LiteSpeed;

/**
 * `craft.litespeed` in templates.
 */
class LiteSpeedVariable
{
    public function noCache(): void
    {
        LiteSpeed::getInstance()->cache->noCache();
    }

    public function ttl(int $seconds): void
    {
        LiteSpeed::getInstance()->cache->setTtl($seconds);
    }

    /**
     * @param string|string[] $tags
     */
    public function tag(string|array $tags): void
    {
        LiteSpeed::getInstance()->cache->addTags($tags);
    }

    public function varyCookie(string $name): void
    {
        LiteSpeed::getInstance()->cache->addVaryCookie($name);
    }
}
