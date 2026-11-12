<?php

/**
 * Characterization tests for the HTML that UAMS_Syndicate_News::generate_shortcode_output() prints.
 *
 * Every output mode is rendered across the attributes it branches on, and the markup is pinned as a
 * Pest snapshot (the `tests` skill, "Pinning legacy behavior"): a change to any of it fails the test
 * that renders it. Snapshots live under tests/.pest/snapshots. A pin of markup believed wrong says so
 * in its name and links its issue.
 *
 * WordPress functions are replaced by small deterministic stand-ins: escaping uses htmlspecialchars,
 * `wp_kses_post()` passes its argument through, and the category functions answer from the id.
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

require_once dirname(__DIR__, 2).'/includes/class-uams-news-syndicate-base.php';
require_once dirname(__DIR__, 2).'/includes/class-uams-news-syndicate-news.php';

mutates(UAMS_Syndicate_News::class);

/**
 * Stand in for the WordPress functions the templates call.
 */
function newsTemplateStubWordPress(): void
{
    Functions\when('add_shortcode')->justReturn(true);
    Functions\when('esc_html')->alias(static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES));
    Functions\when('esc_attr')->alias(static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES));
    Functions\when('esc_url')->alias(static fn ($url) => htmlspecialchars((string) $url, ENT_QUOTES));
    Functions\when('esc_js')->alias(static fn ($text) => addslashes((string) $text));
    Functions\when('wp_kses_post')->alias(static fn ($text) => (string) $text);
    Functions\when('wp_strip_all_tags')->alias(static fn ($text) => strip_tags((string) $text));
    Functions\when('wp_json_encode')->alias(static fn ($data) => json_encode($data));
    Functions\when('absint')->alias(static fn ($value) => abs((int) $value));
    Functions\when('get_home_url')->justReturn('https://this.test/');
    Functions\when('plugin_dir_url')->justReturn('https://this.test/wp-content/plugins/news/');
    Functions\when('get_category_link')->alias(static fn ($id) => 'https://this.test/category/cat-'.$id.'/');
    Functions\when('get_category')->alias(static fn ($id) => (object) ['name' => 'Category '.$id]);
}

/**
 * The attributes display_shortcode() passes: the defaults, then the overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newsTemplateAtts(string $output, array $overrides = []): array
{
    return array_merge((new UAMS_Syndicate_News)->defaults_atts, ['output' => $output], $overrides);
}

/**
 * A remote post as process_remote_posts() leaves it.
 *
 * @param  array<string, mixed>  $overrides
 */
function newsTemplatePost(int $id, array $overrides = []): object
{
    return (object) array_merge([
        'ID' => $id,
        'date' => '2024-05-0'.$id.'T10:00:00',
        'modified' => '2024-05-0'.$id.'T11:00:00',
        'link' => 'https://news.uams.edu/story-'.$id.'/',
        'title' => 'Story '.$id.' <em>title</em>',
        'content' => '<p>Content '.$id.'</p>',
        'excerpt' => '<p>Excerpt '.$id.' <a class="more" href="#">Read more</a></p>',
        'fullcontent' => '',
        'thumbnail' => 'https://news.uams.edu/thumb-'.$id.'.jpg',
        'thumbalt' => 'Thumb alt '.$id,
        'thumbcaption' => 'Thumb caption '.$id,
        'image' => 'https://news.uams.edu/image-'.$id.'.jpg',
        'imagealt' => 'Image alt '.$id,
        'imagecaption' => 'Image caption '.$id,
        'author_name' => 'Author '.$id,
        'terms' => [
            (object) ['slug' => 'alpha', 'name' => 'Alpha'],
            (object) ['slug' => 'beta', 'name' => 'Beta'],
        ],
    ], $overrides);
}

/**
 * A post from this site, whose terms are category ids.
 *
 * @param  array<string, mixed>  $overrides
 */
function newsTemplateLocalPost(int $id, array $overrides = []): object
{
    return newsTemplatePost($id, array_merge([
        'link' => 'https://this.test/story-'.$id.'/',
        'terms' => [7, 8],
    ], $overrides));
}

/**
 * Print the markup for one output mode, through the private method display_shortcode() calls.
 *
 * @param  array<int, object>  $posts
 * @param  array<string, mixed>  $atts
 */
function newsTemplateRender(array $posts, array $atts): string
{
    $method = new ReflectionMethod(UAMS_Syndicate_News::class, 'generate_shortcode_output');

    return (string) $method->invoke(new UAMS_Syndicate_News, $posts, $atts);
}

beforeEach(function (): void {
    newsTemplateStubWordPress();
    $this->timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
});

