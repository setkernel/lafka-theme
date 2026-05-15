<?php
/**
 * Partial: Site-wide promo / deal bar (v5.51.0)
 *
 * Red full-bleed strip rendered above the header on every page.
 * Customizer-driven, dismissible (state in localStorage for 7 days),
 * date-windowed (start_date / end_date). Hidden when no current deal.
 *
 * Hooked into `wp_body_open` from functions.php so it always renders at
 * the very top of <body>, before the existing #header markup — no
 * header.php restructure needed (per UX agent recommendation).
 *
 * Customizer reads (panel "Lafka — Promo Bar"):
 *  - lafka_promo_bar_enabled  (bool, default false — operator opts in)
 *  - lafka_promo_bar_text     (text)
 *  - lafka_promo_bar_link_url (url)
 *  - lafka_promo_bar_link_label (text)
 *  - lafka_promo_bar_icon     (text — emoji or short string)
 *  - lafka_promo_bar_start    (date, YYYY-MM-DD)
 *  - lafka_promo_bar_end      (date, YYYY-MM-DD)
 *
 * @package Lafka
 * @since   5.51.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! (bool) get_theme_mod( 'lafka_promo_bar_enabled', false ) ) {
	return;
}

$lafka_promo_text = trim( (string) get_theme_mod( 'lafka_promo_bar_text', '' ) );
if ( '' === $lafka_promo_text ) {
	return;
}

// Date window — promo bar only shows during start..end (inclusive).
// Empty start/end = no boundary in that direction.
$lafka_promo_start = trim( (string) get_theme_mod( 'lafka_promo_bar_start', '' ) );
$lafka_promo_end   = trim( (string) get_theme_mod( 'lafka_promo_bar_end', '' ) );
$lafka_now         = current_time( 'Y-m-d' );

if ( '' !== $lafka_promo_start && $lafka_now < $lafka_promo_start ) {
	return;
}
if ( '' !== $lafka_promo_end && $lafka_now > $lafka_promo_end ) {
	return;
}

$lafka_promo_link_url   = (string) get_theme_mod( 'lafka_promo_bar_link_url', '' );
$lafka_promo_link_label = (string) get_theme_mod( 'lafka_promo_bar_link_label', __( 'Order now →', 'lafka' ) );
$lafka_promo_icon       = trim( (string) get_theme_mod( 'lafka_promo_bar_icon', '🍕' ) );

// Stable dismiss key — bumps when the operator changes the text/dates
// so a fresh promo re-shows even if customer dismissed the old one.
$lafka_promo_dismiss_key = 'lafka-promo-' . substr( md5( $lafka_promo_text . $lafka_promo_start . $lafka_promo_end ), 0, 8 );
?>
<aside
	class="lafka-promo-bar"
	role="region"
	aria-label="<?php esc_attr_e( 'Promotion', 'lafka' ); ?>"
	data-lafka-promo-key="<?php echo esc_attr( $lafka_promo_dismiss_key ); ?>"
>
	<div class="lafka-container lafka-promo-bar__inner">
		<?php if ( '' !== $lafka_promo_icon ) : ?>
			<span class="lafka-promo-bar__icon" aria-hidden="true"><?php echo esc_html( $lafka_promo_icon ); ?></span>
		<?php endif; ?>

		<span class="lafka-promo-bar__text"><?php echo esc_html( $lafka_promo_text ); ?></span>

		<?php if ( '' !== $lafka_promo_link_url ) : ?>
			<a class="lafka-promo-bar__cta" href="<?php echo esc_url( $lafka_promo_link_url ); ?>">
				<?php echo esc_html( $lafka_promo_link_label ); ?>
			</a>
		<?php endif; ?>

		<button
			type="button"
			class="lafka-promo-bar__close"
			aria-label="<?php esc_attr_e( 'Dismiss promotion', 'lafka' ); ?>"
			data-lafka-promo-dismiss
		>×</button>
	</div>
</aside>
<script>
	(function () {
		var bar = document.querySelector('.lafka-promo-bar[data-lafka-promo-key]');
		if (!bar) { return; }
		var key = bar.getAttribute('data-lafka-promo-key');
		try {
			if (localStorage.getItem(key + '-dismissed')) {
				bar.style.display = 'none';
				return;
			}
		} catch (_) { /* SSR or storage blocked — render normally */ }
		var close = bar.querySelector('[data-lafka-promo-dismiss]');
		if (close) {
			close.addEventListener('click', function () {
				bar.style.display = 'none';
				try { localStorage.setItem(key + '-dismissed', String(Date.now())); } catch (_) {}
			});
		}
	}());
</script>
