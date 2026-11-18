Written against v1.2.0.

A list that shows a remote news feed can also include posts from the site the shortcode is on, merged into one list and sorted newest first. A list can also show this site's posts alone.

## Merge this site's posts into a remote feed

1. Add `local="1"` to the shortcode:

   ```text
   [uamswp_news output="excerpts" host="news.example.edu" count="5" local="1"]
   ```

2. Preview the page. The five newest items across both sites are shown, newest first.

The local request uses the same filters as the remote one: a `category`, `tag` or `id` applies to this site's posts too. Up to `count` posts are taken from each site before the list is cut back to `count`.

**Note:** always set `count` with `local` or `local_only`. Without it the local request asks for `per_page=0`, which the WordPress REST API rejects, and no local posts are shown.

## Show this site's posts only

1. Use `local_only="1"` instead:

   ```text
   [uamswp_news output="cards" count="3" local_only="1"]
   ```

2. Preview the page. Only this site's posts are listed, and no request is made to the remote host.

**Note:** pointing `host` (or `site`) at this site itself gives the same result as `local_only`: the plugin sees that the requested site is the current one and reads its posts directly instead of over HTTP.

## Reference

- [`local` and `local_only`](Shortcode-attributes.md#where-the-posts-come-from)
- [`count`](Shortcode-attributes.md#how-many-and-which)
- [Caching](Caching.md): local results are refreshed whenever a post or page is saved.
