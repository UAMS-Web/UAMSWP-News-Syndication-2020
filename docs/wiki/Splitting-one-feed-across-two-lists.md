Written against v1.2.0.

A common layout puts the newest story in a large format and the next few below it as headlines. Two shortcodes do it: the first takes the newest item, and the second skips it with `offset`.

## Steps

1. Add the featured story:

   ```text
   [uamswp_news output="cards" host="news.example.edu" count="1" news_title="Featured story"]
   ```

2. Below it, add the headlines, skipping the first item:

   ```text
   [uamswp_news output="headlines" host="news.example.edu" count="4" offset="1" hide_title="1"]
   ```

3. Preview the page. The newest story appears once, as a card under **Featured story**, and the four stories after it are listed as headlines.

Both shortcodes need the same `host`, `category`, `tag` and other filters, or the second list does not continue the first one.

**Note:** `offset` adds to the number requested: `count="4" offset="1"` asks the remote site for five posts and skips the first. The request is capped at 100 posts, so `count` plus `offset` above 100 shows fewer items than asked for.

## Reference

- [`count` and `offset`](Shortcode-attributes.md#how-many-and-which)
- [`hide_title`](Shortcode-attributes.md#what-each-item-shows)
- [Output formats](Output-formats.md)
