<?php

/**
 * First unit test pinning harness load for UAMSWP News Syndication 2020.
 */

declare(strict_types=1);

namespace UamswpNewsSyndication\\Tests\Unit;

use UamswpNewsSyndication\\Tests\Support\UnitTestCase;

final class HarnessSmokeTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function test_harness_loads_plugin_surface(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/uamswp-news-syndication.php');
        $this->assertTrue(true);
    }
}
