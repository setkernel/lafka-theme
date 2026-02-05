<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shortcode attributes
 * @var $atts
 * @var $el_class
 * @var $css
 * @var $el_id
 * @var $equal_height
 * @var $content_placement
 * @var $content - shortcode content
 * * Althemist:
 * @var $video_bckgr_url
 * @var $video_opacity
 * @var $video_raster
 * @var $video_bckgr_start
 * @var $video_bckgr_end
 *
 * Shortcode class
 * @var WPBakeryShortCode_Vc_Row_Inner $this
 */
$el_class = $equal_height = $content_placement = $css = $el_id = '';
$disable_element = '';
$after_output = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
extract( $atts );

$el_class = $this->getExtraClass( $el_class );
$css_classes = array(
	'vc_row',
	'wpb_row',
	//deprecated
	'vc_inner',
	'vc_row-fluid',
	$el_class,
	vc_shortcode_custom_css_class( $css ),
);
if ( 'yes' === $disable_element ) {
	if ( vc_is_page_editable() ) {
		$css_classes[] = 'vc_hidden-lg vc_hidden-xs vc_hidden-sm vc_hidden-md';
	} else {
		return '';
	}
}

if ( vc_shortcode_custom_css_has_property( $css, array(
	'border',
	'background',
) ) ) {
	$css_classes[] = 'vc_row-has-fill';
}

if ( ! empty( $atts['gap'] ) ) {
	$css_classes[] = 'vc_column-gap-' . $atts['gap'];
}

if ( ! empty( $equal_height ) ) {
	$flex_row = true;
	$css_classes[] = 'vc_row-o-equal-height';
}

if ( ! empty( $atts['rtl_reverse'] ) ) {
	$css_classes[] = 'vc_rtl-columns-reverse';
}

if ( ! empty( $content_placement ) ) {
	$flex_row = true;
	$css_classes[] = 'vc_row-o-content-' . $content_placement;
}

if ( ! empty( $flex_row ) ) {
	$css_classes[] = 'vc_row-flex';
}

$unique_id = uniqid('unique-row-inner-');
$row_id = '';

$wrapper_attributes = array();
// build attributes for wrapper
if ( ! empty( $el_id ) ) {
	$wrapper_attributes[] = 'id="' . esc_attr( $el_id ) . '"';
} elseif (!empty($video_bckgr_url)) {
	$wrapper_attributes[] = 'id="' . esc_attr('lafka_' . $unique_id) . '"';
	$row_id = 'lafka_' . $unique_id;
}

if ( isset($video_bckgr_url) && $video_bckgr_url ) {
	wp_enqueue_style( 'ytplayer' );
	wp_enqueue_script( 'ytplayer' );
	wp_localize_script( 'lafka-libs-config', 'lafka_ytplayer_conf', array(
		'include' => 'true',
	) );
}

$css_class = preg_replace( '/\s+/', ' ', apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, implode( ' ', array_filter( array_unique( $css_classes ) ) ), $this->settings['base'], $atts ) );
$wrapper_attributes[] = 'class="' . esc_attr( trim( $css_class ) ) . '"';

ob_start();
?>
<div <?php echo implode( ' ', $wrapper_attributes ) ?> >
	<?php echo wpb_js_remove_wpautop( $content ) ?>
</div>

<?php echo wp_kses_post($after_output) ?>

<?php if(!empty($video_bckgr_url)): ?>
	<div class="lafka_bckgr_player"	 data-property="{videoURL:'<?php echo esc_url($video_bckgr_url) ?>',containment:'#<?php echo esc_js($row_id) ?>',autoPlay:true, loop:true, mute:true, startAt:<?php echo esc_attr($video_bckgr_start) ? esc_attr($video_bckgr_start) : 0 ?>, opacity:<?php echo esc_attr($video_opacity) ?>, showControls:false, addRaster:<?php echo esc_attr($video_raster) ? 'true' : 'false' ?>, quality:'default'<?php echo esc_attr($video_bckgr_end) ? ', stopAt:' . esc_attr($video_bckgr_end) : '' ?> }"></div>
<?php endif ?>

<?php
echo ob_get_clean(); // All dynamic data escaped