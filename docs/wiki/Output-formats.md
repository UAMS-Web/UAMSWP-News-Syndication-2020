Written against v1.2.0.

The `output` attribute picks how the list is rendered. Every format except `json` prints a `<section>` with the `news_title` heading and one entry per post; the markup is in `generate_shortcode_output()` in [`includes/class-uams-news-syndicate-news.php`](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/blob/master/includes/class-uams-news-syndicate-news.php). The plugin's stylesheet, `css/uamswp-news-syndication-news.css`, is loaded only on a post or page whose content contains the shortcode.

| `output` | Shows | Options it reads |
| --- | --- | --- |
| `json` (default) | No visible markup: a `<script>` that sets a JavaScript variable to the list of posts, for a theme's own script to render. | `object` |
| `headlines` | A list of linked titles. Requests only the title, date and link of each post. | `hide_title` |
| `excerpts` | Each post with its thumbnail, title, date, author, excerpt and a **Read more** button. | `hide_img`, `hide_date`, `hide_author`, `date_format`, `hide_title` |
| `cards` | Cards with an image, title, excerpt and **Read more** button. | `hide_img`, `include_link`, `hide_title` |
| `grid` | A grid of posts with images, the first one featured larger. | `hide_img`, `include_link`, `hide_title` |
| `side` | Image and text side by side. | `news_position`, `include_link`, `hide_title` |
| `full` | The whole article, with its caption and author. | `hide_author`, `hide_title` |

Every format also reads `style` (extra wrapper classes) and `offset`. Links carry `data-moduletitle` and `data-categorytitle` attributes naming the list and the category.

## An empty list

When no posts come back, `headlines`, `excerpts`, `cards`, `grid` and `full` still print the section and its heading with no items in it. `side` prints only an HTML comment, and `json` sets the variable to an empty array (`var json_data = [];`).

## Images

The thumbnail is the source site's `post-thumbnail` size, then `thumbnail`, then the full image. The larger image used by `cards`, `grid` and `side` is the first of `aspect-16-9`, `aspect-16-9-small`, `news-full` and `uams_news` that the source site has. In `cards`, a post with none of them shows the plugin's logo (`images/uams_logo.jpg`) instead.

## Replacing the markup

A developer can replace any format's markup with the [`uamswp_news_syndicate_news_output`](Hooks.md#filters) filter, or change the final HTML with `uamswp_news_syndicate_news`.
