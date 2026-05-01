<?php
defined( 'ABSPATH' ) || exit;
/**
 * Partial: social profile links rendered in header / footer / sidebar.
 *
 * The map below is the **default** allowlist of supported networks. Each
 * entry's option key (`<slug>_profile`) is read from the Lafka options
 * panel — when the operator pastes a URL there, the corresponding link
 * appears.
 *
 * v5.15.3: filterable via `lafka_social_profiles`. Pre-fix the list was
 * hardcoded to 11 networks frozen circa 2015 (vKontakte, Flickr,
 * Vimeo, Dribbble, Behance — but no Mastodon, Threads, BlueSky,
 * TikTok, Discord, etc.). Now child themes / plugins can hook the
 * filter to add (or remove) entries without forking this template.
 *
 * Three legacy 2015-era networks (Flickr, vKontakte, Behance) stay in
 * the default list to avoid breaking existing sites that have those
 * URLs configured. Operators on a fresh install who don't use them
 * simply leave the option blank and no link renders.
 *
 * Filter signature:
 *   apply_filters( 'lafka_social_profiles', array $defaults )
 *     → array<string, array{title:string, class:string}>
 *
 * @package Lafka\Theme
 */

$lafka_social_profiles = (array) apply_filters(
	'lafka_social_profiles',
	array(
		'facebook'  => array(
			'title' => esc_html__( 'Follow on Facebook', 'lafka' ),
			'class' => 'fa-brands fa-facebook',
		),
		'twitter'   => array(
			'title' => esc_html__( 'Follow on X (Twitter)', 'lafka' ),
			'class' => 'fa-brands fa-x-twitter',
		),
		'instegram' => array(
			// `instegram` (typo) preserved as the option key for back-compat;
			// existing sites with a saved Instagram URL would lose it on a
			// rename. Fixing the spelling needs a one-shot data migration.
			'title' => esc_html__( 'Follow on Instagram', 'lafka' ),
			'class' => 'fa-brands fa-instagram',
		),
		'tiktok'    => array(
			'title' => esc_html__( 'Follow on TikTok', 'lafka' ),
			'class' => 'fa-brands fa-tiktok',
		),
		'threads'   => array(
			'title' => esc_html__( 'Follow on Threads', 'lafka' ),
			'class' => 'fa-brands fa-threads',
		),
		'mastodon'  => array(
			'title' => esc_html__( 'Follow on Mastodon', 'lafka' ),
			'class' => 'fa-brands fa-mastodon',
		),
		'bluesky'   => array(
			'title' => esc_html__( 'Follow on Bluesky', 'lafka' ),
			// Font Awesome added Bluesky in 6.5+; on older FA installs the
			// icon falls back to nothing (operators will see a blank link).
			'class' => 'fa-brands fa-bluesky',
		),
		'youtube'   => array(
			'title' => esc_html__( 'Follow on YouTube', 'lafka' ),
			'class' => 'fa-brands fa-youtube',
		),
		'linkedin'  => array(
			'title' => esc_html__( 'Follow on LinkedIn', 'lafka' ),
			'class' => 'fa-brands fa-linkedin',
		),
		'pinterest' => array(
			'title' => esc_html__( 'Follow on Pinterest', 'lafka' ),
			'class' => 'fa-brands fa-pinterest',
		),
		'discord'   => array(
			'title' => esc_html__( 'Join our Discord', 'lafka' ),
			'class' => 'fa-brands fa-discord',
		),
		// Legacy networks kept for back-compat with existing operator settings.
		'vimeo'     => array(
			'title' => esc_html__( 'Follow on Vimeo', 'lafka' ),
			'class' => 'fa-brands fa-vimeo',
		),
		'dribbble'  => array(
			'title' => esc_html__( 'Follow on Dribbble', 'lafka' ),
			'class' => 'fa-brands fa-dribbble',
		),
		'behance'   => array(
			'title' => esc_html__( 'Follow on Behance', 'lafka' ),
			'class' => 'fa-brands fa-behance',
		),
		'flicker'   => array(
			// `flicker` (typo) preserved as the option key for back-compat.
			'title' => esc_html__( 'Follow on Flickr', 'lafka' ),
			'class' => 'fa-brands fa-flickr',
		),
		'vkontakte' => array(
			'title' => esc_html__( 'Follow on VKontakte', 'lafka' ),
			'class' => 'fa-brands fa-vk',
		),
	)
);
?>
<div class="lafka-social">
	<ul>
		<?php foreach ( $lafka_social_profiles as $lafka_social_name => $lafka_details ) : ?>
			<?php $lafka_url = lafka_get_option( $lafka_social_name . '_profile' ); ?>
			<?php if ( $lafka_url ) : ?>
				<li><a title="<?php echo esc_attr( $lafka_details['title'] ); ?>" class="<?php echo esc_attr( $lafka_social_name ); ?>" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $lafka_url ); ?>"><i class="<?php echo esc_attr( $lafka_details['class'] ); ?>" aria-hidden="true"></i><span class="screen-reader-text"><?php echo esc_html( $lafka_details['title'] ); ?></span></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</div>
