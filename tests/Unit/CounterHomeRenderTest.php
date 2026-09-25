<?php
declare(strict_types=1);

/**
 * GX4 C5: the counter homepage (partials/counter/home.php and its parts),
 * rendered against a neutral example catalogue in the shim store.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/phone-display.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/open-status.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/hours-display.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/deal-value.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/counter-chrome.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/service-eta.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/social-proof.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterHomeRenderTest extends TestCase {

		/** Build a neutral example store: 4 categories in WC order. */
		public static function seed( bool $with_deals = true ): void {
			$GLOBALS['lafka_test_parts_live'] = true;
			$cats                             = array(
				'combos' => 'Combos',
				'fries'  => 'Fries',
				'pizza'  => 'Pizza',
				'wings'  => 'Wings',
				'drinks' => 'Drinks',
			);
			if ( ! $with_deals ) {
				unset( $cats['combos'] );
			}
			$order = 0;
			foreach ( $cats as $slug => $name ) {
				$GLOBALS['lafka_test_terms']['product_cat'][] = new \WP_Term(
					array(
						'term_id'     => 10 + $order,
						'slug'        => $slug,
						'name'        => $name,
						'order'       => $order,
						'count'       => 2,
						'description' => 'fries' === $slug ? 'Hand-cut fries with gravy. Made fresh.' : '',
					)
				);
				++$order;
			}
			$id = 100;
			foreach ( array_keys( $cats ) as $slug ) {
				foreach ( array( 1, 2 ) as $n ) {
					++$id;
					$product = new \WC_Product(
						array(
							'id'        => $id,
							'name'      => ucfirst( $slug ) . " item {$n}",
							'price'     => (string) ( 9 + $n ),
							'image_id'  => $id,
							'cats'      => array( $slug ),
							'permalink' => "http://example.test/product/{$slug}-{$n}/",
						)
					);
					$GLOBALS['lafka_test_catalog'][]           = $product;
					$GLOBALS['lafka_test_products'][ $id ]     = $product;
					$GLOBALS['lafka_test_attachments'][ $id ]  = "http://example.test/{$slug}-{$n}.png";
				}
			}
			$GLOBALS['lafka_test_restaurant_info'] = array(
				'name'            => 'Example Kitchen',
				'phone_e164'      => '+15550100',
				'phone_display'   => '(555) 0100',
				'address_display' => "1 Example St\nExampletown",
				'address_short'   => '1 Example St, Exampletown',
				'map_url'         => 'https://maps.example.test/?q=1',
				'hours'           => array(
					'Sunday'    => '11:00-23:00',
					'Monday'    => '11:00-23:00',
					'Tuesday'   => '11:00-23:00',
					'Wednesday' => '11:00-23:00',
					'Thursday'  => '11:00-23:00',
					'Friday'    => '11:00-00:00',
					'Saturday'  => '11:00-00:00',
				),
			);
		}

		public static function render(): string {
			ob_start();
			\get_template_part( 'partials/counter/home' );
			return (string) ob_get_clean();
		}

		public function test_section_order(): void {
			self::seed();
			$html = self::render();
			$pos  = array();
			foreach ( array( 'lafka-counter-hero', 'id="deals"', 'lafka-counter-costars', 'lafka-counter-rest', 'id="find-us"' ) as $needle ) {
				$pos[ $needle ] = strpos( $html, $needle );
				$this->assertNotFalse( $pos[ $needle ], "{$needle} missing" );
			}
			$sorted = $pos;
			asort( $sorted );
			$this->assertSame( array_keys( $pos ), array_keys( $sorted ), 'hero -> deals -> co-stars -> menu -> find-us' );
			$this->assertStringContainsString( '<div id="main" class="lafka-front-page lafka-counter-home">', $html );
		}

		public function test_heading_outline(): void {
			self::seed();
			$html = self::render();
			$this->assertSame( 1, substr_count( $html, '<h1' ), 'exactly one h1' );
			$this->assertStringContainsString( '>Fries and pizza</h1>', $html, 'neutral default headline from the co-star names' );
			// deals + 2 co-stars + "more" + 2 rest categories + find us.
			$this->assertSame( 7, substr_count( $html, '<h2' ) );
			// 10 products as rows / deals, + 3 find-us labels.
			$this->assertSame( 13, substr_count( $html, '<h3' ) );
		}

		public function test_no_deals_term_hides_the_section(): void {
			self::seed( false );
			$html = self::render();
			$this->assertStringNotContainsString( 'id="deals"', $html );
			$this->assertStringContainsString( '>Fries and pizza</h1>', $html );
		}

		public function test_rest_excludes_deals_and_costars_in_wc_order(): void {
			self::seed();
			$html = self::render();
			$rest = substr( $html, (int) strpos( $html, 'lafka-counter-rest' ) );
			$rest = substr( $rest, 0, (int) strpos( $rest, 'id="find-us"' ) );
			preg_match_all( '#<h2 id="lafka-cat-([a-z]+)-h"#', $rest, $m );
			$this->assertSame( array( 'wings', 'drinks' ), $m[1] );
			preg_match_all( '#<li><a href="\#lafka-cat-([a-z]+)">#', $rest, $jump );
			$this->assertSame( array( 'wings', 'drinks' ), $jump[1], 'jump index follows the sections' );
		}

		public function test_see_all_only_when_truncated(): void {
			self::seed();
			$this->assertStringNotContainsString( '>See all 2<span class="screen-reader-text"> Wings', self::render() );

			$GLOBALS['lafka_test_theme_mods']['lafka_counter_menu_limit'] = 1;
			$this->assertStringContainsString( '>See all 2<span class="screen-reader-text"> Wings', self::render() );
		}

		public function test_costars_always_link_and_show_the_tagline(): void {
			self::seed();
			$html = self::render();
			$this->assertStringContainsString( 'Hand-cut fries with gravy.', $html );
			$this->assertStringContainsString( '>See all 2<span class="screen-reader-text"> Fries', $html );
		}

		public function test_find_us_groups_hours_and_uses_the_resolver(): void {
			self::seed();
			$GLOBALS['lafka_test_options']['start_of_week'] = 0;
			$html = self::render();
			$this->assertStringContainsString( 'Hours, every day', $html );
			$this->assertStringContainsString( '<dt>Sun–Thu</dt>', $html );
			$this->assertStringContainsString( '<dd>11 am–midnight</dd>', $html );
			$this->assertStringContainsString( '1 Example St<br>Exampletown', $html );
			$this->assertStringContainsString( 'href="https://maps.example.test/?q=1"', $html );
			$this->assertStringContainsString( 'Call for pickup or delivery', $html );
			$this->assertStringContainsString( '1 Example St, Exampletown · Open till midnight Fri &amp; Sat', htmlspecialchars( html_entity_decode( $html ), ENT_NOQUOTES ) );

			$GLOBALS['lafka_test_theme_mods']['lafka_counter_show_find_us'] = false;
			$this->assertStringNotContainsString( 'id="find-us"', self::render() );
		}

		public function test_hero_dishes_front_is_the_lcp_image(): void {
			self::seed();
			$html = self::render();
			$this->assertMatchesRegularExpression( '#lafka-counter-hero__dish--front">\s*<img src="http://example.test/fries-1.png"[^>]*loading="eager"[^>]*fetchpriority="high"#', $html );
			$this->assertStringContainsString( 'http://example.test/pizza-1.png', $html );
		}

		public function test_unpublished_hero_pick_falls_back_to_automatic(): void {
			self::seed();
			$GLOBALS['lafka_test_products'][105]->data['status']            = 'draft';
			$GLOBALS['lafka_test_theme_mods']['lafka_counter_hero_product_a'] = 105;
			$html = self::render();
			$this->assertStringNotContainsString( 'http://example.test/pizza-1.png" loading="eager" decoding="async" fetchpriority="high"', $html );
			$this->assertMatchesRegularExpression( '#lafka-counter-hero__dish--front">\s*<img src="http://example.test/fries-1.png"#', $html );
		}

		public function test_operator_copy_wins(): void {
			self::seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_home_hero_headline'] = 'Our own headline';
			$GLOBALS['lafka_test_theme_mods']['lafka_counter_deals_lead'] = 'Operator line.';
			$html = self::render();
			$this->assertStringContainsString( '>Our own headline</h1>', $html );
			$this->assertStringContainsString( 'Operator line.', $html );
		}
	}
}
