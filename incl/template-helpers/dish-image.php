<?php
/**
 * GX4 polish: tell a transparent cut-out dish photo from an opaque one.
 *
 * The counter layout seats cut-outs (a pizza on a transparent background) on
 * a soft elliptical contact shadow, and gives opaque photos (a sandwich shot
 * on a grey backdrop) a subtle rounded crop instead — a shadow under an
 * opaque square reads as a grey box.
 *
 * Detection samples the edge pixels of the smallest stored size with GD once
 * per attachment and caches the answer in post meta `_lafka_is_cutout`
 * (cleared whenever WordPress rewrites the attachment's metadata, e.g. after
 * an edit or a regenerate). Filter: lafka_image_is_cutout.
 *
 * @package Lafka
 * @since   7.2.x (GX4 polish)
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LAFKA_CUTOUT_META' ) ) {
	define( 'LAFKA_CUTOUT_META', '_lafka_is_cutout' );
}

if ( ! function_exists( 'lafka_image_alpha_is_cutout' ) ) {
	/**
	 * Decide from sampled edge alphas (GD scale: 0 opaque … 127 transparent).
	 *
	 * A cut-out has (nearly) transparent edges; an opaque photo has none. At
	 * least three quarters of the samples must be near-transparent, so a dish
	 * that touches one edge still counts as a cut-out.
	 *
	 * @param int[] $alphas Edge-pixel alphas.
	 */
	function lafka_image_alpha_is_cutout( array $alphas ): bool {
		if ( ! $alphas ) {
			return false;
		}
		$clear = count( array_filter( $alphas, static fn( $a ) => (int) $a >= 120 ) );
		return $clear * 4 >= count( $alphas ) * 3;
	}
}

if ( ! function_exists( 'lafka_image_detect_cutout' ) ) {
	/**
	 * Sample an image file's edges. Only PNG / WebP can be transparent.
	 *
	 * @param string $path Absolute file path.
	 */
	function lafka_image_detect_cutout( string $path ): bool {
		if ( '' === $path || ! is_readable( $path ) || ! function_exists( 'imagecolorat' ) ) {
			return false;
		}
		$ext = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( 'png' === $ext && function_exists( 'imagecreatefrompng' ) ) {
			$img = @imagecreatefrompng( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a corrupt file just means "not a cut-out".
		} elseif ( 'webp' === $ext && function_exists( 'imagecreatefromwebp' ) ) {
			$img = @imagecreatefromwebp( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- as above.
		} else {
			return false;
		}
		if ( ! $img ) {
			return false;
		}
		$w = imagesx( $img );
		$h = imagesy( $img );
		if ( $w < 3 || $h < 3 ) {
			return false;
		}
		$alphas = array();
		foreach ( array( 1, (int) ( $w / 2 ), $w - 2 ) as $x ) {
			foreach ( array( 1, (int) ( $h / 2 ), $h - 2 ) as $y ) {
				if ( (int) ( $w / 2 ) === $x && (int) ( $h / 2 ) === $y ) {
					continue; // The centre is the dish itself.
				}
				$alphas[] = ( imagecolorat( $img, $x, $y ) >> 24 ) & 0x7F;
			}
		}
		return lafka_image_alpha_is_cutout( $alphas );
	}
}

if ( ! function_exists( 'lafka_image_is_cutout' ) ) {
	/**
	 * Is this attachment a transparent cut-out? Cached per attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	function lafka_image_is_cutout( int $attachment_id ): bool {
		$is = false;
		if ( $attachment_id > 0 ) {
			$cached = (string) get_post_meta( $attachment_id, LAFKA_CUTOUT_META, true );
			if ( '1' === $cached || '0' === $cached ) {
				$is = '1' === $cached;
			} else {
				$path = function_exists( 'get_attached_file' ) ? (string) get_attached_file( $attachment_id ) : '';
				$meta = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
				// The smallest stored size is the cheapest to decode.
				if ( '' !== $path && is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
					$sizes = $meta['sizes'];
					uasort( $sizes, static fn( $a, $b ) => ( (int) ( $a['width'] ?? 0 ) ) <=> ( (int) ( $b['width'] ?? 0 ) ) );
					$small = reset( $sizes );
					if ( is_array( $small ) && ! empty( $small['file'] ) && is_readable( dirname( $path ) . '/' . $small['file'] ) ) {
						$path = dirname( $path ) . '/' . $small['file'];
					}
				}
				$is = lafka_image_detect_cutout( $path );
				update_post_meta( $attachment_id, LAFKA_CUTOUT_META, $is ? '1' : '0' );
			}
		}
		/**
		 * Override the cut-out detection for one image.
		 *
		 * @param bool $is            Detected (or cached) answer.
		 * @param int  $attachment_id Attachment ID.
		 */
		return (bool) apply_filters( 'lafka_image_is_cutout', $is, $attachment_id );
	}
}

if ( ! function_exists( 'lafka_dish_kind' ) ) {
	/**
	 * 'cutout' or 'photo' — the modifier the counter templates print.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	function lafka_dish_kind( int $attachment_id ): string {
		return lafka_image_is_cutout( $attachment_id ) ? 'cutout' : 'photo';
	}
}

if ( ! function_exists( 'lafka_image_cutout_forget' ) ) {
	/**
	 * wp_update_attachment_metadata: the file may have changed — re-detect.
	 *
	 * @param mixed $data          Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return mixed
	 */
	function lafka_image_cutout_forget( $data, $attachment_id = 0 ) {
		if ( (int) $attachment_id > 0 ) {
			delete_post_meta( (int) $attachment_id, LAFKA_CUTOUT_META );
		}
		return $data;
	}
}
add_filter( 'wp_update_attachment_metadata', 'lafka_image_cutout_forget', 10, 2 );
