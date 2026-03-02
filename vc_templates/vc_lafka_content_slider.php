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
$atts = vc_map_get_attributes($this->getShortcode(), $atts);
$this->resetVariables($atts, $content);
extract($atts);

$this->setGlobalTtaInfo();

$class_to_filter = '';
$class_to_filter .= vc_shortcode_custom_css_class( $css, ' ' ) . $this->getExtraClass( $el_class );
$custom_css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts );

$css_classes = array();
// Full Height option
if($full_height === 'yes') {
	$css_classes[] = 'lafka-fullheight-content-slider';
}
// Navigation color option
$css_classes[] = $navigation_color;
// Pagination type option
if($pagination === 'yes') {
	$css_classes[] = $pagination_type;
}

$unique_id = 'lafka_content_slider_' . uniqid();

$output_escaped = '<div id="' . esc_attr($unique_id) . '" class="lafka_content_slider' . ($custom_css_class ? ' ' . esc_attr($custom_css_class) : '') . (empty($css_classes) ? '' : ' ' . esc_attr(implode(' ', $css_classes))) . '">';
$output_escaped .= $this->getTemplateVariable('title');
$output_escaped .= '<div class="vc_tta-panels owl-carousel">';
$output_escaped .= $this->getTemplateVariable('content');
$output_escaped .= '</div>';
$output_escaped .= $this->getTemplateVariable('tabs-list-bottom');
$output_escaped .= $this->getTemplateVariable('tabs-list-right');
$output_escaped .= '</div>';

$autoplay_owl_option = 'false';
$autoplayTimeout_owl_option = 5000;
if ($autoplay !== 'none' && is_numeric($autoplay) && (int) $autoplay > 0) {
	$autoplay_owl_option = 'true';
	$autoplayTimeout_owl_option = (int) $autoplay * 1000;
}

$navigation_owl_option = 'false';
if ($navigation === 'yes') {
	$navigation_owl_option = 'true';
}

$pause_on_hover_owl_option = 'false';
if ($pause_on_hover === 'yes') {
	$pause_on_hover_owl_option = 'true';
}

$pagination_owl_option = 'false';
if ($pagination === 'yes') {
	$pagination_owl_option = 'true';
}

$animateOut = 'false';
$animateIn = 'false';
if ($transition === 'fade') {
	$animateOut = 'fadeOut';
	$animateIn = 'fadeIn';
} elseif ($transition === 'slide-flip') {
	$animateOut = 'slideOutDown';
	$animateIn = 'flipInX';
}

// This variable has been safely escaped in the following file: lafka/vc_templates/vc_lafka_content_slider.php Line: 40 - 47
echo $output_escaped; // All dynamic data escaped.

// Output initialization script directly in footer to ensure owl-carousel is loaded
add_action('wp_footer', function() use ($unique_id, $pause_on_hover_owl_option, $autoplay_owl_option, $autoplayTimeout_owl_option, $pagination_owl_option, $navigation_owl_option, $animateOut, $animateIn, $transition) {
?>
<script>
(function ($) {
	"use strict";
	function lafkaInitSlider_<?php echo esc_js($unique_id); ?>() {
		if (typeof $.fn.owlCarousel === 'undefined') {
			// owlCarousel not loaded yet, retry in 100ms
			setTimeout(lafkaInitSlider_<?php echo esc_js($unique_id); ?>, 100);
			return;
		}
		var is_lafka_video_background = jQuery("#<?php echo esc_js($unique_id); ?>").find("div.lafka_bckgr_player").length;
		var lafka_loop = !is_lafka_video_background;

		var lafka_owl_args = {
			rtl: <?php echo ( is_rtl() ? 'true' : 'false' ); ?>,
			items: 1,
			autoplayHoverPause: <?php echo esc_js($pause_on_hover_owl_option); ?>,
			autoplay: <?php echo esc_js($autoplay_owl_option); ?>,
			autoplayTimeout: <?php echo (int) $autoplayTimeout_owl_option; ?>,
			autoplaySpeed: 800,
			dots: <?php echo esc_js($pagination_owl_option); ?>,
			nav: <?php echo esc_js($navigation_owl_option); ?>,
			navText: ["<i class='fas fa-angle-left'></i>", "<i class='fas fa-angle-right'></i>"],
			animateOut: <?php echo ($animateOut == 'false' ? 'false' : '"' . esc_js($animateOut) . '"'); ?>,
			animateIn: <?php echo ($animateIn == 'false' ? 'false' : '"' . esc_js($animateIn) . '"'); ?>,
			<?php echo ($transition === 'slide-flip' ? 'smartSpeed: 450,' : ''); ?>
			loop: lafka_loop
		};
		// Using timeout because of strange resizing issue only in Mozilla
		setTimeout(function(){ jQuery("#<?php echo esc_js($unique_id); ?> > .vc_tta-panels").owlCarousel(lafka_owl_args); }, 10);
	}
	// Initialize when DOM ready or window loaded
	if (document.readyState === "complete") {
		lafkaInitSlider_<?php echo esc_js($unique_id); ?>();
	} else {
		$(window).on("load", lafkaInitSlider_<?php echo esc_js($unique_id); ?>);
	}
})(window.jQuery);
</script>
<?php
}, 99);