afterEach(function (): void {
    date_default_timezone_set($this->timezone);
});

/**
 * The cases every listing mode shares: what the posts are, and the attributes that change the markup.
 *
 * @return array<string, array{0: array<string, mixed>, 1: list<object>}>
 */
function newsTemplateSharedCases(): array
{
    $one = [newsTemplatePost(1)];
    $two = [newsTemplatePost(2), newsTemplatePost(1)];
    $three = [newsTemplatePost(3), newsTemplatePost(2), newsTemplatePost(1)];

    return [
        'no posts' => [[], []],
        'one post' => [[], $one],
        'three posts' => [[], $three],
        'title and style' => [['news_title' => 'Campus <b>News</b>', 'style' => 'bg-gray text-center'], $one],
        'hidden title' => [['hide_title' => 1], $one],
        'offset one' => [['offset' => 1], $three],
        'offset past all' => [['offset' => 5], $two],
        'category matched' => [['category' => 'beta'], $two],
        'category unmatched' => [['category' => 'gamma'], $one],
        'category and link' => [['category' => 'beta', 'include_link' => 1], $two],
        'link, no category' => [['include_link' => 1], $two],
        'local post' => [[], [newsTemplateLocalPost(4)]],
        'local, no terms' => [[], [newsTemplateLocalPost(4, ['terms' => []])]],
        'no terms' => [[], [newsTemplatePost(1, ['terms' => []])]],
        'no image' => [[], [newsTemplatePost(1, ['thumbnail' => false, 'image' => false])]],
        'small image' => [[], [newsTemplatePost(1, ['image_sm' => 'https://news.uams.edu/small-1.jpg'])]],
        'hidden images' => [['hide_img' => 1], $two],
        'hidden date' => [['hide_date' => 1], $one],
        'hidden author' => [['hide_author' => 1], $one],
        'date format' => [['date_format' => 'd/m/Y'], $one],
        'quotes in title' => [[], [newsTemplatePost(1, ['title' => 'He said "no"', 'author_name' => 'O\'Brien <x>'])]],
        'other host' => [['scheme' => 'http', 'host' => 'other.uams.edu', 'category' => 'alpha'], $one],
    ];
}

describe('json', function (): void {
    it('prints a JavaScript object', function (): void {
        expect(newsTemplateRender([newsTemplatePost(1)], newsTemplateAtts('json')))->toMatchSnapshot();
    });

    it('prints an empty object', function (): void {
        expect(newsTemplateRender([], newsTemplateAtts('json')))->toMatchSnapshot();
    });

    it('escapes the object name', function (): void {
        expect(newsTemplateRender([newsTemplatePost(1)], newsTemplateAtts('json', ['object' => "my'news"])))->toMatchSnapshot();
    });

    it('prints nothing for an unknown output', function (): void {
        expect(newsTemplateRender([newsTemplatePost(1)], newsTemplateAtts('unknown')))->toBe('');
    });
});

describe('headlines', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('headlines', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases());
});

describe('excerpts', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('excerpts', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases());
});

describe('cards', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('cards', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases());
});

describe('grid', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('grid', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases());

    it('prints the first post as the featured item and the rest as smaller ones', function (): void {
        $html = newsTemplateRender(
            [newsTemplatePost(3), newsTemplatePost(2), newsTemplatePost(1)],
            newsTemplateAtts('grid')
        );

        expect(substr_count($html, 'featured'))->toBe(1)
            ->and($html)->toContain('Story 3');
    });
});

describe('side', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('side', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases() + [
        'image right' => [['news_position' => 'right'], [newsTemplatePost(1)]],
        'no position' => [['news_position' => ''], [newsTemplatePost(1)]],
        'no image alt' => [[], [(function (): object {
            $post = newsTemplatePost(1);
            unset($post->imagealt);

            return $post;
        })()]],
        'empty image' => [[], [newsTemplatePost(1, ['image' => ''])]],
    ]);
});

describe('full', function (): void {
    it('renders', function (array $overrides, array $posts): void {
        expect(newsTemplateRender($posts, newsTemplateAtts('full', $overrides)))->toMatchSnapshot();
    })->with(newsTemplateSharedCases() + [
        'full content' => [[], [newsTemplatePost(1, ['fullcontent' => '<p>The full story</p>'])]],
        'empty author' => [[], [newsTemplatePost(1, ['author_name' => ''])]],
        'with caption' => [[], [newsTemplatePost(1, ['imagecaption' => '<p>A caption</p>'])]],
        'no caption' => [[], [newsTemplatePost(1, ['imagecaption' => ''])]],
    ]);
});
