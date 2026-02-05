<?php
/* Load core functions */
require_once (get_template_directory() . '/incl/system/core-functions.php');

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/*
 * Loads the Options Panel
 */
if (!function_exists('lafka_optionsframework_init')) {
	define('LAFKA_OPTIONS_FRAMEWORK_DIRECTORY', get_template_directory_uri() . '/incl/lafka-options-framework/');
	// framework
	require_once get_template_directory() . '/incl/lafka-options-framework/lafka-options-framework.php';
	// custom functions
	require_once get_template_directory() . '/incl/lafka-options-framework/lafka-options-functions.php';
}

/* Load configuration */
require_once (get_template_directory() . '/incl/system/config.php');

/**
 * Echo the pagination
 */
if (!function_exists('lafka_pagination')) {

	function lafka_pagination($pages = '', $wp_query = '') {
		if (empty($wp_query)) {
			global $wp_query;
		}

		$range = 3;
		$posts_per_page = get_query_var('posts_per_page');
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

		$html = '';

		if ($pages == '') {

			if (isset($wp_query->max_num_pages)) {
				$pages = $wp_query->max_num_pages;
			}

			if (!$pages) {
				$pages = 1;
			}
		}

		if (1 != $pages) {
			$html .= "<div class='pagination'><div class='links'>";
			if ($paged > 2) {
				$html .= "<a href='" . esc_url(get_pagenum_link(1)) . "'>&laquo;</a>";
			}
			if ($paged > 1) {
				$html .= "<a class='prev_page' href='" . esc_url(get_pagenum_link($paged - 1)) . "'>&lsaquo;</a>";
			}

			for ($i = 1; $i <= $pages; $i++) {
				if (1 != $pages && (!( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) )) {
					$class = ( $paged == $i ) ? " class='selected'" : '';
					$html .= "<a href='" . esc_url(get_pagenum_link($i)) . "'$class >$i</a>";
				}
			}

			if ($paged < $pages) {
				$html .= "<a class='next_page' href='" . esc_url(get_pagenum_link($paged + 1)) . "'>&rsaquo;</a>";
			}
			if ($paged < $pages - 1) {
				$html .= "<a href='" . esc_url(get_pagenum_link($pages)) . "'>&raquo;</a>";
			}

			$first_article_on_page = ($posts_per_page * $paged ) - $posts_per_page + 1;

			$last_article_on_page = min($wp_query->found_posts, $wp_query->get('posts_per_page') * $paged);

			$html .= "</div><div class='results'>";
			$html .= sprintf(esc_html__('Showing %1$s to %2$s of %3$s (%4$s Pages)', 'lafka'), $first_article_on_page, $last_article_on_page, $wp_query->found_posts, $pages);
			$html .= "</div></div>";
		}

		echo apply_filters('lafka_pagination', $html);
	}

}

/**
 * Return the page breadcrumbs
 *
 */
