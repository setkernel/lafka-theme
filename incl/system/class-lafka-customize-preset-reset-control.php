<?php
/**
 * Customizer control: "Reset appearance to preset" button (GX4, §7).
 * Loaded only inside customize_register, when WP_Customize_Control exists.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'Lafka_Customize_Preset_Reset_Control' ) ) {

	/**
	 * A button + status line; js in lafka_preset_reset_controls_script().
	 */
	class Lafka_Customize_Preset_Reset_Control extends WP_Customize_Control {

		/** @var string */
		public $type = 'lafka_preset_reset';

		/** Render the button. */
		public function render_content() {
			?>
			<span class="customize-control-title"><?php esc_html_e( 'Reset appearance to preset', 'lafka' ); ?></span>
			<span class="description customize-control-description"><?php esc_html_e( 'Removes saved colour and font overrides (a backup is kept) so the preset shows as designed. Business details, menus and settings are not touched.', 'lafka' ); ?></span>
			<button type="button" class="button" data-lafka-preset-reset><?php esc_html_e( 'Reset appearance', 'lafka' ); ?></button>
			<p class="description" aria-live="polite" data-lafka-preset-reset-status></p>
			<?php
		}
	}
}
