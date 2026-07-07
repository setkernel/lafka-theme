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
$lafka_c_logo  = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'lafka_theme_logo', 0 ) : 0;

$lafka_c_photo_id = (int) get_theme_mod( 'lafka_contact_photo_id', 0 );
$lafka_c_photo    = $lafka_c_photo_id ? wp_get_attachment_image_url( $lafka_c_photo_id, 'large' ) : '';

/* v5.68.0: defaults are now restaurant-agnostic per OSS-bundle policy.
 * Operators can override per FAQ in Customizer, or via the
 * `lafka_contact_faqs` filter (intended path for child themes that want
 * to set site-specific copy). */
$lafka_faqs = (array) apply_filters(
	'lafka_contact_faqs',
	array(
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_1_q', __( 'How long do orders take?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_1_a', __( 'Pickup is typically ready in about 25 minutes. Delivery times vary by location and demand.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_2_q', __( 'Do you deliver?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_2_a', __( 'Yes — see our delivery area and fees during checkout.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_3_q', __( 'Are vegan / vegetarian options available?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_3_a', __( 'Yes — look for the 🌱 and 🥬 marks on the menu, and you can swap toppings on most items.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_4_q', __( 'How do I track my order?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_4_a', __( 'After you place an order you will see a status tracker on the confirmation page and receive an email update.', 'lafka' ) ),
		),
		array(
			'q' => (string) get_theme_mod( 'lafka_contact_faq_5_q', __( 'Can I order for catering or large groups?', 'lafka' ) ),
			'a' => (string) get_theme_mod( 'lafka_contact_faq_5_a', __( 'Yes — give us a call ahead of time so we can plan your order.', 'lafka' ) ),
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

		<section class="lafka-contact__hero <?php echo '' === $lafka_c_photo ? 'lafka-contact__hero--no-media' : ''; ?>">

			<?php
			/* v6.5.0: only render the media column when a photo is configured.
			 * Previously when no photo was set, the placeholder gradient + pin
			 * overlay rendered alone, producing an orphan circular logo
			 * floating above/left of the Address card. With no photo we
			 * collapse the hero to single-column. */
			?>
			<?php if ( '' !== $lafka_c_photo ) : ?>
				<div class="lafka-contact__media">
					<img class="lafka-contact__photo" src="<?php echo esc_url( $lafka_c_photo ); ?>" alt="" loading="lazy">

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
			<?php endif; ?>

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
						<?php
						// v5.92.0: hours footnote (handoff). Operator-tunable via filter.
						$lafka_c_hours_note = (string) apply_filters(
							'lafka_contact_hours_note',
							(string) get_theme_mod(
								'lafka_contact_hours_note',
								__( 'Last orders 15 min before close. Statutory holidays may differ — call ahead.', 'lafka' )
							)
						);
						if ( '' !== $lafka_c_hours_note ) :
							?>
							<p class="lafka-contact__hours-note"><?php echo esc_html( $lafka_c_hours_note ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="lafka-contact__actions">
					<?php if ( '' !== $lafka_c_directions ) : ?>
						<a class="lafka-contact__cta lafka-contact__cta--dark" href="<?php echo esc_url( $lafka_c_directions ); ?>" target="_blank" rel="noopener noreferrer">
							<span aria-hidden="true">📍</span>
							<?php esc_html_e( 'Get directions', 'lafka' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $lafka_c_phone ) : ?>
						<a class="lafka-contact__cta lafka-contact__cta--primary" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_c_tel ) ); ?>">
							<span aria-hidden="true">📞</span>
							<?php esc_html_e( 'Call to order', 'lafka' ); ?>
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
