Written against v1.2.0.

Every attribute `[uamswp_news]` accepts, grouped by what it controls. The defaults come from `$defaults_atts` in [`includes/class-uams-news-syndicate-base.php`](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/blob/master/includes/class-uams-news-syndicate-base.php), and a site can change them with the [`uamswp_news_syndicate_default_atts`](Hooks.md#filters) filter.

Shortcode attributes are text. For the on/off attributes, `1` turns the option on and leaving the attribute out turns it off. Do not write `="false"`: some formats test the raw value, and the word `false` counts as on there.

## Where the posts come from

| Attribute | Default | Effect |
| --- | --- | --- |
| `host` | the plugin's default news site | Host name to read posts from, such as `news.example.edu`. |
| `site` | empty | Host and path, such as `news.example.edu/research/`, for a site below the host's root. Overrides `host` when set. |
| `scheme` | `https` | The scheme of the category links the formats print: `http` or `https`, and any other value becomes `https`. Requests to a remote host are always made over https. |
| `query` | `posts` | The REST route under `wp-json/wp/v2/` to read. Only letters, digits, `_`, `-` and `/` are kept; an empty result falls back to `posts`. |
| `local` | off | `1` merges this site's posts into the remote results. Multisite only, and needs `count`; see [Adding this site's posts to a news list](Adding-this-sites-posts-to-a-news-list.md). |
| `local_only` | off | `1` shows this site's posts only and makes no remote request. Multisite only, and needs `count`. |

**Allowed hosts.** The host of `site` or `host` must be on the allowed list, or the shortcode prints only an HTML comment (see [Troubleshooting](Troubleshooting.md#nothing-shows-and-the-page-source-has-an-error-comment)). The list holds the organization's two public domains with every subdomain of them, this site's own host, and on a multisite network the current site's domain. `get_allowed_hosts()` in the base class holds the list, and the [`uamswp_news_syndicate_allowed_hosts`](Hooks.md#filters) filter changes it.

## How many and which

| Attribute | Default | Effect |
| --- | --- | --- |
| `count` | the source site's own page size | Number of items to show. Requests are capped at 100. |
| `offset` | `0` | Number of newest items to skip. Added to `count` for the request, so the shown number stays `count`. Set `count` with it: an `offset` alone requests only `offset` posts and then skips all of them. |
| `category` | empty | Category slug, or several separated by commas. |
| `advanced_cat` | empty | Category ids separated by commas; a negative id excludes that category. |
| `tag` | empty | Tag slug, or several separated by commas. |
| `id` | empty | Post id, or several separated by commas, to show specific posts. |

Slugs are passed through `sanitize_key()` and ids through `intval()` before they reach the request.

## How the list looks

| Attribute | Default | Effect |
| --- | --- | --- |
| `output` | `json` | The format. One of `json`, `headlines`, `excerpts`, `cards`, `grid`, `side`, `full`; see [Output formats](Output-formats.md). |
| `news_title` | `News & Announcements` | The heading above the list in `headlines`, `excerpts`, `cards` and `grid`. The other formats except `json` use it only as the section's screen-reader label and in the links' `data-moduletitle` attribute. |
| `style` | empty | Extra CSS classes for the outer wrapper, separated by spaces. Each one is passed through `sanitize_html_class()`. |
| `date_format` | `F j, Y` | PHP date format for the item date in `excerpts`. |
| `news_position` | `left` | `left` or `right`: the image side in the `side` format. |
| `object` | `json_data` | The JavaScript variable name the `json` and `side` formats write the posts to. Anything that is not a valid name is replaced with `json_data`. |

## What each item shows

| Attribute | Default | Effect |
| --- | --- | --- |
| `hide_title` | off | `1` hides the heading visually and keeps it for screen readers, in the formats that print one. |
| `hide_img` | off | `1` leaves out the images in `excerpts`, `cards` and `grid`. |
| `hide_date` | off | `1` hides the date in `excerpts`. |
| `hide_author` | off | `1` hides the author in `excerpts` and `full`. |
| `include_link` | off | `1` adds a button linking to the category's archive: **View the Full List** in `cards` and `grid`, which also need `category`, and one named after the category in `side` (**View the Research Archive** for a category named Research). |

## Example

```text
[uamswp_news output="cards" host="news.example.edu" category="research" count="3" include_link="1" news_title="Research news"]
```

## What the source site has to provide

The plugin reads the source site's standard REST API, and a few attributes depend on more than WordPress core provides:

- **`category`, `advanced_cat`, `tag` and `id`** are sent as `filter[...]` query parameters (`filter[category_name]`, `filter[cat]`, `filter[tag]`, `filter[p]`). WordPress core has not read the `filter` parameter since version 4.7, so these narrow the list only where the source site adds support for it, for example with a REST filter plugin.
- **Category names and links** on remote items come from a `post_categories` field in each post's REST response.
- **The `full` format** shows a `fullcontent` field from the response when one is present, and the post content otherwise.
- **Images** use the source site's registered sizes, falling back to the full image; see [Output formats](Output-formats.md#images).

## The editor form

Where the theme provides Shortcake (the `UAMS_Shortcakes` class), the editor offers a **News Syndication** form with Format, Category, Count and Offset fields. Its **Post ID** and **Background Color** fields write attributes the shortcode does not read (`postid` and `bgCcolor`); use `id` and `style` in the shortcode text instead.
