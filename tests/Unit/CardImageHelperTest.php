<?php
declare(strict_types=1);

/**
 * lafka_card_image_html() (incl/template-helpers/product-card-image.php): the
 * handoff product-card image shared by the menu page, the category/tag/shop
 * archives, home favourites and the editorial featured grid.
 *
 *   - rendered through wp_get_attachment_image() (core adds srcset/width/height)
 *     with the grid's `sizes` and decoding=async;
 *   - alt = attachment alt, else the product name;
 *   - lead grids: the first row (4, filterable) loads eagerly, only the very
 *     first card gets fetchpriority="high"; everything else is lazy;
 *   - non-lead grids (home favourites) are always lazy;
 *   - no featured image → '' (callers keep their placeholder) but the card
 *     still counts toward the first row.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CardImageHelperTest extends TestCase {

		protected function setUp(): void {
			parent::setUp();
			\lafka_card_image_next_index( true );
			$GLOBALS['lafka_test_attachments'] = array(
				5 => 'margherita-768.jpg',
				6 => 'pepperoni-768.jpg',
			);
		}

		private function product( int $image_id, string $name = 'Margherita Pizza' ): \WC_Product {
			return new \WC_Product(
				array(
					'image_id' => $image_id,
					'name'     => $name,
				)
			);
		}

		public function test_alt_comes_from_the_attachment_when_set(): void {
			$GLOBALS['lafka_test_post_meta'][5]['_wp_attachment_image_alt'] = 'Wood-fired margherita with basil';

			$html = \lafka_card_image_html( $this->product( 5 ) );

			$this->assertStringContainsString( 'alt="Wood-fired margherita with basil"', $html );
		}

		public function test_alt_falls_back_to_the_product_name(): void {
			$html = \lafka_card_image_html( $this->product( 5 ) );

			$this->assertStringContainsString( 'src="margherita-768.jpg"', $html );
			$this->assertStringContainsString( 'alt="Margherita Pizza"', $html );
			$this->assertStringContainsString( 'class="lafka-favs__img"', $html );
			$this->assertStringContainsString( 'decoding="async"', $html );
		}

		public function test_grid_sizes_are_passed_and_filterable(): void {
			$html = \lafka_card_image_html( $this->product( 5 ) );
			$this->assertStringContainsString( 'sizes="(min-width: 1440px) 340px, (min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 600px) 50vw, 100vw"', $html );

			add_filter( 'lafka_card_image_sizes', static fn() => '50vw' );
			$this->assertStringContainsString( 'sizes="50vw"', \lafka_card_image_html( $this->product( 5 ) ) );
		}

		public function test_non_lead_cards_are_always_lazy(): void {
			for ( $i = 0; $i < 3; $i++ ) {
				$html = \lafka_card_image_html( $this->product( 5 ) );
				$this->assertStringContainsString( 'loading="lazy"', $html );
				$this->assertStringNotContainsString( 'fetchpriority', $html );
			}
		}

		public function test_lead_grid_first_row_is_eager_and_only_the_first_card_is_high_priority(): void {
			$out = array();
			for ( $i = 0; $i < 6; $i++ ) {
				$out[] = \lafka_card_image_html( $this->product( 5 ), array( 'lead_grid' => true ) );
			}

			$this->assertStringContainsString( 'loading="eager"', $out[0] );
			$this->assertStringContainsString( 'fetchpriority="high"', $out[0] );
			foreach ( array( 1, 2, 3 ) as $i ) {
				$this->assertStringContainsString( 'loading="eager"', $out[ $i ], "card {$i} is in the first row" );
				$this->assertStringContainsString( 'fetchpriority="auto"', $out[ $i ] );
			}
			foreach ( array( 4, 5 ) as $i ) {
				$this->assertStringContainsString( 'loading="lazy"', $out[ $i ], "card {$i} is below the first row" );
				$this->assertStringNotContainsString( 'fetchpriority', $out[ $i ] );
			}
			$this->assertSame( 1, substr_count( implode( '', $out ), 'fetchpriority="high"' ) );
		}

		public function test_eager_count_is_filterable(): void {
			add_filter( 'lafka_product_card_eager_count', static fn() => 1 );

			$first  = \lafka_card_image_html( $this->product( 5 ), array( 'lead_grid' => true ) );
			$second = \lafka_card_image_html( $this->product( 6 ), array( 'lead_grid' => true ) );

			$this->assertStringContainsString( 'loading="eager"', $first );
			$this->assertStringContainsString( 'loading="lazy"', $second );
		}

		public function test_card_without_image_renders_nothing_but_still_takes_a_first_row_slot(): void {
			$this->assertSame( '', \lafka_card_image_html( $this->product( 0 ), array( 'lead_grid' => true ) ) );

			$second = \lafka_card_image_html( $this->product( 5 ), array( 'lead_grid' => true ) );
			$this->assertStringContainsString( 'loading="eager"', $second );
			$this->assertStringNotContainsString( 'fetchpriority="high"', $second, 'the imageless first card kept the LCP slot' );
		}

		public function test_empty_class_is_omitted_and_non_products_render_nothing(): void {
			$this->assertStringNotContainsString( 'class=', \lafka_card_image_html( $this->product( 5 ), array( 'class' => '' ) ) );
			$this->assertSame( '', \lafka_card_image_html( null ) );
		}

		public function test_loop_card_helper_prefers_the_attachment_alt(): void {
			$GLOBALS['lafka_test_post_meta'][5]['_wp_attachment_image_alt'] = 'Close-up of a margherita';

			$this->assertStringContainsString( 'alt="Close-up of a margherita"', \lafka_product_card_image_html( $this->product( 5 ) ) );
		}
	}
}
