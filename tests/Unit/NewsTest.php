<?php

/**
 * Characterization tests for UAMS_Syndicate_News: its hooks, the Shortcake UI
 * registration, display_shortcode()'s request building and data flow, and the
 * two post-processing methods.
 *
 * Every expected value is taken from the class as it stands (the `tests` skill,
 * "Pinning legacy behavior"). The HTML templates in generate_shortcode_output()
 * are not covered here; display_shortcode() is exercised through the
 * `uamswp_news_syndicate_news_output` filter, which skips them.
 *
 * Pins that record behavior believed wrong say so in their names and link the
 * issue that will change them: process_local_posts() never uses a `thumbnail`
 * size, and warns on a post with no `aspect-16-9-small` size (#64);
 * display_shortcode() warns on an undefined variable when `local_only` is set
 * for a remote host (#65).
 *
 * Line 1119 (the `thumbnail` branch of process_local_posts()) is unreachable
 * because of #64, so no test covers it.
 */

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

require_once dirname(__DIR__, 2).'/includes/class-uams-news-syndicate-base.php';
require_once dirname(__DIR__, 2).'/includes/class-uams-news-syndicate-news.php';

mutates(UAMS_Syndicate_News::class);

if (! class_exists('WP_REST_Request')) {
    /**
     * Stand-in for WordPress core's WP_REST_Request: only from_url() is used.
     */
    class WP_REST_Request
    {
        /**
         * @param  string  $url  The URL the request was built from.
         */
        public function __construct(public string $url = '') {}

        /**
         * Build a request from a URL, as core does.
         */
        public static function from_url($url): self
        {
            return new self((string) $url);
        }
    }
}

/**
 * A REST response with a status and a data payload.
 */
function newsResponse(int $status, mixed $data): object
{
    return new class($status, $data)
    {
        public function __construct(private int $status, public mixed $data) {}

        public function get_status(): int
        {
            return $this->status;
        }
    };
}

/**
 * Run a callable and return its result with every PHP warning it raised.
 *
 * @return array{0: mixed, 1: list<string>}
 */
function newsCaptureWarnings(callable $callback): array
{
    $warnings = [];
    set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    });

    try {
        $result = $callback();
    } finally {
        restore_error_handler();
    }

    return [$result, $warnings];
}

/**
 * Alias the WordPress functions display_shortcode() depends on with core's behavior.
 */
function newsStubRequestFunctions(): void
{
    Functions\when('shortcode_atts')->alias(static function ($pairs, $atts, $shortcode = '') {
        $atts = (array) $atts;
        $out = [];
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
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
    Functions\when('sanitize_key')->alias(static fn ($key) => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)));
    Functions\when('add_query_arg')->alias(static function (array $args, string $url): string {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($args);
    });
    Functions\when('sanitize_html_class')->alias(static fn ($class) => preg_replace('/[^A-Za-z0-9_-]/', '', (string) $class));
    Functions\when('is_user_logged_in')->justReturn(false);
    Functions\when('get_home_url')->justReturn('https://this.test/');
    Functions\when('is_multisite')->justReturn(false);
}

/**
 * A remote post as the REST API returns it with `_embed`, decoded to objects.
 */
function newsRemotePost(array $overrides = []): object
{
    $post = array_merge([
        'id' => 11,
        'date' => '2024-05-01T10:00:00',
        'modified' => '2024-05-02T10:00:00',
        'link' => 'https://news.uams.edu/a/',
        'title' => ['rendered' => 'A title'],
        'content' => ['rendered' => '<p>Content</p>'],
        'excerpt' => ['rendered' => '<p>Excerpt</p>'],
        'fullcontent' => '<p>Full</p>',
        'featured_media' => 0,
        'post_categories' => [3, 4],
    ], $overrides);

    return json_decode((string) json_encode($post));
}

/**
 * A remote `_embedded` block with one featured image carrying the given sizes.
 */
function newsRemoteMedia(array $sizes, array $author = [['name' => 'Ann Author']]): array
{
    $sizeObjects = [];
    foreach ($sizes as $size) {
        $sizeObjects[$size] = ['source_url' => "https://img.test/{$size}.jpg"];
    }

    return [
        'featured_media' => 9,
        '_embedded' => [
            'wp:featuredmedia' => [[
                'source_url' => 'https://img.test/original.jpg',
                'alt_text' => 'Alt',
                'caption' => ['rendered' => 'Caption'],
                'media_details' => ['sizes' => $sizeObjects],
            ]],
            'author' => $author,
        ],
    ];
}

/**
 * A local post as rest_do_request() returns it, as arrays.
 */
function newsLocalPost(array $overrides = []): array
{
    return array_merge([
        'id' => 21,
        'date' => '2024-06-01T10:00:00',
        'modified' => '2024-06-02T10:00:00',
        'link' => 'https://this.test/b/',
        'title' => ['rendered' => 'B title'],
        'content' => ['rendered' => '<p>B content</p>'],
        'excerpt' => ['rendered' => '<p>B excerpt</p>'],
        'fullcontent' => '<p>B full</p>',
        'featured_media' => 0,
        'author' => 0,
        'categories' => [5],
        '_links' => [],
    ], $overrides);
}

/**
 * Answer rest_do_request() for the media and author links of a local post.
 */
