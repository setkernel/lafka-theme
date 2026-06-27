<?php
/**
 * Single post template — handoff-spec rebuild (v5.76.0).
 *
 * Drops the legacy Revolution Slider, FlexSlider, sidebar holster, and
 * featured-image zoomable-background block. Single posts now render as
 * a reading layout aligned with page.php but with post meta (author,
 * date, category badge) and a clean prev/next nav.
 *
 * Shares lafka-page.css for typography + container. Post-specific
 * blocks (.lafka-post__meta, .lafka-post__nav, .lafka-post__tags) are
 * defined in the same stylesheet.
 *
 * @package Lafka
 * @since   5.76.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$lafka_pst_id    = get_the_ID();
	$lafka_pst_title = get_the_title();
	$lafka_pst_thumb = '';
	if ( has_post_thumbnail( $lafka_pst_id ) ) {
		$lafka_pst_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id( $lafka_pst_id ), 'full' );
		$lafka_pst_thumb     = $lafka_pst_thumb_src ? (string) $lafka_pst_thumb_src[0] : '';
	}
	$lafka_pst_has_hero = ( '' !== $lafka_pst_thumb );
	$lafka_pst_excerpt  = has_excerpt( $lafka_pst_id ) ? get_the_excerpt( $lafka_pst_id ) : '';
	$lafka_pst_cats     = get_the_category( $lafka_pst_id );
	$lafka_pst_tags     = get_the_tags( $lafka_pst_id );
	$lafka_pst_author   = (int) get_the_author_meta( 'ID' );
	$lafka_pst_avatar   = get_avatar( $lafka_pst_author, 40 );
	?>
	<main id="main" class="lafka-page lafka-page--post<?php echo $lafka_pst_has_hero ? ' lafka-page--has-hero' : ''; ?>" role="main">

		<header class="lafka-page__header">
			<?php if ( $lafka_pst_has_hero ) : ?>
				<div class="lafka-page__hero" role="img" aria-label="<?php echo esc_attr( $lafka_pst_title ); ?>" style="background-image:url('<?php echo esc_url( $lafka_pst_thumb ); ?>');"></div>
				<div class="lafka-page__hero-scrim" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="lafka-container lafka-page__header-inner">
				<nav class="lafka-page__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
					<?php if ( ! empty( $lafka_pst_cats ) ) : ?>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( get_category_link( $lafka_pst_cats[0]->term_id ) ); ?>"><?php echo esc_html( $lafka_pst_cats[0]->name ); ?></a>
					<?php endif; ?>
					<span aria-hidden="true">/</span>
					<span><?php echo esc_html( $lafka_pst_title ); ?></span>
				</nav>
				<h1 class="lafka-page__title"><?php echo esc_html( $lafka_pst_title ); ?></h1>
				<?php if ( '' !== $lafka_pst_excerpt ) : ?>
					<p class="lafka-page__subtitle"><?php echo wp_kses_post( $lafka_pst_excerpt ); ?></p>
				<?php endif; ?>
				<div class="lafka-post__meta">
					<?php
					if ( $lafka_pst_avatar ) {
						// get_avatar() returns safe HTML built from WP core.
						echo '<span class="lafka-post__avatar">' . wp_kses(
							$lafka_pst_avatar,
							array(
								'img' => array(
									'src'      => true,
									'srcset'   => true,
									'sizes'    => true,
									'class'    => true,
									'alt'      => true,
									'width'    => true,
									'height'   => true,
									'loading'  => true,
									'decoding' => true,
								),
							)
						) . '</span>';
					}
					?>
					<span class="lafka-post__author"><?php echo esc_html( get_the_author() ); ?></span>
					<span class="lafka-post__sep" aria-hidden="true">·</span>
					<time class="lafka-post__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</div>
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

				<?php if ( ! empty( $lafka_pst_tags ) ) : ?>
					<div class="lafka-post__tags">
						<span class="lafka-post__tags-label"><?php esc_html_e( 'Tagged', 'lafka' ); ?></span>
						<ul class="lafka-post__tags-list" role="list">
							<?php foreach ( $lafka_pst_tags as $lafka_pst_tag ) : ?>
								<li><a class="lafka-post__tag" href="<?php echo esc_url( get_tag_link( $lafka_pst_tag->term_id ) ); ?>">#<?php echo esc_html( $lafka_pst_tag->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<nav class="lafka-post__nav" aria-label="<?php esc_attr_e( 'Post navigation', 'lafka' ); ?>">
					<?php
					$lafka_pst_prev = get_previous_post();
					$lafka_pst_next = get_next_post();
					?>
					<?php if ( $lafka_pst_prev instanceof WP_Post ) : ?>
						<a class="lafka-post__nav-link lafka-post__nav-link--prev" href="<?php echo esc_url( get_permalink( $lafka_pst_prev ) ); ?>" rel="prev">
							<span class="lafka-post__nav-label"><?php esc_html_e( '← Previous', 'lafka' ); ?></span>
							<span class="lafka-post__nav-title"><?php echo esc_html( get_the_title( $lafka_pst_prev ) ); ?></span>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
					<?php if ( $lafka_pst_next instanceof WP_Post ) : ?>
						<a class="lafka-post__nav-link lafka-post__nav-link--next" href="<?php echo esc_url( get_permalink( $lafka_pst_next ) ); ?>" rel="next">
							<span class="lafka-post__nav-label"><?php esc_html_e( 'Next →', 'lafka' ); ?></span>
							<span class="lafka-post__nav-title"><?php echo esc_html( get_the_title( $lafka_pst_next ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>

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
