<?php defined( 'ABSPATH' ) || exit; ?>
<?php
/**
 * Created by PhpStorm.
 * User: aatanasov
 * Date: 4/28/2017
 * Time: 4:06 PM
 */

// Show or not the author avatar
$lafka_show_author_avatar = false;
if ( ( lafka_get_option( 'show_author_avatar' ) ) || ( is_singular() && ! lafka_get_option( 'show_author_info' ) && lafka_get_option( 'show_author_avatar' ) ) ) {
	$lafka_show_author_avatar = true;
}
?>
<div class="blog-post-meta post-meta-bottom">

	<?php if ( ! isset( $lafka_is_latest_posts ) ) : ?>
		<span class="posted_by">
			<?php if ( $lafka_show_author_avatar && get_avatar( get_the_author_meta( 'ID' ), 60 ) ) : ?>
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 60 ); ?>
			<?php else : ?>
				<i class="fa fa-user"></i>
			<?php endif; ?>
			<?php
			echo ' ';
			the_author_posts_link();
			?>
		</span>
		<span class="post-meta-date">
			<a href="<?php echo esc_url( get_the_permalink() ); ?>">
				<?php the_time( get_option( 'date_format' ) ); ?>
			</a>
		</span>
	<?php endif; ?>

	<?php if ( is_singular() ) : ?>
		<?php if ( $lafka_categories = get_the_category() ) : ?>
			<span class="posted_in"><i class="fa fa-folder-open"></i>
				<?php $lafka_lastElmnt = end( $lafka_categories ); ?>
				<?php foreach ( $lafka_categories as $lafka_category ) : ?>
					<a href="<?php echo esc_url( get_category_link( $lafka_category->term_id ) ); ?>"
						title="<?php printf( esc_attr__( 'View all posts in %s', 'lafka' ), esc_attr( $lafka_category->name ) ); ?>"><?php echo esc_html( $lafka_category->name ); ?></a>
						<?php
						if ( $lafka_category != $lafka_lastElmnt ) :
							?>
							,<?php endif; ?>
				<?php endforeach; ?>
				</span>
		<?php endif; ?>
		<?php if ( ! isset( $lafka_is_latest_posts ) ) : ?>
			<?php the_tags( '<i class="fa fa-tags"></i> ' ); ?>
			<span class="count_comments"><i class="fa fa-comments"></i> <a
						href="<?php echo esc_url( get_comments_link() ); ?>"
						title="<?php esc_attr_e( 'View comments', 'lafka' ); ?>"><?php echo get_comments_number(); ?></a></span>
		<?php endif; ?>
	<?php endif; ?>

</div>