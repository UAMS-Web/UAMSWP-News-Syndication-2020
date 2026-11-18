Written against v1.2.0.

The plugin offers six filters and one action. Add callbacks from a theme or another plugin, after this plugin has loaded (it loads its base class on `plugins_loaded`, and loads the `[uamswp_news]` class and registers the shortcode on `init`).

## Filters

| Filter | Arguments | Return | Use it to |
| --- | --- | --- | --- |
| `uamswp_news_syndicate_default_atts` | `array $defaults` | array | Change the default of any [shortcode attribute](Shortcode-attributes.md) site-wide. |
| `uamswp_news_syndicate_allowed_hosts` | `array $hosts` | array | Add or remove host names the shortcode may request from. A host matches an entry or any subdomain of it. |
| `uamswp_news_syndicate_taxonomy_filters` | `string $request_url, array $atts, string $request_url` | string | Add query parameters to the REST request URL before the category, tag and id filters are added. |
| `uams_news_syndication_host_data` | `object $subset, object\|array $post, array $atts` | object | Change the data kept for one post, after the plugin has built it from the REST response. Remote posts arrive as objects, local ones as arrays. |
| `uamswp_news_syndicate_news_output` | `false $content, array $posts, array $atts` | string or `false` | Return your own markup for the whole list. Return `false` to keep the plugin's. |
| `uamswp_news_syndicate_news` | `string $content, array $atts` | string | Change the final HTML the shortcode returns. |

## Action

| Action | Fires | Use it to |
| --- | --- | --- |
| `uamswp_news_syndicate_shortcodes` | On `init`, after `[uamswp_news]` is registered. | Register a shortcode of your own that extends `UAMS_Syndicate_News_Base`. |

## Example: allow another host

```php
add_filter('uamswp_news_syndicate_allowed_hosts', function (array $hosts): array {
    $hosts[] = 'news.example.org';

    return $hosts;
});
```

## Example: change a default

```php
add_filter('uamswp_news_syndicate_default_atts', function (array $defaults): array {
    $defaults['output'] = 'headlines';

    return $defaults;
});
```
