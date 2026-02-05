jQuery(document).ready(function () {
	"use strict";
	var google_fonts_defined_json = lafka_font_prev_params.fonts.replace(/&quot;/g, '"');
	var google_fonts_defined = jQuery.parseJSON(google_fonts_defined_json);

	// For the live fonts setup preview
	jQuery('#body_font_size,#body_font_face').on('change', function () {
		if (!google_fonts_defined.hasOwnProperty(jQuery('#body_font_face').val())) {
			jQuery("[id^='lafka_ogfB_']").remove();
			jQuery('head').append('<link id="lafka_ogfB_' + jQuery('#body_font_face').val() + '" rel="stylesheet" href="//fonts.googleapis.com/css?family=' + jQuery('#body_font_face').val() + ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&subset=' + lafka_font_prev_params.google_subset + '">@font-face {font-family: "' + jQuery('#body_font_face option:selected').text() + '";}</link>');
		}
		if ('' === jQuery('#body_font_face').val()) {
			jQuery('#body_font_preview').html('<p></p>');
		} else {
			jQuery('#body_font_preview').html('<p style="font-family:' + jQuery('#body_font_face').val() + ';font-size:' + jQuery('#body_font_size').val() + ';color:' + jQuery('#body_font_color').val() + '">Sample Text</p>');
			jQuery('#body_font_preview > p').css('color', 'white');
			setTimeout(function () {
				jQuery('#body_font_preview > p').css('color', jQuery('#body_font_color').val());
			}, 1000);
		}
	});

	jQuery('#headings_font_face').on('change', function () {
		if (!google_fonts_defined.hasOwnProperty(jQuery('#headings_font_face').val())) {
			jQuery("[id^='lafka_ogfH_']").remove();
			jQuery('head').append('<link id="lafka_ogfH_' + jQuery('#headings_font_face').val() + '" rel="stylesheet" href="//fonts.googleapis.com/css?family=' + jQuery('#headings_font_face').val() + ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&subset=' + lafka_font_prev_params.google_subset + '">@font-face {font-family: "' + jQuery('#headings_font_face option:selected').text() + '";}</link>');
		}

		if ('' === jQuery('#headings_font_face').val()) {
			jQuery('#h1_font_preview > p').html('');
			jQuery('#h2_font_preview > p').html('');
			jQuery('#h3_font_preview > p').html('');
			jQuery('#h4_font_preview > p').html('');
			jQuery('#h5_font_preview > p').html('');
			jQuery('#h6_font_preview > p').html('');
		} else {
			// H1
			jQuery('#h1_font_preview > p').html('Sample Text');
			jQuery('#h1_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h1_font_preview > p').css('color', 'white');
			// H2
			jQuery('#h2_font_preview > p').html('Sample Text');
			jQuery('#h2_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h2_font_preview > p').css('color', 'white');
			// H3
			jQuery('#h3_font_preview > p').html('Sample Text');
			jQuery('#h3_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h3_font_preview > p').css('color', 'white');
			// H4
			jQuery('#h4_font_preview > p').html('Sample Text');
			jQuery('#h4_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h4_font_preview > p').css('color', 'white');
			// H5
			jQuery('#h5_font_preview > p').html('Sample Text');
			jQuery('#h5_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h5_font_preview > p').css('color', 'white');
			// H6
			jQuery('#h6_font_preview > p').html('Sample Text');
			jQuery('#h6_font_preview > p').css('font-family', jQuery('#headings_font_face option:selected').text());
			jQuery('#h6_font_preview > p').css('color', 'white');
			setTimeout(function () {
				jQuery('#h1_font_preview > p').css('color', jQuery('#h1_font_color').val());
				jQuery('#h2_font_preview > p').css('color', jQuery('#h2_font_color').val());
				jQuery('#h3_font_preview > p').css('color', jQuery('#h3_font_color').val());
				jQuery('#h4_font_preview > p').css('color', jQuery('#h4_font_color').val());
				jQuery('#h5_font_preview > p').css('color', jQuery('#h5_font_color').val());
				jQuery('#h6_font_preview > p').css('color', jQuery('#h6_font_color').val());
			}, 1000);
		}
	});

	jQuery('#h1_font_size,#h1_font_style').on('change', function () {
		jQuery('#h1_font_preview > p').css('font-size', jQuery('#h1_font_size').val());
		var $h1_style = JSON.parse(jQuery('#h1_font_style').val());
		if ($h1_style) {
			jQuery('#h1_font_preview > p').css('font-weight', $h1_style["font-weight"]);
			jQuery('#h1_font_preview > p').css('font-style', $h1_style["font-style"]);
		} else {
			jQuery('#h1_font_preview > p').css('font-weight', "");
			jQuery('#h1_font_preview > p').css('font-style', "");
		}
	});

	jQuery('#h2_font_size,#h2_font_style').on('change', function () {
		jQuery('#h2_font_preview > p').css('font-size', jQuery('#h2_font_size').val());
		var $h2_style = JSON.parse(jQuery('#h2_font_style').val());
		if ($h2_style) {
			jQuery('#h2_font_preview > p').css('font-weight', $h2_style["font-weight"]);
			jQuery('#h2_font_preview > p').css('font-style', $h2_style["font-style"]);
		} else {
			jQuery('#h2_font_preview > p').css('font-weight', "");
			jQuery('#h2_font_preview > p').css('font-style', "");
		}
	});

	jQuery('#h3_font_size,#h3_font_style').on('change', function () {
		jQuery('#h3_font_preview > p').css('font-size', jQuery('#h3_font_size').val());
		var $h3_style = JSON.parse(jQuery('#h3_font_style').val());
		if ($h3_style) {
			jQuery('#h3_font_preview > p').css('font-weight', $h3_style["font-weight"]);
			jQuery('#h3_font_preview > p').css('font-style', $h3_style["font-style"]);
		} else {
			jQuery('#h3_font_preview > p').css('font-weight', "");
			jQuery('#h3_font_preview > p').css('font-style', "");
		}
	});

	jQuery('#h4_font_size,#h4_font_style').on('change', function () {
		jQuery('#h4_font_preview > p').css('font-size', jQuery('#h4_font_size').val());
		var $h4_style = JSON.parse(jQuery('#h4_font_style').val());
		if ($h4_style) {
			jQuery('#h4_font_preview > p').css('font-weight', $h4_style["font-weight"]);
			jQuery('#h4_font_preview > p').css('font-style', $h4_style["font-style"]);
		} else {
			jQuery('#h4_font_preview > p').css('font-weight', "");
			jQuery('#h4_font_preview > p').css('font-style', "");
		}
	});

	jQuery('#h5_font_size,#h5_font_style').on('change', function () {
		jQuery('#h5_font_preview > p').css('font-size', jQuery('#h5_font_size').val());
		var $h5_style = JSON.parse(jQuery('#h5_font_style').val());
		if ($h5_style) {
			jQuery('#h5_font_preview > p').css('font-weight', $h5_style["font-weight"]);
			jQuery('#h5_font_preview > p').css('font-style', $h5_style["font-style"]);
		} else {
			jQuery('#h5_font_preview > p').css('font-weight', "");
			jQuery('#h5_font_preview > p').css('font-style', "");
		}
	});

	jQuery('#h6_font_size,#h6_font_style').on('change', function () {
		jQuery('#h6_font_preview > p').css('font-size', jQuery('#h6_font_size').val());
		var $h6_style = JSON.parse(jQuery('#h6_font_style').val());
		if ($h6_style) {
			jQuery('#h6_font_preview > p').css('font-weight', $h6_style["font-weight"]);
			jQuery('#h6_font_preview > p').css('font-style', $h6_style["font-style"]);
		} else {
			jQuery('#h6_font_preview > p').css('font-weight', "");
			jQuery('#h6_font_preview > p').css('font-style', "");
		}
	});
});