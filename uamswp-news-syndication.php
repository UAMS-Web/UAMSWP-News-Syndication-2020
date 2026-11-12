<?php

declare(strict_types=1);
/*
Plugin Name: UAMSWP News Syndication (2020)
Plugin URI: https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020
Description: News Syndication plugin for uams.edu & uamshealth.com (2020 version)
Author: UAMS Web Services
Author URI: https://communications.uams.edu/web/
Version: 1.0.4
Requires PHP: 8.2
*/

// If this file is called directly, abort.
if (! defined('WPINC')) {
    exit;
}

define('UAMS_NEWS_ROOT_URL', plugin_dir_url(__FILE__));
define('UAMS_NEWS_PATH', plugin_dir_path(__FILE__));
$plugin_header = get_file_data(
    __FILE__,
    [
        'version' => 'Version',
    ]
);
$plugin_version = $plugin_header['version'];
define('UAMS_NEWS_VERSION', $plugin_version);
include_once __DIR__.'/includes/news-syndicate.php';

// Registered here and not in includes/news-syndicate.php: a statement at the top level of that file runs at
// load, before any test can cover it, so a mutation run reported its mutant as uncovered.
add_action('plugins_loaded', 'UAMS\News_Syndicate\bootstrap');
