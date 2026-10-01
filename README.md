# LiteSpeed for Craft CMS

Lets LiteSpeed Web Server's page cache (LSCache) cache Craft pages, and purges them the moment their
content changes. A Craft port of LiteSpeed's own Laravel, Drupal and WordPress cache plugins, using the
same response headers.

- Every cacheable page is sent with `X-LiteSpeed-Cache-Control` and tagged with Craft's own element cache
  tags through `X-LiteSpeed-Tag`.
- Saving, deleting or moving an element purges exactly the pages that showed it, through
  `X-LiteSpeed-Purge`. That also works from the queue and from console commands.
- Logged-in users and cookie-dependent pages get their own cache variant through `X-LiteSpeed-Vary`.
- A CP section purges URLs or tags by hand, and so do the console and the Clear Caches utility.
- Every setting can be managed in the CP, or per environment through `config/litespeed.php`.

## Requirements

Craft CMS 5.2 or later, PHP 8.2 or later, and a LiteSpeed Web Server (Enterprise or OpenLiteSpeed) with the
cache module enabled. The plugin does nothing harmful without LiteSpeed; the headers are simply ignored.

## Installation

```bash
composer require glue-agency/craft-litespeed
php craft plugin/install litespeed
```

Turn on cache lookups in `web/.htaccess`:

```apache
<IfModule LiteSpeed>
    CacheLookup on
    RewriteEngine On
    RewriteRule .* - [E=Cache-Control:no-autoflush]
    CacheKeyModify -qs:utm*
    CacheKeyModify -qs:gclid
    CacheKeyModify -qs:fbclid
</IfModule>
```

Then purge after every deploy, since new templates don't invalidate anything. Do it once the new release is
live: Deployer's Craft recipe runs `clear-caches/all` (which includes the LiteSpeed cache) *before*
`deploy:publish`, so the old release can re-cache its pages in between. With Deployer:

```php
desc('Purges the LiteSpeed cache');
task('craft:litespeed_purge', craft('litespeed/purge/all', ['showOutput']));
after('deploy:publish', 'craft:litespeed_purge');
```

## What gets cached

A response is cached when all of these hold, and is sent `X-LiteSpeed-Cache-Control: no-cache` otherwise:

- It's a GET or HEAD site request that a controller action rendered: no action request, CP request, preview or
  token.
- The visitor is a guest without an active session, and the response sets no cookies.
- The status is 200, or listed in `statusTtls`.
- The path matches none of the `excludeUris`.
- Its `Cache-Control` doesn't contain `no-store`. Craft's `setNoCacheHeaders()` sends that, and Craft calls it
  itself for every page that outputs a CSRF token, and for pages that link to an image transform that hasn't
  been generated yet.

The `Cache-Control` header itself is left alone. It's meant for browsers and for caches downstream of
LiteSpeed, while LiteSpeed follows the plugin's `X-LiteSpeed-Cache-Control`, so `private` or `no-cache` in it
don't keep a page out of LiteSpeed. To keep one out, call `setNoCacheHeaders()` (which sends `no-store`) or
`{% do craft.litespeed.noCache() %}`.

