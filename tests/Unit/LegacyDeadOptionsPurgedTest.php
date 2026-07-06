<?php
/**
 * LegacyDeadOptionsPurgedTest — NX1-02.dead-purge regression guard.
 *
 * The legacy Options Framework registered 48 field keys that no live reader
 * anywhere in the theme, plugin, or child consumes (verified by exact
 * lafka_get_option() and broad bare-key greps during the NX1-02 inventory).
 * The dead-purge slice deletes their registrations from
 * incl/lafka-options-framework/lafka-options.php and removes the sole (never
 * included) reader of the 11 *_profile keys, partials/social-profiles.php.
 *
 * This source-grep guard locks that deletion: it fails if any dead key is
 * re-registered or the orphaned partial reappears. It is intentionally
 * source-based (no WP runtime) — the framework registry function needs the
 * full admin scaffolding to execute, so a byte-level assertion on the file is
 * both cheaper and a truer regression fence.
 *
 * Note on exact matching: several dead keys are prefixes of LIVE keys
 * (top_bar_message vs top_bar_message_color/_phone; number_related_posts vs
 * number_related_products; video_bckgr_* vs video_bckgr_url). The regex anchors
 * on the id-registration pattern WITH the closing quote so a dead key can never
 * match its surviving sibling's registration.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 6.21.0 (NX1-02 dead-purge)
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LegacyDeadOptionsPurgedTest extends TestCase {

	private function options_src(): string {
		$path = dirname( __DIR__, 2 ) . '/incl/lafka-options-framework/lafka-options.php';
		$src  = file_get_contents( $path );
		$this->assertNotFalse( $src, 'Could not read lafka-options.php' );
		return (string) $src;
	}

	#[DataProvider('deadKeysProvider')]
	public function test_dead_key_has_no_field_registration( string $key ): void {
		$src = $this->options_src();
		$this->assertDoesNotMatchRegularExpression(
			"/'id'\\s*=>\\s*'" . preg_quote( $key, '/' ) . "'/",
			$src,
			"Dead legacy option '{$key}' must not be registered in the Options Framework "
				. '(NX1-02.dead-purge removed it — see tests/Unit/LegacyDeadOptionsPurgedTest.php).'
		);
	}

	public function test_orphaned_social_profiles_partial_is_deleted(): void {
		$this->assertFileDoesNotExist(
			dirname( __DIR__, 2 ) . '/partials/social-profiles.php',
			'partials/social-profiles.php was the sole (never-included) reader of the dead '
				. '*_profile keys and must stay deleted.'
		);
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function deadKeysProvider(): array {
		$keys = array(
			'transparent_header_menu_color',
			'transparent_header_menu_hover_color',
			'transparent_header_dark_menu_hover_color',
			'enable_pre_header',
			'top_bar_message',
			'top_bar_message_phone_link',
			'copyright_text',
			'show_logo_in_footer',
			'footer_logo',
			'footer_sidebar',
			'video_bckgr_start',
			'video_bckgr_end',
			'video_bckgr_loop',
			'video_bckgr_mute',
			'shop_video_bckgr_start',
			'shop_video_bckgr_end',
			'shop_video_bckgr_loop',
			'shop_video_bckgr_mute',
			'blog_video_bckgr_start',
			'blog_video_bckgr_end',
			'blog_video_bckgr_loop',
			'blog_video_bckgr_mute',
			'show_related_posts',
			'owl_carousel',
			'number_related_posts',
			'events_subtitle',
			'events_title_background_imgid',
			'events_title_alignment',
			'social_in_footer',
			'facebook_profile',
			'twitter_profile',
			'youtube_profile',
			'vimeo_profile',
			'dribbble_profile',
			'linkedin_profile',
			'flicker_profile',
			'instegram_profile',
			'pinterest_profile',
			'vkontakte_profile',
			'behance_profile',
			'import_lafka0',
			'import_lafka1',
			'import_lafka2',
			'import_lafka3',
			'import_lafka4',
			'import_lafka5',
			'import_lafka6',
			'additional_stylesheet',
		);

		$provider = array();
		foreach ( $keys as $key ) {
			$provider[ $key ] = array( $key );
		}
		return $provider;
	}
}
