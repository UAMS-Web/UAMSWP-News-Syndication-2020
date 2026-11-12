<?php

/**
 * Characterization tests for UAMS_Syndicate_News_Base.
 *
 * Every expected value is taken from the class as it stands (the `tests` skill,
 * "Pinning legacy behavior"), so a change to any of them shows up as a failure.
 * WordPress functions are mocked with Brain Monkey; the ones whose real behavior
 * a method depends on (`shortcode_atts()`, `trailingslashit()`, `wp_parse_url()`,
 * `sanitize_key()`) are replaced by small aliases that follow WordPress core.
 */

declare(strict_types=1);

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

require_once dirname(__DIR__, 2).'/includes/class-uams-news-syndicate-base.php';

mutates(UAMS_Syndicate_News_Base::class);

/**
 * Alias WordPress functions with the behavior of their core implementations.
 */
function newsBaseStubCoreFunctions(): void
{
    // WordPress core's shortcode_atts(), including the per-shortcode filter.
    Functions\when('shortcode_atts')->alias(static function ($pairs, $atts, $shortcode = '') {
        $atts = (array) $atts;
        $out = [];
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }

        if ($shortcode) {
            $out = apply_filters("shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode);
        }

        return $out;
    });
    Functions\when('trailingslashit')->alias(static fn ($value) => rtrim((string) $value, '/\\').'/');
    Functions\when('wp_parse_url')->alias(static fn ($url, $component = -1) => parse_url($url, $component));
    Functions\when('esc_url')->alias(
        // Core's esc_url() prepends http:// to a URL with no scheme.
        static fn ($url) => ($url === '' || str_contains((string) $url, ':') || in_array($url[0], ['/', '#', '?'], true))
            ? $url : 'http://'.$url
    );
    Functions\when('sanitize_key')->alias(
        static fn ($key) => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key))
    );
}

beforeEach(function (): void {
    newsBaseStubCoreFunctions();
    $this->base = new UAMS_Syndicate_News_Base;
});

describe('defaults', function (): void {
    it('declares the REST path and the default attributes', function (): void {
        expect($this->base->default_path)->toBe('wp-json/wp/v2/')
            ->and($this->base->shortcode_name)->toBe('')
            ->and($this->base->local_default_atts)->toBe([])
            ->and($this->base->local_extended_atts)->toBe([])
            ->and($this->base->defaults_atts)->toBe([
                'object' => 'json_data',
                'output' => 'json',
                'host' => 'news.uams.edu',
                'scheme' => 'https',
                'news_title' => 'News &amp; Announcements',
                'hide_title' => false,
                'site' => '',
                'category' => '',
                'advanced_cat' => '',
                'tag' => '',
                'style' => '',
                'query' => 'posts',
                'local' => false,
                'local_only' => false,
                'count' => false,
                'date_format' => 'F j, Y',
                'offset' => 0,
                'id' => '',
                'include_link' => false,
                'hide_img' => false,
                'news_position' => 'left',
                'hide_author' => false,
                'hide_date' => false,
            ]);
    });
});

describe('construct()', function (): void {
    it('calls add_shortcode()', function (): void {
        $base = Mockery::mock(UAMS_Syndicate_News_Base::class)->makePartial();
        $base->shouldReceive('add_shortcode')->once();

        $base->construct();
    });

    it('returns nothing from the base add_shortcode() and an empty string from display_shortcode()', function (): void {
        expect($this->base->add_shortcode())->toBeNull()
            ->and($this->base->display_shortcode(['host' => 'example.test']))->toBe('');
    });
});

describe('process_attributes()', function (): void {
    it('fills every default and keeps a passed attribute', function (): void {
        $result = $this->base->process_attributes(['host' => 'example.test', 'unknown' => 'dropped']);

        expect($result)->toBe(array_merge($this->base->defaults_atts, ['host' => 'example.test']));
    });

    it('treats a non-array attribute value, as WordPress passes an empty shortcode, as no attributes', function (): void {
        expect($this->base->process_attributes(''))->toBe($this->base->defaults_atts);
    });

    it('applies the default-attributes filter before merging', function (): void {
        Filters\expectApplied('uamswp_news_syndicate_default_atts')
            ->once()
            ->with($this->base->defaults_atts)
            ->andReturn(['host' => 'filtered.test', 'count' => 3]);

        expect($this->base->process_attributes([]))->toBe(['host' => 'filtered.test', 'count' => 3]);
    });

    it('overrides a default with a local default of the same name', function (): void {
        $this->base->local_default_atts = ['host' => 'local.test'];

        expect($this->base->process_attributes([])['host'])->toBe('local.test');
    });

    it('ignores a local default that is not a default attribute', function (): void {
        $this->base->local_default_atts = ['not_a_default' => 'x'];

        expect($this->base->process_attributes([]))->toBe($this->base->defaults_atts);
    });

    it('adds each extended attribute and a local_ copy of it, but no local_ copy of a core default', function (): void {
        $this->base->local_extended_atts = ['extra' => 'one'];

        $result = $this->base->process_attributes(['local_extra' => 'two']);

        expect($result)->toBe(array_merge($this->base->defaults_atts, ['extra' => 'one', 'local_extra' => 'two']))
            ->and($result)->not->toHaveKey('local_host');
    });

    it('lets an extended attribute replace a core default value without adding a local_ copy', function (): void {
        $this->base->local_extended_atts = ['host' => 'extended.test'];

        $result = $this->base->process_attributes([]);

        expect($result['host'])->toBe('extended.test')
            ->and($result)->not->toHaveKey('local_host');
    });

    it('passes the shortcode name to shortcode_atts so its filter runs', function (): void {
        $this->base->shortcode_name = 'uamswp_news';
        Filters\expectApplied('shortcode_atts_uamswp_news')->once()->andReturn(['filtered' => true]);

        expect($this->base->process_attributes([]))->toBe(['filtered' => true]);
    });
});

