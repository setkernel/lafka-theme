<?php
// The 404 Page template file.
get_header(); ?>
    <div id="content">
    <div id="lafka_page_title" class="lafka_title_holder">
        <div class="inner fixed">
            <div class="lafka-title-text-container">
                <?php lafka_breadcrumb() ?>
                <h1 class="heading-title"><?php esc_html_e( 'Page not found', 'lafka' ) ?></h1>
            </div>
        </div>
    </div>
    <div class="inner">
        <div id="main" class="fixed box box-common">
            <div class="content_holder">
                <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'lafka' ); ?></p>
				<?php get_search_form(); ?>
            </div>
        </div>
    </div>
<?php
get_footer();