if ( ! function_exists( 'lafka_breadcrumb' ) ) {

	function lafka_breadcrumb( $delimiter = ' <span class="lafka-breadcrumb-delimiter">/</span> ' ) {

		if ( lafka_get_option( 'show_breadcrumb', 1 ) && ! is_404() ) {
			$home      = esc_html__( 'Home', 'lafka' ); // text for the 'Home' link
			$before    = '<span class="current-crumb">'; // tag before the current crumb
			$after     = '</span>'; // tag after the current crumb
			$brdcrmb   = '';

			global $post;
			global $wp_query;
			$homeLink = esc_url(lafka_wpml_get_home_url());

			if ( ! is_home() && ! is_front_page() ) {
				$brdcrmb .= '<a class="home" href="' . esc_url( $homeLink ) . '">' . $home . '</a> ' . $delimiter . ' ';
			}

			if ( is_category() ) {
				$cat_obj   = $wp_query->get_queried_object();
				$thisCat   = $cat_obj->term_id;
				$thisCat   = get_category( $thisCat );
				$parentCat = get_category( $thisCat->parent );

				if ( $thisCat->parent != 0 ) {
					$brdcrmb .= get_category_parents( $parentCat, true, ' ' . $delimiter . ' ' );
				}

				$brdcrmb .= $before . single_cat_title( '', false ) . $after;
				/* If is taxonomy or BBPress topic tag */
			} elseif ( is_tax() || get_query_var( 'bbp_topic_tag' ) ) {
				$cat_obj   = $wp_query->get_queried_object();
				$thisCat   = $cat_obj->term_id;
				$thisCat   = get_term( $thisCat, $cat_obj->taxonomy );
				$parentCat = get_term( $thisCat->parent, $cat_obj->taxonomy );
				$tax_obj   = get_taxonomy( $cat_obj->taxonomy );
				$brdcrmb .= $tax_obj->labels->name . ': ';

				if ( $thisCat->parent != 0 ) {
					$brdcrmb .= lafka_get_taxonomy_parents( $parentCat, $cat_obj->taxonomy, true, ' ' . $delimiter . ' ' );
				}
				$brdcrmb .= $before . $thisCat->name . $after;
			} elseif ( is_day() ) {
				$brdcrmb .= '<a class="no-link" href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">' . get_the_time( 'Y' ) . '</a> ' . $delimiter . ' ';
				$brdcrmb .= '<a class="no-link" href="' . esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ) . '">' . get_the_time( 'F' ) . '</a> ' . $delimiter . ' ';
				$brdcrmb .= $before . get_the_time( 'd' ) . $after;
			} elseif ( is_month() ) {
				$brdcrmb .= '<a class="no-link" href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">' . get_the_time( 'Y' ) . '</a> ' . $delimiter . ' ';
				$brdcrmb .= $before . get_the_time( 'F' ) . $after;
			} elseif ( is_year() ) {
				$brdcrmb .= $before . get_the_time( 'Y' ) . $after;
			} elseif ( is_single() && ! is_attachment() ) {
				if ( isset( $wp_query->post->ID ) && get_post_type( $wp_query->post->ID ) == 'lafka-foodmenu' ) {

					$brdcrmb .= '<a class="no-link" href="' . esc_url( get_post_type_archive_link('lafka-foodmenu') ) . '">' . esc_html__( 'Menu', 'lafka' ) . '</a> ' . $delimiter . ' ';

					$terms = get_the_terms( $post->ID, 'lafka_foodmenu_category' );

					if ( $terms ) {
						$first_cat       = reset( $terms );
						$parent_term_ids = lafka_get_lafka_foodmenu_category_parents( $first_cat->term_id );

						$term_links = '';
						foreach ( $parent_term_ids as $term_id ) {
							$term = get_term( $term_id, 'lafka_foodmenu_category' );
							$term_links .= '<a href="' . esc_url( get_term_link( $term_id ) ) . '">' . $term->name . '</a>' . $delimiter;
						}

						$brdcrmb .= $term_links;
					}

					$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
				} elseif ( isset( $wp_query->post->ID ) && get_post_type( $wp_query->post->ID ) != 'post' ) {
					$post_type = get_post_type_object( get_post_type( $wp_query->post->ID ) );
					$slug      = $post_type->rewrite;
					$real_slug = $slug['slug'];
					if ( $slug['slug'] == 'forums/forum' ) {
						$real_slug = 'forums';
					}
					if ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() ) { // If is Topic
						if ( is_singular() ) {
							$ancestors = array_reverse( (array) get_post_ancestors( $wp_query->post->ID ) );
							// Ancestors exist
							if ( ! empty( $ancestors ) ) {
								// Loop through parents
								foreach ( (array) $ancestors as $parent_id ) {
									// Parents
									$parent = get_post( $parent_id );
									// Skip parent if empty or error
									if ( empty( $parent ) || is_wp_error( $parent ) ) {
										continue;
									}
									// Switch through post_type to ensure correct filters are applied
									switch ( $parent->post_type ) {
										// Forum
										case bbp_get_forum_post_type() :
											$crumbs[] = '<a href="' . esc_url( bbp_get_forum_permalink( $parent->ID ) ) . '" >' . bbp_get_forum_title( $parent->ID ) . '</a>';
											break;
										// Topic
										case bbp_get_topic_post_type() :
											$crumbs[] = '<a href="' . esc_url( bbp_get_topic_permalink( $parent->ID ) ) . '" >' . bbp_get_topic_title( $parent->ID ) . '</a>';
											break;
										// Reply (Note: not in most themes)
										case bbp_get_reply_post_type() :
											$crumbs[] = '<a href="' . esc_url( bbp_get_reply_permalink( $parent->ID ) ) . '" >' . bbp_get_reply_title( $parent->ID ) . '</a>';
											break;
										// WordPress Post/Page/Other
										default :
											$crumbs[] = '<a href="' . esc_url( get_permalink( $parent->ID ) ) . '" >' . get_the_title( $parent->ID ) . '</a>';
											break;
									}
								}

								// Edit topic tag
							}
						}

						$page = bbp_get_page_by_path( bbp_get_root_slug() );
						if ( ! empty( $page ) ) {
							$root_url = get_permalink( $page->ID );

							// Use the root slug
						} else {
							$root_url = get_post_type_archive_link( bbp_get_forum_post_type() );
						}

						$brdcrmb .= '<a class="no-link" href="' . esc_url( $root_url ) . '">' . esc_html__( 'Forums', 'lafka' ) . '</a> ' . $delimiter . ' ';
						foreach ( $crumbs as $crumb ) {
							$brdcrmb .= $crumb . ' ' . $delimiter;
						}

					} elseif ( ! in_array( $post_type->name, array( 'tribe_venue', 'tribe_organizer' ) ) ) {
						$brdcrmb .= '<a class="no-link" href="' . esc_url( $homeLink . '/' . $real_slug ) . '/">' . $post_type->labels->name . '</a> ' . $delimiter . ' ';
					} else {
						$brdcrmb .= '<span>' . $post_type->labels->name . '</span> ' . $delimiter . ' ';
					}

					$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
				} else {
					$cat = get_the_category();
					$cat = $cat[0];
					$brdcrmb .= get_category_parents( $cat, true, ' ' . $delimiter . ' ' );
					if( isset( $wp_query->post->ID ) ) {
						$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
					}
				}
			} elseif ( ! is_single() && ! is_page() && ! is_404() && ! is_search() && isset( $wp_query->post->ID ) && get_post_type( $wp_query->post->ID ) != 'post') {
				$post_type = get_post_type_object( get_post_type( $wp_query->post->ID ) );
				if ( $post_type ) {
				    if($post_type->name === 'lafka-foodmenu') {
					    $brdcrmb .= $before . esc_html__( 'Menu', 'lafka' ) . $after;
                    } else {
					    $brdcrmb .= $before . $post_type->labels->singular_name . $after;
				    }
				}
			} elseif ( is_attachment() ) {
				$parent = get_post( $post->post_parent );
				$cat    = get_the_category( $parent->ID );
				if ( ! empty( $cat ) ) {
					$cat         = $cat[0];
					$cat_parents = get_category_parents( $cat, true, ' ' . $delimiter . ' ' );
					if ( ! is_wp_error( $cat_parents ) ) {
						$brdcrmb .= get_category_parents( $cat, true, ' ' . $delimiter . ' ' );
					}
				}
				$brdcrmb .= '<a class="no-link" href="' . esc_url( get_permalink( $parent ) ) . '">' . $parent->post_title . '</a> ' . $delimiter . ' ';
				if( isset( $wp_query->post->ID ) ) {
					$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
				}
			} elseif ( is_page() && ! $post->post_parent && isset( $wp_query->post->ID ) ) {
				$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
			} elseif ( is_page() && $post->post_parent ) {
				$parent_id   = $post->post_parent;
				$breadcrumbs = array();

				while ( $parent_id ) {
					$page          = get_post( $parent_id );
					$breadcrumbs[] = '<a class="no-link" href="' . esc_url( get_permalink( $page->ID ) ) . '">' . get_the_title( $page->ID ) . '</a>';
					$parent_id     = $page->post_parent;
				}

				$breadcrumbs = array_reverse( $breadcrumbs );
				foreach ( $breadcrumbs as $crumb ) {
					$brdcrmb .= $crumb . ' ' . $delimiter . ' ';
				}

				if( isset( $wp_query->post->ID ) ) {
					$brdcrmb .= $before . get_the_title( $wp_query->post->ID ) . $after;
				}
			} elseif ( is_search() ) {
				$brdcrmb .= $before . 'Search results for "' . get_search_query() . '"' . $after;
			} elseif ( is_tag() ) {
				$brdcrmb .= $before . 'Posts tagged "' . single_tag_title( '', false ) . '"' . $after;
			} elseif ( is_author() ) {
				global $author;
				$userdata = get_userdata( $author );
				$brdcrmb .= $before . 'Articles posted by ' . esc_attr( $userdata->display_name ) . $after;
			} elseif ( is_404() ) {
				$brdcrmb .= $before . 'Error 404' . $after;
			}

			if ( get_query_var( 'paged' ) ) {
				if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) {
					$brdcrmb .= ' (';
				}

				$brdcrmb .= $before . esc_html__( 'Page', 'lafka' ) . ' ' . get_query_var( 'paged' ) . $after;

				if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) {
					$brdcrmb .= ')';
				}
			}

			if ( $brdcrmb ) {
				echo '<div class="breadcrumb">';
				echo wp_kses_post( $brdcrmb );
				echo '</div>';
			}
		} else {
			return false;
		}
	}

}

