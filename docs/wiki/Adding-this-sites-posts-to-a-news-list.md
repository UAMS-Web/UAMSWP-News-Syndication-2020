Written against v1.2.0.

A list that shows a remote news feed can also include posts from the site the shortcode is on, merged into one list and sorted newest first. A list can also show this site's posts alone.

**Warning:** `local` and `local_only` work only on a multisite network. On a single-site install they call `get_site()`, which WordPress defines only on multisite, and the page ends in a fatal error. On a single site, point `host` at the site itself instead (see the last section).

## Merge this site's posts into a remote feed

1. Add `local="1"` and a `count` to the shortcode:

   ```text
   [uamswp_news output="excerpts" host="news.example.edu" count="5" local="1"]
   ```

2. Preview the page. The five newest items across both sites are shown, newest first.

Up to `count` posts are taken from each site before the list is cut back to `count`. The local request is sent the same `category`, `tag` and `id` filters as the remote one; they narrow this site's posts only if this site supports the `filter` parameter (see [What the source site has to provide](Shortcode-attributes.md#what-the-source-site-has-to-provide)).

**Note:** always set `count` with `local` or `local_only`. Without it the local request asks for `per_page=0`, which the WordPress REST API rejects (its minimum is 1), and no local posts are shown.

## Show this site's posts only

1. Use `local_only="1"` instead:

   ```text
   [uamswp_news output="cards" count="3" local_only="1"]
   ```

2. Preview the page. Only this site's posts are listed, and no request is made to the remote host.

## Point the shortcode at this site

Setting `host` (or `site`) to this site's own address also lists this site's posts. The plugin sees that the requested site is the current one and reads its posts directly instead of over HTTP. Unlike `local_only`, this works on a single site and does not need `count`.

## Reference

- [`local` and `local_only`](Shortcode-attributes.md#where-the-posts-come-from)
- [`count`](Shortcode-attributes.md#how-many-and-which)
- [Caching](Caching.md): local results are refreshed whenever a post or page is saved.