function newsStubLocalMedia(array $sizes, ?string $authorName = 'Bea Author'): void
{
    $sizeArrays = [];
    foreach ($sizes as $size) {
        $sizeArrays[$size] = ['source_url' => "https://img.test/local-{$size}.jpg"];
    }

    Functions\when('rest_do_request')->alias(static function (WP_REST_Request $request) use ($sizeArrays, $authorName) {
        if ($request->url === 'https://this.test/wp-json/wp/v2/media/9') {
            return newsResponse(200, [
                'source_url' => 'https://img.test/local-original.jpg',
                'alt_text' => 'Local alt',
                'caption' => ['rendered' => 'Local caption'],
                'media_details' => ['sizes' => $sizeArrays],
            ]);
        }

        return newsResponse(200, $authorName === null ? [] : ['name' => $authorName]);
    });
}

/**
 * The `_links` and ids that make process_local_posts() fetch media and author.
 */
function newsLocalLinks(): array
{
    return [
        'featured_media' => 9,
        'author' => 2,
        '_links' => [
            'wp:featuredmedia' => [['href' => 'https://this.test/wp-json/wp/v2/media/9']],
            'author' => [['href' => 'https://this.test/wp-json/wp/v2/users/2']],
        ],
    ];
}

beforeEach(function (): void {
    Functions\when('add_shortcode')->justReturn(true);
    $this->news = new UAMS_Syndicate_News;
});

describe('construction and hooks', function (): void {
    it('is the uamswp_news shortcode and registers it with display_shortcode()', function (): void {
        $registered = [];
        Functions\when('add_shortcode')->alias(function (string $tag, array $callback) use (&$registered): bool {
            $registered[] = [$tag, $callback[1]];

            return true;
        });

        $news = new UAMS_Syndicate_News;

        expect($registered)->toBe([['uamswp_news', 'display_shortcode']])
            ->and($news->shortcode_name)->toBe('uamswp_news')
            ->and(has_action('wp_enqueue_scripts', [$news, 'enqueue_news_syndication_stylesheet']))->toBe(10);
    });

    it('registers no Shortcake hooks when the UAMS_Shortcakes class is absent', function (): void {
        Actions\expectAdded('admin_init')->never();
        Actions\expectAdded('enqueue_shortcode_ui')->never();

        new UAMS_Syndicate_News;
    });

    it('registers the Shortcake hooks and editor style when the UAMS_Shortcakes class exists', function (): void {
        // `class_exists` is listed in patchwork.json, so this one test can see the class
        // without defining it for every test after it.
        Patchwork\redefine('class_exists', static function ($class, $autoload = true) {
            return $class === 'UAMS_Shortcakes' ? true : Patchwork\relay();
        });
        $styles = [];
        Functions\when('add_editor_style')->alias(static function ($style) use (&$styles): void {
            $styles[] = $style;
        });
        $scripts = [];
        Functions\when('wp_enqueue_script')->alias(static function (...$args) use (&$scripts): void {
            $scripts[] = $args;
        });

        Actions\expectAdded('enqueue_shortcode_ui')->once()->whenHappen(static function (Closure $callback): void {
            $callback();
        });

        $news = new UAMS_Syndicate_News;

        expect(has_action('admin_init', [$news, 'build_shortcake']))->toBe(10)
            ->and($styles)->toBe([UAMS_NEWS_ROOT_URL.'css/uams-news-syndication-admin.css'])
            ->and($scripts)->toBe([[
                'uams_news_syndications_editor_js',
                UAMS_NEWS_ROOT_URL.'js/uams-news-syndication-shortcake.js',
            ]]);
    });

    it('enqueues the stylesheet on a post that uses the shortcode', function (): void {
        Functions\when('get_post')->justReturn((object) ['post_content' => 'x [uamswp_news] y']);
        Functions\expect('has_shortcode')->once()->with('x [uamswp_news] y', 'uamswp_news')->andReturn(true);
        Functions\expect('wp_enqueue_style')->once()->with(
            'uamswp-syndication-news-style',
            UAMS_NEWS_ROOT_URL.'css/uamswp-news-syndication-news.css',
            [],
            UAMS_NEWS_VERSION,
            'all'
        );

        $this->news->enqueue_news_syndication_stylesheet();
    });

    it('does not enqueue the stylesheet on a post without the shortcode', function (): void {
        Functions\when('get_post')->justReturn((object) ['post_content' => 'nothing']);
        Functions\when('has_shortcode')->justReturn(false);
        Functions\expect('wp_enqueue_style')->never();

        $this->news->enqueue_news_syndication_stylesheet();
    });

    it('does not enqueue the stylesheet when there is no post', function (): void {
        Functions\when('get_post')->justReturn(null);
        Functions\expect('has_shortcode')->never();
        Functions\expect('wp_enqueue_style')->never();

        $this->news->enqueue_news_syndication_stylesheet();
    });
});

