<?php
/**
 * PHPUnit bootstrap for the Lafka theme test harness.
 *
 * Pure unit tests only — no WordPress runtime. Common WordPress functions come
 * from tests/support/wp-shims.php (store-backed, reset before every test by
 * ResetWpShimsExtension); anything test-specific is shimmed in the test file
 * with a function_exists() guard.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/support/wp-shims.php';
require_once __DIR__ . '/support/ResetWpShimsExtension.php';
