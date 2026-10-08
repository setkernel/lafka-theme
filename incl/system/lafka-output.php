<?php
/**
 * Output and hook helpers shared by every template.
 *
 * - lafka_allowed_html() is the allowlist handed to wp_kses() wherever a
 *   template prints markup that a helper or a third-party filter built (icons,
 *   worded buttons, cart rows, images). Escaping happens at the point of output.
 * - lafka_core_action() / lafka_core_filter() fire hooks that WordPress or
 *   WooCommerce own, from the templates that override theirs. The theme does not
 *   own those names, so they are passed through rather than prefixed.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

/**
 * The HTML the theme's templates are allowed to print: everything WordPress
 * allows in a post, plus inline SVG, form controls, responsive images and the
 * state attributes the theme's own scripts read.
 *
 * Filter `lafka_allowed_html` adds tags or attributes.
 *
 * @return array<string, array<string, bool>> Tag => attributes, as wp_kses() expects.
 */
function lafka_allowed_html(): array {
	static $allowed = null;
	if ( null !== $allowed ) {
		return $allowed;
	}

	$allowed = wp_kses_allowed_html( 'post' );

	$svg_paint = array(
		'fill'              => true,
		'fill-rule'         => true,
		'clip-rule'         => true,
		'stroke'            => true,
		'stroke-width'      => true,
		'stroke-linecap'    => true,
		'stroke-linejoin'   => true,
		'stroke-miterlimit' => true,
		'opacity'           => true,
		'transform'         => true,
	);

	$extra = array(
		'svg'      => array_merge(
			$svg_paint,
			array(
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'focusable'   => true,
				'aria-hidden' => true,
			)
		),
		'g'        => $svg_paint,
		'path'     => array_merge( $svg_paint, array( 'd' => true ) ),
		'circle'   => array_merge(
			$svg_paint,
			array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			)
		),
		'ellipse'  => array_merge(
			$svg_paint,
			array(
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			)
		),
		'rect'     => array_merge(
			$svg_paint,
			array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			)
		),
		'line'     => array_merge(
			$svg_paint,
			array(
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			)
		),
		'polyline' => array_merge( $svg_paint, array( 'points' => true ) ),
		'polygon'  => array_merge( $svg_paint, array( 'points' => true ) ),
		'title'    => array(),
		'input'    => array(
			'type'         => true,
			'name'         => true,
			'value'        => true,
			'min'          => true,
			'max'          => true,
			'step'         => true,
			'size'         => true,
			'maxlength'    => true,
			'placeholder'  => true,
			'inputmode'    => true,
			'pattern'      => true,
			'autocomplete' => true,
			'checked'      => true,
			'disabled'     => true,
			'readonly'     => true,
			'required'     => true,
			'form'         => true,
			'for'          => true,
		),
		'select'   => array(
			'name'         => true,
			'multiple'     => true,
			'disabled'     => true,
			'required'     => true,
			'autocomplete' => true,
		),
		'option'   => array(
			'value'    => true,
			'selected' => true,
			'disabled' => true,
		),
		'optgroup' => array(
			'label'    => true,
			'disabled' => true,
		),
		'textarea' => array(
			'name'        => true,
			'rows'        => true,
			'cols'        => true,
			'placeholder' => true,
			'disabled'    => true,
			'readonly'    => true,
			'required'    => true,
		),
		'button'   => array(
			'type'     => true,
			'name'     => true,
			'value'    => true,
			'disabled' => true,
		),
		'label'    => array( 'for' => true ),
		'form'     => array(
			'action'         => true,
			'method'         => true,
			'enctype'        => true,
			'novalidate'     => true,
			'accept-charset' => true,
		),
		'img'      => array(
			'src'           => true,
			'srcset'        => true,
			'sizes'         => true,
			'alt'           => true,
			'width'         => true,
			'height'        => true,
			'loading'       => true,
			'decoding'      => true,
			'fetchpriority' => true,
		),
		'picture'  => array(),
		'source'   => array(
			'srcset' => true,
			'sizes'  => true,
			'type'   => true,
			'media'  => true,
		),
		'a'        => array(
			'href'     => true,
			'rel'      => true,
			'target'   => true,
			'download' => true,
			'hreflang' => true,
		),
		'span'     => array(),
		'div'      => array(),
		'p'        => array(),
		'time'     => array( 'datetime' => true ),
	);

	// Attributes any tag may carry: WordPress's core global attributes (also for
	// tags the post allowlist lacks, such as input), the ARIA states and the data
	// hooks the theme's scripts toggle.
	$global = array(
		'class'                => true,
		'id'                   => true,
		'style'                => true,
		'title'                => true,
		'role'                 => true,
		'aria-label'           => true,
		'aria-labelledby'      => true,
		'aria-describedby'     => true,
		'aria-hidden'          => true,
		'aria-expanded'        => true,
		'aria-controls'        => true,
		'aria-current'         => true,
		'aria-live'            => true,
		'aria-atomic'          => true,
		'aria-haspopup'        => true,
		'aria-pressed'         => true,
		'aria-selected'        => true,
		'aria-modal'           => true,
		'aria-disabled'        => true,
		'aria-checked'         => true,
		'aria-invalid'         => true,
		'aria-required'        => true,
		'aria-busy'            => true,
		'aria-valuemin'        => true,
		'aria-valuemax'        => true,
		'aria-valuenow'        => true,
		'aria-valuetext'       => true,
		'aria-owns'            => true,
		'aria-relevant'        => true,
		'aria-roledescription' => true,
		'hidden'               => true,
		'tabindex'             => true,
		'lang'                 => true,
		'dir'                  => true,
		'itemprop'             => true,
		'itemscope'            => true,
		'itemtype'             => true,
		'itemid'               => true,
		'data-*'               => true,
	);

	foreach ( $extra as $tag => $attributes ) {
		$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) && is_array( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $attributes );
	}
	foreach ( $allowed as $tag => $attributes ) {
		$allowed[ $tag ] = array_merge( is_array( $attributes ) ? $attributes : array(), $global );
	}

	/**
	 * Filters the HTML the theme's templates may print.
	 *
	 * @param array<string, array<string, bool>> $allowed Tag => attributes.
	 */
	$allowed = (array) apply_filters( 'lafka_allowed_html', $allowed );

	return $allowed;
}

