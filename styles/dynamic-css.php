<?php defined( 'ABSPATH' ) || exit; ?>
<?php
/**
 * Generate CSS custom properties from theme options.
 * All selectors live in style.css for browser caching.
 * Only ~100 lines of :root{} declarations are inlined per page.
 */
add_action( 'wp_enqueue_scripts', 'lafka_add_custom_css', 99 );

if ( ! function_exists( 'lafka_add_custom_css' ) ) {

	function lafka_add_custom_css() {
		// Gather all theme options
		$accent_color                    = esc_attr( lafka_get_option( 'accent_color' ) );
		$logo_bg_color                   = esc_attr( lafka_get_option( 'logo_background_color' ) );
		$links_color                     = esc_attr( lafka_get_option( 'links_color' ) );
		$links_hover_color               = esc_attr( lafka_get_option( 'links_hover_color' ) );
		$sidebar_titles_color            = esc_attr( lafka_get_option( 'sidebar_titles_color' ) );
		$all_buttons_color               = esc_attr( lafka_get_option( 'all_buttons_color' ) );
		$all_buttons_hover_color         = esc_attr( lafka_get_option( 'all_buttons_hover_color' ) );
		$new_label_color                 = esc_attr( lafka_get_option( 'new_label_color' ) );
		$sale_label_color                = esc_attr( lafka_get_option( 'sale_label_color' ) );
		$page_title_color                = esc_attr( lafka_get_option( 'page_title_color' ) );
		$page_subtitle_color             = esc_attr( lafka_get_option( 'page_subtitle_color' ) );
		$custom_page_title_color         = esc_attr( lafka_get_option( 'custom_page_title_color' ) );
		$transparent_dark_menu_color     = esc_attr( lafka_get_option( 'transparent_header_dark_menu_color' ) );
		$page_title_bg_color             = esc_attr( lafka_get_option( 'page_title_bckgr_color' ) );
		$page_title_border_color         = esc_attr( lafka_get_option( 'page_title_border_color' ) );
		$header_top_bar_color            = esc_attr( lafka_get_option( 'header_top_bar_color' ) );
		$header_top_bar_border_raw       = lafka_get_option( 'header_top_bar_border_color' );
		$header_top_bar_border_color     = $header_top_bar_border_raw ? esc_attr( $header_top_bar_border_raw ) : 'transparent';
		$top_bar_message_color           = esc_attr( lafka_get_option( 'top_bar_message_color' ) );
		$header_services_color           = esc_attr( lafka_get_option( 'header_services_color' ) );
		$top_bar_menu_links_color        = esc_attr( lafka_get_option( 'top_bar_menu_links_color' ) );
		$top_bar_menu_links_hover_color  = esc_attr( lafka_get_option( 'top_bar_menu_links_hover_color' ) );
		$collapsible_bg_color            = esc_attr( lafka_get_option( 'collapsible_bckgr_color' ) );
		$collapsible_titles_color        = esc_attr( lafka_get_option( 'collapsible_titles_color' ) );
		$collapsible_titles_border_color = esc_attr( lafka_get_option( 'collapsible_titles_border_color' ) );
		$collapsible_links_color         = esc_attr( lafka_get_option( 'collapsible_links_color' ) );
		$footer_titles_color             = esc_attr( lafka_get_option( 'footer_titles_color' ) );
		$footer_title_border_color       = esc_attr( lafka_get_option( 'footer_title_border_color' ) );
		$footer_copyright_text_color     = esc_attr( lafka_get_option( 'footer_copyright_bar_text_color' ) );
		$footer_menu_links_color         = esc_attr( lafka_get_option( 'footer_menu_links_color' ) );
		$footer_links_color              = esc_attr( lafka_get_option( 'footer_links_color' ) );
		$footer_text_color               = esc_attr( lafka_get_option( 'footer_text_color' ) );
		$footer_copyright_bg_raw         = lafka_get_option( 'footer_copyright_bar_bckgr_color' );
		$footer_copyright_bg_color       = $footer_copyright_bg_raw ? esc_attr( $footer_copyright_bg_raw ) : 'transparent';
		$add_to_cart_color               = esc_attr( lafka_get_option( 'add_to_cart_color' ) );
		$price_color                     = esc_attr( lafka_get_option( 'price_color_in_listings' ) );
		$price_bg_color                  = esc_attr( lafka_get_option( 'price_background_color_in_listings' ) );
		$fancy_category_title_color      = esc_attr( lafka_get_option( 'fancy_category_title_color' ) );

		// Main menu
		$menu_bg_color             = esc_attr( lafka_get_option( 'main_menu_background_color' ) );
		$menu_links_color          = esc_attr( lafka_get_option( 'main_menu_links_color' ) );
		$menu_links_hover_color    = esc_attr( lafka_get_option( 'main_menu_links_hover_color' ) );
		$menu_links_bg_hover_raw   = lafka_get_option( 'main_menu_links_bckgr_hover_color' );
		$menu_links_bg_hover_color = $menu_links_bg_hover_raw ? esc_attr( $menu_links_bg_hover_raw ) : 'transparent';
		$menu_highlight_bg_color   = $menu_links_bg_hover_raw ? esc_attr( $menu_links_bg_hover_raw ) : $accent_color;
		$menu_icons_color_raw      = lafka_get_option( 'main_menu_icons_color' );
		$menu_icons_color          = $menu_icons_color_raw ? esc_attr( $menu_icons_color_raw ) : 'inherit';

		// Main menu typography
		$main_menu_typography = lafka_get_option( 'main_menu_typography' );
		$main_menu_style      = json_decode( $main_menu_typography['style'], true );
		$menu_font_size       = esc_attr( $main_menu_typography['size'] );
		$menu_font_weight     = $main_menu_style ? esc_attr( $main_menu_style['font-weight'] ) : 'normal';
		$menu_font_style      = $main_menu_style ? esc_attr( $main_menu_style['font-style'] ) : 'normal';

		// Top menu typography
		$top_menu_typography  = lafka_get_option( 'top_menu_typography' );
		$top_menu_style       = json_decode( $top_menu_typography['style'], true );
		$top_menu_font_size   = esc_attr( $top_menu_typography['size'] );
		$top_menu_font_weight = $top_menu_style ? esc_attr( $top_menu_style['font-weight'] ) : 'normal';
		$top_menu_font_style  = $top_menu_style ? esc_attr( $top_menu_style['font-style'] ) : 'normal';

		// Body font
		$body_font        = lafka_get_option( 'body_font' );
		$body_font_family = ! empty( $body_font['face'] ) ? '"' . esc_attr( $body_font['face'] ) . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' : '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
		$body_font_size   = esc_attr( $body_font['size'] );
		$body_font_color  = esc_attr( $body_font['color'] );

		// Text logo typography
		$text_logo_typography = lafka_get_option( 'text_logo_typography' );
		$text_logo_style      = json_decode( $text_logo_typography['style'], true );
		$logo_font_color      = esc_attr( $text_logo_typography['color'] );
		$logo_font_size       = esc_attr( $text_logo_typography['size'] );
		$logo_font_weight     = $text_logo_style ? esc_attr( $text_logo_style['font-weight'] ) : 'normal';
		$logo_font_style      = $text_logo_style ? esc_attr( $text_logo_style['font-style'] ) : 'normal';

		// Headings font
		$headings_font        = lafka_get_option( 'headings_font' );
		$headings_font_family = ! empty( $headings_font['face'] ) ? '"' . esc_attr( $headings_font['face'] ) . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' : 'inherit';

		// H1-H6 fonts
		$h_vars = '';
		for ( $i = 1; $i <= 6; $i++ ) {
			$h_font  = lafka_get_option( 'h' . $i . '_font' );
			$h_style = json_decode( $h_font['style'], true );
			$h_vars .= '--lafka-h' . $i . '-color:' . esc_attr( $h_font['color'] ) . ';';
			$h_vars .= '--lafka-h' . $i . '-size:' . esc_attr( $h_font['size'] ) . ';';
			$h_vars .= '--lafka-h' . $i . '-weight:' . ( $h_style ? esc_attr( $h_style['font-weight'] ) : 'normal' ) . ';';
			$h_vars .= '--lafka-h' . $i . '-style:' . ( $h_style ? esc_attr( $h_style['font-style'] ) : 'normal' ) . ';';
		}

		// Header background
		$header_backgr        = lafka_get_option( 'header_background' );
		$header_bg_color      = esc_attr( $header_backgr['color'] );
		$header_bg_image      = 'none';
		$header_bg_position   = 'center center';
		$header_bg_repeat     = 'no-repeat';
		$header_bg_attachment = 'scroll';
		if ( $header_backgr['image'] ) {
			$header_bg_image      = 'url("' . esc_url( wp_get_attachment_image_url( $header_backgr['image'], 'full' ) ) . '")';
			$header_bg_position   = esc_attr( $header_backgr['position'] );
			$header_bg_repeat     = esc_attr( $header_backgr['repeat'] );
			$header_bg_attachment = esc_attr( $header_backgr['attachment'] );
		}

		// Footer background
		$footer_backgr        = lafka_get_option( 'footer_background' );
		$footer_bg_color      = esc_attr( $footer_backgr['color'] );
		$footer_bg_image      = 'none';
		$footer_bg_position   = 'center center';
		$footer_bg_repeat     = 'no-repeat';
		$footer_bg_attachment = 'scroll';
		$footer_bg_size       = 'auto';
		if ( $footer_backgr['image'] ) {
			$footer_bg_image      = 'url("' . esc_url( wp_get_attachment_image_url( $footer_backgr['image'], 'full' ) ) . '")';
			$footer_bg_position   = esc_attr( $footer_backgr['position'] );
			$footer_bg_repeat     = esc_attr( $footer_backgr['repeat'] );
			$footer_bg_attachment = esc_attr( $footer_backgr['attachment'] );
			if ( $footer_backgr['repeat'] === 'no-repeat' ) {
				$footer_bg_size = 'cover';
			}
		}

		// Title background image
		$title_backgr       = lafka_get_option( 'page_title_default_bckgr_image' );
		$title_bg_image     = 'none';
		$title_bg_font_size = 'inherit';
		if ( $title_backgr ) {
			$title_bg_image     = 'url("' . esc_url( wp_get_attachment_image_url( $title_backgr, 'full' ) ) . '")';
			$title_bg_font_size = '64px';
		}

		$custom_css  = ':root{';
		$custom_css .= '--lafka-accent-color:' . $accent_color . ';';
		$custom_css .= '--lafka-logo-bg-color:' . $logo_bg_color . ';';
		$custom_css .= '--lafka-link-color:' . $links_color . ';';
		$custom_css .= '--lafka-link-hover-color:' . $links_hover_color . ';';
		$custom_css .= '--lafka-sidebar-title-color:' . $sidebar_titles_color . ';';
		$custom_css .= '--lafka-button-color:' . $all_buttons_color . ';';
		$custom_css .= '--lafka-button-hover-color:' . $all_buttons_hover_color . ';';
		$custom_css .= '--lafka-new-label-color:' . $new_label_color . ';';
		$custom_css .= '--lafka-sale-label-color:' . $sale_label_color . ';';
		$custom_css .= '--lafka-page-title-color:' . $page_title_color . ';';
		$custom_css .= '--lafka-page-subtitle-color:' . $page_subtitle_color . ';';
		$custom_css .= '--lafka-custom-page-title-color:' . $custom_page_title_color . ';';
		$custom_css .= '--lafka-transparent-dark-menu-color:' . $transparent_dark_menu_color . ';';
		$custom_css .= '--lafka-page-title-bg-color:' . $page_title_bg_color . ';';
		$custom_css .= '--lafka-page-title-border-color:' . $page_title_border_color . ';';
		$custom_css .= '--lafka-header-top-bar-color:' . $header_top_bar_color . ';';
		$custom_css .= '--lafka-header-top-bar-border-color:' . $header_top_bar_border_color . ';';
		$custom_css .= '--lafka-menu-bg-color:' . $menu_bg_color . ';';
		$custom_css .= '--lafka-menu-link-color:' . $menu_links_color . ';';
		$custom_css .= '--lafka-menu-link-hover-color:' . $menu_links_hover_color . ';';
		$custom_css .= '--lafka-menu-link-bg-hover-color:' . $menu_links_bg_hover_color . ';';
		$custom_css .= '--lafka-menu-highlight-bg-color:' . $menu_highlight_bg_color . ';';
		$custom_css .= '--lafka-menu-icon-color:' . $menu_icons_color . ';';
		$custom_css .= '--lafka-top-bar-message-color:' . $top_bar_message_color . ';';
		$custom_css .= '--lafka-header-services-color:' . $header_services_color . ';';
		$custom_css .= '--lafka-top-menu-link-color:' . $top_bar_menu_links_color . ';';
		$custom_css .= '--lafka-top-menu-link-hover-color:' . $top_bar_menu_links_hover_color . ';';
		$custom_css .= '--lafka-collapsible-bg-color:' . $collapsible_bg_color . ';';
		$custom_css .= '--lafka-collapsible-title-color:' . $collapsible_titles_color . ';';
		$custom_css .= '--lafka-collapsible-title-border-color:' . $collapsible_titles_border_color . ';';
		$custom_css .= '--lafka-collapsible-link-color:' . $collapsible_links_color . ';';
		$custom_css .= '--lafka-footer-title-color:' . $footer_titles_color . ';';
		$custom_css .= '--lafka-footer-title-border-color:' . $footer_title_border_color . ';';
		$custom_css .= '--lafka-footer-copyright-text-color:' . $footer_copyright_text_color . ';';
		$custom_css .= '--lafka-footer-menu-link-color:' . $footer_menu_links_color . ';';
		$custom_css .= '--lafka-footer-link-color:' . $footer_links_color . ';';
		$custom_css .= '--lafka-footer-text-color:' . $footer_text_color . ';';
		$custom_css .= '--lafka-footer-copyright-bg-color:' . $footer_copyright_bg_color . ';';
		$custom_css .= '--lafka-add-to-cart-color:' . $add_to_cart_color . ';';
		$custom_css .= '--lafka-price-color:' . $price_color . ';';
		$custom_css .= '--lafka-price-bg-color:' . $price_bg_color . ';';
		$custom_css .= '--lafka-fancy-category-title-color:' . $fancy_category_title_color . ';';
		// Typography
		$custom_css .= '--lafka-body-font-family:' . $body_font_family . ';';
		$custom_css .= '--lafka-body-font-size:' . $body_font_size . ';';
		$custom_css .= '--lafka-body-font-color:' . $body_font_color . ';';
		$custom_css .= '--lafka-headings-font-family:' . $headings_font_family . ';';
		$custom_css .= '--lafka-logo-font-color:' . $logo_font_color . ';';
		$custom_css .= '--lafka-logo-font-size:' . $logo_font_size . ';';
		$custom_css .= '--lafka-logo-font-weight:' . $logo_font_weight . ';';
		$custom_css .= '--lafka-logo-font-style:' . $logo_font_style . ';';
		$custom_css .= '--lafka-menu-font-size:' . $menu_font_size . ';';
		$custom_css .= '--lafka-menu-font-weight:' . $menu_font_weight . ';';
		$custom_css .= '--lafka-menu-font-style:' . $menu_font_style . ';';
		$custom_css .= '--lafka-top-menu-font-size:' . $top_menu_font_size . ';';
		$custom_css .= '--lafka-top-menu-font-weight:' . $top_menu_font_weight . ';';
		$custom_css .= '--lafka-top-menu-font-style:' . $top_menu_font_style . ';';
		$custom_css .= $h_vars;
		// Backgrounds
		$custom_css .= '--lafka-header-bg-color:' . $header_bg_color . ';';
		$custom_css .= '--lafka-header-bg-image:' . $header_bg_image . ';';
		$custom_css .= '--lafka-header-bg-position:' . $header_bg_position . ';';
		$custom_css .= '--lafka-header-bg-repeat:' . $header_bg_repeat . ';';
		$custom_css .= '--lafka-header-bg-attachment:' . $header_bg_attachment . ';';
		$custom_css .= '--lafka-footer-bg-color:' . $footer_bg_color . ';';
		$custom_css .= '--lafka-footer-bg-image:' . $footer_bg_image . ';';
		$custom_css .= '--lafka-footer-bg-position:' . $footer_bg_position . ';';
		$custom_css .= '--lafka-footer-bg-repeat:' . $footer_bg_repeat . ';';
		$custom_css .= '--lafka-footer-bg-attachment:' . $footer_bg_attachment . ';';
		$custom_css .= '--lafka-footer-bg-size:' . $footer_bg_size . ';';
		$custom_css .= '--lafka-title-bg-image:' . $title_bg_image . ';';
		$custom_css .= '--lafka-title-bg-font-size:' . $title_bg_font_size . ';';
		$custom_css .= '}';

		// Breadcrumb base color (WCAG AA compliant)
		$custom_css .= '.breadcrumb{color:#767676}';

		// Compare table — always hide quickview/compare on compare page
		$custom_css .= 'table.compare-list .add-to-cart td a.lafka-quick-view-link,table.compare-list .add-to-cart td a.compare.button{display:none !important}';

		wp_add_inline_style( 'lafka-style', $custom_css );
	}

}
