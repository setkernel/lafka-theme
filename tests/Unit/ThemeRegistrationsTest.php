<?php
/**
 * Smoke test for theme bootstrap registrations (P2-03a.5).
 *
 * Locks the set of nav-menu locations + image sizes as source-grep assertions
 * — no WP runtime needed. Catches accidental deletions / renames during
 * refactor that would silently break theme switchers, child themes that
 * reference these slugs, and feature-image rendering.
 *
 * Why source-grep over runtime: the registrations happen in `after_setup_theme`
 * action callbacks. To exercise them, we'd have to boot WP. Source-grep gives
 * us 90% of the lock value at 0% of the WP-bootstrap cost.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeRegistrationsTest extends TestCase {

	private const CORE_FUNCTIONS = __DIR__ . '/../../incl/system/core-functions.php';
	private const FUNCTIONS_PHP  = __DIR__ . '/../../functions.php';

	// ─── Nav menu locations ─────────────────────────────────────────────────

	public function test_nav_menus_registered(): void {
		$src = file_get_contents( self::CORE_FUNCTIONS );
		self::assertStringContainsString( "add_action( 'after_setup_theme', 'lafka_register_nav_menus' )", $src );
		self::assertStringContainsString( "register_nav_menus(", $src );
	}

	/**
	 * @dataProvider expected_nav_locations
	 */
	public function test_nav_location_present( string $slug ): void {
		$src = file_get_contents( self::CORE_FUNCTIONS );
		self::assertMatchesRegularExpression(
			"/'{$slug}'\s*=>\s*esc_html__\(/",
			$src,
			"Nav menu location '{$slug}' must remain registered — header.php / child themes depend on it."
		);
	}

	public function expected_nav_locations(): array {
		return array(
			'primary'   => array( 'primary' ),
			'mobile'    => array( 'mobile' ),
			'top-left'  => array( 'top-left' ),
			'top-right' => array( 'top-right' ),
			'tertiary'  => array( 'tertiary' ),
		);
	}

	// ─── Image sizes ────────────────────────────────────────────────────────

	/**
	 * @dataProvider expected_image_sizes
	 */
	public function test_image_size_present( string $slug ): void {
		$src = file_get_contents( self::FUNCTIONS_PHP );
		self::assertMatchesRegularExpression(
			"/add_image_size\(\s*'{$slug}'/",
			$src,
			"Image size '{$slug}' must remain registered — featured-image rendering depends on it."
		);
	}

	public function expected_image_sizes(): array {
		return array(
			'lafka-foodmenu-single-thumb'    => array( 'lafka-foodmenu-single-thumb' ),
			'lafka-640x640'                  => array( 'lafka-640x640' ),
			'lafka-general-small-size'       => array( 'lafka-general-small-size' ),
			'lafka-general-small-size-nocrop' => array( 'lafka-general-small-size-nocrop' ),
			'lafka-widgets-thumb'            => array( 'lafka-widgets-thumb' ),
			'lafka-related-posts'            => array( 'lafka-related-posts' ),
		);
	}
}
