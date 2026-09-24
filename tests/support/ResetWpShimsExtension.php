<?php
/**
 * PHPUnit extension: reset the shared WordPress shim stores before every test.
 *
 * The hook registry is restored to a snapshot taken just before the first test
 * runs — by then PHPUnit has loaded every test file, so the snapshot holds the
 * hooks theme files registered when they were required (for example the preset
 * category-emoji filter) and nothing a test added itself.
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

namespace Lafka\Tests\Support;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class ResetWpShimsExtension implements Extension {

	public function bootstrap( Configuration $configuration, Facade $facade, ParameterCollection $parameters ): void {
		$facade->registerSubscriber(
			new class() implements PreparationStartedSubscriber {
				/** @var array<string, mixed>|null */
				private ?array $baseline = null;

				public function notify( PreparationStarted $event ): void {
					if ( null === $this->baseline ) {
						$this->baseline = $GLOBALS['lafka_test_filters'] ?? array();
					}
					\lafka_test_reset_wp( $this->baseline );
				}
			}
		);
	}
}
