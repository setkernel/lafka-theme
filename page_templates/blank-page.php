<?php
/**
 * Template Name: Content only
 * Template Post Type: page
 *
 * The page content inside the normal header and footer, with no title,
 * breadcrumb or hero. This template was labelled "Blank page", but it has
 * rendered the full site chrome since the 5.55 header rebuild; the file name
 * stays blank-page.php so pages already assigned to it keep working.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="content">
	<div class="inner">
		<!-- CONTENT WRAPPER -->
		<div id="main" class="fixed box box-common">
			<div class="content_holder">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<?php get_template_part( 'content', 'page' ); ?>
				<?php endwhile; ?>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
