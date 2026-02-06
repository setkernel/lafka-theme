<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lafka GitHub Updater
 *
 * Integrates with WordPress update system to check GitHub releases
 * for lafka-theme, lafka-child, and lafka-plugin updates.
 */
class Lafka_GitHub_Updater {

	const THEME_REPO  = 'setkernel/lafka-theme';
	const CHILD_REPO  = 'setkernel/lafka-child';
	const PLUGIN_REPO = 'setkernel/lafka-plugin';

	const THEME_ASSET  = 'lafka.zip';
	const CHILD_ASSET  = 'lafka-child.zip';
	const PLUGIN_ASSET = 'lafka-plugin.zip';
	const PLUGIN_FILE  = 'lafka-plugin/lafka-plugin.php';

	const CACHE_SUCCESS    = 43200; // 12 hours
	const CACHE_FAILURE    = 3600;  // 1 hour
	const CACHE_RATE_LIMIT = 86400; // 24 hours — used when near rate limit

	/** @var string Option key for the admin notice transient. */
	const NOTICE_TRANSIENT = 'lafka_gh_updater_notice';

	public function __construct() {
		// Allow disabling update checks entirely.
		if ( ! lafka_get_option( 'lafka_github_updates_enabled', true ) ) {
			return;
		}

		// Theme update hooks
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_theme_update' ) );
		add_filter( 'themes_api', array( $this, 'theme_info' ), 10, 3 );

		// Plugin update hooks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );

		// Clear our GitHub transients when WordPress forces a fresh update check
		add_action( 'delete_site_transient_update_themes', array( $this, 'flush_theme_cache' ) );
		add_action( 'delete_site_transient_update_plugins', array( $this, 'flush_plugin_cache' ) );

		// Fix directory name after zip extraction
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_directory' ), 10, 4 );

		// Admin notices for update issues
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	// =========================================================================
	// Logging & notices
	// =========================================================================

	/**
	 * Log a message prefixed with [Lafka Updater].
	 *
	 * @param string $message
	 */
	private static function log( $message ) {
		error_log( '[Lafka Updater] ' . $message );
	}

	/**
	 * Store a dismissible admin notice.
	 *
	 * @param string $message  Plain-text or escaped HTML message.
	 * @param string $type     'error' | 'warning' | 'info'.
	 */
	private static function set_notice( $message, $type = 'warning' ) {
		set_transient( self::NOTICE_TRANSIENT, array(
			'message' => $message,
			'type'    => $type,
		), DAY_IN_SECONDS );
	}

	/**
	 * Clear any stored admin notice (called on successful API fetch).
	 */
	private static function clear_notice() {
		delete_transient( self::NOTICE_TRANSIENT );
	}

	/**
	 * Render an admin notice on dashboard/updates/theme-options screens.
	 */
	public function render_admin_notices() {
		if ( ! current_user_can( 'update_themes' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed_screens = array( 'dashboard', 'update-core', 'themes', 'plugins', 'appearance_page_lafka-options' );
		if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
			return;
		}

		$notice = get_transient( self::NOTICE_TRANSIENT );
		if ( empty( $notice ) || ! is_array( $notice ) ) {
			return;
		}

		$type    = in_array( $notice['type'], array( 'error', 'warning', 'info' ), true ) ? $notice['type'] : 'warning';
		$message = wp_kses_post( $notice['message'] );

		printf(
			'<div class="notice notice-%s is-dismissible"><p><strong>Lafka Updater:</strong> %s</p></div>',
			esc_attr( $type ),
			$message
		);
	}

	// =========================================================================
	// Cache management
	// =========================================================================

	/**
	 * Clear the cached GitHub releases for the theme and child theme repos.
	 */
	public function flush_theme_cache() {
		delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::THEME_REPO ) ) );
		delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::CHILD_REPO ) ) );
	}

	/**
	 * Clear the cached GitHub release for the plugin repo.
	 */
	public function flush_plugin_cache() {
		delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::PLUGIN_REPO ) ) );
	}

	// =========================================================================
	// GitHub API
	// =========================================================================

	/**
	 * Build request headers, including auth token if available.
	 *
	 * @return array
	 */
	private static function get_request_headers() {
		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'Lafka-WordPress-Theme',
		);

		$token = lafka_get_option( 'lafka_github_token', '' );
		if ( ! empty( $token ) ) {
			$headers['Authorization'] = 'token ' . $token;
		}

		return $headers;
	}

	/**
	 * Fetch the latest release from a GitHub repo (cached).
	 *
	 * Returns cached data when available. When the transient expires
	 * naturally, the next call fetches fresh data from the API.
	 *
	 * @param string $repo  e.g. 'setkernel/lafka-theme'
	 * @return object|false  Decoded JSON object or false on failure.
	 */
	public static function get_latest_release( $repo ) {
		$transient_key = 'lafka_gh_' . sanitize_key( str_replace( '/', '_', $repo ) );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			// We store 'error' string on failure so we can distinguish from empty cache.
			return ( 'error' === $cached ) ? false : $cached;
		}

		$url      = 'https://api.github.com/repos/' . $repo . '/releases/latest';
		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => self::get_request_headers(),
		) );

		// -- Handle transport errors --
		if ( is_wp_error( $response ) ) {
			self::log( 'API request failed for ' . $repo . ': ' . $response->get_error_message() );
			self::set_notice( 'Could not reach GitHub API: ' . esc_html( $response->get_error_message() ), 'error' );
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$headers     = wp_remote_retrieve_headers( $response );

		// -- Process rate limit headers --
		$rate_remaining = isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : null;
		$rate_limit     = isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : null;
		$rate_reset     = isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : null;

		// Store rate limit info for admin display.
		if ( null !== $rate_remaining && null !== $rate_limit ) {
			set_transient( 'lafka_gh_rate_limit', array(
				'remaining' => $rate_remaining,
				'limit'     => $rate_limit,
				'reset'     => $rate_reset,
			), HOUR_IN_SECONDS );
		}

		// -- Handle rate limiting (403 with remaining=0) --
		if ( 403 === $status_code && 0 === $rate_remaining ) {
			$reset_time = $rate_reset ? human_time_diff( time(), $rate_reset ) : 'unknown';
			self::log( 'Rate limited by GitHub API for ' . $repo . '. Resets in ' . $reset_time . '.' );
			self::set_notice(
				sprintf(
					'GitHub API rate limit reached. Updates will resume in %s. Consider adding a <a href="%s">Personal Access Token</a> for higher limits.',
					esc_html( $reset_time ),
					esc_url( admin_url( 'admin.php?page=lafka-options' ) )
				),
				'warning'
			);
			// Cache the error until rate limit resets.
			$cache_ttl = $rate_reset ? max( $rate_reset - time(), self::CACHE_FAILURE ) : self::CACHE_RATE_LIMIT;
			set_transient( $transient_key, 'error', $cache_ttl );
			return false;
		}

		// -- Handle other HTTP errors --
		if ( 200 !== $status_code ) {
			self::log( 'GitHub API returned HTTP ' . $status_code . ' for ' . $repo . '.' );
			if ( 404 === $status_code ) {
				self::set_notice( 'No releases found for <code>' . esc_html( $repo ) . '</code>. The repository may not have any published releases yet.', 'info' );
			} else {
				self::set_notice( 'GitHub API error (HTTP ' . $status_code . ') when checking <code>' . esc_html( $repo ) . '</code>.', 'error' );
			}
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body ) || empty( $body->tag_name ) ) {
			self::log( 'Invalid JSON or missing tag_name in response for ' . $repo . '.' );
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		// -- Version validation: reject pre-release / malformed tags --
		$version = self::tag_to_version( $body->tag_name );
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			self::log( 'Skipping release with malformed version tag "' . $body->tag_name . '" for ' . $repo . '.' );
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		if ( ! empty( $body->prerelease ) ) {
			self::log( 'Skipping pre-release "' . $body->tag_name . '" for ' . $repo . '.' );
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		// -- Determine cache TTL (extend if near rate limit) --
		$cache_ttl = self::CACHE_SUCCESS;
		if ( null !== $rate_remaining && $rate_remaining < 5 ) {
			self::log( 'Rate limit low (' . $rate_remaining . ' remaining) for ' . $repo . '. Extending cache to 24h.' );
			$cache_ttl = self::CACHE_RATE_LIMIT;
		}

		// Success — clear any previous error notice.
		self::clear_notice();

		set_transient( $transient_key, $body, $cache_ttl );
		return $body;
	}

	/**
	 * Get the download URL for a named asset from a release.
	 *
	 * @param object $release    GitHub release object.
	 * @param string $asset_name e.g. 'lafka.zip'
	 * @return string|false
	 */
	private static function get_asset_url( $release, $asset_name ) {
		if ( empty( $release->assets ) || ! is_array( $release->assets ) ) {
			self::log( 'Release ' . $release->tag_name . ' has no assets.' );
			return false;
		}

		foreach ( $release->assets as $asset ) {
			if ( $asset->name === $asset_name ) {
				return $asset->browser_download_url;
			}
		}

		// Log available assets to aid debugging.
		$available = array();
		foreach ( $release->assets as $asset ) {
			$available[] = $asset->name;
		}
		self::log(
			'Asset "' . $asset_name . '" not found in release ' . $release->tag_name .
			'. Available: ' . implode( ', ', $available )
		);

		return false;
	}

	/**
	 * Strip leading 'v' from tag name to get a clean version string.
	 *
	 * @param string $tag e.g. 'v5.1.0'
	 * @return string e.g. '5.1.0'
	 */
	private static function tag_to_version( $tag ) {
		return ltrim( $tag, 'vV' );
	}

	// =========================================================================
	// Theme updater
	// =========================================================================

	/**
	 * Inject theme update data into the update_themes transient.
	 *
	 * Checks both the parent theme and, if active, the child theme.
	 */
	public function check_theme_update( $transient ) {
		if ( empty( $transient ) ) {
			return $transient;
		}

		// --- Parent theme ---
		$theme_slug    = get_template();
		$current_theme = wp_get_theme( $theme_slug );
		$local_version = $current_theme->get( 'Version' );

		$release = self::get_latest_release( self::THEME_REPO );
		if ( $release ) {
			$remote_version = self::tag_to_version( $release->tag_name );
			$download_url   = self::get_asset_url( $release, self::THEME_ASSET );

			if ( version_compare( $remote_version, $local_version, '>' ) ) {
				if ( $download_url ) {
					$transient->response[ $theme_slug ] = array(
						'theme'       => $theme_slug,
						'new_version' => $remote_version,
						'url'         => 'https://github.com/' . self::THEME_REPO,
						'package'     => $download_url,
					);
				} else {
					self::log( 'Theme update ' . $remote_version . ' available but asset "' . self::THEME_ASSET . '" missing from release.' );
					self::set_notice(
						'Theme update <strong>' . esc_html( $remote_version ) . '</strong> is available but the download asset is missing from the GitHub release. Please report this to the theme maintainer.',
						'warning'
					);
				}
			}
		}

		// --- Child theme (only if one is active) ---
		$child_slug = get_stylesheet();
		if ( $child_slug !== $theme_slug ) {
			$child_theme   = wp_get_theme( $child_slug );
			$child_version = $child_theme->get( 'Version' );

			$child_release = self::get_latest_release( self::CHILD_REPO );
			if ( $child_release ) {
				$child_remote = self::tag_to_version( $child_release->tag_name );
				$child_url    = self::get_asset_url( $child_release, self::CHILD_ASSET );

				if ( version_compare( $child_remote, $child_version, '>' ) ) {
					if ( $child_url ) {
						$transient->response[ $child_slug ] = array(
							'theme'       => $child_slug,
							'new_version' => $child_remote,
							'url'         => 'https://github.com/' . self::CHILD_REPO,
							'package'     => $child_url,
						);
					} else {
						self::log( 'Child theme update ' . $child_remote . ' available but asset "' . self::CHILD_ASSET . '" missing from release.' );
						self::set_notice(
							'Child theme update <strong>' . esc_html( $child_remote ) . '</strong> is available but the download asset is missing from the GitHub release.',
							'warning'
						);
					}
				}
			}
		}

		return $transient;
	}

	/**
	 * Provide theme info for the "View Details" modal.
	 *
	 * Handles both parent theme and child theme requests.
	 */
	public function theme_info( $result, $action, $args ) {
		if ( 'theme_information' !== $action || ! isset( $args->slug ) ) {
			return $result;
		}

		$theme_slug = get_template();
		$child_slug = get_stylesheet();

		// Determine which repo/asset to query
		if ( $args->slug === $theme_slug ) {
			$repo  = self::THEME_REPO;
			$asset = self::THEME_ASSET;
		} elseif ( $child_slug !== $theme_slug && $args->slug === $child_slug ) {
			$repo  = self::CHILD_REPO;
			$asset = self::CHILD_ASSET;
		} else {
			return $result;
		}

		$release = self::get_latest_release( $repo );
		if ( ! $release ) {
			return $result;
		}

		$current_theme  = wp_get_theme( $args->slug );
		$remote_version = self::tag_to_version( $release->tag_name );
		$download_url   = self::get_asset_url( $release, $asset );

		$info                = new stdClass();
		$info->name          = $current_theme->get( 'Name' );
		$info->slug          = $args->slug;
		$info->version       = $remote_version;
		$info->author        = $current_theme->get( 'Author' );
		$info->homepage      = 'https://github.com/' . $repo;
		$info->download_link = $download_url ? $download_url : '';
		$info->sections      = array(
			'description' => $current_theme->get( 'Description' ),
			'changelog'   => ! empty( $release->body ) ? self::format_changelog( $release->body ) : '',
		);
		$info->requires      = '5.0';
		$info->tested        = '6.7';

		return $info;
	}

	// =========================================================================
	// Plugin updater
	// =========================================================================

	/**
	 * Inject plugin update data into the update_plugins transient.
	 */
	public function check_plugin_update( $transient ) {
		if ( empty( $transient ) ) {
			return $transient;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE;
		if ( ! file_exists( $plugin_file ) ) {
			return $transient;
		}

		$plugin_data   = get_plugin_data( $plugin_file );
		$local_version = $plugin_data['Version'];

		$release = self::get_latest_release( self::PLUGIN_REPO );
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = self::tag_to_version( $release->tag_name );
		$download_url   = self::get_asset_url( $release, self::PLUGIN_ASSET );

		if ( version_compare( $remote_version, $local_version, '>' ) ) {
			if ( $download_url ) {
				$transient->response[ self::PLUGIN_FILE ] = (object) array(
					'slug'        => 'lafka-plugin',
					'plugin'      => self::PLUGIN_FILE,
					'new_version' => $remote_version,
					'url'         => 'https://github.com/' . self::PLUGIN_REPO,
					'package'     => $download_url,
					'tested'      => '6.7',
				);
			} else {
				self::log( 'Plugin update ' . $remote_version . ' available but asset "' . self::PLUGIN_ASSET . '" missing from release.' );
				self::set_notice(
					'Plugin update <strong>' . esc_html( $remote_version ) . '</strong> is available but the download asset is missing from the GitHub release. Please report this to the theme maintainer.',
					'warning'
				);
			}
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the "View Details" modal.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || 'lafka-plugin' !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release( self::PLUGIN_REPO );
		if ( ! $release ) {
			return $result;
		}

		$remote_version = self::tag_to_version( $release->tag_name );
		$download_url   = self::get_asset_url( $release, self::PLUGIN_ASSET );

		$info                = new stdClass();
		$info->name          = 'Lafka Plugin';
		$info->slug          = 'lafka-plugin';
		$info->version       = $remote_version;
		$info->author        = 'theAlThemist, Contributors';
		$info->homepage      = 'https://github.com/' . self::PLUGIN_REPO;
		$info->download_link = $download_url ? $download_url : '';
		$info->sections      = array(
			'description' => 'Companion plugin for the Lafka WordPress theme.',
			'changelog'   => ! empty( $release->body ) ? self::format_changelog( $release->body ) : '',
		);
		$info->requires      = '5.0';
		$info->tested        = '6.7';
		$info->banners       = array();

		return $info;
	}

	// =========================================================================
	// Directory slug correction after zip extraction
	// =========================================================================

	/**
	 * Fix source directory name after WP extracts a GitHub release zip.
	 *
	 * GitHub release zips often extract to directories like "lafka-theme-v5.4.5/"
	 * but WordPress expects the slug directory (e.g. "lafka/"). This renames
	 * the extracted folder to match the expected slug.
	 *
	 * @param string      $source        Path to the extracted source directory.
	 * @param string      $remote_source Path to the remote source (unused).
	 * @param WP_Upgrader $upgrader      Upgrader instance.
	 * @param array       $hook_extra    Extra hook data with 'theme' or 'plugin' key.
	 * @return string|WP_Error
	 */
	public function fix_source_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		// Identify if this is one of our packages.
		$expected_slug = false;

		if ( ! empty( $hook_extra['theme'] ) ) {
			$theme_slug = get_template();
			$child_slug = get_stylesheet();

			if ( $hook_extra['theme'] === $theme_slug ) {
				$expected_slug = $theme_slug;
			} elseif ( $hook_extra['theme'] === $child_slug && $child_slug !== $theme_slug ) {
				$expected_slug = $child_slug;
			}
		} elseif ( ! empty( $hook_extra['plugin'] ) && self::PLUGIN_FILE === $hook_extra['plugin'] ) {
			$expected_slug = 'lafka-plugin';
		}

		if ( ! $expected_slug ) {
			return $source;
		}

		// Check if the extracted directory already has the correct name.
		$source_slug = basename( untrailingslashit( $source ) );
		if ( $source_slug === $expected_slug ) {
			return $source;
		}

		// Rename to expected slug.
		$corrected_source = trailingslashit( dirname( untrailingslashit( $source ) ) ) . $expected_slug . '/';

		if ( $wp_filesystem->move( $source, $corrected_source ) ) {
			self::log( 'Renamed extracted directory "' . $source_slug . '" to "' . $expected_slug . '".' );
			return $corrected_source;
		}

		self::log( 'Failed to rename extracted directory "' . $source_slug . '" to "' . $expected_slug . '".' );
		return new WP_Error(
			'lafka_updater_rename_failed',
			sprintf(
				'Lafka Updater could not rename the extracted directory "%s" to "%s". Please try the update again or install manually.',
				$source_slug,
				$expected_slug
			)
		);
	}

	// =========================================================================
	// Static helper for TGMPA
	// =========================================================================

	/**
	 * Get the latest plugin version and download URL for TGMPA registration.
	 *
	 * Falls back to hardcoded values on API failure.
	 *
	 * @return array { 'version' => string, 'source' => string }
	 */
	public static function get_latest_plugin_info() {
		$fallback_version = '8.2.3';
		$fallback_url     = 'https://github.com/' . self::PLUGIN_REPO . '/releases/download/v' . $fallback_version . '/' . self::PLUGIN_ASSET;

		$release = self::get_latest_release( self::PLUGIN_REPO );
		if ( ! $release ) {
			return array(
				'version' => $fallback_version,
				'source'  => $fallback_url,
			);
		}

		$version      = self::tag_to_version( $release->tag_name );
		$download_url = self::get_asset_url( $release, self::PLUGIN_ASSET );

		if ( ! $download_url ) {
			return array(
				'version' => $fallback_version,
				'source'  => $fallback_url,
			);
		}

		return array(
			'version' => $version,
			'source'  => $download_url,
		);
	}

	// =========================================================================
	// Utilities
	// =========================================================================

	/**
	 * Convert GitHub markdown release body to basic HTML for the details modal.
	 *
	 * Runs markdown regex first, then sanitizes via wp_kses_post().
	 */
	private static function format_changelog( $markdown ) {
		$html = $markdown;

		// Headings
		$html = preg_replace( '/^###\s*(.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^##\s*(.+)$/m', '<h3>$1</h3>', $html );

		// Bold
		$html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );

		// List items — wrap consecutive <li> blocks in <ul>.
		$html = preg_replace( '/^[\-\*]\s*(.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/((?:<li>.*<\/li>\s*)+)/', '<ul>$1</ul>', $html );

		// Line breaks for remaining plain-text lines.
		$html = nl2br( $html );

		return wp_kses_post( $html );
	}
}
