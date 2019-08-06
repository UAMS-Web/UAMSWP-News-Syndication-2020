<?php

namespace UAMS\News_Syndicate;

add_action( 'plugins_loaded', 'UAMS\News_Syndicate\bootstrap' );

/**
 * Loads the UAMSWP News Syndicate base.
 *
 * @since 1.0.0
 */
function bootstrap() {
	include_once __DIR__ . '/class-uams-news-syndicate-base.php';

	add_action( 'init', 'UAMS\News_Syndicate\activate_shortcodes' );
	add_action( 'save_post_post', 'UAMS\News_Syndicate\clear_local_news_cache' );
	add_action( 'save_post_page', 'UAMS\News_Syndicate\clear_local_news_cache' );
}

/**
 * Activates the shortcodes built in with UAMSWP News Syndicate.
 *
 * @since 1.0.0
 */
function activate_shortcodes() {
	include_once dirname( __FILE__ ) . '/class-uams-news-syndicate-news.php';

	// Add the [uamswp_news] shortcode to pull standard post news.
	new \UAMS_Syndicate_News();

	do_action( 'uamswp_news_syndicate_shortcodes' );
}

/**
 * Clear the last changed cache for local results whenever
 * a post is saved.
 *
 * @since 1.4.0
 */
function clear_local_news_cache() {
	wp_cache_set( 'last_changed', microtime(), 'uamswp-news' );
}