<?php
/**
 * Template Name: Lafka Contact
 *
 * Handoff-spec contact page (v5.66.0). Operator selects this template
 * from Page Attributes → Template on the Contact page.
 *
 * Per /design_handoff_peppery_ordering/README.md "Contact (/contact)":
 *   - 2-col grid ≥768 with photo placeholder + pin overlay
 *   - Right side: address card + hours card + phone/email/CTAs
 *   - FAQ section: 5 collapsible <details>
 *
 * Reads operator data via lafka_get_restaurant_info() (plugin schema helper).
 * FAQ entries via Customizer (lafka_contact_faq_*) — defaults included.
 *
 * @package Lafka
 * @since   5.66.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lafka_c_info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_c_addr = isset( $lafka_c_info['address_display'] ) ? (string) $lafka_c_info['address_display'] : '';
$lafka_c_short = isset( $lafka_c_info['address_short'] ) ? (string) $lafka_c_info['address_short'] : '';
$lafka_c_phone = isset( $lafka_c_info['phone_display'] ) ? (string) $lafka_c_info['phone_display'] : '';
$lafka_c_tel   = isset( $lafka_c_info['phone_e164'] ) ? (string) $lafka_c_info['phone_e164'] : $lafka_c_phone;
$lafka_c_email = isset( $lafka_c_info['email'] ) ? (string) $lafka_c_info['email'] : (string) get_bloginfo( 'admin_email' );
$lafka_c_hours = isset( $lafka_c_info['hours'] ) && is_array( $lafka_c_info['hours'] ) ? $lafka_c_info['hours'] : array();
$lafka_c_directions = isset( $lafka_c_info['directions_url'] ) ? (string) $lafka_c_info['directions_url'] : '';
$lafka_c_logo  = function_exists( 'lafka_get_option' ) ? lafka_get_option( 'theme_logo' ) : 0;

$lafka_c_photo_id = (int) get_theme_mod( 'lafka_contact_photo_id', 0 );
$lafka_c_photo    = $lafka_c_photo_id ? wp_get_attachment_image_url( $lafka_c_photo_id, 'large' ) : '';

$lafka_faqs = (array) apply_filters(
	'lafka_contact_faqs',
	array(
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_1_q', __( 'How long do orders take?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_1_a', __( 'Pickup orders are typically ready in about 25 minutes. Delivery takes 35–50 minutes depending on demand and where you are in Lower Sackville.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_2_q', __( 'Do you deliver?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_2_a', __( 'Yes — we deliver across Lower Sackville. Free delivery on orders over $30. There is a $4.99 fee on smaller orders.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_3_q', __( 'Are vegan / vegetarian options available?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_3_a', __( 'Absolutely. Look for the 🌱 and 🥬 marks on the menu, and you can swap toppings on any pizza for plant-based alternatives.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_4_q', __( 'How do I track my order?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_4_a', __( 'After you place an order you will see a status tracker. We will also send an email and SMS update when your order moves through each stage.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_5_q', __( 'Can I order for catering or large groups?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_5_a', __( 'Yes — give us a call ahead of time so we can plan your order. We do parties, office lunches, and team events.', 'lafka' ) ),
		),
	)
);
?>
<main id="main" class="lafka-contact" role="main">
	<div class="lafka-container">

		<header class="lafka-contact__header">
			<p class="lafka-contact__eyebrow"><?php esc_html_e( 'Get in touch', 'lafka' ); ?></p>
			<h1 class="lafka-contact__title"><?php esc_html_e( 'Come hang out, or just say hi.', 'lafka' ); ?></h1>
			<p class="lafka-contact__lead"><?php esc_html_e( 'Address, hours, and the fastest way to reach us — plus answers to the questions we get most.', 'lafka' ); ?></p>
		</header>

		<section class="lafka-contact__hero">

			<div class="lafka-contact__media">
				<?php if ( '' !== $lafka_c_photo ) : ?>
					<img class="lafka-contact__photo" src="<?php echo esc_url( $lafka_c_photo ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<div class="lafka-contact__photo-placeholder" aria-hidden="true">🍕</div>
				<?php endif; ?>

				<?php if ( '' !== $lafka_c_short ) : ?>
					<div class="lafka-contact__pin">
						<?php if ( $lafka_c_logo ) : ?>
							<?php
							echo wp_get_attachment_image(
								$lafka_c_logo,
								'thumbnail',
								false,
								array(
									'class' => 'lafka-contact__pin-logo',
									'alt'   => '',
								)
							);
							?>
						<?php endif; ?>
						<div class="lafka-contact__pin-text">
							<span class="lafka-contact__pin-label"><?php esc_html_e( 'Drop in at', 'lafka' ); ?></span>
							<span class="lafka-contact__pin-addr"><?php echo esc_html( $lafka_c_short ); ?></span>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="lafka-contact__info">

				<div class="lafka-contact__card">
					<h2 class="lafka-contact__card-title"><?php esc_html_e( 'Address', 'lafka' ); ?></h2>
					<?php if ( '' !== $lafka_c_addr ) : ?>
						<address class="lafka-contact__address"><?php echo nl2br( esc_html( $lafka_c_addr ) ); ?></address>
					<?php endif; ?>
					<?php if ( '' !== $lafka_c_directions ) : ?>
						<a class="lafka-contact__link" href="<?php echo esc_url( $lafka_c_directions ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get directions', 'lafka' ); ?> →
						</a>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $lafka_c_hours ) ) : ?>
					<div class="lafka-contact__card">
						<h2 class="lafka-contact__card-title"><?php esc_html_e( 'Hours', 'lafka' ); ?></h2>
						<dl class="lafka-contact__hours">
							<?php foreach ( $lafka_c_hours as $lafka_c_day => $lafka_c_range ) : ?>
								<div class="lafka-contact__hours-row">
									<dt><?php echo esc_html( $lafka_c_day ); ?></dt>
									<dd><?php echo esc_html( $lafka_c_range ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</div>
				<?php endif; ?>

				<div class="lafka-contact__actions">
					<?php if ( '' !== $lafka_c_phone ) : ?>
						<a class="lafka-contact__cta lafka-contact__cta--primary" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_c_tel ) ); ?>">
							<span aria-hidden="true">📞</span>
							<?php echo esc_html( $lafka_c_phone ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $lafka_c_email ) : ?>
						<a class="lafka-contact__cta lafka-contact__cta--ghost" href="mailto:<?php echo esc_attr( $lafka_c_email ); ?>">
							<span aria-hidden="true">✉</span>
							<?php esc_html_e( 'Email us', 'lafka' ); ?>
						</a>
					<?php endif; ?>
				</div>

			</div>
		</section>

		<?php if ( ! empty( $lafka_faqs ) ) : ?>
			<section class="lafka-contact__faq" aria-labelledby="lafka-contact-faq-heading">
				<header class="lafka-section-head">
					<p class="lafka-section-eyebrow"><?php esc_html_e( 'Questions', 'lafka' ); ?></p>
					<h2 id="lafka-contact-faq-heading" class="lafka-section-headline"><?php esc_html_e( 'Frequently asked', 'lafka' ); ?></h2>
				</header>

				<div class="lafka-contact__faq-list">
					<?php foreach ( $lafka_faqs as $lafka_faq ) : ?>
						<details class="lafka-contact__faq-item">
							<summary class="lafka-contact__faq-q"><?php echo esc_html( $lafka_faq['q'] ); ?><span class="lafka-contact__faq-icon" aria-hidden="true">+</span></summary>
							<div class="lafka-contact__faq-a">
								<?php echo esc_html( $lafka_faq['a'] ); ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

	</div>
</main>
<?php
get_footer();
