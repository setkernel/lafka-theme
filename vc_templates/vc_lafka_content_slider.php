<?php

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode attributes
 * @var $atts
 * @var $content - shortcode content
 * @var $this WPBakeryShortCode_Lafka_Content_Slider|WPBakeryShortCode_VC_Tta_Tabs|WPBakeryShortCode_VC_Tta_Tour|WPBakeryShortCode_VC_Tta_Pageable
 *
 * Copied from vc-tta-global.php
 */
//$el_class = $css = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
$this->resetVariables( $atts, $content );
extract( $atts );

$this->setGlobalTtaInfo();

$class_to_filter  = '';
$class_to_filter .= vc_shortcode_custom_css_class( $css, ' ' ) . $this->getExtraClass( $el_class );
$custom_css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts );

$css_classes = array();
// Full Height option
if ( $full_height === 'yes' ) {
	$css_classes[] = 'lafka-fullheight-content-slider';
}
// Navigation color option
$css_classes[] = $navigation_color;
// Pagination type option
if ( $pagination === 'yes' ) {
	$css_classes[] = $pagination_type;
}

$unique_id = uniqid( 'lafka_content_slider' );

$output_escaped  = '<div id="' . esc_attr( $unique_id ) . '" class="lafka_content_slider' . ( $custom_css_class ? ' ' . esc_attr( $custom_css_class ) : '' ) . ( empty( $css_classes ) ? '' : ' ' . esc_attr( implode( ' ', $css_classes ) ) ) . '">';
$output_escaped .= $this->getTemplateVariable( 'title' );
$output_escaped .= '<div class="vc_tta-panels owl-carousel">';
$output_escaped .= $this->getTemplateVariable( 'content' );
$output_escaped .= '</div>';
$output_escaped .= $this->getTemplateVariable( 'tabs-list-bottom' );
$output_escaped .= $this->getTemplateVariable( 'tabs-list-right' );
$output_escaped .= '</div>';

$autoplay_owl_option        = 'false';
$autoplayTimeout_owl_option = '5000';
if ( $autoplay !== 'none' ) {
	$autoplay_owl_option        = 'true';
	$autoplayTimeout_owl_option = $autoplay . '000';
}

$navigation_owl_option = 'false';
if ( $navigation === 'yes' ) {
	$navigation_owl_option = 'true';
}

$pause_on_hover_owl_option = 'false';
if ( $pause_on_hover === 'yes' ) {
	$pause_on_hover_owl_option = 'true';
}

$pagination_owl_option = 'false';
if ( $pagination === 'yes' ) {
	$pagination_owl_option = 'true';
}

$animateOut = 'false';
$animateIn  = 'false';
if ( $transition === 'fade' ) {
	$animateOut = 'fadeOut';
	$animateIn  = 'fadeIn';
} elseif ( $transition === 'slide-flip' ) {
	$animateOut = 'slideOutDown';
	$animateIn  = 'flipInX';
}

$inline_js = '(function ($) {
		"use strict";
		var is_lafka_video_background = jQuery("#' . esc_js( $unique_id ) . '").find("div.lafka_bckgr_player").length;
		var lafka_loop = true;
		if(is_lafka_video_background) {
			lafka_loop = false;
		}
		
		var lafka_owl_args = {
				rtl: ' . ( is_rtl() ? 'true' : 'false' ) . ',
				items: 1,
				autoplayHoverPause: ' . esc_js( $pause_on_hover_owl_option ) . ',
				autoplay: ' . esc_js( $autoplay_owl_option ) . ',
				autoplayTimeout: ' . esc_js( $autoplayTimeout_owl_option ) . ',
				autoplaySpeed: 800,
				dots: ' . esc_js( $pagination_owl_option ) . ',
				nav: ' . esc_js( $navigation_owl_option ) . ',
				navText: [
					"<i class=\'fas fa-angle-left\'></i>",
					"<i class=\'fas fa-angle-right\'></i>"
				],
				animateOut: ' . ( $animateOut == 'false' ? 'false' : '"' . esc_js( $animateOut ) . '"' ) . ',
				animateIn: ' . ( $animateIn == 'false' ? 'false' : '"' . esc_js( $animateIn ) . '"' ) . ', ' . ( $transition === 'slide-flip' ? 'smartSpeed:450,' : '' ) . '
		};
		lafka_owl_args["loop"] = lafka_loop;
		
		$(window).on("load", function () {
			// Using timeout because of strange resizing issue only in Mozilla
			setTimeout(function(){ jQuery("#' . esc_js( $unique_id ) . ' > .vc_tta-panels").owlCarousel(lafka_owl_args) }, 10)
		});
	})(window.jQuery);';
wp_add_inline_script( 'owl-carousel', $inline_js );

// This variable has been safely escaped in the following file: lafka/vc_templates/vc_lafka_content_slider.php Line: 40 - 47
echo $output_escaped; // All dynamic data escaped.
