Written against v1.2.0.

- [Nothing shows, and the page source has an error comment](#nothing-shows-and-the-page-source-has-an-error-comment)
- [Nothing shows, and the page source has a script tag](#nothing-shows-and-the-page-source-has-a-script-tag)
- [The heading shows but no posts](#the-heading-shows-but-no-posts)
- [The category or tag is ignored](#the-category-or-tag-is-ignored)
- [Local posts are missing](#local-posts-are-missing)
- [A hide option hides when set to false](#a-hide-option-hides-when-set-to-false)
- [New posts do not appear](#new-posts-do-not-appear)
- [The editor form's Post ID or Background Color has no effect](#the-editor-forms-post-id-or-background-color-has-no-effect)

## Nothing shows, and the page source has an error comment

The page source contains:

```text
<!-- uamswp_news ERROR - an empty or disallowed host was supplied -->
```

**Cause:** the host in `site` or `host` is empty, could not be read as a URL, or is not on the allowed list.

**Fix:** check the spelling of the host. If it is right and outside the organization's domains, a developer adds it with the [`uamswp_news_syndicate_allowed_hosts`](Hooks.md#example-allow-another-host) filter. See [allowed hosts](Shortcode-attributes.md#where-the-posts-come-from).

## Nothing shows, and the page source has a script tag

The page source contains `<!-- UAMSWP Output JSON -->` followed by `<script>var json_data = ...`.

**Cause:** the shortcode has no `output` attribute, and the default is `json`, which prints data for a script instead of visible markup.

**Fix:** add an `output`, for example `output="headlines"`. See [Output formats](Output-formats.md).

## The heading shows but no posts

**Cause:** the request returned no posts. The source site may be unreachable, may answer with an error, or may have no posts matching the filters. An `offset` as large as the number of posts also leaves nothing to show.

**Fix:** open the source site's `wp-json/wp/v2/posts` in a browser and confirm it lists posts. Remove the filters one at a time to find the one that excludes everything. A failed remote request is not cached, so the next page view tries again.

## The category or tag is ignored

The list shows posts from every category even though `category` is set.

**Cause:** the plugin sends the filters as `filter[...]` parameters, which WordPress core does not read. The source site needs to support them.

**Fix:** see [What the source site has to provide](Shortcode-attributes.md#what-the-source-site-has-to-provide).

## Local posts are missing

`local="1"` or `local_only="1"` is set, but none of this site's posts appear.

**Cause:** without `count`, the local request asks for zero posts, which the REST API rejects.

**Fix:** add a `count`. See [Adding this site's posts to a news list](Adding-this-sites-posts-to-a-news-list.md).

## A hide option hides when set to false

`hide_img="false"` or `hide_date="false"` still hides the image or date.

**Cause:** attribute values are text, and some formats treat any non-empty text, the word `false` included, as on.

**Fix:** leave the attribute out to turn the option off. Use `1` to turn it on.

## New posts do not appear

**Cause:** the list is served from the cache.

**Fix:** see [Getting fresh posts now](Caching.md#getting-fresh-posts-now).

## The editor form's Post ID or Background Color has no effect

**Cause:** the Shortcake form writes `postid` and `bgCcolor`, which the shortcode does not read.

**Fix:** edit the shortcode text and use `id` for specific posts and `style` for wrapper classes. See [The editor form](Shortcode-attributes.md#the-editor-form).
