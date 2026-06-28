<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * f053 regression lock: the header cart-count badge and the cart-drawer title
 * count pill must update after an AJAX add-to-cart.
 *
 * Background: lafka_header_add_to_cart_fragment() used to key the WC
 * add-to-cart fragment on `li.lafka-cart-link-item` and render lafka_cart_link()
 * — legacy markup the handoff header (header.php) and the cart drawer
 * (partials/cart-drawer.php) no longer emit. With no matching node in the DOM,
 * WC's fragment replacement was a silent no-op, so the header bag numeral and
 * the drawer-title pill stayed at their server-rendered value until a full page
 * reload. The fix keys the fragments on the nodes those templates actually
 * render:
 *   - span.lafka-header__cart-count        (header badge, header.php)
 *   - a.lafka-header__cart                  (header anchor, for the SR aria-label)
 *   - span.lafka-cart-drawer__count-badge   (drawer pill, cart-drawer.php)
 *
 * Each fragment's value root element matches its selector key, so WC's
 * replaceWith swaps the node cleanly — no <li><li> nesting like the
 * pre-P6-A11Y-7 `a.cart-contents` regression. The assertions below fail if a
 * fragment selector drifts OR a template stops rendering the targeted node, so
 * the fragment can never silently orphan again.
 */
final class CartFragmentNestingTest extends TestCase {

	private string $woocommerce_functions;

	protected function setUp(): void {
		parent::setUp();
		$this->woocommerce_functions = file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/woocommerce-functions.php'
		);
	}

	/**
	 * Each cart-count fragment must (a) be keyed on the real selector, (b) carry
	 * a value whose root element matches that selector key (so replaceWith can
	 * never nest), and (c) target a node the template actually renders. Drift on
	 * any of the three orphans the fragment.
	 *
	 * @return array<string,array{0:string,1:string,2:string,3:string}>
	 */
	public static function provide_cart_count_fragments(): array {
		return array(
			'header badge'  => array(
				"\$fragments['span.lafka-header__cart-count']",
				'\'<span class="lafka-header__cart-count"',
				'/header.php',
				'class="lafka-header__cart-count"',
			),
			'header anchor' => array(
				"\$fragments['a.lafka-header__cart']",
				'\'<a class="lafka-header__cart"',
				'/header.php',
				'class="lafka-header__cart"',
			),
			'drawer pill'   => array(
				"\$fragments['span.lafka-cart-drawer__count-badge']",
				'\'<span class="lafka-cart-drawer__count-badge"',
				'/partials/cart-drawer.php',
				'class="lafka-cart-drawer__count-badge"',
			),
		);
	}

	#[DataProvider( 'provide_cart_count_fragments' )]
	public function test_fragment_targets_rendered_node(
		string $fragment_key,
		string $value_root,
		string $template_path,
		string $template_class
	): void {
		$this->assertStringContainsString(
			$fragment_key,
			$this->woocommerce_functions,
			"The cart fragment must be keyed on {$fragment_key} so WC can find the node to refresh."
		);
		$this->assertStringContainsString(
			$value_root,
			$this->woocommerce_functions,
			"The fragment value root must match its key ({$value_root}) — a mismatch re-introduces <li><li> nesting on refresh."
		);
		$template = file_get_contents( dirname( __DIR__, 2 ) . $template_path );
		$this->assertStringContainsString(
			$template_class,
			$template,
			"{$template_path} must render {$template_class}, otherwise the fragment selector is orphaned and the count never updates."
		);
	}

	public function test_orphaned_li_fragment_removed(): void {
		// The handoff header no longer outputs <li class="lafka-cart-link-item">,
		// so a fragment keyed on it can never match — it must be gone.
		$this->assertStringNotContainsString(
			"\$fragments['li.lafka-cart-link-item']",
			$this->woocommerce_functions,
			'The orphaned li.lafka-cart-link-item fragment must be removed; nothing renders that node anymore.'
		);
	}

	public function test_fragment_not_keyed_on_anchor_contents(): void {
		// Original P6-A11Y-7 lock: keying on a.cart-contents while emitting the
		// <li> wrapper caused <li><li><a>…</a></li></li> nesting on every refresh.
		$this->assertStringNotContainsString(
			"\$fragments['a.cart-contents']",
			$this->woocommerce_functions,
			'Fragment must NOT be keyed on a.cart-contents (causes <li><li> nesting on AJAX refresh).'
		);
	}
}
