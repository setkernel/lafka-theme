<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * f071 regression lock: exactly one <main> landmark per document.
 *
 * Background: header.php opens <main id="content" tabindex="-1"> (closed in
 * footer.php) wrapping every page, and the skip-link points at #content. The
 * page templates then opened a SECOND <main> inside it — front-page.php,
 * page-menu.php, woocommerce/archive-product.php (all <main id="main" ...
 * role="main">) and woocommerce/single-product.php (<main
 * class="lafka-pdp__main">). Nesting <main> inside <main> is invalid HTML and
 * exposes two main landmarks, which breaks screen-reader landmark navigation
 * and trips automated a11y audits on the home page and the entire
 * menu -> product conversion path.
 *
 * The fix demotes the four inner wrappers to non-landmark <div>s while keeping
 * their styling hooks (front-page keeps id="main", menu/archive keep
 * class="lafka-menu", PDP keeps class="lafka-pdp__main"). header.php's
 * <main id="content"> stays the single document landmark.
 *
 * These assertions fail if any template reintroduces a nested <main> or a
 * second role="main", so the duplicate landmark can never silently return.
 */
final class NestedMainLandmarkTest extends TestCase {

	private function template( string $relative_path ): string {
		$path     = dirname( __DIR__, 2 ) . $relative_path;
		$contents = file_get_contents( $path );
		$this->assertNotFalse(
			$contents,
			"Template {$relative_path} must exist and be readable."
		);

		return (string) $contents;
	}

	/**
	 * The four page templates that used to nest a second <main> inside
	 * header.php's #content landmark.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function provide_inner_wrappers(): array {
		return array(
			'front page'      => array( '/front-page.php', '<div id="main" class="lafka-front-page">' ),
			'menu page'       => array( '/page-menu.php', '<div class="lafka-menu">' ),
			'product archive' => array( '/woocommerce/archive-product.php', '<div class="lafka-menu">' ),
			'single product'  => array( '/woocommerce/single-product.php', '<div class="lafka-pdp__main">' ),
		);
	}

	/**
	 * Each page template must NOT open its own <main> and must NOT expose a
	 * second role="main" — the wrapper has to be a plain non-landmark <div>.
	 */
	#[DataProvider( 'provide_inner_wrappers' )]
	public function test_inner_wrapper_is_not_a_main_landmark( string $relative_path, string $expected_wrapper ): void {
		$contents = $this->template( $relative_path );

		$this->assertStringNotContainsString(
			'<main',
			$contents,
			"{$relative_path} must not open a nested <main> — header.php already provides the single main landmark."
		);
		$this->assertStringNotContainsString(
			'role="main"',
			$contents,
			"{$relative_path} must not expose a second role=\"main\" landmark."
		);
		$this->assertStringContainsString(
			$expected_wrapper,
			$contents,
			"{$relative_path} must demote its wrapper to {$expected_wrapper} (keeps styling hooks, drops the landmark)."
		);
	}

	/**
	 * header.php remains the single document landmark, and the skip-link target
	 * #content is the element that carries it.
	 */
	public function test_header_provides_the_single_main_landmark(): void {
		$header = $this->template( '/header.php' );

		// The real landmark carries tabindex="-1" (skip-link focus target); the
		// file docblock also mentions <main id="content"> in prose, so match the
		// concrete element rather than the bare tag.
		$this->assertStringContainsString(
			'<main id="content" tabindex="-1">',
			$header,
			'header.php must keep <main id="content" tabindex="-1"> as the single document landmark (skip-link target).'
		);
		$this->assertSame(
			1,
			substr_count( $header, '<main id="content" tabindex="-1">' ),
			'header.php must open exactly one <main> landmark element.'
		);
	}
}
