Written against v1.2.0.

By the end of this page you will have a page that lists the three newest posts from a news site, as headlines, under a heading of your choosing.

It assumes the plugin is installed and active (see [Installation (README)](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020#installation)) and that you can edit a page.

## 1. Open a page for editing

Create a page, or open one, in the block editor. Add a **Shortcode** block where the list should appear.

## 2. Add the shortcode

Type this into the block, replacing `news.example.edu` with the news site you want to show:

```text
[uamswp_news output="headlines" host="news.example.edu" count="3" news_title="Latest news"]
```

The host has to be on the plugin's allowed list (see [`host`](Shortcode-attributes.md#where-the-posts-come-from)). Leave `host` out to use the plugin's default news site.

## 3. Preview the page

Preview or publish the page. Under the heading **Latest news** you see a list of three linked titles, newest first.

**Note:** if the page shows nothing, view its source and look for a comment that begins `uamswp_news ERROR`. [Troubleshooting](Troubleshooting.md#nothing-shows-and-the-page-source-has-an-error-comment) explains it.

## 4. Try another format

Change `output="headlines"` to `output="cards"` and preview again. The same three posts now show as cards with their featured image, title and a **Read more** button. [Output formats](Output-formats.md) lists all seven formats.

## Go further

- [Adding this site's posts to a news list](Adding-this-sites-posts-to-a-news-list.md): mix this site's own posts into the list.
- [Splitting one feed across two lists](Splitting-one-feed-across-two-lists.md): a featured story above the headlines.
- [Shortcode attributes](Shortcode-attributes.md): every option the shortcode takes.
