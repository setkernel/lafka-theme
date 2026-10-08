<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// The Archive template file for lafka-foodmenu CPT.

get_header();

// Load the partial
get_template_part( 'partials/content', 'lafka-foodmenu-category' );

get_footer();
