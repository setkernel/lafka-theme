<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $values
 * @var $units
 * @var $bgcolor
 * @var $custombgcolor
 * @var $customtxtcolor
 * @var $options
 * @var $el_class
 * @var $el_id
 * @var $css
 * @var $css_animation
 * @var $display_style
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Progress_Bar
 */
$title = $values = $units = $bgcolor = $css = $custombgcolor = $customtxtcolor = $options = $el_class = $el_id = $css_animation = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
$atts = $this->convertAttributesToNewProgressBar( $atts );

extract( $atts );
wp_enqueue_script( 'waypoints' );

$el_class = $this->getExtraClass( $el_class ) . $this->getCSSAnimation( $css_animation );

$bar_options = array();
$options = explode( ',', $options );
if ( in_array( 'animated', $options ) ) {
	$bar_options[] = 'animated';
}
if ( in_array( 'striped', $options ) ) {
	$bar_options[] = 'striped';
}

if ( 'custom' === $bgcolor && '' !== $custombgcolor ) {
	$custombgcolor = ' style="' . vc_get_css_color( 'background-color', $custombgcolor ) . '"';
	if ( '' !== $customtxtcolor ) {
		$customtxtcolor = ' style="' . vc_get_css_color( 'color', $customtxtcolor ) . '"';
	}
	$bgcolor = '';
} else {
	$custombgcolor = '';
	$customtxtcolor = '';
	$bgcolor = 'vc_progress-bar-color-' . esc_attr( $bgcolor );
	$el_class .= ' ' . $bgcolor;
}

$class_to_filter = 'vc_progress_bar wpb_content_element';
$class_to_filter .= vc_shortcode_custom_css_class( $css, ' ' ) . $this->getExtraClass( $el_class );
$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts );

if (!empty($display_style)) {
	$css_class .= ' ' . sanitize_html_class($display_style);
}

$wrapper_attributes = array();
if ( ! empty( $el_id ) ) {
	$wrapper_attributes[] = 'id="' . esc_attr( $el_id ) . '"';
}

ob_start();
?>
<div class="<?php echo esc_attr( $css_class ) ?>" <?php echo implode( ' ', $wrapper_attributes ) ?>>
	<?php echo wpb_widget_title( array( 'title' => $title, 'extraclass' => 'wpb_progress_bar_heading' ) ) ?>
	<?php
	$values = (array) vc_param_group_parse_atts( $values );
	$max_value = 0.0;
	$graph_lines_data = array();
	foreach ( $values as $data ) {
		$new_line = $data;
		$new_line['value'] = isset( $data['value'] ) ? $data['value'] : 0;
		$new_line['label'] = isset( $data['label'] ) ? $data['label'] : '';
		$new_line['bgcolor'] = isset( $data['color'] ) && 'custom' !== $data['color'] ? '' : $custombgcolor;
		$new_line['txtcolor'] = isset( $data['color'] ) && 'custom' !== $data['color'] ? '' : $customtxtcolor;
		if ( isset( $data['customcolor'] ) && ( ! isset( $data['color'] ) || 'custom' === $data['color'] ) ) {
			$new_line['bgcolor'] = ' style="background-color: ' . esc_attr( $data['customcolor'] ) . ';"';
		}
		if ( isset( $data['customtxtcolor'] ) && ( ! isset( $data['color'] ) || 'custom' === $data['color'] ) ) {
			$new_line['txtcolor'] = ' style="color: ' . esc_attr( $data['customtxtcolor'] ) . ';"';
		}

		if ( $max_value < (float) $new_line['value'] ) {
			$max_value = $new_line['value'];
		}
		$graph_lines_data[] = $new_line;
	}
	?>
	<?php foreach ( $graph_lines_data as $line ): ?>
		<div class="vc_general vc_single_bar<?php echo ( ( isset( $line['color'] ) && 'custom' !== $line['color'] ) ? esc_attr(' vc_progress-bar-color-' . $line['color']) : '' ) ?>">
			<small class="vc_label" <?php echo wp_kses_post($line['txtcolor']) ?>>
				<?php echo esc_html($line['label']) ?>
				<?php echo ( '' !== $units ) ? ' <span class="vc_label_units">' . esc_html($line['value'] . $units) . '</span>' : '' ?>
			</small>
			<?php
			if ( $max_value > 100.00 ) {
				$percentage_value = (float) $line['value'] > 0 && $max_value > 100.00 ? round( (float) $line['value'] / $max_value * 100, 4 ) : 0;
			} else {
				$percentage_value = $line['value'];
			}
			?>
			<span
				class="vc_bar <?php echo esc_attr( implode( ' ', $bar_options ) ) ?>"
				data-percentage-value="<?php echo esc_attr( $percentage_value ) ?>"
				data-value="<?php echo esc_attr( $line['value'] ) ?>" <?php echo wp_kses_post($line['bgcolor']) ?>>
			</span>
		</div>
	<?php endforeach ?>
</div>
<?php
echo ob_get_clean();