/**
 * Template for comments and pingbacks.
 */
if (!function_exists('lafka_comment')) {

	function lafka_comment($comment, $args, $depth) {
		if ( $comment->comment_author !== 'ActionScheduler' ) {
			$GLOBALS['comment'] = $comment;
			switch ( $comment->comment_type ) {
				case 'pingback' :
				case 'trackback' :
					?>
                    <li class="post pingback">
                    <p><?php esc_html_e( 'Pingback:', 'lafka' ); ?><?php comment_author_link(); ?><?php edit_comment_link( esc_html__( 'Edit', 'lafka' ), '<span class="edit-link">', '</span>' ); ?></p>
					<?php
					break;
				default :
					?>
                    <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
                    <div id="comment-<?php comment_ID(); ?>" class="comment-body">
						<?php
						$avatar_size = 70;
						echo get_avatar( $comment, $avatar_size );
						echo sprintf( '<span class="tuser">%s</span>', get_comment_author_link() );
						echo sprintf( '<span>%1$s</span>',
							/* translators: 1: date, 2: time */ sprintf( esc_html__( '%1$s at %2$s', 'lafka' ), get_comment_date(), get_comment_time() )
						);
						?>
						<?php edit_comment_link( esc_html__( 'Edit', 'lafka' ), '<span class="edit-link">', '</span>' ); ?>
						<?php if ( $comment->comment_approved == '0' ) : ?>
                            <em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lafka' ); ?></em>
                            <br/>
						<?php endif; ?>

                        <p><?php comment_text(); ?></p>

						<?php comment_reply_link( array_merge( $args, array( 'reply_text' => esc_html__( 'Reply', 'lafka' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>

                    </div><!-- #comment-## -->

					<?php
					break;
			}
		}
	}
}

	/*
	 * Add custom image sizes for the lafka theme blog part
	 */
	if (function_exists('add_image_size')) {
		add_image_size('lafka-foodmenu-single-thumb', 1440); // (not cropped)
		add_image_size('lafka-640x640', 640, 640, true); //(cropped)
		add_image_size('lafka-general-small-size', 100, 100, true); //(cropped)
		add_image_size('lafka-general-small-size-nocrop', 100); // (not cropped)
		add_image_size('lafka-widgets-thumb', 60, 60, true); //(cropped)
		add_image_size('lafka-related-posts', 400, 300, true); //(cropped)
	}

	add_filter('wp_prepare_attachment_for_js', 'lafka_append_image_sizes_js', 10, 3);
	if (!function_exists('lafka_append_image_sizes_js')) {

		/**
		 * Append the 'lafka-general-small-size' custom
		 * sizes to the attachment elements returned by the wp.media
		 *
		 * @param type $response
		 * @param type $attachment
		 * @param type $meta
		 * @return string
		 */
		function lafka_append_image_sizes_js($response, $attachment, $meta) {

			$size_array = array('lafka-general-small-size');

			foreach ($size_array as $size):

				if (isset($meta['sizes'][$size])) {
					$attachment_url = wp_get_attachment_url($attachment->ID);
					$base_url = str_replace(wp_basename($attachment_url), '', $attachment_url);
					$size_meta = $meta['sizes'][$size];

					$response['sizes'][str_replace('-', '_', $size)] = array(
							'height' => $size_meta['height'],
							'width' => $size_meta['width'],
							'url' => $base_url . $size_meta['file'],
							'orientation' => $size_meta['height'] > $size_meta['width'] ? 'portrait' : 'landscape',
					);
				}

			endforeach;

			return $response;
		}

	}

	add_action('init', 'lafka_enable_page_attributes');

	/**
	 * Add page attributes to page post type
	 * - Gives option to select template
	 * Adds excerpt support for pages - mainly used by the About widget
	 */
	if (!function_exists('lafka_enable_page_attributes')) {

		function lafka_enable_page_attributes() {
			add_post_type_support('page', 'page-attributes');
			add_post_type_support('page', 'excerpt');
		}

	}

	/**
	 * Display language switcher
	 *
	 * @return String
	 */
	if (!function_exists('lafka_language_selector_flags')) {

		function lafka_language_selector_flags() {
			$languages = icl_get_languages('skip_missing=0&orderby=code');

			if (!empty($languages)) {
				foreach ($languages as $l) {
					if (!$l['active']) {
						echo '<a title="' . esc_attr($l['native_name']) . '" href="' . esc_url($l['url']) . '">';
					}

					echo '<img src="' . esc_url($l['country_flag_url']) . '" height="12" alt="' . esc_attr($l['language_code']) . '" width="18" />';

					if (!$l['active']) {
						echo '</a>';
					}
				}
			}
		}

	}

    add_filter('excerpt_more', 'lafka_new_excerpt_more');
	if (!function_exists('lafka_new_excerpt_more')) {

		/**
		 * Set custom excerpt more
		 *
		 * @param type $more If is set as 'no_hash' #more keyword is not appended in the url
		 * @return string
		 */
		function lafka_new_excerpt_more($more) {

			$more_html = '...<a class="r_more_blog" href="';
			if ('no_hash' === $more) {
				$more_html .= esc_url(get_the_permalink());
			} else {
				$more_html .= esc_url(get_the_permalink() . '#more-' . esc_attr(get_the_ID()));
			}

			$more_html .= '"> ' . esc_html__('continue reading', 'lafka') . '</a>';

			return $more_html;
		}

	}

	/**
	 * Set custom content more link
	 *
	 * @return String
	 */
	add_filter('the_content_more_link', 'lafka_content_more_link');

	if (!function_exists('lafka_content_more_link')) {

		function lafka_content_more_link() {
			return '<a class="r_more_blog" href="' . esc_url(get_permalink() . '#more-' . esc_attr(get_the_ID())) . '"> ' . esc_html__('continue reading', 'lafka') . '</a>';
		}

	}

	/**
	 * Adds one-half one-third one-forth class to footer widgets
	 */
	if (!function_exists('lafka_widget_class_append')) {

		function lafka_widget_class_append($params) {

			$sidebar_id = $params[0]['id']; // Get the id for the current sidebar we're processing

			if ($sidebar_id != 'bottom_footer_sidebar' && $sidebar_id != 'pre_header_sidebar' && $sidebar_id != 'lafka_product_filters_sidebar') {
				return $params;
			}

			$arr_registered_widgets = wp_get_sidebars_widgets(); // Get an array of ALL registered widgets
			$num_widgets_sidebar = count($arr_registered_widgets[$sidebar_id]);
			$class = 'class="';

			switch ($num_widgets_sidebar) {
				case 0:
				case 1:
					break;
				case 2:
					$class .= 'one_half ';
					break;
				case 3:
					$class .= 'one_third ';
					break;
				default:
					$class .= 'one_fourth ';
			}

			if (!isset($arr_registered_widgets[$sidebar_id]) || !is_array($arr_registered_widgets[$sidebar_id])) { // Check if the current sidebar has no widgets
				return $params; // No widgets in this sidebar.
			}

			$params[0]['before_widget'] = str_replace('class="', $class, $params[0]['before_widget']); // Insert our new classes into "before widget"

			return $params;
		}

	}
	add_filter('dynamic_sidebar_params', 'lafka_widget_class_append');

	if (!function_exists('lafka_get_lafka_foodmenu_category_parents')) {

		/**
		 * Get list of all parent lafka_foodmenu_category-s
		 *
		 * @param int $term_id
		 * @return Array with term ids
		 */
		function lafka_get_lafka_foodmenu_category_parents($term_id) {
			$parents = array();
			// start from the current term
			$parent = get_term_by('id', $term_id, 'lafka_foodmenu_category');
			$parents[] = $parent;
			// climb up the hierarchy until we reach a term with parent = '0'
			while ($parent->parent != '0') {
				$term_id = $parent->parent;

				$parent = get_term_by('id', $term_id, 'lafka_foodmenu_category');
				$parents[] = $parent;
			}
			return $parents;
		}

	}

	add_action('wp_ajax_lafka_ajax_search', 'lafka_ajax_search');
	add_action('wp_ajax_nopriv_lafka_ajax_search', 'lafka_ajax_search');

	if (!function_exists('lafka_ajax_search')) {

		function lafka_ajax_search() {

			unset($_REQUEST['action']);
			if (empty($_REQUEST['s'])) {
				$_REQUEST['s'] = array_shift(array_values($_REQUEST));
			}
			if (empty($_REQUEST['s'])) {
				wp_die();
			}

			$defaults = array(
			        'numberposts' => 5,
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'post_password' => '',
                    'suppress_filters' => false
            );

			$_REQUEST['s'] = apply_filters('get_search_query', $_REQUEST['s']);

			$parameters = array_merge($defaults, $_REQUEST);
			$query = http_build_query($parameters);
			$result = get_posts($query);

			// If there are WC products in the result and visibility is not set for search - remove them
            if(LAFKA_IS_WOOCOMMERCE) {
	            foreach ( $result as $key => $post ) {
	                $product = wc_get_product( $post );
		            if ( is_a($product, 'WC_Product') && !('visible' === $product->get_catalog_visibility() || 'search' === $product->get_catalog_visibility()) ) {
                        unset($result[$key]);
		            }
	            }
            }

			$search_messages = array(
					'no_criteria_matched' => esc_html__("Sorry, no posts matched your criteria", 'lafka'),
					'another_search_term' => esc_html__("Please try another search term", 'lafka'),
					'time_format' => esc_attr(get_option('date_format')),
					'all_results_query' => http_build_query($_REQUEST),
					'all_results_link' => esc_url(home_url('?' . http_build_query($_REQUEST))),
					'view_all_results' => esc_html__('View all results', 'lafka')
			);

			if (empty($result)) {
				$output = "<ul>";
				$output .= "<li>";
				$output .= "<span class='ajax_search_unit ajax_not_found'>";
				$output .= "<span class='ajax_search_content'>";
				$output .= "    <span class='ajax_search_title'>";
				$output .= $search_messages['no_criteria_matched'];
				$output .= "    </span>";
				$output .= "    <span class='ajax_search_excerpt'>";
				$output .= $search_messages['another_search_term'];
				$output .= "    </span>";
				$output .= "</span>";
				$output .= "</span>";
				$output .= "</li>";
				$output .= "</ul>";
				echo wp_kses_post($output);
				wp_die();
			}

			// reorder posts by post type
			$output = "";
			$sorted = array();
			$post_type_obj = array();
			foreach ($result as $post) {
				$sorted[$post->post_type][] = $post;
				if (empty($post_type_obj[$post->post_type])) {
					$post_type_obj[$post->post_type] = get_post_type_object($post->post_type);
				}
			}

			//preapre the output
			foreach ($sorted as $key => $post_type) {
				if (isset($post_type_obj[$key]->labels->name)) {
					$label = $post_type_obj[$key]->labels->name;
					$output .= "<h4>" . esc_html($label) . "</h4>";
				} else {
					$output .= "<hr />";
				}

				$output .= "<ul>";

				foreach ($post_type as $post) {
					$image = get_the_post_thumbnail($post->ID, 'lafka-widgets-thumb');

					$excerpt = "";

					if (!empty($post->post_excerpt)) {
						$excerpt = lafka_generate_excerpt($post->post_excerpt, 70, " ", "...", true, '', true);
					} else {
						$excerpt = get_the_time($search_messages['time_format'], $post->ID);
					}

					$link = get_permalink($post->ID);

					$output .= "<li>";
					$output .= "<a class ='ajax_search_unit' href='" . esc_url($link) . "'>";
					if ($image) {
						$output .= "<span class='ajax_search_image'>";
						$output .= $image;
						$output .= "</span>";
					}
					$output .= "<span class='ajax_search_content'>";
					$output .= "    <span class='ajax_search_title'>";
					$output .= get_the_title($post->ID);
					$output .= "    </span>";
					$output .= "    <span class='ajax_search_excerpt'>";
					$output .= $excerpt;
					$output .= "    </span>";
					$output .= "</span>";
					$output .= "</a>";
					$output .= "</li>";
				}

				$output .= "</ul>";
			}

			$output .= "<a class='ajax_search_unit ajax_search_unit_view_all' href='" . esc_url($search_messages['all_results_link']) . "'>" . esc_html($search_messages['view_all_results']) . "</a>";

			echo wp_kses_post($output);
			wp_die();
		}

	}

	add_filter('wp_import_post_data_processed', 'lafka_preserve_post_ids', 10, 2);

	if (!function_exists('lafka_preserve_post_ids')) {

		/**
		 * WP Import.
		 * Add post id if the record exists
		 *
		 * @param type $postdata
		 * @param type $post
		 * @return Array
		 */
		function lafka_preserve_post_ids($postdata, $post) {

			if (is_array($post) && isset($post['post_id']) && get_post($post['post_id'])) {
				$postdata['ID'] = $post['post_id'];
			}

			return $postdata;
		}

	}

	/* Define ajax calls for each import */
	for ($i = 0; $i <= 6; $i++) {
		add_action('wp_ajax_lafka_import_lafka' . $i, 'lafka_import_lafka' . $i . '_callback');
	}

	if (!function_exists('lafka_import_lafka0_callback')) {

		/**
		 * Import lafka0 demo
		 */
		function lafka_import_lafka0_callback() {
			@set_time_limit(1200);
			$transfer = Lafka_Transfer_Content::getInstance();
			$result = $transfer->doImportDemo('lafka0');

			if ($result) {
				echo 'lafka_import_done';
			}
		}

	}

    if ( ! function_exists( 'lafka_import_lafka1_callback' ) ) {

        /**
         * Import lafka1 demo
         */
        function lafka_import_lafka1_callback() {
            @set_time_limit( 1200 );
            $transfer = Lafka_Transfer_Content::getInstance();
            $result   = $transfer->doImportDemo( 'lafka1' );

            if ( $result ) {
                echo 'lafka_import_done';
            }
        }

    }

    if ( ! function_exists( 'lafka_import_lafka2_callback' ) ) {

        /**
         * Import lafka2 demo
         */
        function lafka_import_lafka2_callback() {
            @set_time_limit( 1200 );
            $transfer = Lafka_Transfer_Content::getInstance();
            $result   = $transfer->doImportDemo( 'lafka2' );

            if ( $result ) {
                echo 'lafka_import_done';
            }
        }

    }

    if ( ! function_exists( 'lafka_import_lafka3_callback' ) ) {

        /**
         * Import lafka3 demo
         */
        function lafka_import_lafka3_callback() {
            @set_time_limit( 1200 );
            $transfer = Lafka_Transfer_Content::getInstance();
            $result   = $transfer->doImportDemo( 'lafka3' );

            if ( $result ) {
                echo 'lafka_import_done';
            }
        }

    }

    if ( ! function_exists( 'lafka_import_lafka4_callback' ) ) {

        /**
         * Import lafka4 demo
         */
        function lafka_import_lafka4_callback() {
            @set_time_limit( 1200 );
            $transfer = Lafka_Transfer_Content::getInstance();
            $result   = $transfer->doImportDemo( 'lafka4' );

            if ( $result ) {
                echo 'lafka_import_done';
            }
        }

    }

    if ( ! function_exists( 'lafka_import_lafka5_callback' ) ) {

        /**
         * Import lafka5 demo
         */
        function lafka_import_lafka5_callback() {
            @set_time_limit( 1200 );
            $transfer = Lafka_Transfer_Content::getInstance();
            $result   = $transfer->doImportDemo( 'lafka5' );

            if ( $result ) {
                echo 'lafka_import_done';
            }
        }

    }

if ( ! function_exists( 'lafka_import_lafka6_callback' ) ) {

	/**
	 * Import lafka6 demo
	 */
	function lafka_import_lafka6_callback() {
		@set_time_limit( 1200 );
		$transfer = Lafka_Transfer_Content::getInstance();
		$result   = $transfer->doImportDemo( 'lafka6' );

		if ( $result ) {
			echo 'lafka_import_done';
		}
	}

}

	// Replace OF textarea sanitization with lafka one - in admin_init, because we will allow <script> tag
	add_action('admin_init', 'lafka_add_script_to_allowed');
	if (!function_exists('lafka_add_script_to_allowed')) {

		function lafka_add_script_to_allowed() {
			// Add script to allowed tags only for the logged users - to be able to add tracking code
			global $allowedposttags;
			$allowedposttags['script'] = array('type' => TRUE);
		}

	}

	/**
	 * Returns selected subsets from options to pass to google
	 */
	if (!function_exists('lafka_get_google_subsets')) {

		function lafka_get_google_subsets() {
			$selected_subsets = lafka_get_option('google_subsets');
			$choosen = array();

			foreach ($selected_subsets as $subset => $is_selected) {
				if ($is_selected != '0') {
					$choosen[] = $subset;
				}
			}

			return implode(',', $choosen);
		}

	}

	/**
	 * WPML HOME URL
	 */
	if (!function_exists('lafka_wpml_get_home_url')) {

		function lafka_wpml_get_home_url() {
			if (function_exists('icl_get_home_url')) {
				return icl_get_home_url();
			} else {
				return home_url('/');
			}
		}

	}

	// Add classes to body
	add_filter('body_class', 'lafka_append_body_classes');
	if (!function_exists('lafka_append_body_classes')) {

		function lafka_append_body_classes($classes) {
			global $wp_query;

			// the layout class
			$general_layout = lafka_get_option('general_layout');

			// check is singular and not Blog/Shop/Forum so we get the real post_meta
			if (!(LAFKA_IS_WOOCOMMERCE && is_shop()) && !lafka_is_blog() && !(LAFKA_IS_BBPRESS && bbp_is_forum_archive()) && is_singular()) {
				$specific_header_size = get_post_meta($wp_query->post->ID, 'lafka_header_size', true) == '' ? 'default' : get_post_meta($wp_query->post->ID, 'lafka_header_size', true);
				$specific_footer_size = get_post_meta($wp_query->post->ID, 'lafka_footer_size', true) == '' ? 'default' : get_post_meta($wp_query->post->ID, 'lafka_footer_size', true);
				$specific_footer_style = get_post_meta($wp_query->post->ID, 'lafka_footer_style', true) == '' ? 'default' : get_post_meta($wp_query->post->ID, 'lafka_footer_style', true);
				$specific_layout = get_post_meta( $wp_query->post->ID, 'lafka_layout', true ) == '' ? 'default' : get_post_meta( $wp_query->post->ID, 'lafka_layout', true );
			} else {
				$specific_header_size = 'default';
				$specific_footer_size = 'default';
				$specific_footer_style = 'default';
				$specific_layout = 'default';
			}

			if ($specific_layout !== 'default') {
				$classes[] = sanitize_html_class($specific_layout);
			} else {
				$classes[] = sanitize_html_class($general_layout);
			}

			// header style
			if(isset($wp_query->post->ID)) {
				$is_header_style_meta = get_post_meta( $wp_query->post->ID, 'lafka_header_syle', true );
			} else {
				$is_header_style_meta = '';
			}
			$is_header_style_blog = lafka_get_option('blog_header_style');
			$is_header_style_shop = lafka_get_option('shop_header_style');
			$is_header_style_forum = lafka_get_option('forum_header_style');
			$is_header_style_events = lafka_get_option('events_header_style');

			$is_search_only_in_products = false;
			if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'only_products' ) ) {
				$is_search_only_in_products = true;
			}

			if(LAFKA_IS_WOOCOMMERCE && (is_product_category() || is_product_tag())) {
				$is_header_style_shop_category = get_term_meta($wp_query->queried_object_id , 'lafka_term_header_style', true );
            }

			$header_style_class = '';
			if ($is_header_style_blog && (lafka_is_blog() || is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author())) {
				if ( is_search() && $is_search_only_in_products ) {
					$header_style_class = $is_header_style_shop;
				} else {
					$header_style_class = $is_header_style_blog;
				}
			} else if (LAFKA_IS_WOOCOMMERCE && is_shop() && $is_header_style_shop) {
				$header_style_class = $is_header_style_shop;
			} else if (LAFKA_IS_WOOCOMMERCE && ( is_product_category() || is_product_tag() ) && $is_header_style_shop_category) {
				$header_style_class = $is_header_style_shop_category;
			} else if (LAFKA_IS_BBPRESS && bbp_is_forum_archive() && $is_header_style_forum) {
				$header_style_class = $is_header_style_forum;
			} else if (LAFKA_IS_EVENTS && lafka_is_events_part() && !is_singular( 'tribe_events' )) {
				$header_style_class = $is_header_style_events;
			} else if (is_singular()) {
				$header_style_class = $is_header_style_meta;
			}

			if ($header_style_class) {
				// If more than one class stored
				$header_style_class_array = explode(' ', $header_style_class);

				foreach ($header_style_class_array as $class) {
					$classes[] = sanitize_html_class( $class );
				}
			}

			// if no header-top
			if (!lafka_get_option('enable_top_header')) {
				$classes[] = sanitize_html_class('lafka-no-top-header');
			}

			// footer reveal
			if (lafka_get_option('footer_style') && $specific_footer_style === 'default') {
				$classes[] = sanitize_html_class(lafka_get_option('footer_style'));
			} elseif ($specific_footer_style !== 'standard' && $specific_footer_style !== 'default') {
				$classes[] = sanitize_html_class($specific_footer_style);
			}

			// Header size
			if (lafka_get_option('header_width') && $specific_header_size === 'default') {
				$classes[] = sanitize_html_class(lafka_get_option('header_width'));
			} else if ($specific_header_size !== 'standard' && $specific_header_size !== 'default') {
				$classes[] = sanitize_html_class($specific_header_size);
			}

			// Footer size
			if (lafka_get_option('footer_width') && $specific_footer_size === 'default') {
				$classes[] = sanitize_html_class(lafka_get_option('footer_width'));
			} else if ($specific_footer_size !== 'standard' && $specific_footer_size !== 'default') {
				$classes[] = sanitize_html_class($specific_footer_size);
			}

			// Sub-menu color Scheme
			if (lafka_get_option('submenu_color_scheme')) {
				$classes[] = sanitize_html_class(lafka_get_option('submenu_color_scheme'));
			}

			// If using video background
			if (lafka_has_to_include_backgr_video()) {
				$classes[] = 'lafka-page-has-video-background';
			}

			// Shop and Category Pages Width
            if(lafka_get_option('shop_pages_width')) {
			    $classes[] = lafka_get_option('shop_pages_width');
            }

			// Blog and Category Pages Width
			if(lafka_get_option('blog_pages_width')) {
				$classes[] = lafka_get_option('blog_pages_width');
			}

			// return the $classes array
			return $classes;
		}

	}

	add_filter('wp_setup_nav_menu_item', 'lafka_setup_nav_menu_item');
	if (!function_exists('lafka_setup_nav_menu_item')) {

		function lafka_setup_nav_menu_item($menu_item) {
			if ($menu_item->db_id != 0) {
				$menu_item->description = apply_filters('nav_menu_description', $menu_item->post_content);
			}

			return $menu_item;
		}

	}

	if (!function_exists('lafka_post_nav')) {

		/**
		 * Returns output for the prev / next links on posts and foodmenus
		 *
		 * @param bool|type $same_category
		 * @param string|type $taxonomy
		 * @return string
		 * @global type $wp_version
		 */
		function lafka_post_nav($same_category = false, $taxonomy = 'category') {
			global $wp_version;
			$excluded_terms = '';

			$type = get_post_type(get_queried_object_id());

			switch ($type) {
                case 'post':
	                $post_type_label = ' '.esc_html__('post', 'lafka');
	                break;
                case 'product':
                    $post_type_label = ' '.esc_html__('product', 'lafka');
	                break;
				case 'lafka-foodmenu':
					$post_type_label = ' '.esc_html__('menu item', 'lafka');
					break;
                default:
	                $post_type_label = '';
            }

			if (!is_singular() || is_post_type_hierarchical($type)) {
				$is_hierarchical = true;
			}

			if (!empty($is_hierarchical)) {
				return;
			}

			$entries = array();
			$prev_translated_key = esc_html__('prev', 'lafka');
			$next_translated_key = esc_html__('next', 'lafka');

			if ( version_compare( $wp_version, '3.8', '>=' ) ) {
				$entries['prev'] = array(
					'key_label' => esc_html__( 'prev', 'lafka' ),
					'near_post' => get_previous_post( $same_category, $excluded_terms, $taxonomy )
				);
				$entries['next'] = array(
					'key_label' => esc_html__( 'next', 'lafka' ),
					'near_post' => get_next_post( $same_category, $excluded_terms, $taxonomy )
				);
			} else {
				$entries['prev'] = array(
					'key_label' => esc_html__( 'prev', 'lafka' ),
					'near_post' => get_previous_post( $same_category )
				);
				$entries['next'] = array(
					'key_label' => esc_html__( 'next', 'lafka' ),
					'near_post' => get_next_post( $same_category )
				);
			}

			$output = "";

			foreach ($entries as $key => $entry) {
				if (empty($entry['near_post'])) {
					continue;
				}

				$the_title = lafka_generate_excerpt(get_the_title($entry['near_post']->ID), 75, " ", " ", true, '', true);
				$link = get_permalink($entry['near_post']->ID);

				$tc1 = $tc2 = "";

				$output .= "<a class='lafka-post-nav lafka-post-{$key} ' href='" . esc_url($link) . "' >";
				$output .= "    <span class='entry-info-wrap'>";
				$output .= "        <span class='entry-info'>";
				$tc1 = "            <span class='entry-title'><small>{$entry['key_label']}{$post_type_label}</small>{$the_title}</span>";
				$output .= $key == $prev_translated_key ? $tc1 . $tc2 : $tc2 . $tc1;
				$output .= "        </span>";
				$output .= "    </span>";
				$output .= "</a>";
			}
			return $output;
		}

	}

	// Disable autoptimize for bbPress pages
	add_filter('autoptimize_filter_noptimize', 'lafka_bbpress_noptimize', 10, 0);
	if (!function_exists('lafka_bbpress_noptimize')) {

		function lafka_bbpress_noptimize() {
			global $post;
			if (function_exists('is_bbpress') && is_bbpress() || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bbp-forum-index'))) {
				return true;
			} else {
				return false;
			}
		}

	}

add_action('activate_the-events-calendar/the-events-calendar.php', 'lafka_set_skeleton_styles_events');

if (!function_exists('lafka_set_skeleton_styles_events')) {

	/**
	 * Set skeleton styles option upon The Events Calendar plugin activation
	 */
	function lafka_set_skeleton_styles_events() {
		$events_options = get_option('tribe_events_calendar_options');
		if(is_array($events_options)) {
			$events_options['stylesheetOption'] = 'skeleton';

			update_option('tribe_events_calendar_options', $events_options);
		}
	}
}

// Remove &nbsp from titles
add_filter( 'the_title', 'lafka_remove_nbsp_from_titles', 10, 2 );
if ( ! function_exists( 'lafka_remove_nbsp_from_titles' ) ) {
	function lafka_remove_nbsp_from_titles( $title, $id ) {
		return str_replace( '&nbsp;', ' ', $title );
	}
}

//override date display with the time - ago
add_filter( 'the_time', 'lafka_convert_to_timeago_date_format', 10, 1 );

if ( ! function_exists( 'lafka_convert_to_timeago_date_format' ) ) {
	/**
     * Convert to time ago format
     *
	 * @param $orig_time
	 *
	 * @return string
	 */
	function lafka_convert_to_timeago_date_format( $orig_time ) {
		global $post;
		$post_unix_time = strtotime( $post->post_date );

		if (lafka_get_option('date_format') == 'lafka_format' && !lafka_is_time_more_than_x_months_ago(6, $post_unix_time)) {
			return human_time_diff( $post_unix_time, current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'lafka' );
		}

		return $orig_time;
	}
}

if ( ! function_exists( 'lafka_is_time_x_months_ago' ) ) {
	/**
     * Return true if $unix_time is more than $months months ago than current time
     *
	 * @param $months
	 * @param $unix_time
	 *
	 * @return bool
	 */
	function lafka_is_time_more_than_x_months_ago( $months, $unix_time ) {

		$x_months_ago = strtotime("-".$months." months");

		if ( $unix_time >= $x_months_ago ) {
			return false;
		}

		return true;
	}
}

// Fix All Import template error
add_action('pmxi_saved_post', 'lafka_remove_page_template', 10, 1);
if ( ! function_exists( 'lafka_remove_page_template' ) ) {
	function lafka_remove_page_template( $id ) {
		delete_post_meta( $id, '_wp_page_template' );
	}
}

if ( ! function_exists( 'lafka_should_show_account_icon' ) ) {
	function lafka_should_show_account_icon() {
		return (LAFKA_IS_WOOCOMMERCE && lafka_get_option('show_my_account') && get_option( 'woocommerce_myaccount_page_id' ) );
	}
}

if ( ! function_exists( 'lafka_should_show_wishlist_icon' ) ) {
	function lafka_should_show_wishlist_icon() {
		return (LAFKA_IS_WOOCOMMERCE && LAFKA_IS_WISHLIST && lafka_get_option('show_wish_in_header'));
	}
}

if ( ! function_exists( 'lafka_build_mobile_menu_items_wrap' ) ) {
	function lafka_build_mobile_menu_items_wrap() {
		global $post;
		ob_start();
		$current_user = wp_get_current_user();
		?>
        <ul class="lafka-mobile-menu-tabs">
            <li>
                <a class="lafka-mobile-menu-tab-link" href="#lafka_mobile_menu_tab"><?php echo esc_html__('Menu', 'lafka'); ?></a>
            </li>
			<?php $has_shortcode_my_account = isset($post->post_content) && has_shortcode($post->post_content, 'woocommerce_my_account'); ?>
			<?php if (lafka_should_show_account_icon() && wp_is_mobile() && (is_user_logged_in() || (!is_user_logged_in() && !$has_shortcode_my_account))): ?>
                <li>
                    <a class="lafka-mobile-account-tab-link" href="#lafka_mobile_account_tab"><?php echo esc_html__('My Account', 'lafka'); ?></a>
                </li>
			<?php endif; ?>
			<?php if (lafka_should_show_wishlist_icon()): ?>
                <li>
                    <a class="lafka-mobile-wishlist" href="<?php echo esc_url(str_replace('%', '%%', YITH_WCWL()->get_wishlist_url())); ?>"><?php echo esc_html__('Wishlist', 'lafka'); ?></a>
                </li>
			<?php endif; ?>
            <li>
                <a class="mob-close-toggle"></a>
            </li>
        </ul>
        <div id="lafka_mobile_menu_tab">
            <ul id="%1$s" class="%2$s">%3$s</ul>
        </div>
		<?php if (lafka_should_show_account_icon() && wp_is_mobile()): ?>
            <div id="lafka_mobile_account_tab">
				<?php if(is_user_logged_in()): ?>
                    <ul>
                        <li>
                            <span class="lafka-header-user-data">
                                <?php echo get_avatar($current_user->ID, 60); ?>
                                <small><?php echo esc_html($current_user->display_name); ?></small>
                            </span>
                        </li>
	                    <?php if (LAFKA_IS_WC_MARKETPLACE && is_user_wcmp_vendor($current_user)): ?>
                            <li class="lafka-header-account-wcmp-dash">
			                    <?php $lafka_wcmp_dashboard_page_link = wcmp_vendor_dashboard_page_id() ? get_permalink(wcmp_vendor_dashboard_page_id()) : '#'; ?>
			                    <?php echo apply_filters('wcmp_vendor_goto_dashboard', '<a href="' . esc_url(str_replace('%', '%%', $lafka_wcmp_dashboard_page_link)) . '">' . esc_html__('Vendor Dashboard', 'lafka') . '</a>'); ?>
                            </li>
	                    <?php elseif(LAFKA_IS_WC_VENDORS_PRO && WCV_Vendors::is_vendor( $current_user->ID )): ?>
                            <li class="lafka-header-account-vcvendors-pro-dash">
			                    <?php $lafka_wcv_pro_dashboard_page 	= WCVendors_Pro::get_option( 'dashboard_page_id' ); ?>
			                    <?php if($lafka_wcv_pro_dashboard_page): ?>
                                    <a href="<?php echo esc_url(str_replace('%', '%%', get_permalink($lafka_wcv_pro_dashboard_page))); ?>"><?php echo esc_html__('Vendor Dashboard', 'lafka'); ?></a>
			                    <?php endif; ?>
                            </li>
	                    <?php elseif(LAFKA_IS_WC_VENDORS && WCV_Vendors::is_vendor( $current_user->ID )): ?>
                            <li class="lafka-header-account-vcvendors-dash">
			                    <?php $lafka_wcv_free_dashboard_page 	= WC_Vendors::$pv_options->get_option( 'vendor_dashboard_page' ); ?>
			                    <?php if($lafka_wcv_free_dashboard_page): ?>
                                    <a href="<?php echo esc_url(str_replace('%', '%%', get_permalink($lafka_wcv_free_dashboard_page))); ?>"><?php echo esc_html__('Vendor Dashboard', 'lafka'); ?></a>
			                    <?php endif; ?>
                            </li>
	                    <?php endif; ?>
						<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
                            <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
                                <a href="<?php echo esc_url( str_replace('%', '%%', wc_get_account_endpoint_url( $endpoint )) ); ?>"><?php echo esc_html( $label ); ?></a>
                            </li>
						<?php endforeach; ?>
                    </ul>
				<?php elseif ( isset( $post->post_content ) && ! has_shortcode( $post->post_content, 'woocommerce_my_account' ) ): ?>
					<?php echo urldecode(do_shortcode('[woocommerce_my_account]')); ?>
				<?php endif; ?>
            </div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'lafka_has_foodmenu_options' ) ) {
	function lafka_has_foodmenu_options( $foodmenu ) {
		for ( $i = 1; $i <= 3; $i ++ ) {
			if ( $foodmenu->{'lafka_item_size' . $i} ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'lafka_get_foodmenu_options' ) ) {
	function lafka_get_foodmenu_options( $foodmenu ) {
		$foodmenu_options_array = array();

		for ( $i = 1; $i <= 3; $i ++ ) {
			if ( $foodmenu->{'lafka_item_size' . $i} ) {
				$foodmenu_options_array[ $foodmenu->{'lafka_item_size' . $i} ] = lafka_get_formatted_price( $foodmenu->{'lafka_item_price' . $i} );
			}
		}

		return $foodmenu_options_array;
	}
}

if ( ! function_exists( 'lafka_get_formatted_price' ) ) {
	function lafka_get_formatted_price( $price ) {
		$has_plus = strpos( $price, '+' );

		if ( LAFKA_IS_WOOCOMMERCE ) {
			$formatted_price = wc_price( $price );
			if ( $has_plus !== false ) {
				$formatted_price = '+' . $formatted_price;
			}
		} else {
			$currency       = '<span class="woocommerce-Price-currencySymbol">' . lafka_get_option( 'foodmenu_currency' ) . '</span>';
			$position       = lafka_get_option( 'foodmenu_currency_position' );
			$stripped_price = trim( str_replace( '+', '', $price ) );

			if ( $position === 'left' ) {
				if ( $has_plus !== false ) {
					$formatted_price = '+' . $currency . $stripped_price;
				} else {
					$formatted_price = $currency . $stripped_price;
				}
			} elseif ( $position === 'right' ) {
				$formatted_price = $stripped_price . $currency;
			} else {
				$formatted_price = $stripped_price;
			}
		}

		return $formatted_price;
	}
}

if ( ! function_exists( 'lafka_is_text_logo' ) ) {
	function lafka_is_text_logo( $lafka_theme_logo_img ) {
	    $to_return = false;

		if ( ! $lafka_theme_logo_img && ( get_bloginfo( 'name' ) || get_bloginfo( 'description' ) ) ) {
			$to_return = true;
		}
		return $to_return;
	}
}

if ( ! function_exists( 'get_nutrition_list_for_foodmenu_entry' ) ) {
	function get_nutrition_list_for_foodmenu_entry( $lafka_foodmenu_custom ) {

		$nutrition_list = array();
		if ( class_exists( 'Lafka_Nutrition_Config' ) ) {
			foreach ( Lafka_Nutrition_Config::$nutrition_meta_fields as $field_name => $data ) {
				if ( isset( $lafka_foodmenu_custom[ $field_name ] ) && is_numeric( $lafka_foodmenu_custom[ $field_name ][0] ) ) {
					$nutrition_list[ $field_name ] = $lafka_foodmenu_custom[ $field_name ][0];
				}
			}
		}

		return $nutrition_list;
	}
}

if ( ! function_exists( 'lafka_comments_are_valid_for_post' ) ) {
	/**
	 * Check if all comments are generated by the ActionScheduler
	 *
	 * @param $post_id
	 *
	 * @return array    Assoc array like this: array('valid_comments' => true, 'valid_comments_count' => 0)
	 */
	function lafka_comments_valid_for_post( $post_id ) {
		$to_return = array('valid_comments' => true, 'valid_comments_count' => 0);

		$comments                            = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		$number_of_action_scheduler_comments = 0;
		/** @var WP_Comment $comment */
		foreach ( $comments as $comment ) {
			if ( $comment->comment_author === 'ActionScheduler' ) {
				$number_of_action_scheduler_comments ++;
			}
		}
		if ( is_array( $comments ) && count( $comments ) === $number_of_action_scheduler_comments ) {
			$to_return['valid_comments'] = false;
		}

		$to_return['valid_comments_count'] = count($comments) - $number_of_action_scheduler_comments;

		return $to_return;
	}
}

add_filter( 'wcml_multi_currency_ajax_actions', 'lafka_add_action_to_multi_currency_ajax', 10, 1 );
if (!function_exists('lafka_add_action_to_multi_currency_ajax')) {
	/**
	 * This function is recommended way by WPML: https://wpml.org/forums/topic/mini-cart-not-showing-correct-currency/
	 * To add any ajax functions called in the theme, to be filtered by WPML
	 *
	 * @param $ajax_actions
	 *
	 * @return array
	 */
	function lafka_add_action_to_multi_currency_ajax( $ajax_actions ) {

		$ajax_actions[] = 'wptf_fragment_refresh'; // Add a AJAX action to the array
		$ajax_actions[] = 'wptf_ajax_add_to_cart';

		// Lafka actions below
		$ajax_actions[] = 'lafka_quickview';
		$ajax_actions[] = 'lafka_wc_add_cart';

		return $ajax_actions;
	}
}

add_filter( 'yith_wcwl_localize_script', 'lafka_add_wishlist_settings', 99 );
if ( ! function_exists( 'lafka_add_wishlist_settings' ) ) {
	/**
     * We need this to enable notifications for Wishlist.
     * This setting is included in their pro version.
     * Below function enables notices only if the setting is not already defined.
     *
	 * @param $wishlist_settings_array
	 *
	 * @return array
	 */
	function lafka_add_wishlist_settings( $wishlist_settings_array ) {
		if ( is_array( $wishlist_settings_array ) && ! array_key_exists( 'enable_notices', $wishlist_settings_array ) ) {
			$wishlist_settings_array['enable_notices'] = true;
		}

		return $wishlist_settings_array;
	}
}