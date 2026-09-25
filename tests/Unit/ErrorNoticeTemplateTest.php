<?php
declare(strict_types=1);

/**
 * T-24: woocommerce/notices/error.php — role="alert" sits on a wrapping
 * <div>, the <ul class="woocommerce-error"> keeps its list semantics, and
 * each <li> keeps WooCommerce's data-id (checkout.js inline errors read it).
 */

namespace {
	if ( ! function_exists( 'wc_get_notice_data_attr' ) ) {
		function wc_get_notice_data_attr( $notice ) {
			if ( empty( $notice['data'] ) ) {
				return '';
			}
			$attr = '';
			foreach ( $notice['data'] as $key => $value ) {
				$attr .= sprintf( ' data-%1$s="%2$s"', sanitize_title( $key ), esc_attr( $value ) );
			}
			return $attr;
		}
	}
	if ( ! function_exists( 'wc_kses_notice' ) ) {
		function wc_kses_notice( $message ) {
			return wp_kses_post( $message );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ErrorNoticeTemplateTest extends TestCase {

		private static function render( array $notices ): string {
			ob_start();
			include dirname( __DIR__, 2 ) . '/woocommerce/notices/error.php';
			return (string) ob_get_clean();
		}

		public function test_the_alert_role_wraps_a_plain_list(): void {
			$html = self::render(
				array(
					array(
						'notice' => '<strong>Billing Phone</strong> is a required field.',
						'data'   => array( 'id' => 'billing_phone' ),
					),
					array(
						'notice' => 'Please read and accept the terms.',
						'data'   => array(),
					),
				)
			);

			$this->assertMatchesRegularExpression( '/^\s*<div class="lafka-notice-alert" role="alert">\s*<ul class="woocommerce-error">/', $html );
			$this->assertStringNotContainsString( '<ul class="woocommerce-error" role=', $html );
			$this->assertSame( 2, substr_count( $html, '<li' ) );
			$this->assertStringContainsString( '<li data-id="billing_phone">', $html );
			$this->assertStringContainsString( '<strong>Billing Phone</strong> is a required field.', $html );
		}

		public function test_no_notices_prints_nothing(): void {
			$this->assertSame( '', trim( self::render( array() ) ) );
		}
	}
}
