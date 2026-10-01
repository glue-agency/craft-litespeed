# Release Notes for LiteSpeed

## 5.0.0 - Initial release

- Send `X-LiteSpeed-Cache-Control` on every site response: public for cacheable guest pages, `no-cache` for everything else
- Tag cached pages with Craft's element cache tags, plus a URL tag and a tag per parent path
- Purge the pages an element appears on when it's saved, deleted, restored or moved, from web requests, queue jobs and console commands alike
- Give logged-in users their own cache variant, and vary on cookies through the `varyCookies` setting or `craft.litespeed.varyCookie()`
- Add a CP section to purge URLs (with their sub-pages, so a site's base URL purges the whole site) and custom tags
- Add CP settings for the cache lifetimes, excluded paths, vary cookies, the logged-in cookie's name (`loggedInCookie`) and purging; `config/litespeed.php` overrides them
- Add `litespeed/purge/all`, `litespeed/purge/urls` and `litespeed/purge/tags` console commands, and a Clear Caches option
- Dutch, French and German translations