describe('build_shortcake()', function (): void {
    it('registers the Shortcake UI for uamswp_news on posts and pages', function (): void {
        Functions\when('esc_html__')->returnArg();
        Functions\expect('shortcode_ui_register_for_shortcode')->once()->andReturnUsing(
            function (string $tag, array $args): void {
                expect($tag)->toBe('uamswp_news')
                    ->and($args['label'])->toBe('News Syndication')
                    ->and($args['listItemImage'])->toBe('dashicons-rss')
                    ->and($args['post_type'])->toBe(['post', 'page'])
                    ->and(array_column($args['attrs'], 'attr'))
                    ->toBe(['output', 'category', 'count', 'offset', 'postid', 'bgCcolor'])
                    ->and(array_column($args['attrs'], 'type'))
                    ->toBe(['radio', 'text', 'number', 'number', 'number', 'radio'])
                    ->and($args['attrs'][0]['options'])
                    ->toBe(['headlines' => 'Headline', 'excerpts' => 'Excerpt', 'cards' => 'Card', 'full' => 'Full'])
                    ->and($args['attrs'][2]['meta'])->toBe(['placeholder' => '1', 'min' => '1', 'step' => '1'])
                    ->and($args['attrs'][3]['meta'])->toBe(['placeholder' => '0', 'min' => '0', 'step' => '1'])
                    ->and($args['attrs'][5]['options'])->toBe(['bg-white' => 'White', 'bg-gray' => 'Gray']);
            }
        );

        $this->news->build_shortcake();
    });
});

