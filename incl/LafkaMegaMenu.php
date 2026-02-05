<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Add mega menu fields to menu editor in backend
add_action( 'wp_nav_menu_item_custom_fields', 'lafka_megamenu_item_custom_fields', 10, 5 );
if ( ! function_exists( 'lafka_megamenu_item_custom_fields' ) ) {
	function lafka_megamenu_item_custom_fields( $item_id, $item, $depth, $args, $id ) {
		?>
        <div class='lafka-megamenu-custom'>
			<?php
			$title = esc_html__('Custom Menu Label', 'lafka');
			$key = "lafka-menu-item-custom_label";
			$value = get_post_meta($item_id, '_' . $key, true);
			?>
            <p class="description description-thin lafka_custom_label">
                <label for="edit-<?php echo esc_attr($key . '-' . $item_id); ?>"><?php echo esc_html($title); ?>
                    <input type="text" id="edit-<?php echo esc_attr($key . '-' . $item_id); ?>" class="widefat edit-menu-item-attr-title" name="<?php echo esc_attr($key . "[" . $item_id . "]"); ?>" value="<?php echo esc_attr($value); ?>" />
                </label>
            </p>
			<?php
			$title = esc_html__('Label Color', 'lafka');
			$key = "lafka-menu-item-label_color";
			$value = get_post_meta($item_id, '_' . $key, true);
			?>
            <p class="description description-thin lafka_label_color">
	            <?php echo esc_html($title); ?><br>
                <input type="text" id="edit-<?php echo esc_attr($key . '-' . $item_id); ?>" class="widefat edit-menu-item-attr-title lafka-menu-colorpicker" name="<?php echo esc_attr($key . "[" . $item_id . "]"); ?>" value="<?php echo esc_attr($value); ?>" />
            </p>
			<?php
			$title = esc_html__('Icon', 'lafka');
			$key = "lafka-menu-item-icon";
			$value = get_post_meta($item_id, '_' . $key, true);
			?>
            <p class="description description-thin lafka_megamenu_icon">
                <label for="edit-<?php echo esc_attr($key . '-' . $item_id); ?>"><?php echo esc_html($title); ?><br/>
                    <input type="text" id="edit-<?php echo esc_attr($key . '-' . $item_id); ?>" class="lafka-menu-icons" name="<?php echo esc_attr($key . "[" . $item_id . "]"); ?>" value="<?php echo esc_attr($value); ?>" />
                </label>
            </p>
	        <?php
	        $title = esc_html__('Image', 'lafka');
	        $key = "lafka-menu-item-image";
	        $value = get_post_meta($item_id, '_' . $key, true);
	        ?>
            <p class="description description-thin lafka_megamenu_image">
	            <?php echo esc_html($title); ?><br>
	            <?php echo lafka_medialibrary_uploader( 'edit-' . esc_attr( $key . '-' . $item_id ), $value, '', $key . "[" . $item_id . "]", false, true ); ?>
            </p>
			<?php
			$title = esc_html__('Set as Mega Menu', 'lafka');
			$key = "lafka-menu-item-is_megamenu";
			$value = get_post_meta($item_id, '_' . $key, true);
			if ($value != "") {
				$value = "checked='checked'";
			}
			?>
            <p class="description description-wide lafka_checkbox lafka_is_mega_field">
                <label for="edit-<?php echo esc_attr($key . '-' . $item_id); ?>">
                    <input type="checkbox" value="active" id="edit-<?php echo esc_attr($key . '-' . $item_id); ?>" class=" <?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key . "[" . $item_id . "]"); ?>" <?php echo esc_attr($value); ?> /><?php echo esc_html($title); ?>
                </label>
            </p>
			<?php
			$title = esc_html__('Use the Description to create a Text Block. It will hide the Navigation Label and display the description instead. (note: dont remove the label text, otherwise WordPress will delete the item)', 'lafka');
			$key = "lafka-menu-item-is_description";
			$value = get_post_meta($item_id, '_' . $key, true);
			if ($value != "")
				$value = "checked='checked'";
			?>
            <p class="description description-wide lafka_checkbox lafka_is_description_field">
                <label for="edit-<?php echo esc_attr($key . '-' . $item_id); ?>">
                    <input type="checkbox" value="active" id="edit-<?php echo esc_attr($key . '-' . $item_id); ?>" class=" <?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key . "[" . $item_id . "]"); ?>" <?php echo esc_attr($value); ?> /><span><?php echo esc_html($title); ?></span>
                </label>
            </p>
        </div>
		<?php
	}
}

