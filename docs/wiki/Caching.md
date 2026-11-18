Written against v1.2.0.

The plugin keeps the posts it fetched in the WordPress object cache, so a page view does not always make a request. What it caches is the list of posts, not the rendered HTML, so a change to the markup or the stylesheet shows at once.

| Source | Cache key | Kept for | Cleared when |
| --- | --- | --- | --- |
| A remote site | every attribute of the shortcode | 10 minutes | the time runs out, or any attribute changes |
| This site (`local`, `local_only`, or a `host` that is this site) | the request URL and the cache's last-changed time | until cleared | a post or a page on this site is saved |

**Logged-in visitors skip the local cache.** Local posts are read as the current user, so a logged-in user's results are neither read from nor written to the shared cache; anonymous visitors share one cached copy.

**Without a persistent object cache** (Redis, Memcached or similar), the WordPress object cache lasts only for one request, so every page view fetches again. With one, the times above apply.

## Getting fresh posts now

- **A remote list:** wait up to ten minutes, flush the object cache, or change an attribute to a value that changes what is requested or shown (for example, a different `style` class). An empty `style=""` or an attribute the shortcode does not know leaves the cache key unchanged.
- **A local list:** save any post or page on this site.

## Reference

- `get_content_cache()` and `set_content_cache()` in [`includes/class-uams-news-syndicate-base.php`](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/blob/master/includes/class-uams-news-syndicate-base.php)
- `clear_local_news_cache()` in [`includes/news-syndicate.php`](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/blob/master/includes/news-syndicate.php)
