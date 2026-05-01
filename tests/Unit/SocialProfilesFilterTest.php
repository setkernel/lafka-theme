<?php
/**
 * SocialProfilesFilterTest — locks down v5.15.3 social-profiles
 * extensibility and modern-network defaults.
 *
 * Pre-fix the social profile list was a hardcoded array of 11 networks
 * frozen circa 2015 (vKontakte, Flickr, Vimeo, Dribbble, Behance — but
 * no Mastodon, Threads, BlueSky, TikTok, Discord, etc.). Now filterable
 * via `lafka_social_profiles` so child themes / plugins can extend
 * without forking the partial.
 *
 * @package Lafka\Tests\Unit
 * @since   5.15.3
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SocialProfilesFilterTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/partials/social-profiles.php' );
	}

	public function test_partial_uses_apply_filters_for_extensibility(): void {
		$this->assertMatchesRegularExpression(
			"/apply_filters\(\s*\n?\s*'lafka_social_profiles'/",
			$this->src,
			"Partial must expose 'lafka_social_profiles' filter so child themes can extend the network list."
		);
	}

	/**
	 * @dataProvider modernNetworksProvider
	 */
	public function test_default_list_includes_modern_network( string $key ): void {
		// 2015-era list missed every network from the past decade. Defaults
		// must include modern networks so operators with a fresh install
		// have those keys available without writing a filter.
		$this->assertMatchesRegularExpression(
			"/'" . preg_quote( $key, '/' ) . "'\s*=>\s*array\(/",
			$this->src,
			"Default social-profiles list must include the '{$key}' network."
		);
	}

	public function modernNetworksProvider(): array {
		return array(
			'tiktok'   => array( 'tiktok' ),
			'threads'  => array( 'threads' ),
			'mastodon' => array( 'mastodon' ),
			'bluesky'  => array( 'bluesky' ),
			'discord'  => array( 'discord' ),
		);
	}

	public function test_legacy_typos_preserved_for_back_compat(): void {
		// `instegram` (sic) and `flicker` (sic) are misspellings frozen as
		// option keys since circa 2015. Existing operators have URLs saved
		// against those keys; renaming the key without a data migration
		// would silently lose those URLs. Tests that they STAY misspelled
		// in the defaults (until someone ships a migration).
		$this->assertStringContainsString( "'instegram'", $this->src );
		$this->assertStringContainsString( "'flicker'", $this->src );
	}

	public function test_target_blank_links_have_noopener(): void {
		// v5.15.2 tab-nabbing defence — links to external networks must
		// carry rel="noopener noreferrer" alongside target="_blank".
		$lines = file( dirname( __DIR__, 2 ) . '/partials/social-profiles.php', FILE_IGNORE_NEW_LINES );
		foreach ( $lines as $line_index => $line ) {
			if ( false === strpos( $line, 'target="_blank"' ) ) {
				continue;
			}
			$this->assertStringContainsString(
				'rel="noopener noreferrer"',
				$line,
				sprintf( 'Line %d emits target="_blank" without rel="noopener noreferrer".', $line_index + 1 )
			);
		}
	}

	public function test_renders_screen_reader_text_for_a11y(): void {
		// Icons-only links are inscrutable to screen readers without an
		// accessible name. .screen-reader-text + aria-hidden on the <i>
		// is the WP-canonical pattern.
		$this->assertStringContainsString( 'screen-reader-text', $this->src );
		$this->assertMatchesRegularExpression(
			'/<i\s+class="<\?php echo esc_attr\(\s*\$lafka_details\[\'class\'\]\s*\)\s*;\s*\?>"\s+aria-hidden="true"/',
			$this->src,
			'Icon <i> must include aria-hidden="true".'
		);
	}
}