// Handle mega menu fields update
add_action('wp_update_nav_menu_item', 'lafka_update_mega_menu_item', 100, 3);
if ( ! function_exists( 'lafka_update_mega_menu_item' ) ) {
	function lafka_update_mega_menu_item( $menu_id, $menu_item_db ) {
		$fields = array('is_megamenu', 'is_description', 'custom_label', 'label_color', 'highlight', 'icon', 'image');

		foreach ($fields as $field) {
			if (!isset($_POST['lafka-menu-item-' . $field][$menu_item_db])) {
				$_POST['lafka-menu-item-' . $field][$menu_item_db] = "";
			}

			$value = $_POST['lafka-menu-item-' . $field][$menu_item_db];
			update_post_meta($menu_item_db, '_lafka-menu-item-' . $field, $value);
		}
	}
}

if ( ! class_exists( 'LafkaFrontWalker' ) ) {

	/**
	 * Renders the mega menu on frontend
	 */
	class LafkaFrontWalker extends Walker_Nav_Menu {
		/**
		 * @var int $columns
		 */
		var $columns = 0;

		/**
		 * @var int $max_columns maximum number of columns within one mega menu
		 */
		var $max_columns = 0;

		/**
		 * @var string $is_mega_active hold information whatever we are currently rendering a mega menu or not
		 */
		var $is_mega_active = 0;

		public function start_lvl( &$output, $depth = 0, $args = null ) {
			if ( $depth === 0 && $this->is_mega_active ) {
				$output .= '<div class="lafka-mega-menu" style="display:none">';
			}

			parent::start_lvl( $output, $depth, $args );
		}

		public function end_lvl( &$output, $depth = 0, $args = null ) {
			parent::end_lvl( $output, $depth, $args );

			if ( $depth === 0 && $this->is_mega_active ) {
				$output .= '</div>';
			}
		}

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( $depth === 0 ) {
				$this->is_mega_active = get_post_meta( $item->ID, '_lafka-menu-item-is_megamenu', true );
			}

			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

			$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			// Mega column headings class
			if ( $this->is_mega_active && $depth === 1 ) {
				$classes[] = 'lafka_colum_title';
			}
			// Handle the FA icons on menu items
			if ( get_post_meta( $item->ID, '_lafka-menu-item-icon', true ) ) {
				$classes[] = 'lafka-link-has-icon';
			}
			// Mega menu description classes
			if ( $depth >= 2 && $this->is_mega_active && get_post_meta( $item->ID, '_lafka-menu-item-is_description', true ) ) {
				$classes[] = 'lafka_mega_text_block';
			}

			/**
			 * Filters the arguments for a single nav menu item.
			 *
			 * @since 4.4.0
			 *
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param WP_Post  $item  Menu item data object.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

			/**
			 * Filters the CSS classes applied to a menu item's list item element.
			 *
			 * @since 3.0.0
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
			 * @param WP_Post  $item    The current menu item.
			 * @param stdClass $args    An object of wp_nav_menu() arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			/**
			 * Filters the ID applied to a menu item's list item element.
			 *
			 * @since 3.0.1
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param string   $menu_id The ID that is applied to the menu item's `<li>` element.
			 * @param WP_Post  $item    The current menu item.
			 * @param stdClass $args    An object of wp_nav_menu() arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			$output .= $indent . '<li' . $id . $class_names . '>';

			$atts           = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target ) ? $item->target : '';
			if ( '_blank' === $item->target && empty( $item->xfn ) ) {
				$atts['rel'] = 'noopener noreferrer';
			} else {
				$atts['rel'] = $item->xfn;
			}
			$atts['href']         = ! empty( $item->url ) ? $item->url : '';
			$atts['aria-current'] = $item->current ? 'page' : '';

			/**
			 * Filters the HTML attributes applied to a menu item's anchor element.
			 *
			 * @since 3.6.0
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param array $atts {
			 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
			 *
			 *     @type string $title        Title attribute.
			 *     @type string $target       Target attribute.
			 *     @type string $rel          The rel attribute.
			 *     @type string $href         The href attribute.
			 *     @type string $aria_current The aria-current attribute.
			 * }
			 * @param WP_Post  $item  The current menu item.
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
					$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			/** This filter is documented in wp-includes/post-template.php */
			$title = apply_filters( 'the_title', $item->title, $item->ID );

			/**
			 * Filters a menu item's title.
			 *
			 * @since 4.4.0
			 *
			 * @param string   $title The menu item's title.
			 * @param WP_Post  $item  The current menu item.
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

			$has_mega_description = get_post_meta( $item->ID, '_lafka-menu-item-is_description', true );
			$font_awesome_icon    = get_post_meta( $item->ID, '_lafka-menu-item-icon', true );
			$custom_image_id      = get_post_meta( $item->ID, '_lafka-menu-item-image', true );

			$item_output = $args->before;
			if ( $depth >= 2 && $this->is_mega_active && $has_mega_description ) {
				$item_output .= do_shortcode( $item->post_content );
			} elseif($title != '-' && $title != '"-"' && $title != '&#8211;' && !$has_mega_description) {
				$item_output .= '<a' . $attributes . '>';
				$custom_image_html = '';
				if ( $custom_image_id ) {
					$custom_image_url       = wp_get_attachment_image_url( $custom_image_id );
					$custom_image_classes = array('lafka-menu-image-icon');
					if ( substr( $custom_image_url, - 4 ) === '.svg' ) {
						$custom_image_classes[] = 'lafka-svg-icon';
					}
					$custom_image_html = wp_get_attachment_image( $custom_image_id, 'lafka-widgets-thumb', false, array( 'class' => implode( ' ', $custom_image_classes ) ) );
				}
				if ( $custom_image_html ) {
					$item_output .= $custom_image_html;
				} elseif ( $font_awesome_icon ) {
					$item_output .= '<i class="' . $font_awesome_icon . '"></i> ';
				}
				$item_output .= $args->link_before . $title . $args->link_after;
				// Show the label and color in the menu
				$custom_menu_label_val = get_post_meta($item->ID, '_lafka-menu-item-custom_label', true);
				$custom_menu_label_color = get_post_meta($item->ID, '_lafka-menu-item-label_color', true);
				if ($custom_menu_label_val) {
					$item_output .= '<span class="lafka-custom-menu-label" ' . ($custom_menu_label_color ? 'style="background-color:' . esc_attr($custom_menu_label_color) . '"' : '') . ' >' . esc_html($custom_menu_label_val) . '</span>';
				}
				$item_output .= '</a>';
			}
			$item_output .= $args->after;

			/**
			 * Filters a menu item's starting output.
			 *
			 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
			 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
			 * no filter for modifying the opening and closing `<li>` for a menu item.
			 *
			 * @since 3.0.0
			 *
			 * @param string   $item_output The menu item's starting HTML output.
			 * @param WP_Post  $item        Menu item data object.
			 * @param int      $depth       Depth of menu item. Used for padding.
			 * @param stdClass $args        An object of wp_nav_menu() arguments.
			 */
			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}
}