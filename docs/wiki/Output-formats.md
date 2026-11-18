Written against v1.2.0.

The `output` attribute picks how the list is rendered. The markup is in `generate_shortcode_output()` in [`includes/class-uams-news-syndicate-news.php`](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/blob/master/includes/class-uams-news-syndicate-news.php). The plugin's stylesheet, `css/uamswp-news-syndication-news.css`, is loaded only on a post or page whose content contains the shortcode.

`headlines`, `excerpts`, `cards` and `grid` print one `<section>` with the `news_title` heading. `full` prints one `<section>` with no heading. `side` prints one `<section>` per post, and uses `news_title` only as its screen-reader label.

| `output` | Shows | Options it reads |
| --- | --- | --- |
| `json` (default) | No visible markup: a `<script>` that sets a JavaScript variable to the list of posts, for a theme's own script to render. | `object` |
| `headlines` | A list of linked titles. Requests only the title, date and link of each post. | `hide_title` |
| `excerpts` | Each post with its thumbnail, title, date, author, excerpt and a **Read more** button. | `hide_img`, `hide_date`, `hide_author`, `date_format`, `hide_title` |
| `cards` | Cards with an image, title, excerpt and **Read more** button. | `hide_img`, `include_link`, `hide_title` |
| `grid` | The first post featured with its image, and the rest beside it as title, excerpt and **Read more**, with no image. | `hide_img`, `include_link`, `hide_title` |
| `side` | Each post as image and text side by side, with its category. It also writes the posts to the `object` JavaScript variable, as `json` does. | `news_position`, `include_link`, `object` |
| `full` | The whole article, with its image, caption and author. | `hide_author` |

Every format except `json` reads `style` (extra wrapper classes) and skips the first `offset` posts. `json` reads neither: with `count="4" offset="1"` it outputs five posts and skips none. Links carry `data-moduletitle` and `data-categorytitle` attributes naming the list and the category.

## An empty list

When no posts come back, `headlines`, `excerpts`, `cards` and `grid` still print the section and its heading with no items in it, and `full` prints an empty section. `side` prints only an HTML comment, and `json` sets the variable to an empty array (`var json_data = [];`).

## Images

The thumbnail used by `excerpts` is the source site's `post-thumbnail` size, then `thumbnail`, then the full image.

The larger image used by the other formats is the first size the post's image has:

- **A remote post:** `aspect-16-9`, `aspect-16-9-small`, `news-full`, `uams_news`.
- **A local post:** `aspect-16-9`, `aspect-16-9-small`, `large`.

`cards` prefers the smaller size where one was found (`aspect-16-9-small`, or `uams_news` beside `news-full`). `cards`, the featured `grid` post and `side` show the plugin's logo (`images/uams_logo.jpg`) for a post with no larger image.

## Replacing the markup

A developer can replace any format's markup with the [`uamswp_news_syndicate_news_output`](Hooks.md#filters) filter, or change the final HTML with `uamswp_news_syndicate_news`.
