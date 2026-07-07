<?php
/**
 * NX2-01 preset registry.
 *
 * Discovers `presets/*​/preset.json` under the parent (+ child) theme, resolves
 * `extends` deep-merges, skips malformed definitions, applies the `lafka_presets`
 * filter (child / 3rd-party registration) and caches the file-discovered set in a
 * transient keyed by a directory-mtime fingerprint. See docs/PRESET_ENGINE.md §8.
 *
 * Preset FILES always resolve from the parent theme (the NX1-02 child-active
 * trap): prod runs the child theme, but the shipped presets live in the parent.
 * A child theme adds presets either via a `presets/` dir of its own or the
 * `lafka_presets` filter.
 *
 * @package Lafka
 * @since   7.1.0 (NX2-01)
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Presets' ) ) {

	/**
	 * Discovery + cache + active() resolution for the preset set.
	 */
	class Lafka_Presets {

		/** @var Lafka_Presets|null Runtime singleton. */
		private static $instance = null;

		/** @var array<int,string> Directories scanned for `*​/preset.json`. */
		private $dirs;

		/** @var array<string,Lafka_Preset> slug => preset (valid only). */
		private $presets = array();

		/**
		 * @param array<int,string>|null $dirs Override discovery dirs (tests); null = default.
		 */
		private function __construct( ?array $dirs = null ) {
			$this->dirs = null === $dirs ? self::default_dirs() : array_values( $dirs );
			$this->load();
		}

		/** Runtime singleton over the default (theme) discovery dirs. */
		public static function instance(): Lafka_Presets {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/** Drop the singleton (tests). */
		public static function reset(): void {
			self::$instance = null;
		}

		/**
		 * Fresh (non-singleton) registry over explicit dirs (tests / CLI tools).
		 *
		 * @param array<int,string> $dirs
		 */
		public static function from_dirs( array $dirs ): Lafka_Presets {
			return new self( $dirs );
		}

		/**
		 * Default discovery dirs: the parent theme's `presets/` first, then the
		 * child's when a child theme is active. Falls back to the parent theme
		 * dir relative to THIS file when no WordPress theme API is present
		 * (isolated unit tests / CLI).
		 *
		 * @return array<int,string>
		 */
		private static function default_dirs(): array {
			$dirs = array();
			if ( function_exists( 'get_template_directory' ) ) {
				$dirs[] = get_template_directory() . '/presets';
				if ( function_exists( 'get_stylesheet_directory' ) ) {
					$child = get_stylesheet_directory() . '/presets';
					if ( ! in_array( $child, $dirs, true ) ) {
						$dirs[] = $child;
					}
				}
			} else {
				$dirs[] = dirname( __DIR__, 2 ) . '/presets';
			}
			return $dirs;
		}

		/** Discover (cached) then hydrate + filter. */
		private function load(): void {
			$raw = $this->get_cache();
			if ( null === $raw ) {
				$raw = $this->discover_raw();
				$this->set_cache( $raw );
			}
			$this->hydrate( $raw );
		}

		/**
		 * Glob + decode every `preset.json`, force each slug to its directory
		 * name, then resolve `extends` deep-merges. Malformed JSON is skipped.
		 *
		 * @return array<string,array<string,mixed>>
		 */
		private function discover_raw(): array {
			$files = array();
			foreach ( $this->dirs as $dir ) {
				foreach ( (array) glob( $dir . '/*/preset.json' ) as $file ) {
					$slug           = basename( dirname( $file ) );
					$files[ $slug ] = $file; // later dir (child) overrides.
				}
			}

			$decoded = array();
			foreach ( $files as $slug => $file ) {
				$data = $this->read_json( $file );
				if ( null === $data ) {
					$this->debug_log( $slug, array( 'malformed JSON in ' . $file ) );
					continue;
				}
				$data['slug']     = $slug; // directory name is authoritative.
				$decoded[ $slug ] = $data;
			}

			$resolved = array();
			foreach ( array_keys( $decoded ) as $slug ) {
				$resolved[ $slug ] = $this->resolve_extends( $slug, $decoded, array() );
			}
			return $resolved;
		}

		/**
		 * Deep-merge a preset onto its (recursively resolved) `extends` base.
		 * Cycles and unknown parents degrade to the child verbatim.
		 *
		 * @param string                            $slug
		 * @param array<string,array<string,mixed>> $all
		 * @param array<string,bool>                $seen
		 * @return array<string,mixed>
		 */
		private function resolve_extends( string $slug, array $all, array $seen ): array {
			$data   = $all[ $slug ];
			$parent = empty( $data['extends'] ) ? '' : (string) $data['extends'];
			if ( '' === $parent || ! isset( $all[ $parent ] ) || isset( $seen[ $slug ] ) ) {
				return $data;
			}
			$seen[ $slug ] = true;
			$base          = $this->resolve_extends( $parent, $all, $seen );
			$merged        = self::deep_merge( $base, $data );
			$merged['slug']    = $slug; // never inherit the parent's slug.
			$merged['extends'] = null;  // resolved.
			return $merged;
		}

		/**
		 * Recursive associative merge — child scalars/lists win; child assoc
		 * arrays merge key-wise onto the base.
		 *
		 * @param array<string,mixed> $base
		 * @param array<string,mixed> $over
		 * @return array<string,mixed>
		 */
		private static function deep_merge( array $base, array $over ): array {
			foreach ( $over as $key => $value ) {
				if (
					isset( $base[ $key ] ) && is_array( $base[ $key ] ) && is_array( $value )
					&& self::is_assoc( $base[ $key ] ) && self::is_assoc( $value )
				) {
					$base[ $key ] = self::deep_merge( $base[ $key ], $value );
				} else {
					$base[ $key ] = $value;
				}
			}
			return $base;
		}

		/** @param array<mixed> $arr */
		private static function is_assoc( array $arr ): bool {
			if ( array() === $arr ) {
				return true;
			}
			return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
		}

		/**
		 * Validate every discovered definition, drop the malformed (WP_DEBUG
		 * log), then apply the `lafka_presets` filter for child/3rd-party
		 * registration. Re-key by slug so the map is always canonical.
		 *
		 * @param array<string,array<string,mixed>> $raw
		 */
		private function hydrate( array $raw ): void {
			$presets = array();
			foreach ( $raw as $slug => $data ) {
				$preset = new Lafka_Preset( $data );
				$errors = $preset->validate();
				if ( $errors ) {
					$this->debug_log( (string) $slug, $errors );
					continue;
				}
				$presets[ $preset->slug() ] = $preset;
			}

			if ( function_exists( 'apply_filters' ) ) {
				/**
				 * Filter the registered preset set.
				 *
				 * @param array<string,Lafka_Preset> $presets slug => preset.
				 */
				$presets = apply_filters( 'lafka_presets', $presets );
			}

			$clean = array();
			foreach ( (array) $presets as $preset ) {
				if ( $preset instanceof Lafka_Preset && '' !== $preset->slug() ) {
					$clean[ $preset->slug() ] = $preset;
				}
			}
			$this->presets = $clean;
		}

		/**
		 * @param string $file
		 * @return array<string,mixed>|null
		 */
		private function read_json( string $file ) {
			$contents = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.PHP.NoSilencedErrors.Discouraged -- local preset file, missing/unreadable degrades to skip.
			if ( false === $contents ) {
				return null;
			}
			$data = json_decode( $contents, true );
			return is_array( $data ) ? $data : null;
		}

		/** Directory-mtime fingerprint so a preset edit / add / remove busts. */
		private function cache_key(): string {
			$fp = '';
			foreach ( $this->dirs as $dir ) {
				foreach ( (array) glob( $dir . '/*/preset.json' ) as $file ) {
					$fp .= $file . ':' . (string) @filemtime( $file ) . '|'; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
			return 'lafka_presets_' . md5( $fp );
		}

		/** @return array<string,array<string,mixed>>|null */
		private function get_cache(): ?array {
			if ( ! function_exists( 'get_transient' ) ) {
				return null;
			}
			$v = get_transient( $this->cache_key() );
			return is_array( $v ) ? $v : null;
		}

		/** @param array<string,array<string,mixed>> $raw */
		private function set_cache( array $raw ): void {
			if ( function_exists( 'set_transient' ) && defined( 'DAY_IN_SECONDS' ) ) {
				set_transient( $this->cache_key(), $raw, DAY_IN_SECONDS );
			}
		}

		/**
		 * @param string           $slug
		 * @param array<int,string> $errors
		 */
		private function debug_log( string $slug, array $errors ): void {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
				error_log( "[lafka] preset '{$slug}' skipped: " . implode( '; ', $errors ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
			}
		}

		/** @return array<string,Lafka_Preset> */
		public function all(): array {
			return $this->presets;
		}

		/** @return array<int,string> */
		public function slugs(): array {
			return array_keys( $this->presets );
		}

		public function has( string $slug ): bool {
			return isset( $this->presets[ $slug ] );
		}

		public function get( string $slug ): ?Lafka_Preset {
			return $this->presets[ $slug ] ?? null;
		}

		/**
		 * The active preset. Resolves the given slug (or the stored
		 * `lafka_active_preset` theme_mod), falling back to peppery, then to any
		 * discovered preset, then to a synthesised identity so the engine never
		 * fatals on a broken install.
		 *
		 * @param string|null $slug Explicit slug (tests); null = resolve from storage.
		 */
		public function active( ?string $slug = null ): Lafka_Preset {
			if ( null === $slug ) {
				$slug = function_exists( 'lafka_get_active_preset_slug' )
					? lafka_get_active_preset_slug()
					: 'peppery';
			}
			if ( isset( $this->presets[ $slug ] ) ) {
				return $this->presets[ $slug ];
			}
			if ( isset( $this->presets['peppery'] ) ) {
				return $this->presets['peppery'];
			}
			$first = reset( $this->presets );
			return $first instanceof Lafka_Preset
				? $first
				: new Lafka_Preset(
					array(
						'slug'   => 'peppery',
						'schema' => 1,
						'label'  => 'Peppery',
					)
				);
		}
	}
}
