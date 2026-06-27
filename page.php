<?php
/**
 * Static page template — handoff-spec rebuild (v5.75.0).
 *
 * Drops legacy Revolution Slider, FlexSlider, Tribe Events branches, and
 * the optional sidebar holster — none of which appear in the handoff
 * spec. Pages now render as a single-column reading layout with optional
 * hero (featured image), tight breadcrumb, h1, subtitle, and a 65ch
 * prose body that picks up Gutenberg block styling from the theme.
 *
 * Backward compat: still honours the `lafka_page_subtitle` post meta if
 * an operator set one in the legacy admin UI. New pages don't need it —
 * the post excerpt fills the same role.
 *
 * @package Lafka
 * @since   5.75.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$lafka_pg_id       = get_the_ID();
	$lafka_pg_title    = get_the_title();
	$lafka_pg_excerpt  = has_excerpt( $lafka_pg_id ) ? get_the_excerpt( $lafka_pg_id ) : '';
	$lafka_pg_thumb    = '';
	if ( has_post_thumbnail( $lafka_pg_id ) ) {
		$lafka_pg_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id( $lafka_pg_id ), 'full' );
		$lafka_pg_thumb     = $lafka_pg_thumb_src ? (string) $lafka_pg_thumb_src[0] : '';
	}
	$lafka_pg_subtitle = (string) get_post_meta( $lafka_pg_id, 'lafka_page_subtitle', true );
	if ( '' === $lafka_pg_subtitle && '' !== $lafka_pg_excerpt ) {
		$lafka_pg_subtitle = $lafka_pg_excerpt;
	}
	$lafka_pg_has_hero = ( '' !== $lafka_pg_thumb );
	?>
	<main id="main" class="lafka-page<?php echo $lafka_pg_has_hero ? ' lafka-page--has-hero' : ''; ?>" role="main">

		<header class="lafka-page__header">
			<?php if ( $lafka_pg_has_hero ) : ?>
				<div class="lafka-page__hero" role="img" aria-label="<?php echo esc_attr( $lafka_pg_title ); ?>" style="background-image:url('<?php echo esc_url( $lafka_pg_thumb ); ?>');"></div>
				<div class="lafka-page__hero-scrim" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="lafka-container lafka-page__header-inner">
				<nav class="lafka-page__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
					<span aria-hidden="true">/</span>
					<span><?php echo esc_html( $lafka_pg_title ); ?></span>
				</nav>
				<h1 class="lafka-page__title"><?php echo esc_html( $lafka_pg_title ); ?></h1>
				<?php if ( '' !== $lafka_pg_subtitle ) : ?>
					<p class="lafka-page__subtitle"><?php echo wp_kses_post( $lafka_pg_subtitle ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lafka-page__article' ); ?>>
			<div class="lafka-container">
				<div class="lafka-page__content entry-content">
					<?php the_content(); ?>
					<?php
					wp_link_pages(
						array(
							'before' => '<nav class="lafka-page__pagination" aria-label="' . esc_attr__( 'Page sections', 'lafka' ) . '"><span>' . esc_html__( 'Pages:', 'lafka' ) . '</span>',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<?php if ( comments_open() || get_comments_number() ) : ?>
					<div class="lafka-page__comments">
						<?php comments_template(); ?>
					</div>
				<?php endif; ?>
			</div>
		</article>

	</main>
	<?php
endwhile;

get_footer();
