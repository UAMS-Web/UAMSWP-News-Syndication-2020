<?php

/**
 * First unit test pinning harness load for UAMSWP News Syndication 2020.
 */

declare(strict_types=1);

it('loads the plugin surface', function (): void {
    expect(dirname(__DIR__, 2).'/uamswp-news-syndication.php')->toBeFile();
});
