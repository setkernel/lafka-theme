<?php
/**
 * Partial: site search results on counter storefronts (GX O-15 / T-27).
 *
 * The same page shell as the menu (lafka-menu header, crumbs, a real product
 * search form) with the matching pages / posts as plain links + excerpts, a
 * result count, numbered pagination and an empty state that points to the
 * menu. No author links, no full-size images.
 *
 * @package Lafka
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lafka_sr_query = function_exists( 'get_search_query' ) ? (string) get_search_query( false ) : '';
$lafka_sr_found = ( isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) && isset( $GLOBALS['wp_query']->found_posts ) ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
$lafka_sr_menu  = function_exists( 'lafka_theme_menu_url' ) ? lafka_theme_menu_url() : home_url( '/' );
?>
<div id="content" class="lafka-menu lafka-menu--search lafka-search-results">
	<header class="lafka-menu__header">
		<div class="lafka-container">
			<nav class="lafka-menu__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php esc_html_e( 'Search results', 'lafka' ); ?></span>
			</nav>
			<h1 class="lafka-menu__title">
				<?php
				echo esc_html(
					'' !== $lafka_sr_query
						/* translators: %s: the visitor's search words. */
						? sprintf( __( 'Results for “%s”', 'lafka' ), $lafka_sr_query )
						: __( 'Search', 'lafka' )
				);
				?>
			</h1>
			<?php if ( $lafka_sr_found > 0 ) : ?>
				<p class="lafka-menu__lead">
					<?php
					/* translators: %s: number of results. */
					echo esc_html( sprintf( _n( '%s result', '%s results', $lafka_sr_found, 'lafka' ), number_format_i18n( $lafka_sr_found ) ) );
					?>
				</p>
			<?php endif; ?>
		</div>
	</header>

	<div class="lafka-container">
		<form class="lafka-menu__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="lafka-menu__search-label" for="lafka-search-results-input">
				<span class="screen-reader-text"><?php esc_html_e( 'Search the menu', 'lafka' ); ?></span>
				<span class="lafka-menu__search-icon" aria-hidden="true">🔍</span>
				<input type="search" id="lafka-search-results-input" class="lafka-menu__search-input" name="s" value="<?php echo esc_attr( $lafka_sr_query ); ?>" placeholder="<?php esc_attr_e( 'Search the menu…', 'lafka' ); ?>">
				<input type="hidden" name="post_type" value="product">
			</label>
		</form>
	</div>

	<div class="lafka-menu__body" id="main">
		<div class="lafka-container">
			<?php if ( have_posts() ) : ?>
				<ul class="lafka-search-results__list" role="list">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<li class="lafka-search-results__item">
							<h2 class="lafka-search-results__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="lafka-search-results__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt() ), 30 ) ); ?></p>
						</li>
					<?php endwhile; ?>
				</ul>
				<?php
				if ( function_exists( 'lafka_menu_pagination_html' ) ) {
					echo lafka_menu_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() markup built from escaped URLs.
				}
				?>
			<?php else : ?>
				<div class="lafka-menu__empty">
					<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
					<h2 class="lafka-menu__empty-title">
						<?php
						echo esc_html(
							'' !== $lafka_sr_query
								/* translators: %s: the visitor's search words. */
								? sprintf( __( 'Nothing matches “%s”', 'lafka' ), $lafka_sr_query )
								: __( 'Type what you are looking for', 'lafka' )
						);
						?>
					</h2>
					<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Try a shorter word, or browse the whole menu.', 'lafka' ); ?></p>
					<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_sr_menu ); ?>"><?php esc_html_e( 'See the full menu', 'lafka' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
get_footer();
