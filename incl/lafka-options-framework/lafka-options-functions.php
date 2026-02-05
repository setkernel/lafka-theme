<?php

/*
 * This is an example of how to add custom scripts to the options panel.
 * This one shows/hides the an option when a checkbox is clicked.
 */

add_action('lafka_optionsframework_custom_scripts', 'lafka_optionsframework_custom_scripts');

function lafka_optionsframework_custom_scripts() {

	wp_enqueue_script('lafka-of-fonts-preview', LAFKA_OPTIONS_FRAMEWORK_DIRECTORY . 'js/lafka-of-fonts-preview.js', array('jquery'), false, true);
	wp_localize_script('lafka-of-fonts-preview', 'lafka_font_prev_params', array(
			'fonts' => esc_js(json_encode(lafka_typography_get_os_fonts())),
			'google_subset' => esc_js(lafka_get_google_subsets())
	));
}

/**
 * Returns an array of system fonts
 * Feel free to edit this, update the font fallbacks, etc.
 */
function lafka_typography_get_os_fonts() {
	// OS Font Defaults
	$os_faces = array(
			'' => esc_html__( '-- None --', 'lafka' ),
			'Arial' => 'Arial',
			'Verdana' => 'Verdana',
			'Helvetica' => 'Helvetica',
			'Lucida Grande' => 'Lucida Grande',
			'Trebuchet MS' => 'Trebuchet MS',
			'Times New Roman' => 'Times New Roman',
			'Tahoma' => 'Tahoma',
			'Georgia' => 'Georgia'
	);
	return $os_faces;
}

/**
 * Returns a select list of Google fonts
 */
