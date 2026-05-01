<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EditorialCustomizerTest extends TestCase {
	public function test_customizer_file_exists_in_parent_incl(): void {
		$this->assertFileExists(
			dirname( __DIR__, 2 ) . '/incl/customizer-editorial.php',
			'Editorial customizer registration must live in lafka-theme/incl/.'
		);
	}

	public function test_functions_php_requires_customizer_file(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			"/require(_once)?\s+.*customizer-editorial\.php/",
			$src,
			'Parent functions.php must require_once incl/customizer-editorial.php.'
		);
	}

	public function test_customizer_file_registers_two_panels(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-editorial.php' );
		$this->assertStringContainsString( "'lafka_editorial_home'", $src );
		$this->assertStringContainsString( "'lafka_editorial_contact'", $src );
	}

	public function test_customizer_hooks_into_customize_register(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-editorial.php' );
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]customize_register['\"]/",
			$src
		);
	}

	public function test_editorial_css_enqueue_gated_to_editorial_templates(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertStringContainsString( "'lafka-editorial'", $src );
		$this->assertStringContainsString( 'styles/editorial.css', $src );
		$this->assertMatchesRegularExpression(
			"/is_page_template\(\s*array\(\s*\n?\s*['\"]page_templates\/template-editorial-home\.php['\"]/",
			$src,
			'Editorial CSS must enqueue only when the page uses an editorial template.'
		);
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*['\"]lafka-editorial['\"][\s\S]*?array\(\s*['\"]lafka-style['\"]/",
			$src,
			'lafka-editorial must depend on lafka-style for cascade order — without this dep, base theme styles can override editorial overrides depending on enqueue order.'
		);
	}
}
