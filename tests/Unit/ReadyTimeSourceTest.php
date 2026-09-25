<?php
declare(strict_types=1);

/**
 * H-09: one "ready in" source — the operator's Service ETA drives the hero
 * AND the PDP trust line (via lafka-plugin's lafka_pdp_prep_time_text filter).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/service-eta.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ReadyTimeSourceTest extends TestCase {

		public function test_service_eta_is_the_hero_figure_and_rewrites_the_pdp_line(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_service_eta_pickup'] = '20–30 min';

			$this->assertSame( '20–30 min', \lafka_ready_time_text() );
			$this->assertSame( 'Ready in 20–30 min', \apply_filters( 'lafka_pdp_prep_time_text', 'Ready in ~25 min', 25, 7 ) );
		}

		public function test_without_a_service_eta_the_pdp_line_is_untouched(): void {
			$this->assertSame( 'Ready in ~25 min', \apply_filters( 'lafka_pdp_prep_time_text', 'Ready in ~25 min', 25, 7 ) );
		}
	}
}
