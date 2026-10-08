<?php
/**
 * Social profile links for the footer.
 *
 * The operator lists their profile URLs once, under WooCommerce → Settings →
 * Restaurant → Social Profiles (they also feed the schema.org sameAs data).
 * The footer shows the ones that belong to a social network the theme has an
 * icon for, recognised by host; listings such as Yelp or Google are not social
 * links and are left out.
 *
 * @package Lafka
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_theme_social_label' ) ) {
	/**
	 * Display name of a social network key.
	 *
	 * @since 7.4.0
	 * @param string $network Key from lafka_theme_social_links().
	 * @return string
	 */
	function lafka_theme_social_label( string $network ): string {
		$labels = array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'tiktok'    => 'TikTok',
			'x'         => 'X',
			'pinterest' => 'Pinterest',
			'linkedin'  => 'LinkedIn',
		);
		return $labels[ $network ] ?? ucfirst( $network );
	}
}

if ( ! function_exists( 'lafka_theme_social_links' ) ) {
	/**
	 * Social network => profile URL, at most one URL per network, in the order
	 * the operator listed them.
	 *
	 * @since 7.4.0
	 * @return array<string,string> Network key (also the lafka_icon() name) => URL.
	 */
	function lafka_theme_social_links(): array {
		$info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
		$urls = isset( $info['same_as'] ) && is_array( $info['same_as'] ) ? $info['same_as'] : array();

		// host suffix => network key.
		$hosts = array(
			'facebook.com'  => 'facebook',
			'fb.com'        => 'facebook',
			'instagram.com' => 'instagram',
			'tiktok.com'    => 'tiktok',
			'x.com'         => 'x',
			'twitter.com'   => 'x',
			'pinterest.com' => 'pinterest',
			'linkedin.com'  => 'linkedin',
		);

		$links = array();
		foreach ( $urls as $url ) {
			$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
			if ( '' === $host ) {
				continue;
			}
			foreach ( $hosts as $suffix => $network ) {
				if ( ! isset( $links[ $network ] ) && ( $host === $suffix || substr( $host, -strlen( '.' . $suffix ) ) === '.' . $suffix ) ) {
					$links[ $network ] = (string) $url;
					break;
				}
			}
		}

		/**
		 * Filter the footer's social links.
		 *
		 * @since 7.4.0
		 * @param array<string,string> $links Network key => URL.
		 */
		return (array) apply_filters( 'lafka_footer_social_links', $links );
	}
}