A page whose HTML depends on a cookie needs that cookie in `varyCookies`, plus the `.htaccess` rule (see
[Vary](#vary)), *before* the plugin is enabled. Otherwise the first visitor's variant is served to everyone.

Craft's `{% cache %}` tags keep working: their element tags are passed on to LiteSpeed even when the block comes
from Craft's cache.

### Forms and CSRF

A `{{ csrfInput() }}` makes a page uncacheable. Enable Craft's asynchronous CSRF inputs so forms stay
cacheable:

```php
// config/general.php
'asyncCsrfInputs' => true,
```

or per form: `{{ csrfInput({ async: true }) }}`.

## Purging

| Trigger | What's purged |
|---|---|
| Element saved, deleted, restored, moved or propagated | The pages that showed it, and the element's own URL |
| Section, entry type, volume, field, site or global set changed | Every page that queried that kind of element; for fields, sites and globals, the whole site |
| Drafts and revisions | Nothing |
| Craft's garbage collection | Nothing, unless `purgeOnGc` is on |
| `php craft litespeed/purge/all` or *Utilities → Clear Caches → LiteSpeed cache* | The whole site |
| `php craft litespeed/purge/all --everything` | The entire LiteSpeed cache, including other apps on the same vhost |
| `php craft litespeed/purge/urls <url>... [--subpages]` | Those URLs, and with `--subpages` everything below them |
| `php craft litespeed/purge/tags <tag>...` | The pages tagged with `craft.litespeed.tag()` |

LiteSpeed only accepts a purge on a response it serves. In a web request the purge rides along on the
response itself. Console commands and queue jobs relay it with a request to each site's base URL; when that
request fails, the purge is kept and the next web request sends it.

The relay request goes through the public hostname, so give it whatever it needs to get through. For a staging
site behind basic auth:

```php
// config/litespeed.php
return [
    '*' => [],
    'staging' => [
        'loopbackOptions' => ['auth' => ['dev', 'dev']],
    ],
];
```

The relay is authenticated with Craft's `securityKey`, so the web and console processes need the same one.

## The control panel

The **LiteSpeed** section purges one or more URLs, optionally with all their sub-pages. To purge a whole
site, enter its base URL and tick "Purge all sub-pages". The **Purge tags** permission adds the Tags tab.

## Vary

Logged-in users get a `_lscache_vary` cookie when they log in, which is removed again when they log out.
LiteSpeed varies on that cookie name without being told to, so a logged-in user never gets a guest's cached
page. Set `varyLoggedIn` to `false` to turn this off.

The cookie's name is the `loggedInCookie` setting. LiteSpeed only varies on it unprompted while the name starts
with `_lscache_vary`; any other name needs a matching rule in `.htaccess`:

```apache
<IfModule LiteSpeed>
    RewriteRule .* - [E="Cache-Vary:my_vary_cookie"]
</IfModule>
```

When a page renders differently depending on a cookie of your own, cache a variant per cookie value. Either for
the whole site:

```php
// config/litespeed.php
return [
    'varyCookies' => ['customer_group'],
];
```

or only on the pages that read it:

```twig
{% do craft.litespeed.varyCookie('customer_group') %}
```

That sends `X-LiteSpeed-Vary: cookie=customer_group`. Add the same cookie to the cache lookup in
`.htaccess`, so LiteSpeed takes it into account before it serves a cached page:

```apache
<IfModule LiteSpeed>
    RewriteRule .* - [E=Cache-Vary:customer_group]
</IfModule>
```

## Templates

```twig
{# Keep this page out of the cache #}
{% do craft.litespeed.noCache() %}

{# Cache this page for an hour instead of the configured TTL #}
{% do craft.litespeed.ttl(3600) %}

{# Tag this page, to purge it by hand later on #}
{% do craft.litespeed.tag(['news', 'footer']) %}

{# Cache a variant per value of this cookie #}
{% do craft.litespeed.varyCookie('customer_group') %}
```

A template that sets `X-LiteSpeed-Cache-Control` itself, with `{% header %}`, keeps it: the plugin only adds
its tags.

## Settings

Everything below can be set under *LiteSpeed → Settings*, which admins see in the section's nav (*Settings →
Plugins → LiteSpeed* leads there too). The CP saves to project config, so it's only editable where `allowAdminChanges` is on. To set a value per
environment, enter an environment variable such as `$LITESPEED_ENABLED` or `$LITESPEED_TAG_PREFIX` (`enabled`,
`tagPrefix` and the relay URLs accept one), or set it in `config/litespeed.php`, which overrides the CP: an
overridden field is shown read-only. The relay's Guzzle options (`loopbackOptions`) and `maxHeaderLength` are config-file only.

| Setting | Default | |
|---|---|---|
| `enabled` | `true` | Send any LiteSpeed headers at all |
| `tagPrefix` | hash of the system UID | Prefix for every tag, letters and digits only. It keeps purges from reaching other apps on the same vhost, so it must never change between deploys |
| `ttl` | `86400` | Public cache lifetime, in seconds |
| `statusTtls` | `[404 => 3600]` | Lifetimes for non-200 responses, by status code. Any other status isn't cached |
| `excludeUris` | `[]` | Regular expressions matched against the path (without leading slash) |
| `varyCookies` | `[]` | Cookies every page varies on |
| `varyLoggedIn` | `true` | Give logged-in users their own cache variant |
| `loggedInCookie` | `_lscache_vary` | Name of the logged-in users' vary cookie |
| `purgeStale` | `false` | Serve the stale copy while a purged page regenerates |
| `purgeOnGc` | `false` | Purge everything after garbage collection |
| `loopbackUrls` | each host's first site URL | Where console and queue purges are relayed to. The URL must answer the POST itself, without a redirect |
| `loopbackOptions` | `[]` | Guzzle options for that relay request, merged over a 10 second timeout and no redirects. Config file only |
| `maxHeaderLength` | `8000` | Longest tag or purge header sent, in bytes. Config file only |

`maxHeaderLength` exists because servers cap header sizes and LiteSpeed doesn't document its limit. The only
data point is an unconfirmed OpenLiteSpeed report: a header line over 16 KB is dropped and the request hangs, more
than 64 KB of headers gives a 503. At about 15 bytes a tag, 8000 bytes is roughly 500 tags. A page with more tags
than fit swaps its per-element tags for per-type ones, so it's still purged, just by any change to that element
type; if even that doesn't fit, the page isn't cached. A purge that doesn't fit is split into parts that do: from
the console or the queue each part is its own relay request, and a web response carries the first part and leaves
the rest to a queue job. Both are logged. Only change it once the server's real limit is known.

## Logging

Everything the plugin does goes to `storage/logs/litespeed-<date>.log`:

- `[INFO] Purging public,tag=…`: the purge header sent with a response, from a CP save, a relay or the next
  request after a failed relay.
- `[INFO] Relayed purge to <url>`: a console or queue purge reached the site and came back with a 204.
- `[ERROR] Couldn’t relay purge to <url>: …`: the relay request failed (timeout, basic auth, a redirect). The
  purge is kept and sent with the next web request.
- `[INFO] Purge too long for one response, the other N parts go out from the queue.`: a big purge from a web
  request, split over a queue job.
- `[WARNING] Too many cache tags for <path>, not caching it.`: the page-side `maxHeaderLength` fallback. A page
  that falls back to per-type tags is logged at `[INFO]`.

LiteSpeed itself doesn't acknowledge a purge, so the log shows what was sent, not what LiteSpeed did with it. To
confirm, request the page again: `x-litespeed-cache: miss` means it was purged.

## Caveats

- **Separate domains.** A purge from a web request only reaches the LiteSpeed vhost that served that request.
  When the sites of a multi-site install are served by different vhosts, a save in the CP only purges the CP's
  vhost; the others catch up when their TTL runs out.
- **Element API.** An endpoint cached by Element API's own `cache` option loses its element tags on Craft's
  cache hits, so LiteSpeed is never told to purge it. Set `'cache' => false` on endpoints LiteSpeed caches.
- **Scheduled entries.** An entry whose post or expiry date passes isn't saved, so nothing purges it. Run
  `craft update-statuses` from cron (Craft 5.7+), which resaves them, or rely on the TTL.
- **Copied databases.** The default tag prefix comes from the system UID, so a staging site built from a
  production database shares production's prefix. That's harmless while each runs on its own vhost; set
  `tagPrefix` when two of them share one.
- **Not yet supported:** private cache for logged-in users and ESI. Logged-in users are never cached.