describe('display_shortcode()', function (): void {
    beforeEach(function (): void {
        newsStubRequestFunctions();
        // Every function below records what it was asked, because a later
        // expect() does not replace a when() stub set here.
        $this->remoteUrl = null;
        $this->bodyRead = false;
        $this->cacheSets = [];
        $this->cacheGets = [];
        $this->restUrls = [];
        $this->cached = static fn ($key, $group) => false;
        $this->restResponse = static fn (WP_REST_Request $request) => newsResponse(200, []);
        $this->captured = null;
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_safe_remote_get')->alias(function (string $url) {
            $this->remoteUrl = $url;

            return ['body' => 'response'];
        });
        $this->body = '[]';
        Functions\when('wp_remote_retrieve_body')->alias(function () {
            $this->bodyRead = true;

            return $this->body;
        });
        Functions\when('wp_cache_get')->alias(function ($key, $group) {
            $this->cacheGets[] = [$key, $group];

            return ($this->cached)($key, $group);
        });
        Functions\when('wp_cache_set')->alias(function (...$args): bool {
            $this->cacheSets[] = $args;

            return true;
        });
        Functions\when('wp_cache_get_last_changed')->justReturn('lc1');
        Functions\when('rest_do_request')->alias(function (WP_REST_Request $request) {
            $this->restUrls[] = $request->url;

            return ($this->restResponse)($request);
        });
        Functions\when('get_site')->justReturn((object) ['domain' => 'this.test', 'path' => '/']);
        Filters\expectApplied('uamswp_news_syndicate_news_output')->andReturnUsing(
            function ($content, $data, $atts) {
                $this->captured = ['content' => $content, 'data' => $data, 'atts' => $atts];

                return 'OUTPUT';
            }
        );
    });

    $ids = static fn (array $posts): array => array_map(static fn ($post) => $post->ID, array_values($posts));

    it('returns an error comment for an empty host', function (): void {
        expect($this->news->display_shortcode(['host' => '']))
            ->toBe('<!-- uamswp_news ERROR - an empty or disallowed host was supplied -->')
            ->and($this->captured)->toBeNull()
            ->and($this->remoteUrl)->toBeNull();
    });

    it('returns an error comment and makes no request for a host outside the allowed hosts', function (): void {
        expect($this->news->display_shortcode(['host' => 'https://example.com']))
            ->toBe('<!-- uamswp_news ERROR - an empty or disallowed host was supplied -->')
            ->and($this->news->display_shortcode(['site' => 'http://169.254.169.254', 'host' => 'news.uams.edu']))
            ->toBe('<!-- uamswp_news ERROR - an empty or disallowed host was supplied -->')
            ->and($this->captured)->toBeNull()
            ->and($this->remoteUrl)->toBeNull()
            ->and($this->restUrls)->toBe([]);
    });

    it('requests a remote site over https with _embed, then applies the output and final filters', function (): void {
        Filters\expectApplied('uamswp_news_syndicate_news')->once()->with('OUTPUT', Mockery::type('array'))->andReturn('FINAL');

        expect($this->news->display_shortcode(['host' => 'news.uams.edu']))->toBe('FINAL')
            ->and($this->remoteUrl)->toBe('https://news.uams.edu/wp-json/wp/v2/posts?_embed=')
            ->and($this->captured['content'])->toBeFalse()
            ->and($this->captured['data'])->toBe([])
            ->and($this->restUrls)->toBe([]);
    });

    it('asks for count plus offset items', function (): void {
        $this->news->display_shortcode(['host' => 'news.uams.edu', 'count' => '3', 'offset' => '2']);

        expect($this->remoteUrl)->toBe('https://news.uams.edu/wp-json/wp/v2/posts?per_page=5&_embed=')
            ->and($this->captured['atts']['count'])->toBe(5);
    });

    it('asks for count items with no offset', function (): void {
        $this->news->display_shortcode(['host' => 'news.uams.edu', 'count' => '3']);

        expect($this->remoteUrl)->toBe('https://news.uams.edu/wp-json/wp/v2/posts?per_page=3&_embed=')
            ->and($this->captured['atts']['count'])->toBe('3');
    });

    it('caps the requested count at 100, and keeps 100 and 99 as given', function (string $count, string $perPage): void {
        $this->news->display_shortcode(['host' => 'news.uams.edu', 'count' => $count]);

        expect($this->remoteUrl)->toBe("https://news.uams.edu/wp-json/wp/v2/posts?per_page={$perPage}&_embed=");
    })->with([
        '150' => ['150', '100'],
        '101' => ['101', '100'],
        '100' => ['100', '100'],
        '99' => ['99', '99'],
    ]);

    it('asks only for title, date and link for headlines', function (): void {
        $this->news->display_shortcode(['host' => 'news.uams.edu', 'output' => 'headlines']);

        expect($this->remoteUrl)->toBe(
            'https://news.uams.edu/wp-json/wp/v2/posts?_embed=&_fields%5B%5D=title&_fields%5B%5D=date&_fields%5B%5D=link'
        );
    });

    it('processes a remote response, caches it for 600 seconds, and passes the posts newest first', function () use ($ids): void {
        $this->body = (string) json_encode([
            newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00']),
            newsRemotePost(['id' => 2, 'date' => '2024-02-01T00:00:00']),
        ]);

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect($ids($this->captured['data']))->toBe([2, 1])
            ->and($this->cacheSets)->toHaveCount(1)
            ->and($this->cacheSets[0][2])->toBe('uamswp_news')
            ->and($this->cacheSets[0][3])->toBe(600)
            ->and($ids($this->cacheSets[0][1]))->toBe([1, 2]);
    });

    it('keeps only count items after sorting', function () use ($ids): void {
        $this->body = (string) json_encode([
            newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00']),
            newsRemotePost(['id' => 2, 'date' => '2024-02-01T00:00:00']),
            newsRemotePost(['id' => 3, 'date' => '2024-03-01T00:00:00']),
        ]);

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'count' => '2']);

        expect($ids($this->captured['data']))->toBe([3, 2])
            ->and(array_keys($this->captured['data']))->toBe([0, 1]);
    });

    it('passes every post when no count is set', function () use ($ids): void {
        $this->body = (string) json_encode([
            newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00']),
            newsRemotePost(['id' => 2, 'date' => '2024-02-01T00:00:00']),
        ]);

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect(array_keys($this->captured['data']))
            ->toBe([strtotime('2024-02-01T00:00:00'), strtotime('2024-01-01T00:00:00')]);
    });

    it('treats an undecodable body as no posts and caches that', function (): void {
        $this->body = 'not json';

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect($this->captured['data'])->toBe([])
            ->and($this->cacheSets[0][1])->toBe([]);
    });

    it('treats a 404 as no posts and caches nothing', function (): void {
        Functions\when('wp_remote_retrieve_response_code')->justReturn(404);

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect($this->captured['data'])->toBe([])
            ->and($this->bodyRead)->toBeFalse()
            ->and($this->cacheSets)->toBe([]);
    });

    it('treats a request error as no posts', function (): void {
        Functions\when('is_wp_error')->justReturn(true);

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect($this->captured['data'])->toBe([])
            ->and($this->bodyRead)->toBeFalse()
            ->and($this->cacheSets)->toBe([]);
    });

    it('uses cached posts for a remote site without a request', function (): void {
        $cached = [100 => (object) ['ID' => 7]];
        $this->cached = static fn ($key, $group) => $group === 'uamswp_news' ? $cached : false;

        $this->news->display_shortcode(['host' => 'news.uams.edu']);

        expect($this->remoteUrl)->toBeNull()
            ->and($this->captured['data'])->toBe($cached);
    });

    it('skips the remote request and currently warns on an undefined variable when local_only is set (#65)', function (): void {
        [, $warnings] = newsCaptureWarnings(
            fn () => $this->news->display_shortcode(['host' => 'news.uams.edu', 'local_only' => '1'])
        );

        expect($warnings)->toBe(['Undefined variable $new_data'])
            ->and($this->remoteUrl)->toBeNull()
            ->and($this->restUrls)->toBe(['https://this.test/wp-json/wp/v2/posts?per_page=0&_embed='])
            ->and($this->captured['data'])->toBe([]);
    });

    it('reads this site through rest_do_request(), cached under the URL and last-changed key', function () use ($ids): void {
        $url = 'https://this.test/wp-json/wp/v2/posts?_embed=';
        $this->restResponse = static fn () => newsResponse(200, [newsLocalPost()]);

        $this->news->display_shortcode(['host' => 'this.test']);

        expect($this->remoteUrl)->toBeNull()
            ->and($this->restUrls)->toBe([$url])
            ->and($this->cacheGets)->toBe([[md5($url).':lc1', 'uamswp-news']])
            ->and($this->cacheSets)->toHaveCount(1)
            ->and($this->cacheSets[0][0])->toBe(md5($url).':lc1')
            ->and($this->cacheSets[0][2])->toBe('uamswp-news')
            ->and($ids($this->captured['data']))->toBe([21]);
    });

    it('does not read or write the cache for this site when the visitor is logged in', function () use ($ids): void {
        Functions\when('is_user_logged_in')->justReturn(true);
        $url = 'https://this.test/wp-json/wp/v2/posts?_embed=';
        $this->cached = static fn () => [5 => (object) ['ID' => 8]];
        $this->restResponse = static fn () => newsResponse(200, [newsLocalPost()]);

        $this->news->display_shortcode(['host' => 'this.test']);

        expect($this->restUrls)->toBe([$url])
            ->and($this->cacheGets)->toBe([])
            ->and($this->cacheSets)->toBe([])
            ->and($ids($this->captured['data']))->toBe([21]);
    });

    it('does not read or write the merged local cache when the visitor is logged in', function (): void {
        Functions\when('is_user_logged_in')->justReturn(true);
        $this->body = (string) json_encode([newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00'])]);
        $this->restResponse = static fn () => newsResponse(200, [newsLocalPost(['id' => 2, 'date' => '2024-02-01T00:00:00'])]);

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1']);

        expect($this->restUrls)->toHaveCount(1)
            ->and(array_filter($this->cacheGets, static fn ($get) => $get[1] === 'uamswp-news'))->toBe([])
            ->and(array_filter($this->cacheSets, static fn ($set) => $set[2] === 'uamswp-news'))->toBe([]);
    });

    it('caches false for this site when the local request does not answer 200', function (): void {
        $this->restResponse = static fn () => newsResponse(404, null);

        $this->news->display_shortcode(['host' => 'this.test']);

        expect($this->cacheSets)->toHaveCount(1)
            ->and($this->cacheSets[0][1])->toBeFalse()
            ->and($this->captured['data'])->toBe([]);
    });

    it('uses cached posts for this site without a request', function (): void {
        $cached = [5 => (object) ['ID' => 8]];
        $this->cached = static fn () => $cached;

        $this->news->display_shortcode(['host' => 'this.test']);

        expect($this->restUrls)->toBe([])
            ->and($this->cacheSets)->toBe([])
            ->and($this->captured['data'])->toBe($cached);
    });

    it('merges this site posts into remote ones when local is set', function () use ($ids): void {
        $this->body = (string) json_encode([newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00'])]);
        $this->restResponse = static fn () => newsResponse(200, [newsLocalPost(['id' => 2, 'date' => '2024-02-01T00:00:00'])]);

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1', 'count' => '5']);

        expect($this->restUrls)->toBe(['https://this.test/wp-json/wp/v2/posts?per_page=5&_embed='])
            ->and($ids($this->captured['data']))->toBe([2, 1]);
    });

    it('keeps a remote post over a local one with the same timestamp', function () use ($ids): void {
        $this->body = (string) json_encode([newsRemotePost(['id' => 1, 'date' => '2024-01-01T00:00:00'])]);
        $this->restResponse = static fn () => newsResponse(200, [newsLocalPost(['id' => 2, 'date' => '2024-01-01T00:00:00'])]);

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1']);

        expect($ids($this->captured['data']))->toBe([1]);
    });

    it('asks this site for at most 100 posts when merging', function (string $count, string $perPage): void {
        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1', 'count' => $count]);

        expect($this->restUrls)->toBe(["https://this.test/wp-json/wp/v2/posts?per_page={$perPage}&_embed="]);
    })->with([
        '150' => ['150', '100'],
        '101' => ['101', '100'],
        '100' => ['100', '100'],
    ]);

    it('caches the merged local result under the local URL key', function (): void {
        $url = 'https://this.test/wp-json/wp/v2/posts?per_page=0&_embed=';

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1']);

        expect($this->cacheGets)->toContain([md5($url).':lc1', 'uamswp-news'])
            ->and(array_column($this->cacheSets, 0))->toContain(md5($url).':lc1');
    });

    it('passes local_ attributes, without the prefix, to the local request', function (): void {
        $this->news->local_extended_atts = ['extra' => 'remote'];
        $seen = [];
        Filters\expectApplied('uamswp_news_syndicate_taxonomy_filters')->andReturnUsing(
            function ($url, $atts) use (&$seen) {
                $seen[] = [parse_url($url, PHP_URL_HOST), $atts['extra'], $atts['host']];

                return $url;
            }
        );

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1', 'local_extra' => 'local']);

        expect($seen)->toBe([
            ['news.uams.edu', 'remote', 'news.uams.edu'],
            ['this.test', 'local', 'this.test/'],
        ]);
    });

    it('caches an empty local result when the local request fails while merging', function (): void {
        $this->restResponse = static fn () => newsResponse(500, null);

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1']);

        $local = array_values(array_filter($this->cacheSets, static fn ($set) => $set[2] === 'uamswp-news'));
        expect($local)->toHaveCount(1)
            ->and($local[0][1])->toBe([])
            ->and($this->captured['data'])->toBe([]);
    });

    it('uses cached local posts when merging', function (): void {
        $cachedLocal = [50 => (object) ['ID' => 9]];
        $this->cached = static fn ($key, $group) => $group === 'uamswp-news' ? $cachedLocal : false;

        $this->news->display_shortcode(['host' => 'news.uams.edu', 'local' => '1']);

        expect($this->restUrls)->toBe([])
            ->and($this->captured['data'])->toBe($cachedLocal);
    });

    it('returns the output filter content when it is not false', function (): void {
        expect($this->news->display_shortcode(['host' => 'news.uams.edu']))->toBe('OUTPUT');
    });
});

describe('process_remote_posts()', function (): void {
    it('returns no posts for empty data', function (): void {
        expect($this->news->process_remote_posts([], ['output' => 'json']))->toBe([])
            ->and($this->news->process_remote_posts(null, ['output' => 'json']))->toBe([]);
    });

    it('keeps only link, date and title for headlines, keyed by timestamp', function (): void {
        $result = $this->news->process_remote_posts([newsRemotePost()], ['output' => 'headlines']);

        expect(array_keys($result))->toBe([strtotime('2024-05-01T10:00:00')])
            ->and((array) $result[strtotime('2024-05-01T10:00:00')])->toBe([
                'link' => 'https://news.uams.edu/a/',
                'date' => '2024-05-01T10:00:00',
                'title' => 'A title',
            ]);
    });

    it('maps a post without a featured image', function (): void {
        $result = $this->news->process_remote_posts([newsRemotePost()], ['output' => 'json']);

        expect((array) current($result))->toBe([
            'ID' => 11,
            'date' => '2024-05-01T10:00:00',
            'link' => 'https://news.uams.edu/a/',
            'modified' => '2024-05-02T10:00:00',
            'title' => 'A title',
            'content' => '<p>Content</p>',
            'excerpt' => '<p>Excerpt</p>',
            'thumbnail' => false,
            'author_name' => '',
            'terms' => [3, 4],
        ]);
    });

    it('adds the full content for the full output only', function (): void {
        $full = current($this->news->process_remote_posts([newsRemotePost()], ['output' => 'full']));
        $cards = current($this->news->process_remote_posts([newsRemotePost()], ['output' => 'cards']));

        expect($full->fullcontent)->toBe('<p>Full</p>')
            ->and(property_exists($cards, 'fullcontent'))->toBeFalse();
    });

    it('ignores embedded media when featured_media is empty', function (): void {
        $post = newsRemotePost(array_merge(newsRemoteMedia(['post-thumbnail']), ['featured_media' => 0]));

        expect(current($this->news->process_remote_posts([$post], ['output' => 'json']))->thumbnail)->toBeFalse();
    });

    it('ignores featured_media with no embedded media', function (): void {
        $post = newsRemotePost(['featured_media' => 9, '_embedded' => ['wp:featuredmedia' => []]]);

        expect(current($this->news->process_remote_posts([$post], ['output' => 'json']))->thumbnail)->toBeFalse();
    });

    it('picks the thumbnail from post-thumbnail, then thumbnail, then the original', function (array $sizes, string $expected): void {
        $subset = current($this->news->process_remote_posts([newsRemotePost(newsRemoteMedia($sizes))], ['output' => 'json']));

        expect($subset->thumbnail)->toBe($expected)
            ->and($subset->thumbalt)->toBe('Alt')
            ->and($subset->thumbcaption)->toBe('Caption');
    })->with([
        'post-thumbnail first' => [['post-thumbnail', 'thumbnail'], 'https://img.test/post-thumbnail.jpg'],
        'thumbnail' => [['thumbnail'], 'https://img.test/thumbnail.jpg'],
        'original' => [[], 'https://img.test/original.jpg'],
    ]);

    it('picks the image and small image by size preference', function (array $sizes, mixed $image, ?string $small): void {
        $subset = current($this->news->process_remote_posts([newsRemotePost(newsRemoteMedia($sizes))], ['output' => 'json']));

        expect($subset->image)->toBe($image)
            ->and($subset->image_sm ?? null)->toBe($small);
        if ($image !== false) {
            expect($subset->imagealt)->toBe('Alt')
                ->and($subset->imagecaption)->toBe('Caption');
        }
    })->with([
        '16:9 with small' => [['aspect-16-9', 'aspect-16-9-small', 'news-full'], 'https://img.test/aspect-16-9.jpg', 'https://img.test/aspect-16-9-small.jpg'],
        '16:9 alone' => [['aspect-16-9'], 'https://img.test/aspect-16-9.jpg', null],
        '16:9 small alone' => [['aspect-16-9-small', 'news-full'], 'https://img.test/aspect-16-9-small.jpg', 'https://img.test/aspect-16-9-small.jpg'],
        'news-full with uams_news' => [['news-full', 'uams_news'], 'https://img.test/news-full.jpg', 'https://img.test/uams_news.jpg'],
        'news-full alone' => [['news-full'], 'https://img.test/news-full.jpg', null],
        'uams_news alone' => [['uams_news'], 'https://img.test/uams_news.jpg', null],
        'none of them' => [['thumbnail'], false, null],
    ]);

    it('takes the first embedded author name', function (): void {
        $post = newsRemotePost(newsRemoteMedia([], [['name' => 'First'], ['name' => 'Second']]));

        expect(current($this->news->process_remote_posts([$post], ['output' => 'json']))->author_name)->toBe('First');
    });

    it('leaves the author name empty when no author is embedded', function (): void {
        $post = newsRemotePost(newsRemoteMedia([], []));

        expect(current($this->news->process_remote_posts([$post], ['output' => 'json']))->author_name)->toBe('');
    });

    it('applies the host data filter to each subset with the post and attributes', function (): void {
        $post = newsRemotePost();
        $atts = ['output' => 'headlines'];
        Filters\expectApplied('uams_news_syndication_host_data')
            ->once()
            ->with(Mockery::type(stdClass::class), $post, $atts)
            ->andReturn((object) ['filtered' => true]);

        expect(current($this->news->process_remote_posts([$post], $atts)))->toEqual((object) ['filtered' => true]);
    });

    it('moves a post with the same timestamp to the next free second', function (): void {
        $result = $this->news->process_remote_posts(
            [newsRemotePost(['id' => 1]), newsRemotePost(['id' => 2]), newsRemotePost(['id' => 3])],
            ['output' => 'json']
        );
        $base = strtotime('2024-05-01T10:00:00');

        expect(array_keys($result))->toBe([$base, $base + 1, $base + 2])
            ->and(array_map(static fn ($post) => $post->ID, array_values($result)))->toBe([1, 2, 3]);
    });

    it('keys a post with no date by the current time', function (): void {
        $before = time();
        $result = $this->news->process_remote_posts([newsRemotePost(['date' => ''])], ['output' => 'headlines']);
        $key = array_key_first($result);

        expect($key)->toBeGreaterThanOrEqual($before)->toBeLessThanOrEqual(time());
    });
});

describe('process_local_posts()', function (): void {
    it('returns no posts for empty data', function (): void {
        expect($this->news->process_local_posts([], ['output' => 'json']))->toBe([]);
    });

    it('keeps only ID, link, date and title for headlines', function (): void {
        $result = $this->news->process_local_posts([newsLocalPost()], ['output' => 'headlines']);

        expect((array) $result[strtotime('2024-06-01T10:00:00')])->toBe([
            'ID' => 21,
            'link' => 'https://this.test/b/',
            'date' => '2024-06-01T10:00:00',
            'title' => 'B title',
        ]);
    });

    it('maps a post without media or author', function (): void {
        Functions\expect('rest_do_request')->never();

        expect((array) current($this->news->process_local_posts([newsLocalPost()], ['output' => 'json'])))->toBe([
            'ID' => 21,
            'date' => '2024-06-01T10:00:00',
            'link' => 'https://this.test/b/',
            'modified' => '2024-06-02T10:00:00',
            'title' => 'B title',
            'content' => '<p>B content</p>',
            'excerpt' => '<p>B excerpt</p>',
            'thumbnail' => false,
            'author_name' => '',
            'terms' => [5],
        ]);
    });

    it('adds the full content for the full output only', function (): void {
        $full = current($this->news->process_local_posts([newsLocalPost()], ['output' => 'full']));
        $grid = current($this->news->process_local_posts([newsLocalPost()], ['output' => 'grid']));

        expect($full->fullcontent)->toBe('<p>B full</p>')
            ->and(property_exists($grid, 'fullcontent'))->toBeFalse();
    });

    it('fetches media and author when both ids and links are present', function (): void {
        newsStubLocalMedia(['post-thumbnail', 'aspect-16-9', 'aspect-16-9-small']);

        $subset = current($this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json']));

        expect($subset->thumbnail)->toBe('https://img.test/local-post-thumbnail.jpg')
            ->and($subset->image)->toBe('https://img.test/local-aspect-16-9.jpg')
            ->and($subset->image_sm)->toBe('https://img.test/local-aspect-16-9-small.jpg')
            ->and($subset->imagealt)->toBe('Local alt')
            ->and($subset->imagecaption)->toBe('Local caption')
            ->and($subset->author_name)->toBe('Bea Author');
    });

    it('skips the media request when featured_media is empty or has no link', function (Closure $change): void {
        newsStubLocalMedia([]);
        $post = newsLocalPost(newsLocalLinks());
        $post = $change($post);

        expect(current($this->news->process_local_posts([$post], ['output' => 'json']))->thumbnail)->toBeFalse();
    })->with([
        'no id' => [static function (array $post): array {
            $post['featured_media'] = 0;

            return $post;
        }],
        'no link' => [static function (array $post): array {
            $post['_links']['wp:featuredmedia'] = [];

            return $post;
        }],
    ]);

    it('skips the author request when the author id or link is missing', function (Closure $change): void {
        newsStubLocalMedia(['post-thumbnail', 'aspect-16-9']);
        $post = newsLocalPost(newsLocalLinks());
        $post = $change($post);

        expect(current($this->news->process_local_posts([$post], ['output' => 'json']))->author_name)->toBe('');
    })->with([
        'no id' => [static function (array $post): array {
            $post['author'] = 0;

            return $post;
        }],
        'no link' => [static function (array $post): array {
            $post['_links']['author'] = [];

            return $post;
        }],
    ]);

    it('leaves the author name empty when the author response has no name', function (): void {
        newsStubLocalMedia(['post-thumbnail', 'aspect-16-9'], null);

        expect(current($this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json']))->author_name)
            ->toBe('');
    });

    it('uses the thumbnail size when there is no post-thumbnail size (#64)', function (): void {
        newsStubLocalMedia(['thumbnail', 'aspect-16-9']);

        $subset = current($this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json']));

        expect($subset->thumbnail)->toBe('https://img.test/local-thumbnail.jpg');
    });

    it('uses the 16:9 size without a small image when the small size is absent', function (): void {
        newsStubLocalMedia(['post-thumbnail', 'aspect-16-9']);

        $subset = current($this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json']));

        expect($subset->image)->toBe('https://img.test/local-aspect-16-9.jpg')
            ->and(property_exists($subset, 'image_sm'))->toBeFalse();
    });

    it('uses the small 16:9 size for both images when only it exists', function (): void {
        newsStubLocalMedia(['post-thumbnail', 'aspect-16-9-small']);

        $subset = current($this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json']));

        expect($subset->image)->toBe('https://img.test/local-aspect-16-9-small.jpg')
            ->and($subset->image_sm)->toBe('https://img.test/local-aspect-16-9-small.jpg')
            ->and($subset->imagealt)->toBe('Local alt')
            ->and($subset->imagecaption)->toBe('Local caption');
    });

    it('uses large for both images, with no warning, when neither 16:9 size exists (#64)', function (): void {
        newsStubLocalMedia(['post-thumbnail', 'large']);

        [$result, $warnings] = newsCaptureWarnings(
            fn () => $this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json'])
        );
        $subset = current($result);

        expect($warnings)->toBe([])
            ->and($subset->image)->toBe('https://img.test/local-large.jpg')
            ->and($subset->image_sm)->toBe('https://img.test/local-large.jpg')
            ->and($subset->imagealt)->toBe('Local alt')
            ->and($subset->imagecaption)->toBe('Local caption');
    });

    it('sets no image, with no warning, when no image size matches (#64)', function (): void {
        newsStubLocalMedia(['post-thumbnail']);

        [$result, $warnings] = newsCaptureWarnings(
            fn () => $this->news->process_local_posts([newsLocalPost(newsLocalLinks())], ['output' => 'json'])
        );

        expect($warnings)->toBe([])
            ->and(current($result)->image)->toBeFalse();
    });

    it('applies the host data filter to each local subset', function (): void {
        $post = newsLocalPost();
        Filters\expectApplied('uams_news_syndication_host_data')
            ->once()
            ->with(Mockery::type(stdClass::class), $post, ['output' => 'headlines'])
            ->andReturn((object) ['filtered' => true]);

        expect(current($this->news->process_local_posts([$post], ['output' => 'headlines'])))->toEqual((object) ['filtered' => true]);
    });

    it('moves a local post with the same timestamp to the next free second', function (): void {
        $result = $this->news->process_local_posts(
            [newsLocalPost(['id' => 1]), newsLocalPost(['id' => 2])],
            ['output' => 'headlines']
        );
        $base = strtotime('2024-06-01T10:00:00');

        expect(array_keys($result))->toBe([$base, $base + 1]);
    });

    it('keys a local post with no date by the current time', function (): void {
        $before = time();
        $key = array_key_first($this->news->process_local_posts([newsLocalPost(['date' => ''])], ['output' => 'headlines']));

        expect($key)->toBeGreaterThanOrEqual($before)->toBeLessThanOrEqual(time());
    });
});

describe('generate_shortcode_output()', function (): void {
    beforeEach(function (): void {
        Functions\when('esc_url')->alias(static fn ($url) => htmlspecialchars((string) $url, ENT_QUOTES));
        Functions\when('esc_attr')->alias(static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES));
        Functions\when('esc_html')->alias(static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES));
        Functions\when('wp_kses_post')->alias(static fn ($text) => preg_replace('#<script.*?</script>#is', '', (string) $text));
        Functions\when('absint')->alias(static fn ($value) => abs((int) $value));
        $this->render = function (array $atts, array $post): string {
            $method = new ReflectionMethod(UAMS_Syndicate_News::class, 'generate_shortcode_output');
            $atts += [
                'output' => 'full', 'style' => '', 'news_title' => '', 'offset' => 0, 'hide_author' => 0,
                'date_format' => 'F j, Y',
            ];
            $content = (object) ($post + [
                'link' => 'https://news.uams.edu/a/', 'title' => 'A title', 'content' => 'Content',
                'fullcontent' => '', 'image' => '', 'imagealt' => '', 'imagecaption' => '',
                'author_name' => 'Ann', 'date' => '2024-05-01T10:00:00', 'modified' => '2024-05-02T10:00:00',
            ]);

            return (string) $method->invoke(new UAMS_Syndicate_News, [$content], $atts);
        };
    });

    it('escapes the featured image alt text in the full output', function (): void {
        $html = ($this->render)([], ['image' => 'https://news.uams.edu/i.jpg', 'imagealt' => '"><script>alert(1)</script>']);

        expect($html)->toContain('alt="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"')
            ->and($html)->not->toContain('<script>alert(1)');
    });

    it('filters the featured image caption in the full output', function (): void {
        $html = ($this->render)([], ['imagecaption' => 'Caption<script>alert(1)</script>']);

        expect($html)->toContain('<div class="wp-caption-text">Caption</div>');
    });

    it('does not run shortcodes on fetched content in the full output', function (): void {
        Functions\expect('do_shortcode')->never();

        $html = ($this->render)([], ['fullcontent' => '[dangerous]', 'content' => '[other]']);

        expect($html)->toContain('[dangerous]')
            ->and($html)->not->toContain('[other]');

        $html = ($this->render)([], ['fullcontent' => '', 'content' => '[other]']);

        expect($html)->toContain('[other]');
    });

    it('escapes the style in the wrapper class', function (): void {
        $html = ($this->render)(['style' => 'bg-gray"x'], []);

        expect($html)->toContain('uams-module bg-gray&quot;x"');
    });
});
