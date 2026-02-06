<?php

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode attributes
 * @var $atts
 * @var $el_class
 * @var $full_width
 * @var $full_height
 * @var $equal_height
 * @var $columns_placement
 * @var $content_placement
 * @var $parallax
 * @var $parallax_image
 * @var $css
 * @var $el_id
 * @var $video_bg
 * @var $video_bg_url
 * @var $video_bg_parallax
 * @var $parallax_speed_bg
 * @var $parallax_speed_video
 * @var $content - shortcode content
 * @var $css_animation
 * * Althemist:
 * @var $video_bckgr_url
 * @var $video_opacity
 * @var $video_raster
 * @var $video_bckgr_start
 * @var $video_bckgr_end
 * @var $general_row_align
 * @var $allow_overflow
 * @var $fixed_background
 * *
 * Shortcode class
 * @var WPBakeryShortCode_Vc_Row $this
 */
$el_class = $full_height = $parallax_speed_bg = $parallax_speed_video = $full_width = $equal_height = $flex_row = $columns_placement = $content_placement = $parallax = $parallax_image = $css = $el_id = $video_bg = $video_bg_url = $video_bg_parallax = $css_animation = '';
$disable_element = '';
$after_output = '';
$atts = vc_map_get_attributes($this->getShortcode(), $atts);
extract($atts);

wp_enqueue_script('wpb_composer_front_js');

$el_class = $this->getExtraClass($el_class) . $this->getCSSAnimation( $css_animation );

$css_classes = array(
		'vc_row',
		'wpb_row', //deprecated
		'vc_row-fluid',
		$el_class,
		vc_shortcode_custom_css_class($css),
);

if ('yes' === $disable_element) {
	if (vc_is_page_editable()) {
		$css_classes[] = 'vc_hidden-lg vc_hidden-xs vc_hidden-sm vc_hidden-md';
	} else {
		return '';
	}
}

if ( vc_shortcode_custom_css_has_property( $css, array(
		'border',
		'background',
	) ) || $video_bg || $parallax
) {
	$css_classes[] = 'vc_row-has-fill';
}

if (!empty($atts['gap'])) {
	$css_classes[] = 'vc_column-gap-' . $atts['gap'];
}

$unique_id = uniqid('unique-row-');
$row_id = '';

if ( ! empty( $atts['rtl_reverse'] ) ) {
	$css_classes[] = 'vc_rtl-columns-reverse';
}

$wrapper_attributes = array();
// build attributes for wrapper
if (!empty($el_id)) {
	$wrapper_attributes[] = 'id="' . esc_attr($el_id) . '"';
	$row_id = $el_id;
} elseif (!empty($video_bckgr_url)) {
	$wrapper_attributes[] = 'id="' . esc_attr('lafka_' . $unique_id) . '"';
	$row_id = 'lafka_' . $unique_id;
}
if (!empty($full_width)) {
	$wrapper_attributes[] = 'data-vc-full-width="true"';
	$wrapper_attributes[] = 'data-vc-full-width-init="false"';
	if ('stretch_row_content' === $full_width) {
		$wrapper_attributes[] = 'data-vc-stretch-content="true"';
	} elseif ('stretch_row_content_no_spaces' === $full_width) {
		$wrapper_attributes[] = 'data-vc-stretch-content="true"';
		$css_classes[] = 'vc_row-no-padding';
	}
	$after_output .= '<div class="vc_row-full-width vc_clearfix"></div>';
}

if (!empty($full_height)) {
	$css_classes[] = ' vc_row-o-full-height';
	if (!empty($columns_placement)) {
		$flex_row = true;
		$css_classes[] = ' vc_row-o-columns-' . $columns_placement;
		if ('stretch' === $columns_placement) {
			$css_classes[] = 'vc_row-o-equal-height';
		}
	}
}

if (!empty($equal_height)) {
	$flex_row = true;
	$css_classes[] = ' vc_row-o-equal-height';
}

if (!empty($content_placement)) {
	$flex_row = true;
	$css_classes[] = ' vc_row-o-content-' . $content_placement;
}

