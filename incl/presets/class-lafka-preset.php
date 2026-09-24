<?php
/**
 * NX2-01 preset value object.
 *
 * A thin, pure (WordPress-free) wrapper over one decoded `preset.json`. It
 * only exposes typed accessors + a whitelist-aware validity check; discovery,
 * caching and the `lafka_presets` filter live in Lafka_Presets. See
 * docs/PRESET_ENGINE.md §2-3.
 *
 * @package Lafka
 * @since   7.1.0 (NX2-01)
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Preset' ) ) {

	/**
	 * Immutable view over a single preset definition.
	 */
	class Lafka_Preset {

		/** @var array<string,mixed> The decoded preset.json data. */
		private $data;

		/**
		 * @param array<string,mixed> $data Decoded preset.json (already merged if it `extends`).
		 */
		public function __construct( array $data ) {
			$this->data = $data;
		}

		/** Directory-name slug (== the preset folder). */
		public function slug(): string {
			return isset( $this->data['slug'] ) ? (string) $this->data['slug'] : '';
		}

		/** Human label for the switcher UI (falls back to the slug). */
		public function label(): string {
			return isset( $this->data['label'] ) && '' !== $this->data['label']
				? (string) $this->data['label']
				: $this->slug();
		}

		/** One-line description. */
		public function description(): string {
			return isset( $this->data['description'] ) ? (string) $this->data['description'] : '';
		}

		/** True when the preset activates the dark scaffold + emits a dark PTL. */
		public function is_dark(): bool {
			return ! empty( $this->data['dark'] );
		}

		/** Parent preset slug to deep-merge from, or null. */
		public function extends_slug(): ?string {
			if ( empty( $this->data['extends'] ) ) {
				return null;
			}
			return (string) $this->data['extends'];
		}

		/**
		 * PTL token overrides (`--lafka-*` => value). NOT yet whitelist-filtered —
		 * the emitter drops out-of-whitelist keys at build time.
		 *
		 * @return array<string,string>
		 */
		public function tokens(): array {
			return isset( $this->data['tokens'] ) && is_array( $this->data['tokens'] )
				? $this->data['tokens']
				: array();
		}

		/**
		 * TML chrome defaults (`lafka_*` theme_mod key => default value).
		 *
		 * @return array<string,mixed>
		 */
		public function chrome(): array {
			return isset( $this->data['chrome'] ) && is_array( $this->data['chrome'] )
				? $this->data['chrome']
				: array();
		}

		/**
		 * Font declarations (NX2-03 interface; engine wave uses source:"base").
		 *
		 * @return array<string,mixed>
		 */
		public function fonts(): array {
			return isset( $this->data['fonts'] ) && is_array( $this->data['fonts'] )
				? $this->data['fonts']
				: array();
		}

		/**
		 * Category-emoji map fed into the `lafka_category_emoji` filter.
		 *
		 * @return array<string,string>
		 */
		public function category_emoji(): array {
			return isset( $this->data['category_emoji'] ) && is_array( $this->data['category_emoji'] )
				? $this->data['category_emoji']
				: array();
		}

		/**
		 * Flat variant map (inert this wave; later drives `lafka-variant--k-v`
		 * body classes).
		 *
		 * @return array<string,mixed>
		 */
		public function variants(): array {
			return isset( $this->data['variants'] ) && is_array( $this->data['variants'] )
				? $this->data['variants']
				: array();
		}

		/**
		 * Audited AA contrast waivers (NX2-02 gate reads these).
		 *
		 * @return array<int,string>
		 */
		public function contrast_exceptions(): array {
			return isset( $this->data['contrast_exceptions'] ) && is_array( $this->data['contrast_exceptions'] )
				? array_values( $this->data['contrast_exceptions'] )
				: array();
		}

		/**
		 * The raw decoded array (for the registry / tests).
		 *
		 * @return array<string,mixed>
		 */
		public function raw(): array {
			return $this->data;
		}

		/**
		 * Structural + whitelist validity. Returns a list of problems (empty =
		 * valid). Used by Lafka_Presets discovery (WP_DEBUG log + skip) and by
		 * PresetSchemaTest.
		 *
		 * @return array<int,string>
		 */
		public function validate(): array {
			$errors = array();

			$slug = $this->slug();
			if ( '' === $slug ) {
				$errors[] = 'missing slug';
			} elseif ( function_exists( 'sanitize_key' ) && sanitize_key( $slug ) !== $slug ) {
				$errors[] = "slug '{$slug}' is not a sanitize_key() identity";
			}

			$schema = isset( $this->data['schema'] ) ? (int) $this->data['schema'] : 0;
			if ( 1 !== $schema ) {
				$errors[] = "unsupported schema '{$schema}' (expected 1)";
			}

			$token_whitelist = defined( 'LAFKA_PRESET_TOKEN_WHITELIST' ) ? LAFKA_PRESET_TOKEN_WHITELIST : array();
			foreach ( array_keys( $this->tokens() ) as $key ) {
				if ( ! in_array( $key, $token_whitelist, true ) ) {
					$errors[] = "token '{$key}' is not in LAFKA_PRESET_TOKEN_WHITELIST";
				}
			}

			$chrome_whitelist = defined( 'LAFKA_PRESET_CHROME_WHITELIST' ) ? LAFKA_PRESET_CHROME_WHITELIST : array();
			foreach ( array_keys( $this->chrome() ) as $key ) {
				if ( ! in_array( $key, $chrome_whitelist, true ) ) {
					$errors[] = "chrome key '{$key}' is not in LAFKA_PRESET_CHROME_WHITELIST";
				}
			}

			return $errors;
		}
	}
}