/**
 * Fire a hook that WordPress or WooCommerce own.
 *
 * @param string $hook_name Hook name.
 * @param mixed  ...$args   Hook arguments.
 */
function lafka_core_action( string $hook_name, ...$args ): void {
	call_user_func_array( 'do_action', array_merge( array( $hook_name ), $args ) );
}

/**
 * Apply a filter that WordPress or WooCommerce own.
 *
 * @param string $hook_name Filter name.
 * @param mixed  $value     Value to filter.
 * @param mixed  ...$args   Extra filter arguments.
 * @return mixed The filtered value.
 */
function lafka_core_filter( string $hook_name, $value, ...$args ) {
	return call_user_func_array( 'apply_filters', array_merge( array( $hook_name, $value ), $args ) );
}

/**
 * Read one public, read-only query-string parameter, sanitised.
 *
 * For links and filter state that change nothing on the server (shop price
 * filter, per-page, search); anything that changes state must verify a nonce.
 *
 * @param string $name Query-string key.
 * @return string|null The sanitised value, or null when the key is absent or not a scalar.
 */
function lafka_query_arg( string $name ): ?string {
	$value = filter_input( INPUT_GET, $name, FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
	return is_string( $value ) ? $value : null;
}

/**
 * Read a local file through WordPress' direct filesystem driver.
 *
 * @param string $path Absolute path of a file inside the theme or uploads.
 * @return string|false The contents, or false when the file cannot be read.
 */
function lafka_read_file( string $path ) {
	static $filesystem = null;
	if ( null === $filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new WP_Filesystem_Direct( null );
	}
	return $filesystem->get_contents( $path );
}
