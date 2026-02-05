<?php
/*
  Template Name: Blank page
 */
global $lafka_is_blank;
$lafka_is_blank = true;

get_header();
?>
<div id="content">
	<div class="inner">
		<!-- CONTENT WRAPPER -->
		<div id="main" class="fixed box box-common">
			<div class="content_holder">
				<?php while (have_posts()) : the_post(); ?>
					<?php get_template_part('content', 'page'); ?>
				<?php endwhile; ?>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
