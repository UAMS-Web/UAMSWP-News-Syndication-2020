<?php

/**
 * Characterization tests for includes/news-syndicate.php, the plugin bootstrap.
 *
 * The file registers its `plugins_loaded` callback as it loads, so the load itself is tested in a
 * child process (tests/Support/load-news-syndicate.php), because tests/bootstrap.php has already
 * loaded it in this one. The three functions are tested in this process.
 */

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

mutates(UAMS\News_Syndicate\bootstrap::class, UAMS\News_Syndicate\activate_shortcodes::class, UAMS\News_Syndicate\clear_local_news_cache::class);

/**
 * Include the bootstrap file (a no-op once tests/bootstrap.php has loaded it).
 */
function newsBootstrapLoad(): void
{
    require_once dirname(__DIR__, 2).'/includes/news-syndicate.php';
}

it('registers bootstrap() on plugins_loaded as the file loads', function (): void {
    $script = dirname(__DIR__).'/Support/load-news-syndicate.php';
    $output = [];
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' 2>&1', $output, $status);

    expect($status)->toBe(0, implode(PHP_EOL, $output))
        ->and(implode(PHP_EOL, $output))->toBe('loaded');
});

describe('bootstrap()', function (): void {
    it('loads the base class', function (): void {
        newsBootstrapLoad();
        UAMS\News_Syndicate\bootstrap();

        expect(class_exists('UAMS_Syndicate_News_Base', false))->toBeTrue();
    });

    it('registers the shortcodes on init and the cache clearing on both save hooks', function (): void {
        newsBootstrapLoad();

        Actions\expectAdded('init')->once()->with('UAMS\News_Syndicate\activate_shortcodes');
        Actions\expectAdded('save_post_post')->once()->with('UAMS\News_Syndicate\clear_local_news_cache');
        Actions\expectAdded('save_post_page')->once()->with('UAMS\News_Syndicate\clear_local_news_cache');

        UAMS\News_Syndicate\bootstrap();
    });

    it('registers only those three hooks', function (): void {
        newsBootstrapLoad();
        UAMS\News_Syndicate\bootstrap();

        expect(has_action('init', 'UAMS\News_Syndicate\activate_shortcodes'))->not->toBeFalse()
            ->and(has_action('save_post_post', 'UAMS\News_Syndicate\clear_local_news_cache'))->not->toBeFalse()
            ->and(has_action('save_post_page', 'UAMS\News_Syndicate\clear_local_news_cache'))->not->toBeFalse()
            ->and(has_action('save_post_attachment', 'UAMS\News_Syndicate\clear_local_news_cache'))->toBeFalse();
    });
});

describe('activate_shortcodes()', function (): void {
    it('registers the uamswp_news shortcode', function (): void {
        newsBootstrapLoad();
        Functions\expect('add_shortcode')
            ->once()
            ->with('uamswp_news', Mockery::type('array'));

        UAMS\News_Syndicate\activate_shortcodes();
    });

    it('fires the uamswp_news_syndicate_shortcodes action once, after the news class is available', function (): void {
        newsBootstrapLoad();
        Functions\when('add_shortcode')->justReturn(true);
        Actions\expectDone('uamswp_news_syndicate_shortcodes')->once()->whenHappen(function (): void {
            expect(class_exists('UAMS_Syndicate_News', false))->toBeTrue();
        });

        UAMS\News_Syndicate\activate_shortcodes();
    });
});

describe('clear_local_news_cache()', function (): void {
    it('stamps last_changed in the uamswp-news cache group', function (): void {
        newsBootstrapLoad();
        Functions\expect('wp_cache_set')
            ->once()
            ->with('last_changed', Mockery::pattern('/^0\.\d+ \d+$/'), 'uamswp-news');

        UAMS\News_Syndicate\clear_local_news_cache();
    });
});
