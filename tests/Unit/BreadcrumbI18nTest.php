<?php
/**
 * BreadcrumbI18nTest — regression locks for the v5.15.1 breadcrumb i18n fix
 * in functions.php (lafka_breadcrumb()).
 *
 * Pre-fix four hardcoded English strings in the breadcrumb's archive-context
 * branches were emitted via plain string concatenation:
 *
 *   'Search results for "..."'
 *   'Posts tagged "..."'
 *   'Articles posted by ...'
 *   'Error 404'
 *
 * Non-English stores got English breadcrumbs no matter their locale. Same
 * bug class as the schema breadcrumb i18n fix (lafka-plugin v9.7.4) and the
 * review-prompt email i18n fix (lafka-plugin v9.7.16).
 *
 * @package Lafka\Tests\Unit
 * @since   5.15.1
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BreadcrumbI18nTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_search_results_breadcrumb_translatable(): void {
		$this->assertMatchesRegularExpression(
			"/esc_html__\(\s*'Search results for \"%s\"'\s*,\s*'lafka'\s*\)/",
			$this->src,
			"Breadcrumb 'Search results for' must be translatable via esc_html__."
		);
	}

	public function test_posts_tagged_breadcrumb_translatable(): void {
		$this->assertMatchesRegularExpression(
			"/esc_html__\(\s*'Posts tagged \"%s\"'\s*,\s*'lafka'\s*\)/",
			$this->src,
			"Breadcrumb 'Posts tagged' must be translatable via esc_html__."
		);
	}

	public function test_articles_posted_by_breadcrumb_translatable(): void {
		$this->assertMatchesRegularExpression(
			"/esc_html__\(\s*'Articles posted by %s'\s*,\s*'lafka'\s*\)/",
			$this->src,
			"Breadcrumb 'Articles posted by' must be translatable via esc_html__."
		);
	}

	public function test_error_404_breadcrumb_translatable(): void {
		$this->assertMatchesRegularExpression(
			"/esc_html__\(\s*'Error 404'\s*,\s*'lafka'\s*\)/",
			$this->src,
			"Breadcrumb 'Error 404' must be translatable via esc_html__."
		);
	}

	public function test_no_hardcoded_breadcrumb_strings_remain(): void {
		// The pre-fix patterns must not regress. Each was a plain
		// concatenation onto $brdcrmb without __() wrapping.
		$forbidden_patterns = array(
			"/\\\$brdcrmb\s*\.=\s*\\\$before\s*\.\s*'Search results for/",
			"/\\\$brdcrmb\s*\.=\s*\\\$before\s*\.\s*'Posts tagged/",
			"/\\\$brdcrmb\s*\.=\s*\\\$before\s*\.\s*'Articles posted by/",
			"/\\\$brdcrmb\s*\.=\s*\\\$before\s*\.\s*'Error 404'/",
		);
		foreach ( $forbidden_patterns as $pattern ) {
			$this->assertDoesNotMatchRegularExpression(
				$pattern,
				$this->src,
				"Hardcoded English breadcrumb string must not be reintroduced."
			);
		}
	}

	public function test_translator_comments_present(): void {
		// /* translators: %s: ... */ comments tell translators what each
		// placeholder is. WP coding standards require them for any sprintf
		// of a translatable string with placeholders.
		$this->assertMatchesRegularExpression(
			"/translators:\s*%s:\s*the search term/",
			$this->src,
			"Search-results sprintf must have a /* translators: */ comment."
		);
		$this->assertMatchesRegularExpression(
			"/translators:\s*%s:\s*the tag name/",
			$this->src
		);
		$this->assertMatchesRegularExpression(
			"/translators:\s*%s:\s*the author display name/",
			$this->src
		);
	}
}
