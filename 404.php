<?php
/**
 * 404 — handoff-spec rebuild (v5.66.0).
 *
 * Per /design_handoff_peppery_ordering/README.md "404 (catch-all)":
 *   - 96px emoji
 *   - Fraunces h1
 *   - Lead paragraph using the actual unmatched path
 *   - Two CTAs: Browse the menu (primary) + Back to home (ghost)
 *
 * @package Lafka
 * @since   5.66.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of the unmatched path; no state mutation.
$lafka_404_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_strip_all_tags( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
$lafka_404_path = strtok( $lafka_404_path, '?' );
?>
<main id="main" class="lafka-404" role="main">
	<div class="lafka-container lafka-404__inner">

		<span class="lafka-404__emoji" aria-hidden="true">🤷</span>

		<h1 class="lafka-404__title"><?php esc_html_e( 'Page not found', 'lafka' ); ?></h1>

		<p class="lafka-404__lead">
			<?php
			if ( '' !== $lafka_404_path ) {
				/* translators: %s — the unmatched URL path (e.g. "/foo/bar"). */
				printf( esc_html__( "We couldn't find anything at %s. It may have moved, or never existed.", 'lafka' ), '<code class="lafka-404__path">' . esc_html( $lafka_404_path ) . '</code>' );
			} else {
				esc_html_e( "We couldn't find that page. It may have moved, or never existed.", 'lafka' );
			}
			?>
		</p>

		<div class="lafka-404__actions">
			<a class="lafka-404__cta lafka-404__cta--primary" href="<?php echo esc_url( lafka_theme_menu_url() ); ?>">
				<?php esc_html_e( 'Browse the menu', 'lafka' ); ?>
				<span class="lafka-404__arrow" aria-hidden="true">→</span>
			</a>
			<a class="lafka-404__cta lafka-404__cta--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Back to home', 'lafka' ); ?>
			</a>
		</div>

	</div>
</main>
<?php
get_footer();
