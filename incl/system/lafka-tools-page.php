<?php
/**
 * Lafka Maintenance tools page (v6.2.0).
 *
 * Replaces the maintenance UI that lived inside the legacy Theme Options
 * panel (retired in v6.1.0). Houses operator-facing utilities like the
 * GitHub release cache flush, WP-update transient force-refresh, and
 * version readout — all in one canonical WP location:
 *
 *   Tools → Lafka Maintenance
 *
 * Purposefully a *Tools* page (not Settings or Appearance) — these are
 * maintenance utilities, not configuration. Tools is the WP-canonical
 * home for "do something to the site, then go back".
 *
 * @package Lafka
 * @since   6.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Tools_Page' ) ) {

	final class Lafka_Tools_Page {

		const MENU_SLUG = 'lafka-maintenance';

		/**
		 * Register hooks.
		 */
		public static function init(): void {
			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		}

		/**
		 * Add the Tools submenu page.
		 */
		public static function register_menu(): void {
			add_management_page(
				esc_html__( 'Lafka Maintenance', 'lafka' ),
				esc_html__( 'Lafka Maintenance', 'lafka' ),
				'update_themes',
				self::MENU_SLUG,
				array( __CLASS__, 'render_page' )
			);
		}

		/**
		 * Render the page body.
		 */
		public static function render_page(): void {
			if ( ! current_user_can( 'update_themes' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'lafka' ), 403 );
			}

			$theme       = wp_get_theme();
			$theme_name  = (string) $theme->get( 'Name' );
			$theme_ver   = (string) $theme->get( 'Version' );

			// If a child theme is active, surface the parent theme version
			// too — operators tracking which `lafka` (parent) release ships
			// with their `lafka-child` need both numbers in one glance.
			$parent_theme = $theme->parent();
			$parent_name  = $parent_theme ? (string) $parent_theme->get( 'Name' ) : '';
			$parent_ver   = $parent_theme ? (string) $parent_theme->get( 'Version' ) : '';

			$plugin_ver  = '—';
			$plugin_file = WP_PLUGIN_DIR . '/lafka-plugin/lafka-plugin.php';
			if ( file_exists( $plugin_file ) && function_exists( 'get_plugin_data' ) ) {
				$plugin_data = get_plugin_data( $plugin_file, false, false );
				$plugin_ver  = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '—';
			}

			$flush_url = class_exists( 'Lafka_GitHub_Updater' )
				? Lafka_GitHub_Updater::get_flush_cache_url()
				: '#';
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Lafka Maintenance', 'lafka' ); ?></h1>
				<p><?php esc_html_e( 'Operator-facing maintenance utilities for the Lafka theme + plugin. Each action below is a one-click utility — use only when something needs explicit refreshing.', 'lafka' ); ?></p>

				<h2><?php esc_html_e( 'Versions', 'lafka' ); ?></h2>
				<table class="widefat striped" style="max-width: 540px;">
					<tbody>
						<tr>
							<td>
								<strong><?php echo $parent_theme ? esc_html__( 'Active (child) theme', 'lafka' ) : esc_html__( 'Theme', 'lafka' ); ?></strong>
								<?php if ( '' !== $theme_name ) : ?>
									<br><span class="description"><?php echo esc_html( $theme_name ); ?></span>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $theme_ver ); ?></code></td>
						</tr>
						<?php if ( $parent_theme ) : ?>
							<tr>
								<td>
									<strong><?php esc_html_e( 'Parent theme', 'lafka' ); ?></strong>
									<?php if ( '' !== $parent_name ) : ?>
										<br><span class="description"><?php echo esc_html( $parent_name ); ?></span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html( $parent_ver ); ?></code></td>
							</tr>
						<?php endif; ?>
						<tr>
							<td><strong><?php esc_html_e( 'Plugin', 'lafka' ); ?></strong></td>
							<td><code><?php echo esc_html( $plugin_ver ); ?></code></td>
						</tr>
					</tbody>
				</table>

				<h2 style="margin-top: 32px;"><?php esc_html_e( 'GitHub release cache', 'lafka' ); ?></h2>
				<p>
					<?php esc_html_e( 'WordPress caches GitHub release metadata for ~12 hours between update checks. After pushing a new release tag, click below to force WordPress to re-check immediately — otherwise the new version may not appear in Updates for several hours.', 'lafka' ); ?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( $flush_url ); ?>">
						<?php esc_html_e( 'Clear GitHub release cache + re-check now', 'lafka' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">
						<?php esc_html_e( 'Go to Updates page', 'lafka' ); ?>
					</a>
				</p>
				<p class="description">
					<?php
					/* translators: %s — internal admin-post action name */
					printf(
						esc_html__( 'Internal action: %s. Clears the lafka_gh_* release transients and the WordPress update_themes / update_plugins site transients in one click. Safe to run repeatedly.', 'lafka' ),
						'<code>lafka_flush_github_cache</code>'
					);
					?>
				</p>

				<h2 style="margin-top: 32px;"><?php esc_html_e( 'Quick links', 'lafka' ); ?></h2>
				<ul style="list-style: disc; margin-left: 24px;">
					<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lafka_settings' ) ); ?>"><?php esc_html_e( 'Lafka — Site Settings (logos, brand, general)', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lafka_restaurant_info' ) ); ?>"><?php esc_html_e( 'Lafka — Restaurant Information (NAP, hours, cuisines)', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lafka_announce_bar' ) ); ?>"><?php esc_html_e( 'Lafka — Announce Bar', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lafka_home' ) ); ?>"><?php esc_html_e( 'Lafka — Home Page', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lafka_service_eta' ) ); ?>"><?php esc_html_e( 'Lafka — Service ETA', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><?php esc_html_e( 'WordPress → Settings → General (site title, timezone)', 'lafka' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>"><?php esc_html_e( 'WooCommerce → Settings (store address, payment, shipping)', 'lafka' ); ?></a></li>
				</ul>
			</div>
			<?php
		}
	}

	Lafka_Tools_Page::init();
}
