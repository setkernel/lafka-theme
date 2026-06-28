<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * f034 regression lock: the PDP variation radiogroup must have an accessible
 * name. The <legend> needs a unique id (derived from the per-attribute loop
 * variable) and the role="radiogroup" <div> must reference it via
 * aria-labelledby so screen readers announce "Size" / "Crust" before the
 * options (WCAG 1.3.1 / 4.1.2).
 */
final class PdpPickerRadiogroupLabelTest extends TestCase {

	private function partial_source(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/partials/pdp-pickers.php' );
	}

	public function test_legend_has_unique_id_from_attribute_name(): void {
		$src = $this->partial_source();
		$this->assertMatchesRegularExpression(
			'/<legend\s+id="lafka-pdp-pick-<\?php\s+echo\s+esc_attr\(\s*\$attr_name\s*\);\s*\?>"/',
			$src,
			'<legend> must carry a unique id built from esc_attr( $attr_name ).'
		);
	}

	public function test_radiogroup_is_labelled_by_the_legend(): void {
		$src = $this->partial_source();
		$this->assertMatchesRegularExpression(
			'/role="radiogroup"\s+aria-labelledby="lafka-pdp-pick-<\?php\s+echo\s+esc_attr\(\s*\$attr_name\s*\);\s*\?>"/',
			$src,
			'role="radiogroup" must reference the legend id via aria-labelledby.'
		);
	}

	public function test_radiogroup_still_marked_required(): void {
		$src = $this->partial_source();
		$this->assertStringContainsString(
			'aria-required="true"',
			$src,
			'radiogroup must remain aria-required for the gating size/crust choice.'
		);
	}

	public function test_radiogroup_has_no_unnamed_group(): void {
		$src = $this->partial_source();
		// The old markup declared role="radiogroup" immediately followed by
		// aria-required with no accessible name in between. Lock that out.
		$this->assertDoesNotMatchRegularExpression(
			'/role="radiogroup"\s+aria-required=/',
			$src,
			'role="radiogroup" must not appear without an accessible name.'
		);
	}
}
