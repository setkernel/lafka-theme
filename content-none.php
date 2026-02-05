<?php
// The template for displaying a "No posts found" message.
?>
<div <?php !empty( get_post_class() ) ? post_class() : ''; ?>>
    <h2 class="heading-title"><?php esc_html_e( 'Nothing Found', 'lafka' ); ?></h2>
    <div class="blog-post-excerpt">
        <p><?php esc_html_e( 'Apologies, but no results were found. Perhaps searching will help find a related post.', 'lafka' ); ?></p>
		<?php get_search_form(); ?>
    </div>
</div>