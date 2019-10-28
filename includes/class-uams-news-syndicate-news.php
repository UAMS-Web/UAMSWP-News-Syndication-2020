<?php

class UAMS_Syndicate_News extends UAMS_Syndicate_News_Base {

	/**
	 * @var string Shortcode name.
	 */
	public $shortcode_name = 'uamswp_news';
	public function __construct() {
		parent::construct();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_news_syndication_stylesheet' ) );
		if ( class_exists('UAMS_Shortcakes') ) {
			add_action( 'admin_init', array( $this, 'build_shortcake' ) );
			add_editor_style( plugins_url( '/css/uams-news-syndication-admin.css', __DIR__ ) );
			add_action( 'enqueue_shortcode_ui', function() {
				wp_enqueue_script( 'uams_news_syndications_editor_js', plugins_url( '/js/uams-news-syndication-shortcake.js', __DIR__ ) );
			});
		}
		// if (function_exists('register_block_type')) {
		// 	wp_enqueue_script( 'uams_news_syndications_block_js',
		// 		plugins_url( '/block/blocks.build.js', __DIR__ ),
		// 		array(
		// 			'wp-blocks',
		// 			'wp-i18n',
		// 			'wp-element',
		// 			'wp-components'
		// 		)
		// 	);
		// 	add_action( 'init', array( $this, 'build_block' ) );
		// }
		// }
		// add_action( 'acf/init', array( $this, 'uamswp_news_register_blocks'));
		// add_action( 'admin_init', array( $this, 'uamswp_news_register_acf') );
		//add_action( 'admin_init', array( $this, 'enqueue_news_syndication_stylesheet_admin' ) );
	}
	/**
	 * Add the shortcode provided.
	 */
	public function add_shortcode() {
		add_shortcode( 'uamswp_news', array( $this, 'display_shortcode' ) );
	}

	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	public function enqueue_news_syndication_stylesheet() {
		$post = get_post();
	 	if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'uamswp_news' ) ) {
			wp_enqueue_style( 'uamswp-syndication-news-style', plugins_url( '/css/uamswp-news-syndication-news.css', __DIR__ ), array(), '' );
		}
	}

	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	// public function enqueue_news_syndication_stylesheet_admin() {
	// 	add_editor_style( 'uamswp-syndication-news-style-admin', plugins_url( '/css/uamswp-news-syndication-admin.css', __DIR__ ), array(), '' );
	// }
	public function build_shortcake() {
		shortcode_ui_register_for_shortcode(

			/** Your shortcode handle */
			'uamswp_news',

			/** Your Shortcode label and icon */
			array(

			/** Label for your shortcode user interface. This part is required. */
			'label' => esc_html__('News Syndication', 'uamswp_news'),

			/** Icon or an image attachment for shortcode. Optional. src or dashicons-$icon.  */
			'listItemImage' => 'dashicons-rss',

			/** Shortcode Attributes */
			'attrs'          => array(

				/** Output format */
				array(
				'label'     => esc_html__('Format', 'uamswp_news'),
				'attr'      => 'output',
				'type'      => 'radio',
				    'options' => array(
				        'headlines'      => 'Headline',
				        'excerpts'    => 'Excerpt',
				        'cards'     => 'Card',
				        'full'     => 'Full',
				    ),
				'description'  => 'Preferred output format',
				),

				array(

				/** This label will appear in user interface */
				'label'        => esc_html__('Category', 'uamswp_news'),
				'attr'         => 'category',
				'type'         => 'text',
				'description'  => 'Please enter the filter / category',
				),

				/** Count - Number of articles */
				array(
				'label'        => esc_html__('Count', 'uamswp_news'),
				'attr'         => 'count',
				'type'         => 'number',
				'description'  => 'Number of news articles to display',
				'meta'   => array(
						'placeholder' 	=> esc_html__( '1' ),
						'min'			=> '1',
						'step'			=> '1',
					),
				),

				/** Offset - Number of articles to skip */
				array(
				'label'        => esc_html__('Offset', 'uamswp_news'),
				'attr'         => 'offset',
				'type'         => 'number',
				'description'  => 'Number of news articles to skip',
				'meta'   => array(
						'placeholder' 	=> esc_html__( '0' ),
						'min'			=> '0',
						'step'			=> '1',
					),
				),

				/** ID - ID of specific of articles */
				array(
				'label'        => esc_html__('Post ID', 'uamswp_news'),
				'attr'         => 'postid',
				'type'         => 'number',
				'description'  => 'Specific ID of article',
				),

				/** Output format */
				array(
					'label'     => esc_html__('Background Color', 'uamswp_news'),
					'attr'      => 'bgCcolor',
					'type'      => 'radio',
						'options' => array(
							'bg-white'      => 'White',
							'bg-gray'    => 'Gray',
						),
					'description'  => 'Preferred background color',
					),

			),

			/** You can select which post types will show shortcode UI */
			'post_type'     => array( 'post', 'page' ),
			)
		);
	}

	// public function build_block() {
	// 	register_block_type('uamswp/uams-news', array(
	// 		'editor_script' => 'uams_syndications_block_js',
	// 		'render_callback' => 'uamswp_news_block_handler',
	// 		'attributes' => [
	// 			'output' => [
	// 				'default' => 'headline'
	// 			],
	// 			'category' => [
	// 				'default' => 'internal-news'
	// 			],
	// 			'count' => [
	// 				'default' => 3
	// 			],
	// 			'advancedCat' => [
	// 				'default' => ''
	// 			],
	// 			'local' => [
	// 				'default' => 0
	// 			],
	// 			'site' => [
	// 				'default' => ''
	// 			],
	// 			'offset' => [
	// 				'default' => 0
	// 			],
	// 			'bgcolor' => [
	// 				'default' => ''
	// 			]
	// 		]
	// 	));
	// }

	// public function uamswp_news_block_handler($atts){
	// 	return ('Works');
	// 	// return $this->display_shortcode($atts[ 'category' ], $atts[ 'count' ]);
	// 	// echo ($atts[ 'category' ] . $atts[ 'count' ]);
	// 	// return display_shortcode($atts[ 'object' ], $atts[ 'output' ], $atts[ 'host' ], $atts[ 'scheme' ], $atts[ 'category' ], $atts[ 'advanced_cat' ], $atts[ 'tag' ], $atts[ 'id' ], $atts[ 'query' ], $atts[ 'local' ], $atts[ 'offset' ]);
	// 	// return display_shortcode( $atts[ 'output' ], $atts[ 'category' ], $atts[ 'count' ], $atts[ 'advancedCat' ], $atts[ 'local' ], $atts[ 'site' ], $atts[ 'offset' ], $atts[ 'bgColor' ]);
	// }

	/**
	 * Process the requested parameters for use with the WordPress JSON API and output
	 * the response accordingly.
	 *
	 * @param array $atts {
	 *     Attributes passed with the shortcode.
	 *
	 *     @type string $object                   The name of the JSON object to use when output is set to json.
	 *     @type string $output                   The type of output to display.
	 *                              - json           Output a JSON object to be used with custom Javascript.
	 *                              - headlines      Display an unordered list of headlines.
	 *                              - excerpts       Display only excerpt information in an unordered list.
	 *                              - cards       	 Display information in a card format.
	 * 								- grid       	 Display information in a 1 x 2 grid format.
	 * 								- side       	 Display information in a Side-by-Side Image & Text format.
	 *                              - full           Display full content for each item.
	 *     @type string $host                     The hostname to pull items from. Defaults to uamshealth.com.
	 *     @type string $site                     Overrides setting for host. Hostname and path to pull items from.
	 *     @type string $category       		  The slug of a Site Category. Defaults to empty.
	 *     @type string $advanced_cat       	  The ids of a Site Category, including exclusions (-id). Defaults to empty.
	 *     @type string $tag                      The slug of a tag. Defaults to empty.
	 *     @type string $id                       The id of post. Defaults to empty.
	 *     @type string $style                    Adds additional styles to the wrapper. Defaults to empty.
	 *     @type string $query                    Allows for a custom WP-API query. Defaults as "posts". Any
	 *     @type int    $local             		  The number of local items to merge with the remote results.
	 *     @type int    $count                    The number of items to pull from a feed. Defaults to the
	 *                                            posts_per_page setting of the remote site.
	 *     @type string $date_format              PHP Date format for the output of the item's date.
	 *     @type int    $offset                   The number of items to offset when displaying. Used with multiple
	 *                                            shortcode instances where one may pull in an excerpt and another
	 *                                            may pull in the rest of the feed as headlines.
	 *     @type string $cache_bust (Depreciated) Any change to this value will clear the cache and pull fresh data.
	 * 	   @type int	$include_link			  Option to include link to category. Optional
	 * 	   @type string $news_position			  The image position for side-by-side.
	 * }
	 *
	 * @return string Data to output where the shortcode is used.
	 */
	public function display_shortcode( $atts ) {
		$atts = $this->process_attributes( $atts );

		$site_url = $this->get_request_url( $atts );
		if ( ! $site_url ) {
			return '<!-- uamswp_news ERROR - an empty host was supplied -->';
		}

		// Retrieve existing content from cache if available.
		// $content = $this->get_content_cache( $atts, 'uamswp_news' );
		// if ( $content ) {
		// 	return apply_filters( 'uamswp_content_syndication_news', $content, $atts );
		// }

		$request = $this->build_initial_request( $site_url, $atts );
		$request_url = $this->build_taxonomy_filters( $atts, $request['url'] );

		if ( ! empty( $atts['offset'] ) ) {
			$atts['count'] = absint( $atts['count'] ) + absint( $atts['offset'] );
		}
		if ( $atts['count'] ) {
			$count = ( 100 < absint( $atts['count'] ) ) ? 100 : $atts['count'];
			$request_url = add_query_arg( array(
				'per_page' => absint( $count ),
			), $request_url );
		}
		$request_url = add_query_arg( array(
			'_embed' => '',
		), $request_url );

		if ( 'headlines' === $atts['output'] ) {
			$request_url = add_query_arg( array(
				'_fields[]' => 'title',
			), $request_url );
			$request_url = add_query_arg( array(
				'_fields[]' => 'date',
			), $request_url );
			$request_url = add_query_arg( array(
				'_fields[]' => 'link',
			), $request_url );
		}

		if ( 'local' === $request['scheme'] ) {
			$last_changed = wp_cache_get_last_changed( 'uamswp-news' );
			$cache_key = md5( $request_url ) . ':' . $last_changed;
			$new_data = wp_cache_get( $cache_key, 'uamswp-news' );
			if ( ! is_array( $new_data ) ) {
				$request = WP_REST_Request::from_url( $request_url );
				$response = rest_do_request( $request );
				if ( 200 === $response->get_status() ) {
					$new_data = $this->process_local_posts( $response->data, $atts );
				}
				wp_cache_set( $cache_key, $new_data, 'uamswp-news' );
			}
		} else {
			$new_data = $this->get_content_cache( $atts, 'uamswp_news' );
			if ( ! is_array( $new_data ) ) {
				$response = wp_remote_get( $request_url );
				if ( ! is_wp_error( $response ) && 404 !== wp_remote_retrieve_response_code( $response ) ) {
					$data = wp_remote_retrieve_body( $response );
					$data = json_decode( $data );
					if ( null === $data ) {
						$data = array();
					}
					$new_data = $this->process_remote_posts( $data, $atts );
					// Store the built content in cache for repeated use.
					$this->set_content_cache( $atts, 'uamswp_news', $new_data );
				}
			}
		}

		if ( ! is_array( $new_data ) ) {
			$new_data = array();
		}

		if ( 0 !== absint( $atts['local'] ) ) {
			$local_atts = array();
			foreach ( $atts as $attribute => $value ) {
				if ( 0 === stripos( $attribute, 'local_' ) ) {
					$local_atts[ substr( $attribute, 6 ) ] = $value;
				} else {
					$local_atts[ $attribute ] = $value;
				}
			}
			$local_atts['host'] = get_site()->domain . get_site()->path;
			$local_atts['count'] = $atts['count'];
			$local_url = $this->get_request_url( $local_atts );
			$request = $this->build_initial_request( $local_url, $local_atts );
			$request_url = $this->build_taxonomy_filters( $local_atts, $request['url'] );
			$local_count = ( 100 < absint( $local_atts['count'] ) ) ? 100 : $local_atts['count'];
			$request_url = add_query_arg( array(
				'per_page' => absint( $local_count ),
				'_embed' => '',
			), $request_url );
			$last_changed = wp_cache_get_last_changed( 'uamswp-news' );
			$cache_key = md5( $request_url ) . ':' . $last_changed;
			$local_data = wp_cache_get( $cache_key, 'uamswp-news' );
			if ( ! is_array( $local_data ) ) {
				$request = WP_REST_Request::from_url( $request_url );
				$response = rest_do_request( $request );
				$local_data = array();
				if ( 200 === $response->get_status() ) {
					$local_data = $this->process_local_posts( $response->data, $atts );
				}
				wp_cache_set( $cache_key, $local_data, 'uamswp-news' );
			}
			if ( is_array( $local_data ) ) {
				$new_data = $new_data + $local_data;
			}
		} // End if().

		// Reverse sort the array of data by date.
		krsort( $new_data );

		// Only provide a count to match the total count, the array may be larger if local
		// items are also requested.
		if ( $atts['count'] ) {
			$new_data = array_slice( $new_data, 0, $atts['count'], false );
		}

		$content = apply_filters( 'uamswp_news_syndicate_news_output', false, $new_data, $atts );
		if ( false === $content ) {
			$content = $this->generate_shortcode_output( $new_data, $atts );
		}
		$content = apply_filters( 'uamswp_news_syndicate_news', $content, $atts );
		return $content;

	}

	/**
	 * Generates the content to display for a shortcode.
	 *
	 * @since 1.1.0
	 *
	 * @param array $new_data Data containing the posts to be displayed.
	 * @param array $atts     Array of options passed with the shortcode.
	 *
	 * @return string Content to display for the shortcode.
	 */
	private function generate_shortcode_output( $new_data, $atts ) {

		ob_start();
		$style = $atts['style'] ? ' ' . $atts['style'] : '';
		// By default, we output a JSON object that can then be used by a script.
		if ( 'json' === $atts['output'] ) {
            echo '<!-- UAMSWP Output JSON -->';
            // print_r ($new_data);
            echo '<script>var ' . esc_js( $atts['object'] ) . ' = ' . wp_json_encode( $new_data ) .';</script>';
		} elseif ( 'headlines' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Headlines -->
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?>">
				<div class="uamswp-news-syndication-headlines">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12">
								<h2 class="module-title<?php echo (0 !== absint( $atts['hide_title'] ) ) ? ' sr-only' : ''; ?>"><span class="title"><?php echo $atts['news_title'] ? esc_html( $atts['news_title'] ) : 'News &amp; Announcements'; ?></span></h2>
							</div>
							<div class="col-12">
								<div class="inner-container">
									<ul class="uamswp-news-syndication-list">
										<?php
										$offset_x = 0;
										foreach ( $new_data as $content ) {
											if ( $offset_x < absint( $atts['offset'] ) ) {
												$offset_x++;
												continue;
											}
											?><li class="uamswp-news-syndication-item"><a href="<?php echo esc_url( $content->link ); ?>"><?php echo esc_html( $content->title ); ?></a></li><?php
										}
										?>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<?php
		} elseif ( 'excerpts' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Excerpts -->
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?>">
				<div class="uamswp-news-syndication-excerpts">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12">
								<h2 class="module-title<?php echo (0 !== absint( $atts['hide_title'] ) ) ? ' sr-only' : ''; ?>"><span class="title"><?php echo $atts['news_title'] ? esc_html( $atts['news_title'] ) : 'News &amp; Announcements'; ?></span></h2>
							</div>
							<div class="col-12">
								<div class="item-container">
								<?php
								$offset_x = 0;
								foreach ( $new_data as $content ) {
									if ( $offset_x < absint( $atts['offset'] ) ) {
										$offset_x++;
										continue;
									}
									?>
									<div class="item">
										<div class="uamswp-news-syndication-item" itemscope itemtype="http://schema.org/NewsArticle">
											<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
											<div class="row">
												<?php if ( $content->thumbnail && !$atts[ 'hide_img' ] ) { ?>
												<div class="col-12 col-sm-4 image-container">
													<img src="<?php echo esc_url( $content->thumbnail ); ?>" alt="<?php echo esc_html( $content->thumbalt ); ?>" itemprop="url">
												</div>
												<div class="col-12 col-sm-8 text-container">
												<?php } else { ?>
												<div class="col-12 col-sm-8 col-sm-offset-4 text-container">
												<?php } //endif ?>
													<h3 class="h5 news-item-title" itemprop="headline"><?php echo esc_html( $content->title ); ?></h3>
													<div class="news-item-byline">
														<span class="news-item-byline-date" itemprop="datePublished" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"><small><?php echo esc_html( date( $atts['date_format'], strtotime( $content->date ) ) ); ?> | </small></span>
														<meta itemprop="dateModified" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"/>
														<span class="news-item-byline-author" itemprop="author" itemscope itemtype="http://schema.org/Person"><small itemprop="name"><?php echo esc_html( $content->author_name ); ?></small></span>
													</div>
													<div class="news-item-excerpt" itemprop="articleBody"><?php echo preg_replace('#<a class="more"(.*?)</a>#', '', wp_kses_post( $content->excerpt )); ?></div>
													<a class="btn btn-primary stretched-link" href="<?php echo esc_url( $content->link ); ?>" itemprop="url" aria-label="<?php echo esc_html( $content->title ); ?>">Read more</a>
													<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
														<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
														<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
															<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
															<meta itemprop="width" content="297"/>
															<meta itemprop="height" content="40"/>
														</span>
													</span>
												</div>
											</div>
										</div>
									</div>
									<?php
								}
								?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<?php
		} elseif ( 'cards' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Cards -->
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?>">
				<div class="uamswp-news-syndication-cards">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12">
								<h2 class="module-title<?php echo (0 !== absint( $atts['hide_title'] ) ) ? ' sr-only' : ''; ?>"><span class="title"><?php echo $atts['news_title'] ? esc_html( $atts['news_title'] ) : 'News &amp; Announcements'; ?></span></h2>
							</div>
							<?php
							$offset_x = 0;
							foreach ( $new_data as $content ) {
								if ( $offset_x < absint( $atts['offset'] ) ) {
									$offset_x++;
									continue;
								}
								?>
								<div class="col-12 col-sm-6 col-xl-3 item" itemscope itemtype="http://schema.org/NewsArticle">
									<div class="card">
										<?php if ( !$atts[ 'hide_img' ] ) { ?>
										<div class="card-img-top">
											<picture>
												<?php if ( $content->image_sm ) : ?>
													<img src="<?php echo esc_url( $content->image_sm ); ?>" alt="<?php echo esc_html( $content->imagealt ); ?>" itemprop="url">
													<?php elseif ( $content->image ) : ?>
													<img src="<?php echo esc_url( $content->image ); ?>" alt="<?php echo esc_html( $content->imagealt ); ?>" itemprop="url">
													<?php else: ?>
												<img src="<?php echo plugin_dir_url( __DIR__ ) . 'images/uams_logo.png'; ?>" alt="UAMS Logo" itemprop="url">
												<?php endif; ?>
											</picture>
										</div>
										<?php } ?>
										<div class="card-body">
											<h3 class="card-title h5" itemprop="headline"><?php echo esc_html( $content->title ); ?></h3>
											<?php echo preg_replace('#<a class="more"(.*?)</a>#', '', wp_kses_post( $content->excerpt )); ?>
											<!-- <p class="card-text" itemprop="articleBody"></p> -->
											<a href="<?php echo esc_url( $content->link ); ?>" class="btn btn-primary stretched-link" aria-label="<?php echo esc_html( $content->title ); ?>" itemprop="url">Read more</a>
										</div>
									</div>
									<span class="news-item-byline-author" itemprop="author" itemscope itemtype="http://schema.org/Person">
										<meta itemprop="name" content="<?php echo esc_html( $content->author_name ); ?>"/>
									</span>
									<meta itemprop="datePublished" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"/>
									<meta itemprop="dateModified" content="<?php echo esc_html( date( 'c', strtotime( $content->modified ) ) ); ?>"/>
									<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
										<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
										<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
											<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
											<meta itemprop="width" content="297"/>
											<meta itemprop="height" content="40"/>
										</span>
									</span>
									<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
								</div>
								<?php
							}
							if (strpos($content->link, get_home_url()) !== false) { // Local
								if($content->terms) {
									foreach ($content->terms as $cat_id) {
										if( strpos($atts['category'], get_category( $cat_id )->slug ) !== false ) {
											$categorylink = get_category_link($cat_id);
											$categoryname = get_category( $cat_id )->name;
										}
									}
								} 
							} else { // Remote
								if ( $atts['category'] ) {
									foreach( $content->terms as $cat ) {
										if ( strpos($atts['category'], $cat->slug ) !== false ) {
											$categoryname = $cat->name;
											$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
										}
									}
								} else {
									foreach( $content->terms as $cat ) {
										$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
										$categoryname = $cat->name;
									}
								}
							}
							// Original Code
							// if( 0 !== absint( $atts['local'] ) ) {
							// 	$categorylink = get_category_link( get_category_by_slug( $atts['category'] )->term_id );
							// 	$categoryname = get_category_by_slug( $atts['category'] )->name ;
							// } else {
							// 	$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $atts['category'] . '/';
							// 	foreach( $content->terms as $cat ) {
							// 		if ( $atts['category'] == $cat->slug )
							// 		$categoryname = $cat->name;
							// 	}
							// }
								if ( 0 !== absint( $atts['include_link'] ) && $atts['category'] ) {
							?>
							<div class="col-12 more">
								<p class="lead">Want to read more stories like these?</p>
								<div class="cta-container">
									<a href="<?php echo $categorylink; ?>" class="btn btn-outline-primary" aria-label="View the full list of <?php echo $categoryname; ?> stories">View the Full List</a>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</section>
			<?php
		} elseif ( 'grid' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Grid -->
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?>">
				<div class="uamswp-news-syndication-grid">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12">
								<h2 class="module-title<?php echo $atts['hide_title'] ? ' sr-only' : ''; ?>"><span class="title"><?php echo $atts['news_title'] ? esc_html( $atts['news_title'] ) : 'News &amp; Announcements'; ?></span></h2>
							</div>
							<div class="col-12">
								<div class="inner-container">
									<div class="row">
										<!-- <style>.uamswp-news-syndication-grid a.more {display: none;}</style> -->
										<?php
										$offset_x = 0;
										$count = 1;
										// echo '<script>var ' . esc_js( $atts['object'] ) . ' = ' . wp_json_encode( $new_data ) .';</script>';
										foreach ( $new_data as $content ) {
											if ( $offset_x < absint( $atts['offset'] ) ) {
												$offset_x++;
												continue;
											}
											if (strpos($content->link, get_home_url()) !== false) { // Local
												if($content->terms) {
													foreach ($content->terms as $cat_id) {
														if( strpos($atts['category'], get_category( $cat_id )->slug ) !== false ) {
															$categorylink = get_category_link($cat_id);
															$categoryname = get_category( $cat_id )->name;
														}
													}
												} 
											} else { // Remote
												if ( $atts['category'] ) {
													foreach( $content->terms as $cat ) {
														if ( strpos($atts['category'], $cat->slug ) !== false ) {
															$categoryname = $cat->name;
															$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
														}
													}
												} else {
													foreach( $content->terms as $cat ) {
														$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
														$categoryname = $cat->name;
													}
												}
											}
											// Original Code
											// if( 0 !== absint( $atts['local'] ) ) {
											// 	if ( $atts['category'] ) {
											// 		$categorylink = get_category_link( get_category_by_slug( $atts['category'] )->term_id );
											// 		$categoryname = get_category_by_slug( $atts['category'] )->name ;
											// 	} else {
											// 		foreach( $content->terms as $cat ) {
											// 			$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
											// 			$categoryname = $cat->name;
											// 		}
											// 	}
											// } else {
											// 	if ( $atts['category'] ) {
											// 		$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $atts['category'] . '/';
											// 		foreach( $content->terms as $cat ) {
											// 			if ( $atts['category'] == $cat->slug )
											// 			$categoryname = $cat->name;
											// 		}
											// 	} else {
											// 		foreach( $content->terms as $cat ) {
											// 			$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
											// 			$categoryname = $cat->name;
											// 		}
											// 	}
											// }
											?>
											<?php if( 1 == $count ) { ?>
											<div class="col-12 col-sm-7 col-md-12 col-lg-7 featured">
												<div class="item" itemscope itemtype="http://schema.org/NewsArticle">
													<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
													<?php if ( !$atts[ 'hide_img' ] ) { ?>
													<picture itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
														<?php if ( $content->image ) : ?><img src="<?php echo esc_url( $content->image ); ?>" alt="<?php echo esc_html( $content->imagecaption ); ?>" itemprop="url"><?php else: ?><img src="<?php echo plugin_dir_url( __DIR__ ) . 'images/uams_logo.png'; ?>" alt="" itemprop="url"><?php endif; ?>
													</picture>
													<?php } ?>
													<h3 class="h4" itemprop="headline"><?php echo esc_html( $content->title ); ?></h3>
													<span itemprop="articleBody">
													<p><?php echo preg_replace('#<a class="more"(.*?)</a>#', '', wp_kses_post( $content->excerpt )); ?></p>
													</span>
													<a href="<?php echo esc_url( $content->link ); ?>" class="btn btn-primary stretched-link" itemprop="url" aria-label="<?php echo esc_html( $content->title ); ?>">Read more</a>
													<span class="news-item-byline-author" itemprop="author" itemscope itemtype="http://schema.org/Person"><meta itemprop="name" content="<?php echo esc_html( $content->author_name ); ?>"/></span>
														<meta itemprop="datePublished" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"/>
														<meta itemprop="dateModified" content="<?php echo esc_html( date( 'c', strtotime( $content->modified ) ) ); ?>"/>
													<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
														<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
														<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
															<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
															<meta itemprop="width" content="297"/>
															<meta itemprop="height" content="40"/>
														</span>
													</span>
												</div>
											</div>
											<div class="col-12 col-sm-5 col-md-12 col-lg-5 secondary">
											<?php } else { ?>
												<div class="item-container">
													<div class="item" itemscope itemtype="http://schema.org/NewsArticle">
														<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
														<h3 class="h5"><?php echo esc_html( $content->title ); ?></h3>
														<p><?php echo preg_replace('#<a class="more"(.*?)</a>#', '', wp_kses_post( $content->excerpt )); ?></p>
														<a class="btn btn-primary stretched-link" href="<?php echo esc_url( $content->link ); ?>" aria-label="<?php echo esc_html( $content->title ); ?>">Read more</a>
													</div>
												</div>
											<?php } ?>

											<?php
											$count++;
											}
											?>
										</div>
										<?php
											if ( $atts['include_link'] && $atts['category'] ) {
										?>
										<div class="col-12 more">
											<p class="lead">Want to read more stories like these?</p>
											<div class="cta-container">
												<a href="<?php echo $categorylink; ?>" class="btn btn-outline-primary" aria-label="View the full list of <?php echo $categoryname; ?> stories">View the Full List</a>
											</div>
										</div>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<?php
		} elseif ( 'side' === $atts['output'] ) {
			$image_position = $atts['news_position'] ? $atts['news_position'] : 'left';
			?>
			<!-- UAMSWP Output Side-by-Side Image & Text -->
			<?php
			$offset_x = 0;
			foreach ( $new_data as $content ):
				if ( $offset_x < absint( $atts['offset'] ) ) {
					$offset_x++;
					continue;
				}
				
				if (strpos($content->link, get_home_url()) !== false) { // Local
					if($content->terms) {
						foreach ($content->terms as $cat_id) {
							if( strpos($atts['category'], get_category( $cat_id )->slug ) !== false ) {
								$categorylink = get_category_link($cat_id);
								$categoryname = get_category( $cat_id )->name;
							}
						}
					} 
				} else { // Remote
					if ( $atts['category'] ) {
						foreach( $content->terms as $cat ) {
							if ( strpos($atts['category'], $cat->slug ) !== false ) {
								$categoryname = $cat->name;
								$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
							}
						}
					} else {
						foreach( $content->terms as $cat ) {
							$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
							$categoryname = $cat->name;
						}
					}
				}
				// Original Code
				// if( 0 !== absint( $atts['local'] ) ) {
				// 	if ( $atts['category'] ) {
				// 		$categorylink = get_category_link( get_category_by_slug( $atts['category'] )->term_id );
				// 		$categoryname = get_category_by_slug( $atts['category'] )->name ;
				// 	} else {
				// 		if ($content->terms) {
				// 			foreach( $content->terms as $cat ) {
				// 				$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
				// 				$categoryname = $cat->name;
				// 			}
				// 		} else {
				// 			$postcat = get_the_category( $content->ID );
				// 			if ( ! empty( $postcat ) ) {
				// 				foreach( $postcat  as $cat ) {
				// 					$categorylink = get_category_link( $cat->term_id );
				// 					$categoryname = $cat->name ;
				// 				}
				// 			}
				// 		}
				// 	}
				// } else {
				// 	if ( $atts['category'] ) {
				// 		$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $atts['category'] . '/';
				// 		foreach( $content->terms as $cat ) {
				// 			if ( $atts['category'] == $cat->slug )
				// 			$categoryname = $cat->name;
				// 		}
				// 	} else {
				// 		foreach( $content->terms as $cat ) {
				// 			$categorylink = $atts[ 'scheme' ] . '://'. $atts[ 'host' ] . '/category/' . $cat->slug . '/';
				// 			$categoryname = $cat->name;
				// 		}
				// 	}
				// }
				$article_id = esc_attr($content->ID);
			?>
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?> no-padding side-by-side image-on-<?php echo $image_position; ?> image-background-center"id="side-by-side-<?php echo $article_id; ?>">
				<div class="uamswp-news-syndication-side">
				<?php echo '<script>var ' . esc_js( $atts['object'] ) . ' = ' . wp_json_encode( $new_data ) .';</script>'; ?>
					<div class="container-fluid">
						<div class="row">
							<div class="col-12 col-md-6 image-container" aria-label="<?php echo ($content->imagealt) ? esc_html( $content->imagealt ) : 'UAMS logo'; ?>" role="img">
								<style>
									#side-by-side-<?php echo $article_id; ?> .image-container { background-image: url("<?php echo ( $content->image ) ? esc_url( $content->image ) : plugin_dir_url( __DIR__ ) . 'images/uams_logo.png'; ?>"); }
								</style>
								<div class="image-inner-container">
								</div>
							</div>
							<div class="col-12 col-md-6 text-container">
								<div class="text-inner-container">
									<h2 class="h3">
										<span class="category"><a href="<?php echo $categorylink; ?>" aria-label="See more <?php echo $categoryname; ?> stories"><?php echo $categoryname; ?></a></span><span class="sr-only">: </span>
										<span class="title"><?php echo esc_html( $content->title ); ?></span>
									</h2>
									<p><?php echo preg_replace('#<a class="more"(.*?)</a>#', '', wp_kses_post( $content->excerpt )); ?></p>
									<div class="cta-container">
										<a class="btn btn-primary" href="<?php echo esc_url( $content->link ); ?>" aria-label="<?php echo esc_html( $content->title ); ?>">Read more</a>
										<?php
											if ( $atts['include_link'] ) {
										?>
											<a href="<?php echo $categorylink; ?>" class="btn btn-outline-primary" aria-label="View the full list of <?php echo $categoryname; ?> stories">View the <?php echo $categoryname; ?> Archive</a>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>
				<?php
				endforeach;
				?>
			<?php
		} elseif ( 'full' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Full -->
			<section class="uamswp-news-syndication-wrapper uams-module<?php echo $style; ?>">
				<div class="uamswp-news-syndication-container">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
						<section class="uamswp-news-syndication-full" itemscope itemtype="http://schema.org/NewsArticle">
							<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
							<div class="news-item-thumbnail" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
								<?php if ( $content->image ) : ?><img src="<?php echo esc_url( $content->image ); ?>" alt="<?php echo ($content->imagealt); ?>"><?php else: ?><meta itemprop="url" content="http://www.uams.edu/_images/blank.gif"/><?php endif; ?>
								<?php echo( $content->imagecaption ? '<div class="wp-caption-text">' . $content->imagecaption . '</div>' : '' );?>
							</div>
							<header class="news-item-title"><a href="<?php echo esc_url( $content->link ); ?>" itemprop="url"><?php echo '<h2 itemprop="headline">' . esc_html( $content->title ) . '</h2>'; ?></a></header>
							<div class="news-item-byline">
								<!-- <?php if ( $content->date) : ?><span class="news-item-byline-date"><?php echo esc_html( date( $atts['date_format'], strtotime( $content->date ) ) ); ?></span> | <?php endif; ?>-->
								<?php if ( $content->author_name) : ?><span class="news-item-byline-author" itemprop="author" itemscope itemtype="http://schema.org/Person">By <span itemprop="name"><?php echo esc_html( $content->author_name ); ?></span></span><?php endif; ?>
								<meta itemprop="datePublished" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"/>
					            <meta itemprop="dateModified" content="<?php echo esc_html( date( 'c', strtotime( $content->modified ) ) ); ?>"/>
							</div>
							<div class="news-item-content" itemprop="articleBody">
								<?php
									if ( $content->fullcontent ) {
										echo do_shortcode( wp_kses_post( $content->fullcontent ) );
									} else {
										echo do_shortcode( wp_kses_post( $content->content ) );
									}
								?>
								<!-- <hr size="1" width="75%"/> -->
							</div>
							<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
								<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
								<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
									<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
								    <meta itemprop="width" content="297"/>
								    <meta itemprop="height" content="40"/>
								</span>
							</span>
						</section>
						<?php
					}
					?>
				</div>
			</section>
			<?php
		} // End if().
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Process REST API results received remotely through `wp_remote_get()`
	 *
	 * @since 0.9.0
	 *
	 * @param object $data List of post data.
	 * @param array  $atts Attributes passed with the original shortcode.
	 *
	 * @return array Array of objects representing individual posts.
	 */
	public function process_remote_posts( $data, $atts ) {
		if ( empty( $data ) ) {
			return array();
		}

		$new_data = array();

		foreach ( $data as $post ) {
			$subset = new StdClass();

			// Only a subset of data is returned for a headlines request.
			if ( 'headlines' === $atts['output'] ) {
				$subset->link = $post->link;
				$subset->date = $post->date;
				$subset->title = $post->title->rendered;
			} else {
				$subset->ID = $post->id;
				$subset->date = $post->date; // In time zone of requested site
				$subset->link = $post->link;
				$subset->modified = $post->modified; // Added for schema

				// These fields all provide a rendered version when the response is generated.
				$subset->title   = $post->title->rendered;
				$subset->content = $post->content->rendered;
				$subset->excerpt = $post->excerpt->rendered;

				if ('full' === $atts['output'] ) {
					$subset->fullcontent = $post->fullcontent;
				}

				// If a featured image is assigned (int), the full data will be in the `_embedded` property.
				if ( ! empty( $post->featured_media ) && isset( $post->_embedded->{'wp:featuredmedia'} ) && 0 < count( $post->_embedded->{'wp:featuredmedia'} ) ) {
					$subset_feature = $post->_embedded->{'wp:featuredmedia'}[0]->media_details;

					if ( isset( $subset_feature->sizes->{'post-thumbnail'} ) ) {
						$subset->thumbnail = $subset_feature->sizes->{'post-thumbnail'}->source_url;
						$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} elseif ( isset( $subset_feature->sizes->{'thumbnail'} ) ) {
						$subset->thumbnail = $subset_feature->sizes->{'thumbnail'}->source_url;
						$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} else {
						$subset->thumbnail = $post->_embedded->{'wp:featuredmedia'}[0]->source_url;
						$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					}

					// Add Medium Image
					if ( isset( $subset_feature->sizes->{'aspect-16-9'} ) ) {
						$subset->image = $subset_feature->sizes->{'aspect-16-9'}->source_url;
						if ( isset( $subset_feature->sizes->{'aspect-16-9-small'} ) ) {
							$subset->image_sm = $subset_feature->sizes->{'aspect-16-9-small'}->source_url;
						}
						$subset->imagealt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->imagecaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} elseif ( isset( $subset_feature->sizes->{'aspect-16-9-small'} ) ) {
						$subset->image = $subset_feature->sizes->{'aspect-16-9-small'}->source_url;
						$subset->image_sm = $subset_feature->sizes->{'aspect-16-9-small'}->source_url;
						$subset->imagealt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->imagecaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} elseif ( isset( $subset_feature->sizes->{'news-full'} ) ) {
						$subset->image = $subset_feature->sizes->{'news-full'}->source_url;
						if ( isset( $subset_feature->sizes->{'uams_news'} ) ) {
							$subset->image_sm = $subset_feature->sizes->{'uams_news'}->source_url;
						}
						$subset->imagealt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->imagecaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} elseif ( isset( $subset_feature->sizes->{'uams_news'} ) ) {
						$subset->image = $subset_feature->sizes->{'uams_news'}->source_url;
						$subset->imagealt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
						$subset->imagecaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
					} else {
						$subset->image = false;
					}
				} else {
					$subset->thumbnail = false;
				}

				// If an author is available, it will be in the `_embedded` property.
				if ( isset( $post->_embedded ) && isset( $post->_embedded->author ) && 0 < count( $post->_embedded->author ) ) {
					$subset->author_name = $post->_embedded->author[0]->name;
				} else {
					$subset->author_name = '';
				}

				// We've always provided an empty value for terms. @todo Implement terms. :)
				$subset->terms = array();
				$subset->terms = $post->post_categories;
				// if ( isset( $post->post_categories ) && isset( $post->post_categories->slug ) && 0 < count( $post->post_categories ) ) {
				// 	foreach( $post->post_categories as $cat ) {
				// 		$subset->terms[ $cat->slug ] = $cat->name;
				// 	}
				// }

			} // End if().

			/**
			 * Filter the data stored for an individual result after defaults have been built.
			 *
			 * @since 0.7.10
			 *
			 * @param object $subset Data attached to this result.
			 * @param object $post   Data for an individual post retrieved via `wp-json/posts` from a remote host.
			 * @param array  $atts   Attributes originally passed to the `uamswp_news` shortcode.
			 */
			$subset = apply_filters( 'uams_news_syndication_host_data', $subset, $post, $atts );

			if ( $post->date ) {
				$subset_key = strtotime( $post->date );
			} else {
				$subset_key = time();
			}

			while ( array_key_exists( $subset_key, $new_data ) ) {
				$subset_key++;
			}
			$new_data[ $subset_key ] = $subset;
		} // End foreach().

		return $new_data;
	}

	/**
	 * Process REST API results received locally through `rest_do_request()`
	 *
	 * @since 0.9.0
	 *
	 * @param array $data Array of post data.
	 * @param array $atts Attributes passed with the original shortcode.
	 *
	 * @return array Array of objects representing individual posts.
	 */
	public function process_local_posts( $data, $atts ) {
		if ( empty( $data ) ) {
			return array();
		}

		$new_data = array();

		foreach ( $data as $post ) {
			$subset = new stdClass();

			// Only a subset of data is returned for a headlines request.
			if ( 'headlines' === $atts['output'] ) {
				$subset->link = $post->link;
				$subset->date = $post->date;
				$subset->title = $post->title->rendered;
			} else {
				$subset->ID = $post['id'];
				$subset->date = $post['date']; // In time zone of requested site
				$subset->link = $post['link'];
				$subset->modified = $post['modified']; // Added for schema

				// These fields all provide a rendered version when the response is generated.
				$subset->title   = $post['title']['rendered'];
				$subset->content = $post['content']['rendered'];
				$subset->excerpt = $post['excerpt']['rendered'];
				if ('full' === $atts['output'] ) {
					$subset->fullcontent = $post['fullcontent'];
				}

				if ( ! empty( $post['featured_media'] ) && ! empty( $post['_links']['wp:featuredmedia'] ) ) {
					$media_request_url = $post['_links']['wp:featuredmedia'][0]['href'];
					$media_request = WP_REST_Request::from_url( $media_request_url );
					$media_response = rest_do_request( $media_request );
					$data = $media_response->data;
					$data = $data['media_details']['sizes'];

					if ( isset( $data['post-thumbnail'] ) ) {
						$subset->thumbnail = $data['post-thumbnail']['source_url'];
					} elseif ( isset( $data['thumbnail'] ) ) {
						$subset->thumbnail = $data['thumbnail']['source_url'];
					} else {
						$subset->thumbnail = $media_response->data['source_url'];
					}
					// Add Image
					if ( isset( $data['aspect-16-9'] ) ) {
						$subset->image = $data['aspect-16-9']['source_url'];
						$subset->imagealt = $media_response->data['alt_text'];
						$subset->imagecaption = $media_response->data['caption']['rendered'];
						if ( isset( $data['aspect-16-9-small'] ) ) {
							$subset->image_sm =  $data['aspect-16-9-small']['source_url'];
						}
					} elseif ( isset( $data['aspect-16-9-small'] ) ) {
						$subset->image = $data['aspect-16-9-small']['source_url'];
						$subset->image_sm = $data['aspect-16-9-small']['source_url'];
						$subset->imagealt = $media_response->data['alt_text'];
						$subset->imagecaption = $media_response->data['caption']['rendered'];
					} elseif ( isset( $data['large'] ) ) {
						$subset->image = $data['large']['source_url'];
						$subset->image_sm = $data['large']['source_url'];
						$subset->imagealt = $media_response->data['alt_text'];
						$subset->imagecaption = $media_response->data['caption']['rendered'];
					} else {
						$subset->image = false;
					}

				} else {
					$subset->thumbnail = false;
				}

				$subset->author_name = '';

				if ( ! empty( $post['author'] ) && ! empty( $post['_links']['author'] ) ) {
					$author_request_url = $post['_links']['author'][0]['href'];
					$author_request = WP_REST_Request::from_url( $author_request_url );
					$author_response = rest_do_request( $author_request );
					if ( isset( $author_response->data['name'] ) ) {
						$subset->author_name = $author_response->data['name'];
					}
				}

				// We've always provided an empty value for terms. @todo Implement terms. :)
				$subset->terms = array();
				$subset->terms = $post['categories']; // Category IDs
			} // End if().

			/**
			 * Filter the data stored for an individual result after defaults have been built.
			 *
			 * @since 0.7.10
			 *
			 * @param object $subset Data attached to this result.
			 * @param object $post   Data for an individual post retrieved via `wp-json/posts` from a remote host.
			 * @param array  $atts   Attributes originally passed to the `uamswp_news` shortcode.
			 */
			$subset = apply_filters( 'uams_news_syndication_host_data', $subset, $post, $atts );

			if ( $post['date'] ) {
				$subset_key = strtotime( $post['date'] );
			} else {
				$subset_key = time();
			}

			while ( array_key_exists( $subset_key, $new_data ) ) {
				$subset_key++;
			}
			$new_data[ $subset_key ] = $subset;
		} // End foreach().

		return $new_data;
	}
}
