<?php
/*
Plugin Name: UAMSWP News Syndication (2020)
Plugin URI: -
Description: News Syndication plugin for uams.edu & uamshealth.com (2020 version)
Author: uams, Todd McKee, MEd
Author URI: http://www.uams.edu/
Version: 1.0.4
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This plugin uses namespaces and requires PHP 5.3 or greater.
define('UAMS_NEWS_ROOT_URL', plugin_dir_url(__FILE__));
define('UAMS_NEWS_PATH', plugin_dir_path(__FILE__));
$plugin_header  = get_file_data(
		__FILE__,
		[
			'version' => 'Version',
		]
	);
$plugin_version = $plugin_header['version'];
define('UAMS_NEWS_VERSION', $plugin_version);
include_once __DIR__ . '/includes/news-syndicate.php';
