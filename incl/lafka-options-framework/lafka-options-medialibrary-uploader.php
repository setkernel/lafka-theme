<?php

/**
 * WooThemes Media Library-driven AJAX File Uploader Module (2010-11-05)
 *
 * Slightly modified for use in the Options Framework.
 */
if (is_admin()) {

	// Load additional css and js for image uploads on the Options Framework page
	$lafka_page = 'appearance_page_options-framework';
	add_action("admin_enqueue_scripts", 'lafka_optionsframework_mlu_js');
}

/**
 * Registers and enqueues (loads) the necessary JavaScript file for working with the
 * Media Library-driven AJAX File Uploader Module.
 */
if (!function_exists('lafka_optionsframework_mlu_js')) {

	function lafka_optionsframework_mlu_js() {

		// Registers custom scripts for the Media Library AJAX uploader.
		wp_enqueue_script('lafka-of-medialibrary-uploader', LAFKA_OPTIONS_FRAMEWORK_DIRECTORY . 'js/lafka-of-medialibrary-uploader.js', array('jquery', 'thickbox'), false, true);
	}

}

/**
 * Media Uploader Using the WordPress Media Library (multiple files).
 *
 * Parameters:
 * - string $_id - A token to identify this field (the name).
 * - string $_value - The value of the field, if present.
 * - string $_mode - The display mode of the field.
 * - string $_desc - An optional description of the field.
 * - int $_postid - An optional post id (used in the meta boxes).
 *
 * Dependencies:
 * - lafka_optionsframework_mlu_get_silentpost()
 */
if (!function_exists('lafka_medialibrary_uploader')) {

	function lafka_medialibrary_uploader($_id, $_value, $_desc = '', $_name = '', $is_multiple = false, $is_for_menu = false) {

		wp_enqueue_media();

		// Gets the unique option id
		$option_name = 'lafka';

		$output = '';
		$classes = array('upload');
		$value = '';
		$multiple_images_class = $is_multiple ? 'is_multiple' : '';
		$upload_label = $is_for_menu ? __('Manage', 'lafka') : __('Manage images', 'lafka');

		$id = strip_tags(strtolower($_id));
		// Change for each field, using a "silent" post. If no post is present, one will be created.
		$int = lafka_optionsframework_mlu_get_silentpost($id);

		// If a value is passed and we don't have a stored value, use the value that's passed through.
		if ($_value != '' && $value == '') {
			$value = $_value;
		}

		if($is_for_menu) {
			$name = $_name;
		} else {
			if ( $_name != '' ) {
				$name = $option_name . '[' . $id . '][' . $_name . ']';
			} else {
				$name = $option_name . '[' . $id . ']';
			}
		}

		if ($value) {
			$classes[] = 'has-file';
		}
		$output .= '<input id="' . esc_attr($id) . '" class="' . esc_attr(implode(' ', $classes)) . '" type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />' . "\n";
		$output .= '<input id="upload_' . esc_attr($id) . '" class="lafka_upload_image_button button ' . esc_attr($multiple_images_class) . '" type="button" value="' .  esc_attr($upload_label) . '" rel="' . esc_attr($int) . '" />' . "\n";
		$output .= '<a id="' . esc_attr($id) . '_remove_link" ' . ($value ? '' : 'style="display:none"') . ' class="lafka_remove_image_link" href="#" title="'.esc_attr__('Remove Image', 'lafka').'" >' . esc_html__('Remove Image(s)', 'lafka') . '</a>';

		if ($_desc != '') {
			$output .= '<span class="lafka_metabox_desc">' . $_desc . '</span>' . "\n";
		}

		$output .= '<span class="screenshot" id="' . esc_attr($id) . '_images">' . "\n";

		if ($value != '') {
			$image_ids = explode(';', $value);
			foreach ($image_ids as $id) {
				// if is numeric, then it is an id, else it is full path to image
				if (is_numeric($id)) {
					$image = wp_get_attachment_url($id);
				} else {
					$image = $id;
				}

				$is_image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico|svg*)/i', $image );
				if ( $is_image ) {
					if ( is_numeric( $id ) ) {
						if ( $is_for_menu ) {
							$image = wp_get_attachment_image_src( $id, 'lafka-widgets-thumb' );
						} else {
							$image = wp_get_attachment_image_src( $id, 'medium' );
						}
						$image = $image[0];
					}

					$output .= '<img src="' . esc_url($image) . '" />';
				} else {
					$parts = explode("/", $image);
					for ($i = 0; $i < sizeof($parts); ++$i) {
						$title = $parts[$i];
					}

					// No output preview if it's not an image.
					$output .= '';

					// Standard generic output if it's not an image.
					$title = esc_html__('View File', 'lafka');
					$output .= '<div class="no_image"><span class="file_link"><a href="' . esc_url($image) . '" target="_blank" rel="external">' . esc_html($title) . '</a></span></div>';
				}
			}
		}
		$output .= '</span>' . "\n";

		return $output;
	}

}

