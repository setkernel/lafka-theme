<?php
declare(strict_types=1);

/**
 * GX4 hard gate (plan D9): the counter surfaces never invent content. With no
 * social proof, no service ETA, no "serves" values and no deals line set, the
 * rendered homepage says nothing about reviews, ratings, minutes, per-person
 * prices or being cheaper than the delivery apps — and each line appears once
 * the operator (or product data) supplies it.
 *
 * @package Lafka\Tests
 */

// The store seeding + renderer are CounterHomeRenderTest's (every test file is
// loaded before any test runs, so the class and the helpers it requires exist).

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class NoFabricatedCounterContentTest extends TestCase {

		private const BANNED = array( 'review', 'rating', 'stars', 'minutes', ' each', 'pay less', 'delivery apps', 'Google' );

		public function test_nothing_is_invented_without_data(): void {
			CounterHomeRenderTest::seed();
			$html = strtolower( wp_strip_all_tags( CounterHomeRenderTest::render() ) );
			foreach ( self::BANNED as $phrase ) {
				$this->assertStringNotContainsString( strtolower( $phrase ), $html, "Invented copy on the counter home: '{$phrase}'" );
			}
		}

		public function test_each_line_appears_only_from_real_data(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_social_proof_rating']   = '4.6';
			$GLOBALS['lafka_test_theme_mods']['lafka_social_proof_count']    = 88;
			$GLOBALS['lafka_test_theme_mods']['lafka_social_proof_provider'] = 'ExampleMaps';
			$GLOBALS['lafka_test_theme_mods']['lafka_service_eta_pickup']    = 'about 20 minutes';
			\add_filter(
				'lafka_product_serves',
				static function ( $n, $p ) {
					return 101 === $p->get_id() ? 2 : $n;
				},
				10,
				2
			);

			$html = CounterHomeRenderTest::render();
			$this->assertStringContainsString( '4.6 on ExampleMaps · 88 reviews', $html );
			$this->assertStringContainsString( 'Ready in about 20 minutes', $html );
			$this->assertStringContainsString( 'For 2: about $5 each', $html );
		}
	}
}
