<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// One post in a blog list (index.php, archive.php, search.php). Single posts
// render through single.php.

// Featured image size
$lafka_featured_image_size = 'lafka-content-wide';

$lafka_post_classes = array( 'blog-post' );
if ( ! has_post_thumbnail() ) {
	array_push( $lafka_post_classes, 'lafka-post-no-image' );
}
?>
<div id="post-<?php the_ID(); ?>" <?php post_class( $lafka_post_classes ); ?>>
	<?php // Featured content for post list ?>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="post-unit-holder">
			<?php the_post_thumbnail( $lafka_featured_image_size ); ?>
			<?php if ( ! is_single() ) : ?>
				<div class="foodmenu-unit-info">
					<a class="go_to_page go_to_page_blog" title="<?php esc_attr_e( 'View', 'lafka' ); ?>" href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php // End Featured content for post list ?>

	<div class="lafka_post_data_holder">
		<?php if ( ! is_singular() ) : ?>
			<?php get_template_part( 'partials/blog-post-meta-top' ); ?>
		<?php endif; ?>
		<?php if ( ! is_single() ) : ?>
			<h2	class="heading-title">
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
			</h2>
		<?php endif; ?>

		<?php if ( ! is_singular() ) : ?>
			<?php get_template_part( 'partials/blog-post-meta-bottom' ); ?>
		<?php endif; ?>

		<?php // SINGLE POST CONTENT ?>
		<?php if ( is_single() ) : ?>
			<?php the_content(); ?>
			<div class="clear"></div>
			<?php if ( get_theme_mod( 'lafka_show_author_info', true ) && ( trim( get_the_author_meta( 'description' ) ) ) ) : ?>
				<div class="lafka-author-info">
					<div class="title">
						<h2><?php echo esc_html__( 'About the Author:', 'lafka' ); ?> <?php the_author_posts_link(); ?></h2>
					</div>
					<div class="lafka-author-content">
						<div class="avatar">
							<?php echo get_avatar( get_the_author_meta( 'email' ), 160 ); ?>
						</div>
						<div class="description">
							<?php the_author_meta( 'description' ); ?>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			<?php endif; ?>
			<?php
			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lafka' ),
					'after'  => '</div>',
				)
			);
			?>
		<?php else : ?>
			<?php // BLOG / ARCHIVE / CATEGORY / TAG / SEARCH / SHORTCODE POST CONTENT ?>
			<div class="blog-post-excerpt">
				<?php
				if ( isset( $post->post_content ) && strpos( $post->post_content, '<!--more-->' ) ) {
					the_content();
				} else {
					echo '<div class="lafka-defined-excerpt">';
					the_excerpt();
					echo '</div>';
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</div>