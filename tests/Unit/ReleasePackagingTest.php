<?php
/**
 * Ratchet: dev-only files must never ship in the installable release zip.
 *
 * release.yml builds the distributable via `rsync -a --exclude=... ./ lafka/`,
 * after `npm run build` regenerates the .min siblings. A future dev-file class
 * (a new tooling dir, a new config, a design doc) could silently start shipping
 * to end users if that exclude list isn't kept in step. This test parses the
 * workflow's `--exclude=` patterns and asserts the known dev-file classes are
 * excluded — and, symmetrically, that runtime assets (readme.txt, languages/,
 * theme.json, the store/demo importer, the built .min files) are NOT excluded.
 * Scans the workflow as text; runs no rsync, needs no node_modules.
 *
 * The build-runs-before-zip ordering is separately guarded by
 * AssetBuildPipelineTest::test_release_workflow_builds_before_zipping.
 *
 * NX1-10d.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleasePackagingTest extends TestCase {

	private const ROOT = __DIR__ . '/../..';

	/**
	 * The rsync `--exclude='...'` patterns from the release workflow.
	 *
	 * @return list<string>
	 */
	private function release_excludes(): array {
		$yml = (string) file_get_contents( self::ROOT . '/.github/workflows/release.yml' );
		self::assertNotSame( '', $yml, 'release.yml is missing or empty' );
		self::assertStringContainsString( 'rsync -a ', $yml, 'release.yml has no rsync build step' );
		self::assertStringContainsString(
			'zip -r lafka.zip lafka/',
			$yml,
			'release.yml no longer zips the rsync destination'
		);

		$count = preg_match_all( "/--exclude='([^']*)'/", $yml, $m );
		self::assertGreaterThan( 0, $count, 'no rsync --exclude entries found in release.yml' );

		return $m[1];
	}

	public function test_dev_only_files_excluded_from_zip(): void {
		$excludes = $this->release_excludes();

		// `readme.md` is the GitHub project readme; it needs the lowercase
		// exclude because `README.md` does not match it on the case-sensitive
		// Linux runner.
		$dev = array(
			'.git',
			'.github',
			'.gitignore',
			'.githooks',
			'.npmrc',
			'.wp-env*.json',
			'node_modules',
			'vendor',
			'package.json',
			'package-lock.json',
			'composer.json',
			'composer.lock',
			'.phpcs.xml.dist',
			'.stylelintrc.json',
			'eslint.config.mjs',
			'phpunit.xml.dist',
			'.phpunit.result.cache',
			'playwright.config.js',
			'tests',
			'scripts',
			'CONTRIBUTING.md',
			'DESIGN_SYSTEM.md',
			'README.md',
			'readme.md',
		);

		foreach ( $dev as $needle ) {
			self::assertContains(
				$needle,
				$excludes,
				"release.yml must exclude dev-only '{$needle}' from the release zip"
			);
		}
	}

	public function test_runtime_files_not_excluded_from_zip(): void {
		$excludes = $this->release_excludes();

		// `store` must stay: incl/LafkaTransferContent.class.php loads
		// store/demo/ at runtime for the one-click demo importer.
		$runtime = array(
			'readme.txt',
			'languages',
			'assets',
			'incl',
			'styles',
			'js',
			'store',
			'woocommerce',
			'theme.json',
			'partials',
			'functions.php',
		);

		foreach ( $runtime as $needle ) {
			self::assertNotContains(
				$needle,
				$excludes,
				"release.yml must NOT exclude runtime path '{$needle}' from the release zip"
			);
		}
	}

	public function test_built_minified_assets_are_shipped(): void {
		// The release job runs `npm run build` before rsync; the generated
		// .min.css / .min.js must be packaged, so no exclude may match them.
		foreach ( $this->release_excludes() as $pattern ) {
			self::assertStringNotContainsString(
				'.min.',
				$pattern,
				"release.yml exclude '{$pattern}' would drop the built minified assets"
			);
		}
	}
}
