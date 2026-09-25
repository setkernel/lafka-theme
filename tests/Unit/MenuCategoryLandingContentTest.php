<?php
declare(strict_types=1);

/**
 * GX3 category landing content (incl/template-helpers/menu-category-seo.php +
 * partials/menu-category-faq.php):
 *
 *   - the term description renders as a `.lafka-menu__intro` block with real
 *     paragraphs (no <p> nested in the old lead <p>), nothing when empty;
 *   - the category FAQ partial renders <details> items from the plugin's
 *     lafka_seo_get_term_faqs(), skips half-filled pairs, has a filterable
 *     heading, and renders nothing when there are no items or no plugin.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-category-seo.php';

	if ( ! function_exists( 'wpautop' ) ) {
		/** Minimal wpautop: blank-line-separated blocks become paragraphs. */
		function wpautop( $text, $br = true ) {
			$text = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $text ) );
			if ( '' === $text ) {
				return '';
			}
			$out = '';
			foreach ( preg_split( '/\n\s*\n/', $text ) as $block ) {
				$out .= '<p>' . trim( $block ) . "</p>\n";
			}
			return $out;
		}
	}

	/**
	 * Define the lafka-plugin FAQ reader on demand (store-backed), so the
	 * "plugin absent" case can run in a separate process without it.
	 */
	function lafka_test_define_term_faqs_shim() {
		if ( ! function_exists( 'lafka_seo_get_term_faqs' ) ) {
			function lafka_seo_get_term_faqs( $term_id ) {
				return $GLOBALS['lafka_test_term_faqs'][ (int) $term_id ] ?? array();
			}
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;
	use PHPUnit\Framework\TestCase;

	final class MenuCategoryLandingContentTest extends TestCase {

		protected function setUp(): void {
			parent::setUp();
			$GLOBALS['lafka_test_term_faqs'] = array();
		}

		private function render_faq( int $term_id ): string {
			$args = array( 'term_id' => $term_id );
			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/menu-category-faq.php';
			return (string) ob_get_clean();
		}

		// ── Intro ───────────────────────────────────────────────────────────

		public function test_multi_paragraph_description_renders_an_intro_block_of_paragraphs(): void {
			$html = \lafka_menu_term_intro_html( "Hand-cut fries, squeaky curds.\n\nMade to order every day." );

			$this->assertStringStartsWith( '<div class="lafka-menu__intro">', $html );
			$this->assertSame( 2, substr_count( $html, '<p>' ) );
			$this->assertStringContainsString( '<p>Made to order every day.</p>', $html );
			$this->assertStringNotContainsString( 'lafka-menu__lead', $html );
		}

		public function test_empty_or_markup_only_description_renders_nothing(): void {
			$this->assertSame( '', \lafka_menu_term_intro_html( '' ) );
			$this->assertSame( '', \lafka_menu_term_intro_html( "  \n " ) );
			$this->assertSame( '', \lafka_menu_term_intro_html( '<p> </p>' ) );
		}

		// ── FAQ ─────────────────────────────────────────────────────────────

		public function test_faq_renders_filled_pairs_as_details(): void {
			\lafka_test_define_term_faqs_shim();
			$GLOBALS['lafka_test_term_faqs'][12] = array(
				array( 'q' => 'Is the gravy vegetarian?', 'a' => 'Yes — it is made <strong>without</strong> meat stock.' ),
				array( 'q' => 'Half-filled question', 'a' => '' ),
				array( 'q' => 'Can I order a large?', 'a' => 'Large is available.' ),
			);

			$html = $this->render_faq( 12 );

			$this->assertStringContainsString( '<section class="lafka-menu__faq" aria-labelledby="lafka-menu-faq-12">', $html );
			$this->assertStringContainsString( '<h2 id="lafka-menu-faq-12" class="lafka-menu__faq-title">Frequently asked questions</h2>', $html );
			$this->assertSame( 2, substr_count( $html, '<details class="lafka-menu__faq-item">' ) );
			$this->assertStringContainsString( '<summary class="lafka-menu__faq-q">Is the gravy vegetarian?</summary>', $html );
			$this->assertStringContainsString( '<strong>without</strong>', $html );
			$this->assertStringNotContainsString( 'Half-filled question', $html );
		}

		public function test_faq_heading_is_filterable(): void {
			\lafka_test_define_term_faqs_shim();
			$GLOBALS['lafka_test_term_faqs'][7] = array( array( 'q' => 'Q?', 'a' => 'A.' ) );
			add_filter( 'lafka_category_faq_heading', static fn( $heading, $term_id ) => 'Poutine questions #' . $term_id, 10, 2 );

			$this->assertStringContainsString( '>Poutine questions #7</h2>', $this->render_faq( 7 ) );
		}

		public function test_faq_renders_nothing_without_items(): void {
			\lafka_test_define_term_faqs_shim();

			$this->assertSame( '', $this->render_faq( 99 ) );
			$this->assertSame( '', $this->render_faq( 0 ) );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_faq_renders_nothing_when_the_plugin_is_absent(): void {
			$this->assertFalse( function_exists( 'lafka_seo_get_term_faqs' ) );
			$this->assertSame( array(), \lafka_menu_category_faq_items( 12 ) );
			$this->assertSame( '', $this->render_faq( 12 ) );
		}
	}
}
