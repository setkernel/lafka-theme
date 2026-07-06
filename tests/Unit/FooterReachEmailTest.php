<?php
declare(strict_types=1);

/**
 * Audit V4 regression lock: the footer "Reach us" email must prefer the
 * configured business email and must NEVER leak the host port — the resolver's
 * admin_email default is "info@localhost:8080" on a ported dev install, and that
 * ":8080" was rendering into the mailto: and on-page text.
 *
 * lafka_theme_reach_email() (incl/template-helpers/contact-email.php) is
 * exercised behaviourally here: configured email wins; the fallback derives a
 * host-only address via wp_parse_url( home_url(), PHP_URL_HOST ).
 *
 * The WP shims live in the GLOBAL namespace (the helper resolves its calls
 * there). Each test runs in its own process so these shims win regardless of
 * the shims other test files define for the same function names.
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'home_url' ) ) {
		function home_url( $path = '' ) {
			$base = isset( $GLOBALS['lafka_reach_home_url'] ) ? (string) $GLOBALS['lafka_reach_home_url'] : 'http://example.test';
			return $base . $path;
		}
	}
	if ( ! function_exists( 'wp_parse_url' ) ) {
		function wp_parse_url( $url, $component = -1 ) {
			return parse_url( (string) $url, (int) $component );
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $GLOBALS['lafka_reach_theme_mods'][ $name ] ?? $default;
		}
	}
	if ( ! function_exists( 'lafka_get_restaurant_info' ) ) {
		function lafka_get_restaurant_info() {
			return $GLOBALS['lafka_reach_info'] ?? array();
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/contact-email.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class FooterReachEmailTest extends TestCase {

		protected function setUp(): void {
			// A ported dev install: home_url carries a :8080 port.
			$GLOBALS['lafka_reach_home_url']   = 'http://localhost:8080';
			$GLOBALS['lafka_reach_theme_mods'] = array();
			$GLOBALS['lafka_reach_info']       = array();
		}

		public function test_configured_business_email_wins(): void {
			$GLOBALS['lafka_reach_info'] = array( 'email' => 'hello@realbrand.example' );
			$this->assertSame( 'hello@realbrand.example', \lafka_theme_reach_email() );
		}

		public function test_theme_mod_email_used_when_resolver_email_empty(): void {
			$GLOBALS['lafka_reach_info']       = array( 'email' => '' );
			$GLOBALS['lafka_reach_theme_mods'] = array( 'lafka_business_email' => 'team@brand.example' );
			$this->assertSame( 'team@brand.example', \lafka_theme_reach_email() );
		}

		public function test_fallback_derives_host_only_never_port(): void {
			$GLOBALS['lafka_reach_info'] = array( 'email' => '' );
			$email = \lafka_theme_reach_email();
			$this->assertSame( 'info@localhost', $email );
			$this->assertStringNotContainsString( ':8080', $email );
		}

		public function test_port_leaking_configured_email_is_rejected(): void {
			// The admin_email default on a ported dev install is "info@localhost:8080".
			$GLOBALS['lafka_reach_info'] = array( 'email' => 'info@localhost:8080' );
			$email = \lafka_theme_reach_email();
			$this->assertSame( 'info@localhost', $email );
			$this->assertStringNotContainsString( ':8080', $email );
		}
	}
}
