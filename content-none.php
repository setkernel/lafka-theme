<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// The template for displaying a "No posts found" message.
?>
<?php $lafka_none_classes = get_post_class(); ?>
<div<?php echo ! empty( $lafka_none_classes ) ? ' class="' . esc_attr( implode( ' ', $lafka_none_classes ) ) . '"' : ''; ?>>
	<h2 class="heading-title"><?php esc_html_e( 'Nothing Found', 'lafka' ); ?></h2>
	<div class="blog-post-excerpt">
		<p><?php esc_html_e( 'Apologies, but no results were found. Perhaps searching will help find a related post.', 'lafka' ); ?></p>
		<?php get_search_form(); ?>
	</div>
</div>
