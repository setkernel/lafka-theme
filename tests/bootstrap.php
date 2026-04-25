<?php
/**
 * PHPUnit bootstrap for the Lafka theme test harness.
 *
 * Pure unit tests only — no WordPress runtime. Theme functions reached from
 * tests must be mocked or invoked via shims declared in the test file itself.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
