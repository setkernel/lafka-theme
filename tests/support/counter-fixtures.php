<?php
/**
 * GX4 shared catalogue fixtures (neutral example data, no real site values).
 * Built per test — constructors write the per-test shim stores.
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

if ( ! function_exists( 'lafka_test_use_classic_layouts' ) ) {
	/**
	 * Operator-select the classic layout on every surface (GX4: Peppery — the
	 * default preset — now defaults to the counter layouts, so a test of a
	 * classic surface says so explicitly).
	 */
	function lafka_test_use_classic_layouts(): void {
		foreach ( array( 'header', 'home', 'menu', 'footer', 'drawer' ) as $surface ) {
			$GLOBALS['lafka_test_theme_mods'][ 'lafka_' . $surface . '_layout' ] = 'classic';
		}
		$GLOBALS['lafka_test_theme_mods']['lafka_motif'] = 'none';
	}
}

if ( ! function_exists( 'lafka_test_identity_preset' ) ) {
	/** The engine no-op fixture (presets/__fixtures__/identity, the pre-GX4 Peppery). */
	function lafka_test_identity_preset(): Lafka_Preset {
		$data = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/presets/__fixtures__/identity/preset.json' ), true );
		return new Lafka_Preset( (array) $data );
	}
}

if ( ! function_exists( 'lafka_test_activate_identity_preset' ) ) {
	/**
	 * Register the identity fixture (tests only — it lives under __fixtures__,
	 * so discovery never finds it) and make it the active preset.
	 */
	function lafka_test_activate_identity_preset(): void {
		add_filter(
			'lafka_presets',
			static function ( $presets ) {
				$presets['identity'] = lafka_test_identity_preset();
				return $presets;
			}
		);
		$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'identity';
		if ( class_exists( 'Lafka_Presets' ) ) {
			Lafka_Presets::reset();
		}
	}
}

if ( ! class_exists( 'Lafka_Test_Catalog' ) ) {
	final class Lafka_Test_Catalog {

		/**
		 * Register attribute terms (in attribute order) for a taxonomy.
		 *
		 * @param string               $taxonomy   e.g. pa_size.
		 * @param array<string,string> $slug_names slug => name.
		 * @return list<WP_Term>
		 */
		public static function attr_terms( string $taxonomy, array $slug_names ): array {
			$out = array();
			foreach ( $slug_names as $slug => $name ) {
				$out[] = new WP_Term(
					array(
						'slug'     => (string) $slug,
						'name'     => $name,
						'taxonomy' => $taxonomy,
					)
				);
			}
			$GLOBALS['lafka_test_attr_terms'][ $taxonomy ] = $out;
			return $out;
		}

		/** A pizza: pa_crust x pa_size; gluten-free only in M/L (priced higher). */
		public static function pizza( int $id = 7, string $name = 'Classic Combo' ): WC_Product_Variable {
			self::attr_terms(
				'pa_size',
				array(
					'small'  => 'Small',
					'medium' => 'Medium',
					'large'  => 'Large',
					'xlarge' => 'X-Large',
				)
			);
			self::attr_terms(
				'pa_crust',
				array(
					'regular'     => 'Regular',
					'thin'        => 'Thin',
					'gluten-free' => 'Gluten-free',
				)
			);
			$regular = array(
				'small'  => 13.95,
				'medium' => 19.45,
				'large'  => 25.95,
				'xlarge' => 32.95,
			);
			$rows    = array();
			$vid     = $id * 100;
			foreach ( array( 'regular', 'thin' ) as $crust ) {
				foreach ( $regular as $size => $price ) {
					$rows[ ++$vid ] = array(
						'price'      => $price,
						'attributes' => array(
							'attribute_pa_crust' => $crust,
							'attribute_pa_size'  => $size,
						),
					);
				}
			}
			$gluten_free = array(
				'medium' => 22.45,
				'large'  => 28.95,
			);
			foreach ( $gluten_free as $size => $price ) {
				$rows[ ++$vid ] = array(
					'price'      => $price,
					'attributes' => array(
						'attribute_pa_crust' => 'gluten-free',
						'attribute_pa_size'  => $size,
					),
				);
			}
			return new WC_Product_Variable(
				array(
					'id'                   => $id,
					'name'                 => $name,
					'permalink'            => 'http://example.test/product/' . sanitize_title( $name ) . '/',
					'variation_attributes' => array(
						'pa_crust' => array( 'regular', 'thin', 'gluten-free' ),
						'pa_size'  => array( 'small', 'medium', 'large', 'xlarge' ),
					),
					'variations'           => $rows,
				)
			);
		}

		/** A two-size dish (regular / large). */
		public static function two_size( int $id = 9, string $name = 'Loaded Fries', float $regular = 10.99, float $large = 14.99 ): WC_Product_Variable {
			self::attr_terms(
				'pa_size',
				array(
					'regular' => 'Regular',
					'large'   => 'Large',
				)
			);
			return new WC_Product_Variable(
				array(
					'id'                   => $id,
					'name'                 => $name,
					'permalink'            => 'http://example.test/product/' . sanitize_title( $name ) . '/',
					'variation_attributes' => array( 'pa_size' => array( 'regular', 'large' ) ),
					'variations'           => array(
						$id * 100 + 2 => array(
							'price'      => $large,
							'attributes' => array( 'attribute_pa_size' => 'large' ),
						),
						$id * 100 + 1 => array(
							'price'      => $regular,
							'attributes' => array( 'attribute_pa_size' => 'regular' ),
						),
					),
				)
			);
		}
	}
}
