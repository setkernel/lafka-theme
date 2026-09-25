<?php
/**
 * Counter layout: the 2-tap size chooser — ONE native <dialog> per page,
 * printed in wp_footer after the JSON island (lafka_chooser_print_data()).
 * js/lafka-size-chooser.js fills it from the product's payload:
 *   - secondary attributes (e.g. Crust) as radio groups, preselected;
 *   - the price-driving attribute as big worded buttons
 *     ("Medium · $19.45 · Add to order"), unavailable combinations disabled.
 * Tapping a size is the second tap: it adds that variation.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="lafka-chooser" class="lafka-chooser" aria-labelledby="lafka-chooser-title" data-lafka-chooser>
	<div class="lafka-chooser__panel">
		<div class="lafka-chooser__head">
			<div>
				<p class="lafka-chooser__eyebrow"><?php esc_html_e( 'Choose a size', 'lafka' ); ?></p>
				<h2 id="lafka-chooser-title" class="lafka-chooser__title" data-lafka-chooser-name></h2>
			</div>
			<form method="dialog">
				<button type="submit" class="lafka-chooser__close lafka-counter-btn">
					<?php echo lafka_counter_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php esc_html_e( 'Close', 'lafka' ); ?></span>
				</button>
			</form>
		</div>
		<div class="lafka-chooser__groups" data-lafka-chooser-groups></div>
		<fieldset class="lafka-chooser__sizes">
			<legend class="lafka-chooser__legend" data-lafka-chooser-primary-label></legend>
			<div class="lafka-chooser__options" data-lafka-chooser-options></div>
		</fieldset>
		<p class="lafka-chooser__more" data-lafka-chooser-more hidden>
			<a class="lafka-counter-link" href="#" data-lafka-chooser-more-link><?php esc_html_e( 'More choices', 'lafka' ); ?></a>
		</p>
	</div>
</dialog>
<p class="screen-reader-text" aria-live="polite" data-lafka-chooser-live></p>