describe('content cache', function (): void {
    it('reads the cache under the md5 of the serialized attributes, in the shortcode group', function (): void {
        $atts = ['host' => 'example.test'];
        Functions\expect('wp_cache_get')->once()->with(md5(serialize($atts)), 'uamswp_news')->andReturn('cached');

        expect($this->base->get_content_cache($atts, 'uamswp_news'))->toBe('cached');
    });

    it('returns false on a cache miss', function (): void {
        Functions\expect('wp_cache_get')->once()->andReturn(false);

        expect($this->base->get_content_cache([], 'uamswp_news'))->toBeFalse();
    });

    it('writes the cache under the same key for 600 seconds', function (): void {
        $atts = ['host' => 'example.test'];
        Functions\expect('wp_cache_set')->once()->with(md5(serialize($atts)), 'content', 'uamswp_news', 600);

        expect($this->base->set_content_cache($atts, 'uamswp_news', 'content'))->toBeNull();
    });
});

describe('build_initial_request()', function (): void {
    beforeEach(function (): void {
        $this->atts = ['scheme' => 'https', 'query' => 'posts'];
        $this->remote = ['host' => 'news.uams.edu', 'path' => '/'];
    });

    it('builds an https request to a remote site on a single site', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/');
        Functions\when('is_multisite')->justReturn(false);

        expect($this->base->build_initial_request($this->remote, $this->atts))->toBe([
            'url' => 'https://news.uams.edu/wp-json/wp/v2/posts',
            'scheme' => 'https',
            'site_id' => false,
        ]);
    });

    it('turns the legacy local scheme into http for a remote site', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/');
        Functions\when('is_multisite')->justReturn(false);
        $this->atts['scheme'] = 'local';

        expect($this->base->build_initial_request($this->remote, $this->atts)['scheme'])->toBe('http');
    });

    it('marks a request for this site as local, with site 1 and the home URL scheme', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/sub');

        expect($this->base->build_initial_request(['host' => 'this.test', 'path' => '/sub/'], $this->atts))->toBe([
            'url' => 'http://this.test/sub/wp-json/wp/v2/posts',
            'scheme' => 'local',
            'site_id' => 1,
        ]);
    });

    it('does not treat the same host on a different path as this site', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/sub');
        Functions\when('is_multisite')->justReturn(false);

        expect($this->base->build_initial_request(['host' => 'this.test', 'path' => '/other/'], $this->atts)['site_id'])
            ->toBeFalse();
    });

    it('does not treat the same path on a different host as this site', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/');
        Functions\when('is_multisite')->justReturn(false);

        expect($this->base->build_initial_request(['host' => 'other.test', 'path' => '/'], $this->atts)['site_id'])
            ->toBeFalse();
    });

    it('uses the matching network site, its id and its home URL scheme on multisite', function (): void {
        Functions\when('get_home_url')->alias(
            static fn ($blog_id = null) => $blog_id === 7 ? 'http://news.uams.edu/' : 'https://main.test/'
        );
        Functions\when('is_multisite')->justReturn(true);
        Functions\expect('get_blog_details')
            ->once()
            ->with(['domain' => 'news.uams.edu', 'path' => '/'], false)
            ->andReturn((object) ['blog_id' => 7]);

        expect($this->base->build_initial_request($this->remote, $this->atts))->toBe([
            'url' => 'http://news.uams.edu/wp-json/wp/v2/posts',
            'scheme' => 'http',
            'site_id' => 7,
        ]);
    });

    it('keeps the remote request when no network site matches on multisite', function (): void {
        Functions\when('get_home_url')->justReturn('https://main.test/');
        Functions\when('is_multisite')->justReturn(true);
        Functions\expect('get_blog_details')->once()->andReturn(false);

        expect($this->base->build_initial_request($this->remote, $this->atts))->toBe([
            'url' => 'https://news.uams.edu/wp-json/wp/v2/posts',
            'scheme' => 'https',
            'site_id' => false,
        ]);
    });

    it('escapes the base URL but appends the query unescaped', function (): void {
        Functions\when('get_home_url')->justReturn('http://this.test/');
        Functions\when('is_multisite')->justReturn(false);
        // A `when()` stub from beforeEach answers before a later `expect()`, so the
        // argument is checked inside the alias instead.
        Functions\when('esc_url')->alias(
            static fn ($url) => $url === 'https://news.uams.edu/wp-json/wp/v2/' ? 'ESCAPED/' : 'UNEXPECTED/'
        );
        $this->atts['query'] = 'posts?a=b';

        expect($this->base->build_initial_request($this->remote, $this->atts)['url'])->toBe('ESCAPED/posts?a=b');
    });
});