if (!empty($flex_row)) {
	$css_classes[] = ' vc_row-flex';
}

// use default video if user checked video, but didn't chose url
if (!empty($video_bg) && empty($video_bg_url)) {
	$video_bg_url = 'https://www.youtube.com/watch?v=lMJXxhRFO1k';
}

$has_video_bg = (!empty($video_bg) && !empty($video_bg_url) && vc_extract_youtube_id($video_bg_url) );

$parallax_speed = $parallax_speed_bg;
if ($has_video_bg) {
	$parallax = $video_bg_parallax;
	$parallax_speed = $parallax_speed_video;
	$parallax_image = $video_bg_url;
	$css_classes[] = ' vc_video-bg-container';
	wp_enqueue_script('vc_youtube_iframe_api_js');
	// Lafka video background
} elseif (isset($video_bckgr_url) && $video_bckgr_url) {
	wp_enqueue_style('ytplayer');
	wp_enqueue_script('ytplayer');
	wp_localize_script('lafka-libs-config', 'lafka_ytplayer_conf', array(
			'include' => 'true',
	));
}

if (!empty($parallax)) {
	wp_enqueue_script('vc_jquery_skrollr_js');
	$wrapper_attributes[] = 'data-vc-parallax="' . esc_attr($parallax_speed) . '"'; // parallax speed
	$css_classes[] = 'vc_general vc_parallax vc_parallax-' . $parallax;
	if (false !== strpos($parallax, 'fade')) {
		$css_classes[] = 'js-vc_parallax-o-fade';
		$wrapper_attributes[] = 'data-vc-parallax-o-fade="on"';
	} elseif (false !== strpos($parallax, 'fixed')) {
		$css_classes[] = 'js-vc_parallax-o-fixed';
	}
}

if (!empty($general_row_align)) {
	$css_classes[] = $general_row_align;
}
if (!empty($allow_overflow) && $allow_overflow == 'yes') {
	$css_classes[] = 'lafka-visible-overlay';
}
if (!empty($fixed_background) && $fixed_background == 'yes') {
	$css_classes[] = 'lafka-fixed-background';
}

if (!empty($parallax_image)) {
	if ($has_video_bg) {
		$parallax_image_src = $parallax_image;
	} else {
		$parallax_image_id = preg_replace('/[^\d]/', '', $parallax_image);
		$parallax_image_src = wp_get_attachment_image_src($parallax_image_id, 'full');
		if (!empty($parallax_image_src[0])) {
			$parallax_image_src = $parallax_image_src[0];
		}
	}
	$wrapper_attributes[] = 'data-vc-parallax-image="' . esc_attr($parallax_image_src) . '"';
}
if (!$parallax && $has_video_bg) {
	$wrapper_attributes[] = 'data-vc-video-bg="' . esc_attr($video_bg_url) . '"';
}
$css_class = preg_replace('/\s+/', ' ', apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, implode(' ', array_filter(array_unique($css_classes))), $this->settings['base'], $atts));
$wrapper_attributes[] = 'class="' . esc_attr(trim($css_class)) . '"';

ob_start();
?>
<div <?php echo implode( ' ', $wrapper_attributes ) ?>>
    <?php echo wpb_js_remove_wpautop( $content ) ?>
</div>

<?php echo wp_kses_post($after_output) ?>

<?php if(!$has_video_bg && !empty($video_bckgr_url)): // Lafka video background ?>
	<div class="lafka_bckgr_player"	 data-property="{videoURL:'<?php echo esc_url($video_bckgr_url) ?>',containment:'#<?php echo esc_js($row_id) ?>',autoPlay:true, loop:true, mute:true, startAt:<?php echo esc_attr($video_bckgr_start) ? esc_attr($video_bckgr_start) : 0 ?>, opacity:<?php echo esc_attr($video_opacity) ?>, showControls:false, addRaster:<?php echo esc_attr($video_raster) ? 'true' : 'false' ?>, quality:'default'<?php echo esc_attr($video_bckgr_end) ? ', stopAt:' . esc_attr($video_bckgr_end) : '' ?> }"></div>
<?php endif ?>

<?php
echo ob_get_clean();