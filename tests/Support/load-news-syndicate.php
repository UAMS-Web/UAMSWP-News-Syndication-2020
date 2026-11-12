<?php

/**
 * Loads includes/news-syndicate.php in a process that has not included it, and checks the one hook
 * the file registers as it loads. Run by tests/Unit/NewsBootstrapTest.php; exits 0 on success.
 */

declare(strict_types=1);

// Not tests/bootstrap.php: that loads the plugin, and with it the file under test.
require_once dirname(__DIR__, 2).'/vendor/autoload.php';
require_once dirname(__DIR__, 2).'/vendor/antecedent/patchwork/Patchwork.php';

use Brain\Monkey\Actions;

Brain\Monkey\setUp();

Actions\expectAdded('plugins_loaded')->once()->with('UAMS\News_Syndicate\bootstrap');

require_once dirname(__DIR__, 2).'/includes/news-syndicate.php';

if (! function_exists('UAMS\News_Syndicate\bootstrap')) {
    fwrite(STDERR, "bootstrap() is not declared\n");
    exit(1);
}

Brain\Monkey\tearDown();

echo 'loaded';
