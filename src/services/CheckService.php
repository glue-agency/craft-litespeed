<?php

namespace GlueAgency\LiteSpeed\services;

use Craft;
use craft\base\Component;
use GlueAgency\LiteSpeed\enums\CacheStatus;
use GlueAgency\LiteSpeed\helpers\Pages;
use GlueAgency\LiteSpeed\helpers\Urls;
use GlueAgency\LiteSpeed\LiteSpeed;
use Throwable;

/**
 * Asks LiteSpeed itself whether a page is in its cache, with a HEAD request that doesn't put it there.
 */
class CheckService extends Component
{
    /**
     * @return array{status: CacheStatus, message: string}
     */
    public function check(string $url, string $variant = ''): array
    {
        $absolute = Urls::absolute($url, Urls::defaultOrigin());

        if ($absolute === null) {
            return $this->result(CacheStatus::ERROR, Craft::t('litespeed', 'Enter a full URL, or a path that starts with a /.'));
        }

        if (! $this->isSiteHost((string) parse_url($absolute, PHP_URL_HOST))) {
            return $this->result(CacheStatus::ERROR, Craft::t('litespeed', 'Only pages of this website can be checked.'));
        }

        $client = Craft::createGuzzleClient(array_merge([
            'timeout'         => 10,
            'allow_redirects' => false,
            'http_errors'     => false,
        ], LiteSpeed::getInstance()->getSettings()->loopbackOptions));
        $options = [];

        if ($variant !== '') {
            $options['headers'] = ['Cookie' => Pages::cookieHeader($variant)];
        }

        try {
            $response = $client->head($absolute, $options);
        } catch (Throwable $e) {
            return $this->result(CacheStatus::ERROR, Craft::t('litespeed', 'Couldn’t reach the page: {error}', ['error' => $e->getMessage()]));
        }

        $status = CacheStatus::fromResponse($response);

        return $this->result($status, match ($status) {
            CacheStatus::CACHED        => Craft::t('litespeed', 'In the cache: LiteSpeed serves this page without asking the website.'),
            CacheStatus::CACHEABLE     => Craft::t('litespeed', 'Not in the cache right now. LiteSpeed stores it on the next visit.'),
            CacheStatus::NOT_CACHEABLE => Craft::t('litespeed', 'Never cached: the website builds this page on every visit.'),
            CacheStatus::REDIRECT      => Craft::t('litespeed', 'Redirects to {url}', ['url' => $response->getHeaderLine('Location')]),
            CacheStatus::ERROR         => Craft::t('litespeed', 'Unexpected answer from the page (status {status}).', ['status' => $response->getStatusCode()]),
        });
    }

    protected function isSiteHost(string $host): bool
    {
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            if (strcasecmp((string) parse_url($site->getBaseUrl() ?? '', PHP_URL_HOST), $host) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{status: CacheStatus, message: string}
     */
    protected function result(CacheStatus $status, string $message): array
    {
        return [
            'status'  => $status,
            'message' => $message,
        ];
    }
}
