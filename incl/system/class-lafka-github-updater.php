<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lafka GitHub Updater
 *
 * Integrates with WordPress update system to check GitHub releases
 * for both lafka-theme and lafka-plugin updates.
 */
class Lafka_GitHub_Updater {

	const THEME_REPO  = 'setkernel/lafka-theme';
	const PLUGIN_REPO = 'setkernel/lafka-plugin';

	const THEME_ASSET  = 'lafka.zip';
	const PLUGIN_ASSET = 'lafka-plugin.zip';
	const PLUGIN_FILE  = 'lafka-plugin/lafka-plugin.php';

	const CACHE_SUCCESS = 43200; // 12 hours
	const CACHE_FAILURE = 3600;  // 1 hour

	public function __construct() {
		// Theme update hooks (only if we are the active parent theme)
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_theme_update' ) );
		add_filter( 'themes_api', array( $this, 'theme_info' ), 10, 3 );

		// Plugin update hooks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );

		// Clear our GitHub transients when WordPress forces a fresh update check
		add_action( 'delete_site_transient_update_themes', array( $this, 'flush_theme_cache' ) );
		add_action( 'delete_site_transient_update_plugins', array( $this, 'flush_plugin_cache' ) );
	}

	/**
	 * Clear the cached GitHub release for the theme repo.
	 */
	public function flush_theme_cache() {
		delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::THEME_REPO ) ) );
	}

	/**
	 * Clear the cached GitHub release for the plugin repo.
	 */
	public function flush_plugin_cache() {
		delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::PLUGIN_REPO ) ) );
	}

	/**
	 * Fetch the latest release from a GitHub repo (cached).
	 *
	 * @param string $repo  e.g. 'setkernel/lafka-theme'
	 * @return object|false  Decoded JSON object or false on failure.
	 */
	public static function get_latest_release( $repo ) {
		$transient_key = 'lafka_gh_' . sanitize_key( str_replace( '/', '_', $repo ) );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			// We store 'error' string on failure so we can distinguish from empty cache
			return ( 'error' === $cached ) ? false : $cached;
		}

		$url      = 'https://api.github.com/repos/' . $repo . '/releases/latest';
		$response = wp_remote_get( $url, array(
			'timeout'    => 10,
			'headers'    => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'Lafka-WordPress-Theme',
			),
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body ) || empty( $body->tag_name ) ) {
			set_transient( $transient_key, 'error', self::CACHE_FAILURE );
			return false;
		}

		set_transient( $transient_key, $body, self::CACHE_SUCCESS );
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
			return false;
		}

		foreach ( $release->assets as $asset ) {
			if ( $asset->name === $asset_name ) {
				return $asset->browser_download_url;
			}
		}

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
	 */
	public function check_theme_update( $transient ) {
		if ( empty( $transient ) ) {
			return $transient;
		}

		$theme_slug    = get_template();
		$current_theme = wp_get_theme( $theme_slug );
		$local_version = $current_theme->get( 'Version' );

		$release = self::get_latest_release( self::THEME_REPO );
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = self::tag_to_version( $release->tag_name );

		// Cached release is older than installed version — cache is stale, re-fetch
		if ( version_compare( $remote_version, $local_version, '<' ) ) {
			delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::THEME_REPO ) ) );
			$release = self::get_latest_release( self::THEME_REPO );
			if ( ! $release ) {
				return $transient;
			}
			$remote_version = self::tag_to_version( $release->tag_name );
		}

		$download_url = self::get_asset_url( $release, self::THEME_ASSET );

		if ( ! $download_url || ! version_compare( $remote_version, $local_version, '>' ) ) {
			return $transient;
		}

		$transient->response[ $theme_slug ] = array(
			'theme'       => $theme_slug,
			'new_version' => $remote_version,
			'url'         => 'https://github.com/' . self::THEME_REPO,
			'package'     => $download_url,
		);

		return $transient;
	}

	/**
	 * Provide theme info for the "View Details" modal.
	 */
	public function theme_info( $result, $action, $args ) {
		if ( 'theme_information' !== $action ) {
			return $result;
		}

		$theme_slug = get_template();
		if ( ! isset( $args->slug ) || $args->slug !== $theme_slug ) {
			return $result;
		}

		$release = self::get_latest_release( self::THEME_REPO );
		if ( ! $release ) {
			return $result;
		}

		$current_theme  = wp_get_theme( $theme_slug );
		$remote_version = self::tag_to_version( $release->tag_name );
		$download_url   = self::get_asset_url( $release, self::THEME_ASSET );

		$info                = new stdClass();
		$info->name          = $current_theme->get( 'Name' );
		$info->slug          = $theme_slug;
		$info->version       = $remote_version;
		$info->author        = $current_theme->get( 'Author' );
		$info->homepage      = 'https://github.com/' . self::THEME_REPO;
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

		// Cached release is older than installed version — cache is stale, re-fetch
		if ( version_compare( $remote_version, $local_version, '<' ) ) {
			delete_transient( 'lafka_gh_' . sanitize_key( str_replace( '/', '_', self::PLUGIN_REPO ) ) );
			$release = self::get_latest_release( self::PLUGIN_REPO );
			if ( ! $release ) {
				return $transient;
			}
			$remote_version = self::tag_to_version( $release->tag_name );
		}

		$download_url = self::get_asset_url( $release, self::PLUGIN_ASSET );

		if ( ! $download_url || ! version_compare( $remote_version, $local_version, '>' ) ) {
			return $transient;
		}

		$transient->response[ self::PLUGIN_FILE ] = (object) array(
			'slug'        => 'lafka-plugin',
			'plugin'      => self::PLUGIN_FILE,
			'new_version' => $remote_version,
			'url'         => 'https://github.com/' . self::PLUGIN_REPO,
			'package'     => $download_url,
			'tested'      => '6.7',
		);

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
		$fallback_version = '8.1.0';
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
	 */
	private static function format_changelog( $markdown ) {
		// Basic markdown-to-HTML: headings, bold, list items, line breaks
		$html = esc_html( $markdown );
		$html = preg_replace( '/^###\s*(.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^##\s*(.+)$/m', '<h3>$1</h3>', $html );
		$html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );
		$html = preg_replace( '/^[\-\*]\s*(.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html );
		$html = nl2br( $html );

		return $html;
	}
}
