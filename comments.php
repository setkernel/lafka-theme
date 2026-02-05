<?php
// The template for displaying Comments.

$lafka_commenter = wp_get_current_commenter();
$lafka_req = get_option('require_name_email');
$lafka_aria_req = ( $lafka_req ? " aria-required='true'" : '' );
?>
<div class="clear"></div>
<?php if (post_password_required()) : ?>
	<div id="comments">
		<p class="nopassword"><?php esc_html_e('This post is password protected. Enter the password to view any comments.', 'lafka'); ?></p>
	</div><!-- #comments -->
	<?php return; ?>
<?php endif; ?>

<div id="comments">
	<?php
	$lafka_comments_meta = lafka_comments_valid_for_post( get_the_ID() );
	?>
	<?php if ( have_comments() && $lafka_comments_meta['valid_comments'] ) : ?>
		<h3 class="heading-title">
			<?php
			printf(_n('%1$s thought on %2$s', '%1$s thoughts on %2$s', $lafka_comments_meta['valid_comments_count'], 'lafka'), '<span class="lafka_comments_count">' . number_format_i18n($lafka_comments_meta['valid_comments_count']) . '</span>', '<span>' . get_the_title() . '</span>');
			?>
		</h3>

		<?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
			<div id="comment-nav-above">
				<div class="nav-previous"><?php previous_comments_link(esc_html__('&larr; Older Comments', 'lafka')); ?></div>
				<div class="nav-next"><?php next_comments_link(esc_html__('Newer Comments &rarr;', 'lafka')); ?></div>
			</div>
		<?php endif; ?>

		<ul class="commentlist">
			<?php
			wp_list_comments(array('callback' => 'lafka_comment'));
			?>
		</ul>

		<?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : // are there comments to navigate through  ?>
			<div id="comment-nav-below">
				<div class="nav-previous"><?php previous_comments_link(esc_html__('&larr; Older Comments', 'lafka')); ?></div>
				<div class="nav-next"><?php next_comments_link(esc_html__('Newer Comments &rarr;', 'lafka')); ?></div>
			</div>
		<?php endif; ?>

		<?php
		if (!comments_open() && get_comments_number()) :
			?>
			<p class="nocomments"><?php esc_html_e('Comments are closed.', 'lafka'); ?></p>
		<?php endif; ?>

	<?php endif; ?>

	<?php comment_form(); ?>

</div><!-- #comments -->
