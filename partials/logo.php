<?php
$lafka_theme_logo_img        = lafka_get_option( 'theme_logo' );
$lafka_mobile_logo_img       = lafka_get_option( 'mobile_theme_logo' );
$lafka_persistent_logo_class = 'persistent_logo';
$lafka_is_text_logo          = lafka_is_text_logo( $lafka_theme_logo_img );
?>
<div <?php if ( $lafka_is_text_logo )
	echo 'class="lafka_text_logo"' ?> id="logo">
    <a href="<?php echo esc_url( lafka_wpml_get_home_url() ); ?>"
       title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
		<?php
		// Main logo
		if ( $lafka_theme_logo_img ) {
			echo wp_get_attachment_image( $lafka_theme_logo_img, 'full', false, array( 'class' => esc_attr( $lafka_persistent_logo_class ) ) );
		}

		// Mobile logo
		if ( $lafka_mobile_logo_img ) {
			echo wp_get_attachment_image( $lafka_mobile_logo_img, 'full', false, array( 'class' => 'lafka_mobile_logo' ) );
		}
		?>
		<?php if ( $lafka_is_text_logo ): ?>
            <span class="lafka-logo-title"><?php bloginfo( 'name' ) ?></span>
            <span class="lafka-logo-subtitle"><?php bloginfo( 'description' ) ?></span>
		<?php endif; ?>
    </a>
</div>