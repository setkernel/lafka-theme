<?php
declare(strict_types=1);

/**
 * GX4 A7: homepage sections — deals / co-stars / every other category — from
 * WooCommerce category order, with no slug hardcoded.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterSectionsResolverTest extends TestCase {

		/** @return list<\WP_Term> in WC order */
		private static function terms(): array {
			$out = array();
			foreach ( array( 'combos', 'fries', 'pizza', 'wings', 'desserts' ) as $i => $slug ) {
				$out[] = new \WP_Term(
					array(
						'term_id' => 10 + $i,
						'slug'    => $slug,
						'name'    => ucfirst( $slug ),
						'order'   => $i,
						'count'   => 3,
					)
				);
			}
			return $out;
		}

		private static function slugs( array $terms ): array {
			return array_map( static fn( $t ) => $t->slug, $terms );
		}

		public function test_defaults_follow_wc_order_and_known_deal_slugs(): void {
			$r = \lafka_counter_resolve_sections( self::terms(), array() );
			$this->assertSame( 'combos', $r['deals']->slug );
			$this->assertSame( array( 'fries', 'pizza' ), self::slugs( $r['costars'] ) );
			$this->assertSame( array( 'wings', 'desserts' ), self::slugs( $r['rest'] ) );
		}

		public function test_settings_pick_deals_and_costars(): void {
			$r = \lafka_counter_resolve_sections(
				self::terms(),
				array(
					'deals_cat' => 14,
					'costar_a'  => 12,
					'costar_b'  => 11,
				)
			);
			$this->assertSame( 'desserts', $r['deals']->slug );
			$this->assertSame( array( 'pizza', 'fries' ), self::slugs( $r['costars'] ), 'explicit picks keep the chosen order' );
			$this->assertSame( array( 'combos', 'wings' ), self::slugs( $r['rest'] ) );
		}

		public function test_a_single_pick_keeps_its_slot(): void {
			$terms = self::terms();
			$only_b = \lafka_counter_resolve_sections( $terms, array( 'costar_b' => 11 ) );
			$this->assertSame( 'fries', self::slugs( $only_b['costars'] )[1], 'costar B stays second' );
			$only_a = \lafka_counter_resolve_sections( $terms, array( 'costar_a' => 12 ) );
			$this->assertSame( 'pizza', self::slugs( $only_a['costars'] )[0], 'costar A leads the headline' );
		}

		public function test_no_deals_term_and_no_duplicates(): void {
			$terms = array_slice( self::terms(), 1 );
			$r     = \lafka_counter_resolve_sections( $terms, array( 'costar_a' => 11, 'costar_b' => 11 ) );
			$this->assertNull( $r['deals'] );
			$all = array_merge( self::slugs( $r['costars'] ), self::slugs( $r['rest'] ) );
			$this->assertSame( $all, array_values( array_unique( $all ) ), 'no term appears twice' );
			$this->assertCount( 4, $all );
		}

		public function test_deal_slugs_are_filterable(): void {
			\add_filter(
				'lafka_counter_deals_slugs',
				static function () {
					return array( 'wings' );
				}
			);
			$this->assertSame( 'wings', \lafka_counter_resolve_sections( self::terms(), array() )['deals']->slug );
		}

		public function test_top_categories_use_wc_order_and_skip_children(): void {
			$terms   = self::terms();
			$terms[] = new \WP_Term(
				array(
					'term_id' => 30,
					'slug'    => 'kids',
					'name'    => 'Kids',
					'parent'  => 11,
					'count'   => 2,
				)
			);
			$terms[] = new \WP_Term(
				array(
					'term_id' => 31,
					'slug'    => 'empty',
					'name'    => 'Empty',
					'count'   => 0,
				)
			);
			$GLOBALS['lafka_test_terms']['product_cat'] = array_reverse( $terms );
			$this->assertSame( array( 'combos', 'fries', 'pizza', 'wings', 'desserts' ), self::slugs( \lafka_menu_top_categories() ) );
		}

		public function test_section_products_put_featured_first_and_count_the_total(): void {
			foreach ( range( 1, 5 ) as $i ) {
				$GLOBALS['lafka_test_catalog'][] = new \WC_Product(
					array(
						'id'       => 100 + $i,
						'featured' => 4 === $i,
						'cats'     => array( 'pizza' ),
					)
				);
			}
			$rows = \lafka_counter_section_products( self::terms()[2], 3 );
			$this->assertSame( array( 104, 101, 102 ), $rows['ids'] );
			$this->assertSame( 5, $rows['total'] );
		}

		public function test_tagline_meta_then_first_sentence(): void {
			$term = new \WP_Term(
				array(
					'term_id'     => 50,
					'description' => '<p>Hand-cut fries, cheese curds and gravy. Made to order every day.</p>',
				)
			);
			$this->assertSame( 'Hand-cut fries, cheese curds and gravy.', \lafka_category_tagline( $term ) );

			$GLOBALS['lafka_test_term_meta'][50]['lafka_tagline'] = 'Crispy, saucy, done right';
			$this->assertSame( 'Crispy, saucy, done right', \lafka_category_tagline( $term ) );

			$long = new \WP_Term(
				array(
					'term_id'     => 51,
					'description' => str_repeat( 'word ', 40 ),
				)
			);
			$line = \lafka_category_tagline( $long );
			$this->assertLessThanOrEqual( 111, mb_strlen( $line ) );
			$this->assertStringEndsWith( 'word…', $line, 'never cut mid-word' );

			$this->assertSame( '', \lafka_category_tagline( new \WP_Term( array( 'term_id' => 52 ) ) ) );
		}

		public function test_sections_are_cached_and_the_cache_busts(): void {
			$GLOBALS['lafka_test_terms']['product_cat'] = self::terms();
			$GLOBALS['lafka_test_catalog'][]            = new \WC_Product(
				array(
					'id'   => 201,
					'cats' => array( 'pizza' ),
				)
			);
			$first = \lafka_counter_sections();
			$this->assertSame( 'pizza', $first['costars'][0]['term']->slug, 'empty categories are dropped' );
			$this->assertSame( 1, $GLOBALS['lafka_test_counters']['transient_set'] );

			\lafka_counter_sections();
			$this->assertSame( 1, $GLOBALS['lafka_test_counters']['transient_set'], 'second call served from the transient' );

			\do_action( 'edited_product_cat', 12 );
			\lafka_counter_sections();
			$this->assertSame( 2, $GLOBALS['lafka_test_counters']['transient_set'], 'a category edit busts the cache' );

			\do_action( 'updated_term_meta', 1, 12, 'order', 3 );
			\lafka_counter_sections();
			$this->assertSame( 3, $GLOBALS['lafka_test_counters']['transient_set'], 'a drag-reorder busts the cache' );
		}
	}
}