function lafka_typography_get_google_fonts() {
	// Google Font Default
	$google_faces_default = array(
		'Aclonica' => 'Aclonica',
		'Allan' => 'Allan',
		'Annie Use Your Telescope' => 'Annie Use Your Telescope',
		'Anonymous Pro' => 'Anonymous Pro',
		'Allerta Stencil' => 'Allerta Stencil',
		'Allerta' => 'Allerta',
		'Amaranth' => 'Amaranth',
		'Anton' => 'Anton',
		'Architects Daughter' => 'Architects Daughter',
		'Arimo' => 'Arimo',
		'Artifika' => 'Artifika',
		'Arvo' => 'Arvo',
		'Asset' => 'Asset',
		'Astloch' => 'Astloch',
		'Bangers' => 'Bangers',
		'Bentham' => 'Bentham',
		'Bevan' => 'Bevan',
		'Bigshot One' => 'Bigshot One',
		'Bowlby One' => 'Bowlby One',
		'Bowlby One SC' => 'Bowlby One SC',
		'Brawler' => 'Brawler ',
		'Buda:300' => 'Buda',
		'Cabin' => 'Cabin',
		'Calligraffitti' => 'Calligraffitti',
		'Candal' => 'Candal',
		'Cantarell' => 'Cantarell',
		'Cardo' => 'Cardo',
		'Carter One' => 'Carter One',
		'Caudex' => 'Caudex',
		'Cedarville Cursive' => 'Cedarville Cursive',
		'Cherry Cream Soda' => 'Cherry Cream Soda',
		'Chewy' => 'Chewy',
		'Coda' => 'Coda',
		'Coming Soon' => 'Coming Soon',
		'Copse' => 'Copse',
		'Corben' => 'Corben',
		'Cousine' => 'Cousine',
		'Covered By Your Grace' => 'Covered By Your Grace',
		'Crafty Girls' => 'Crafty Girls',
		'Crimson Text' => 'Crimson Text',
		'Crushed' => 'Crushed',
		'Cuprum' => 'Cuprum',
		'Damion' => 'Damion',
		'Dancing Script' => 'Dancing Script',
		'Dawning of a New Day' => 'Dawning of a New Day',
		'Didact Gothic' => 'Didact Gothic',
		'Droid Sans' => 'Droid Sans',
		'Droid Sans Mono' => 'Droid Sans Mono',
		'Droid Serif' => 'Droid Serif',
		'EB Garamond' => 'EB Garamond',
		'Expletus Sans' => 'Expletus Sans',
		'Fontdiner Swanky' => 'Fontdiner Swanky',
		'Forum' => 'Forum',
		'Francois One' => 'Francois One',
		'Federo' => 'Federo',
		'Geo' => 'Geo',
		'Give You Glory' => 'Give You Glory',
		'Goblin One' => 'Goblin One',
		'Goudy Bookletter 1911' => 'Goudy Bookletter 1911',
		'Grand Hotel' => 'Grand Hotel',
		'Gravitas One' => 'Gravitas One',
		'Great Vibes' => 'Great Vibes',
		'Gruppo' => 'Gruppo',
		'Hammersmith One' => 'Hammersmith One',
		'Holtwood One SC' => 'Holtwood One SC',
		'Homemade Apple' => 'Homemade Apple',
		'Inconsolata' => 'Inconsolata',
		'Indie Flower' => 'Indie Flower',
		'IM Fell DW Pica' => 'IM Fell DW Pica',
		'IM Fell DW Pica SC' => 'IM Fell DW Pica SC',
		'IM Fell Double Pica' => 'IM Fell Double Pica',
		'IM Fell Double Pica SC' => 'IM Fell Double Pica SC',
		'IM Fell English' => 'IM Fell English',
		'IM Fell English SC' => 'IM Fell English SC',
		'IM Fell French Canon' => 'IM Fell French Canon',
		'IM Fell French Canon SC' => 'IM Fell French Canon SC',
		'IM Fell Great Primer' => 'IM Fell Great Primer',
		'IM Fell Great Primer SC' => 'IM Fell Great Primer SC',
		'Irish Grover' => 'Irish Grover',
		'Irish Growler' => 'Irish Growler',
		'Istok Web' => 'Istok Web',
		'Josefin Sans' => 'Josefin Sans Regular 400',
		'Josefin Slab' => 'Josefin Slab Regular 400',
		'Judson' => 'Judson',
		'Jura' => 'Jura',
		'Just Another Hand' => 'Just Another Hand',
		'Just Me Again Down Here' => 'Just Me Again Down Here',
		'Kameron' => 'Kameron',
		'Kenia' => 'Kenia',
		'Kranky' => 'Kranky',
		'Kreon' => 'Kreon',
		'Kristi' => 'Kristi',
		'La Belle Aurore' => 'La Belle Aurore',
		'Lato' => 'Lato',
		'League Script' => 'League Script',
		'Lekton' => 'Lekton ',
		'Limelight' => 'Limelight ',
		'Lobster' => 'Lobster',
		'Lobster Two' => 'Lobster Two',
		'Lora' => 'Lora',
		'Love Ya Like A Sister' => 'Love Ya Like A Sister',
		'Loved by the King' => 'Loved by the King',
		'Luckiest Guy' => 'Luckiest Guy',
		'Maiden Orange' => 'Maiden Orange',
		'Mako' => 'Mako',
		'Maven Pro' => 'Maven Pro',
		'Meddon' => 'Meddon',
		'MedievalSharp' => 'MedievalSharp',
		'Megrim' => 'Megrim',
		'Merriweather' => 'Merriweather',
		'Metrophobic' => 'Metrophobic',
		'Michroma' => 'Michroma',
		'Miltonian Tattoo' => 'Miltonian Tattoo',
		'Miltonian' => 'Miltonian',
		'Modern Antiqua' => 'Modern Antiqua',
		'Monofett' => 'Monofett',
		'Molengo' => 'Molengo',
		'Mountains of Christmas' => 'Mountains of Christmas',
		'Montserrat' => 'Montserrat',
		'Muli' => 'Muli',
		'Neucha' => 'Neucha',
		'Neuton' => 'Neuton',
		'News Cycle' => 'News Cycle',
		'Nixie One' => 'Nixie One',
		'Nobile' => 'Nobile',
		'Nova Cut' => 'Nova Cut',
		'Nova Flat' => 'Nova Flat',
		'Nova Mono' => 'Nova Mono',
		'Nova Oval' => 'Nova Oval',
		'Nova Round' => 'Nova Round',
		'Nova Script' => 'Nova Script',
		'Nova Slim' => 'Nova Slim',
		'Nova Square' => 'Nova Square',
		'Nunito' => 'Nunito',
		'OFL Sorts Mill Goudy TT' => 'OFL Sorts Mill Goudy TT',
		'Old Standard TT' => 'Old Standard TT',
		'Open Sans' => 'Open Sans',
		'Open Sans Condensed:300' => 'Open Sans Condensed',
		'Orbitron' => 'Orbitron',
		'Oswald' => 'Oswald',
		'Over the Rainbow' => 'Over the Rainbow',
		'Reenie Beanie' => 'Reenie Beanie',
		'Pacifico' => 'Pacifico',
		'Patrick Hand' => 'Patrick Hand',
		'Paytone One' => 'Paytone One',
		'Permanent Marker' => 'Permanent Marker',
		'Philosopher' => 'Philosopher',
		'Play' => 'Play',
		'Playfair Display' => 'Playfair Display ',
		'Podkova' => 'Podkova ',
		'Poppins' => 'Poppins',
		'PT Sans' => 'PT Sans',
		'PT Sans Narrow' => 'PT Sans Narrow',
		'PT Serif' => 'PT Serif',
		'Puritan' => 'Puritan',
		'Quattrocento' => 'Quattrocento',
		'Quattrocento Sans' => 'Quattrocento Sans',
		'Radley' => 'Radley',
		'Raleway' => 'Raleway',
		'Redressed' => 'Redressed',
		'Rock Salt' => 'Rock Salt',
		'Roboto' => 'Roboto',
		'Roboto Slab' => 'Roboto Slab',
		'Rokkitt' => 'Rokkitt',
		'Ruslan Display' => 'Ruslan Display',
		'Schoolbell' => 'Schoolbell',
		'Shadows Into Light' => 'Shadows Into Light',
		'Shanti' => 'Shanti',
		'Sigmar One' => 'Sigmar One',
		'Six Caps' => 'Six Caps',
		'Slackey' => 'Slackey',
		'Smythe' => 'Smythe',
		'Sniglet' => 'Sniglet',
		'Special Elite' => 'Special Elite',
		'Stardos Stencil' => 'Stardos Stencil',
		'Sue Ellen Francisco' => 'Sue Ellen Francisco',
		'Sunshiney' => 'Sunshiney',
		'Swanky and Moo Moo' => 'Swanky and Moo Moo',
		'Syncopate' => 'Syncopate',
		'Tangerine' => 'Tangerine',
		'Tenor Sans' => 'Tenor Sans',
		'Terminal Dosis Light' => 'Terminal Dosis Light',
		'The Girl Next Door' => 'The Girl Next Door',
		'Tinos' => 'Tinos',
		'Ubuntu Condensed' => 'Ubuntu Condensed',
		'Ultra' => 'Ultra',
		'Unkempt' => 'Unkempt',
		'UnifrakturCook:bold' => 'UnifrakturCook',
		'UnifrakturMaguntia' => 'UnifrakturMaguntia',
		'Varela' => 'Varela',
		'Varela Round' => 'Varela Round',
		'Vibur' => 'Vibur',
		'Vollkorn' => 'Vollkorn',
		'Waiting for the Sunrise' => 'Waiting for the Sunrise',
		'Wallpoet' => 'Wallpoet',
		'Walter Turncoat' => 'Walter Turncoat',
		'Wire One' => 'Wire One',
		'Yanone Kaffeesatz' => 'Yanone Kaffeesatz',
		'Yeseva One' => 'Yeseva One',
		'Yellowtail' => 'Yellowtail',
		'Zeyada' => 'Zeyada',
		'Rochester' => 'Rochester'
	);

	// Get actual google fonts list (cached for a week)
	$google_faces_json = get_transient( 'lafka_google_fonts_list' );

	if ( $google_faces_json === false ) {
		// It wasn't there, so regenerate the data and save the transient

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}

		$google_fonts_list_file_name = get_template_directory() . '/incl/lafka-google-fonts-list.json';
		if ( $wp_filesystem->exists( $google_fonts_list_file_name ) ) {
			$google_faces_json = $wp_filesystem->get_contents( $google_fonts_list_file_name );
		}

		// If successfully go the fonts list, save it in transient for a week
		if ( $google_faces_json !== false && is_string( $google_faces_json ) && lafka_is_string_valid_json( $google_faces_json ) ) {
			set_transient( 'lafka_google_fonts_list', $google_faces_json, WEEK_IN_SECONDS );
		} else {
			// Set it to string "use_default" transient so it doesn't on every request
			set_transient( 'lafka_google_fonts_list', 'use_default', WEEK_IN_SECONDS );
		}
	}

	// get the transient again as it may be set above
	$google_faces_transient = get_transient( 'lafka_google_fonts_list' );
	$google_faces_to_return = array();

	// If $google_faces_transient == 'use_default' - it cant' get the font list, so get default, but include the currently set font
	if ( $google_faces_transient == 'use_default' ) {
		$google_faces_to_return = $google_faces_default;

		// include the currently chosen heading font
		$headings_font = lafka_get_option( 'headings_font', array(
			'face' => 'Rubik'
		));
		if ( ! array_key_exists( $headings_font['face'], $google_faces_to_return ) ) {
			$google_faces_to_return[ $headings_font['face'] ] = $headings_font['face'];
		}

		// include the currently chosen body font
		$body_font = lafka_get_option( 'body_font', array(
			'face' => 'Open Sans'
		) );
		if ( ! array_key_exists( $body_font['face'], $google_faces_to_return ) ) {
			$google_faces_to_return[ $body_font['face'] ] = $body_font['face'];
		}
		
	} else {
		$tmp_array = json_decode( $google_faces_transient, true );

		if ( is_array( $tmp_array ) && array_key_exists( 'items', $tmp_array ) ) {
			foreach ( $tmp_array['items'] as $key => $value ) {
				$google_faces_to_return[ $value["family"] ] = $value["family"];
			}
		}
	}

	return $google_faces_to_return;
}

function lafka_is_string_valid_json($string) {
	json_decode($string);

	return (json_last_error() === JSON_ERROR_NONE);
}

/*
 * Load the additional stylesheet, defined as skin
 */

function lafka_options_stylesheets_alt_style() {
	if (lafka_get_option('additional_stylesheet') != 'default') {
		wp_enqueue_style('lafka_stylesheets_alt_style', lafka_get_option('additional_stylesheet'), array(), null);
	}
}

add_action('wp_enqueue_scripts', 'lafka_options_stylesheets_alt_style');