describe('get_request_url()', function (): void {
    it('parses the host attribute with a trailing slash', function (): void {
        expect($this->base->get_request_url(['site' => '', 'host' => 'https://news.uams.edu']))->toBe([
            'scheme' => 'https',
            'host' => 'news.uams.edu',
            'path' => '/',
        ]);
    });

    it('prefers the site attribute over the host attribute', function (): void {
        expect($this->base->get_request_url(['site' => 'https://site.test/blog', 'host' => 'https://news.uams.edu']))
            ->toBe(['scheme' => 'https', 'host' => 'site.test', 'path' => '/blog/']);
    });

    it('adds http:// to a host with no scheme, through esc_url()', function (): void {
        expect($this->base->get_request_url(['site' => '', 'host' => 'news.uams.edu']))
            ->toBe(['scheme' => 'http', 'host' => 'news.uams.edu', 'path' => '/']);
    });

    it('returns false when the URL has no host', function (): void {
        expect($this->base->get_request_url(['site' => '', 'host' => '/relative']))->toBeFalse()
            ->and($this->base->get_request_url(['site' => '', 'host' => '']))->toBeFalse();
    });
});

describe('build_taxonomy_filters()', function (): void {
    beforeEach(function (): void {
        $this->atts = ['advanced_cat' => '', 'category' => '', 'tag' => '', 'id' => ''];
        Functions\when('add_query_arg')->alias(
            static fn (array $args, string $url) => $url.'|'.key($args).'='.current($args)
        );
    });

    it('returns the URL unchanged when no taxonomy attribute is set', function (): void {
        expect($this->base->build_taxonomy_filters($this->atts, 'https://x.test/posts'))->toBe('https://x.test/posts');
    });

    it('applies the taxonomy filter with the attributes and the URL', function (): void {
        Filters\expectApplied('uamswp_news_syndicate_taxonomy_filters')
            ->once()
            ->with('https://x.test/posts', $this->atts, 'https://x.test/posts')
            ->andReturn('https://filtered.test/posts');

        expect($this->base->build_taxonomy_filters($this->atts, 'https://x.test/posts'))
            ->toBe('https://filtered.test/posts');
    });

    it('adds each filter, in order, with sanitized terms', function (): void {
        $atts = ['advanced_cat' => '3,x', 'category' => 'News,Re-search', 'tag' => 'A B', 'id' => '10,11'];

        expect($this->base->build_taxonomy_filters($atts, 'u'))
            ->toBe('u|filter[cat]=3,0|filter[category_name]=news,re-search|filter[tag]=ab|filter[p]=10,11');
    });

    it('adds only the filters whose attribute is set', function (string $key, string $value, string $expected): void {
        $atts = array_merge($this->atts, [$key => $value]);

        expect($this->base->build_taxonomy_filters($atts, 'u'))->toBe('u|'.$expected);
    })->with([
        'advanced_cat' => ['advanced_cat', '5', 'filter[cat]=5'],
        'category' => ['category', 'news', 'filter[category_name]=news'],
        'tag' => ['tag', 'covid', 'filter[tag]=covid'],
        'id' => ['id', '42', 'filter[p]=42'],
    ]);
});

describe('term sanitizing', function (): void {
    it('sanitizes each comma-separated term with sanitize_key()', function (): void {
        expect($this->base->sanitized_terms('News, Health ,re_search'))->toBe('news,health,re_search');
    });

    it('casts each comma-separated id to an integer', function (): void {
        expect($this->base->sanitized_ids('12, 7,abc,3x'))->toBe('12,7,0,3');
    });

    it('accepts a non-string value, as a filter-altered default can supply, and coerces it', function (): void {
        expect($this->base->sanitized_ids(42))->toBe('42')
            ->and($this->base->sanitized_terms(7))->toBe('7')
            ->and($this->base->sanitized_terms(true))->toBe('1');
    });

    it('builds an id filter from an int attribute without a TypeError', function (): void {
        Functions\when('add_query_arg')->alias(
            static fn (array $args, string $url) => $url.'|'.key($args).'='.current($args)
        );

        expect($this->base->build_taxonomy_filters(['advanced_cat' => '', 'category' => '', 'tag' => '', 'id' => 42], 'u'))
            ->toBe('u|filter[p]=42');
    });

    it('returns a single term or id unchanged in shape', function (): void {
        expect($this->base->sanitized_terms('news'))->toBe('news')
            ->and($this->base->sanitized_ids('9'))->toBe('9');
    });
});
