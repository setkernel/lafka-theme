<?php
/**
 * Created by PhpStorm.
 * User: aatanasov
 * Date: 4/28/2017
 * Time: 4:06 PM
 */
?>
<div class="blog-post-meta post-meta-top">
	<?php if ( $lafka_categories = get_the_category() ): ?>
        <span class="posted_in"><i class="fa fa-folder-open"></i>
			<?php $lafka_lastElmnt = end( $lafka_categories ); ?>
			<?php foreach ( $lafka_categories as $lafka_category ): ?>
                <a href="<?php echo esc_url( get_category_link( $lafka_category->term_id ) ) ?>"
                   title="<?php echo sprintf( esc_attr__( "View all posts in %s", 'lafka' ), esc_attr( $lafka_category->name ) ) ?>"><?php echo esc_html( $lafka_category->name ) ?></a><?php if ( $lafka_category != $lafka_lastElmnt ): ?>,<?php endif; ?>
			<?php endforeach; ?>
				</span>
	<?php endif; ?>
	<?php if ( ! isset( $lafka_is_latest_posts ) ): ?>
		<?php the_tags( '<i class="fa fa-tags"></i> ' ); ?>
        <span class="count_comments"><i class="fa fa-comments"></i> <a
                    href="<?php echo esc_url( get_comments_link() ) ?>"
                    title="<?php esc_attr_e("View comments", "lafka")?>"><?php echo get_comments_number() ?></a></span>
	<?php endif; ?>
</div>