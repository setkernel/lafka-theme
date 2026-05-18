<?php
/**
 * Web Push subscribe prompt partial (Pillar 3E, theme v6.12.0).
 *
 * Renders the dismissible "Want occasional treats?" card in the bottom-right
 * (desktop) / bottom-center (mobile) of the viewport. Markup is server-side
 * but visibility is gated client-side by js/lafka-push-subscribe.js (which
 * counts page views, checks Notification.permission, etc).
 *
 * Renders nothing when:
 *   - Master push toggle is OFF.
 *   - Prompt toggle is OFF.
 *   - The customer is mid-conversion (cart / checkout / order-received /
 *     my-account) — same blocklist as exit-intent + review banner.
 *   - The site has no VAPID public key configured (the JS would no-op anyway,
 *     but suppressing the markup saves a render).
 *
 * Markup contract — keep stable so CSS + JS selectors don't drift:
 *
 *   <aside class="lafka-push-prompt" data-visible="true|undefined"
 *          data-dismissing="true|undefined"
 *          role="complementary" aria-live="polite"
 *          aria-labelledby="lafka-push-prompt-copy">
 *     <div class="lafka-push-prompt__inner">
 *       <span class="lafka-push-prompt__bell" aria-hidden="true">…</span>
 *       <div class="lafka-push-prompt__body">
 *         <p class="lafka-push-prompt__copy" id="…">…</p>
 *         <div class="lafka-push-prompt__actions">
 *           <button class="lafka-push-prompt__accept">Yes please</button>
 *           <button class="lafka-push-prompt__deny">No thanks</button>
 *         </div>
 *       </div>
 *       <button class="lafka-push-prompt__close" aria-label="Dismiss">×</button>
 *     </div>
 *   </aside>
 *
 * @package Lafka_Theme
 * @since   6.12.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_theme_mod' ) ) {
	return;
}

// Master toggle gates everything.
if ( '1' !== (string) get_theme_mod( 'lafka_push_enabled', '0' ) ) {
	return;
}
// Prompt channel toggle (default ON when master is ON).
if ( '1' !== (string) get_theme_mod( 'lafka_push_subscribe_prompt_enabled', '1' ) ) {
	return;
}
// No point rendering without a VAPID key.
$lafka_push_vapid_pub = (string) get_theme_mod( 'lafka_push_vapid_public_key', '' );
if ( '' === $lafka_push_vapid_pub ) {
	return;
}

// Never distract a customer who's mid-funnel.
$lafka_push_prompt_on_conversion_page = (
	( function_exists( 'is_cart' ) && is_cart() )
	|| ( function_exists( 'is_checkout' ) && is_checkout() )
	|| ( function_exists( 'is_account_page' ) && is_account_page() )
	|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) )
);
if ( $lafka_push_prompt_on_conversion_page ) {
	return;
}

$lafka_push_prompt_copy = (string) get_theme_mod(
	'lafka_push_subscribe_prompt_copy',
	'Want occasional treats? We send 1-2 notifications a week max - never spam.'
);
if ( '' === trim( $lafka_push_prompt_copy ) ) {
	$lafka_push_prompt_copy = 'Want occasional treats? We send 1-2 notifications a week max - never spam.';
}

$lafka_push_prompt_accept_label = esc_html__( 'Yes please', 'lafka' );
$lafka_push_prompt_deny_label   = esc_html__( 'No thanks', 'lafka' );
$lafka_push_prompt_close_aria   = esc_attr__( 'Dismiss push notifications prompt', 'lafka' );
?>
<aside class="lafka-push-prompt"
	role="complementary"
	aria-live="polite"
	aria-labelledby="lafka-push-prompt-copy">
	<div class="lafka-push-prompt__inner">
		<span class="lafka-push-prompt__bell" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="presentation">
				<path d="M12 2a1 1 0 0 1 1 1v.6a7 7 0 0 1 6 6.9V14l1.6 2.4a1 1 0 0 1-.83 1.55H4.23a1 1 0 0 1-.83-1.55L5 14v-3.5A7 7 0 0 1 11 3.6V3a1 1 0 0 1 1-1Zm0 19a3 3 0 0 1-2.83-2h5.66A3 3 0 0 1 12 21Z" fill="currentColor"/>
			</svg>
		</span>
		<div class="lafka-push-prompt__body">
			<p class="lafka-push-prompt__copy" id="lafka-push-prompt-copy">
				<?php echo esc_html( $lafka_push_prompt_copy ); ?>
			</p>
			<div class="lafka-push-prompt__actions">
				<button type="button" class="lafka-push-prompt__accept">
					<?php echo esc_html( $lafka_push_prompt_accept_label ); ?>
				</button>
				<button type="button" class="lafka-push-prompt__deny">
					<?php echo esc_html( $lafka_push_prompt_deny_label ); ?>
				</button>
			</div>
		</div>
		<button type="button" class="lafka-push-prompt__close"
			aria-label="<?php echo esc_attr( $lafka_push_prompt_close_aria ); ?>">
			&times;
		</button>
	</div>
</aside>