/**
 * Uses "silent" posts in the database to store relationships for images.
 * This also creates the facility to collect galleries of, for example, logo images.
 *
 * Return: $_postid.
 *
 * If no "silent" post is present, one will be created with the type "lafka_optionsframework"
 * and the post_name of "of-$_token".
 *
 * Example Usage:
 * lafka_optionsframework_mlu_get_silentpost ( 'lafka_logo' );
 */
if (!function_exists('lafka_optionsframework_mlu_get_silentpost')) {

	function lafka_optionsframework_mlu_get_silentpost($_token) {

		global $wpdb;
		$_id = 0;

		// Check if the token is valid against a whitelist.
		// Sanitise the token.

		$_token = strtolower(str_replace(' ', '_', $_token));

		// if ( in_array( $_token, $_whitelist ) ) {
		if ($_token) {

			// Tell the function what to look for in a post.

			$_args = array('post_type' => 'lafka-optionsframework', 'post_name' => 'of-' . $_token, 'post_status' => 'draft', 'comment_status' => 'closed', 'ping_status' => 'closed');

			// Look in the database for a "silent" post that meets our criteria.
			$query = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_parent = 0';
			foreach ($_args as $k => $v) {
				$query .= ' AND ' . $k . ' = "' . $v . '"';
			} // End FOREACH Loop

			$query .= ' LIMIT 1';
			$_posts = $wpdb->get_row($query);

			// If we've got a post, loop through and get it's ID.
			if (null != $_posts) {
				$_id = $_posts->ID;
			} else {

				// If no post is present, insert one.
				// Prepare some additional data to go with the post insertion.
				$_words = explode('_', $_token);
				$_title = join(' ', $_words);
				$_title = ucwords($_title);
				$_post_data = array('post_title' => $_title);
				$_post_data = array_merge($_post_data, $_args);
				$_id = wp_insert_post($_post_data);
			}
		}
		return $_id;
	}

}

/**
 * Trigger code inside the Media Library popup.
 */
if (!function_exists('lafka_optionsframework_mlu_insidepopup')) {

	function lafka_optionsframework_mlu_insidepopup() {

		if (isset($_REQUEST['lafka_is_optionsframework']) && $_REQUEST['lafka_is_optionsframework'] == 'yes') {

			add_filter('media_upload_tabs', 'lafka_optionsframework_mlu_modify_tabs');
		}
	}

}

/**
 * Triggered inside the Media Library popup to modify the title of the "Gallery" tab.
 */
if (!function_exists('lafka_optionsframework_mlu_modify_tabs')) {

	function lafka_optionsframework_mlu_modify_tabs($tabs) {
		$tabs['gallery'] = str_replace(esc_html__('Gallery', 'lafka'), esc_html__('Previously Uploaded', 'lafka'), $tabs['gallery']);
		return $tabs;
	}

}