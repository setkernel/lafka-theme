/**
 * Prints out the inline javascript needed for choosing
 * the tabs in the panel.
 */

jQuery(document).ready(function ($) {

	// Fade out the save message
	$('.fade').delay(1000).fadeOut(1000);


	// Switches option sections
	$('.group').hide();
	var activetab = '';
	if (typeof (localStorage) != 'undefined') {
		activetab = localStorage.getItem("activetab");
	}
	if (activetab != '' && $(activetab).length) {
		$(activetab).fadeIn();
	} else {
		$('.group:first').fadeIn();
	}
	$('.group .collapsed').each(function () {
		$(this).find('input:checked').parent().parent().parent().nextAll().each(
						function () {
							if ($(this).hasClass('last')) {
								$(this).removeClass('hidden');
								return false;
							}
							$(this).filter('.hidden').removeClass('hidden');
						});
	});

	if (activetab != '' && $(activetab + '-tab').length) {
		$(activetab + '-tab').addClass('nav-tab-active');
	}
	else {
		$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
	}
	$('.nav-tab-wrapper a').on('click', function (evt) {
		$('.nav-tab-wrapper a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active').blur();
		var clicked_group = $(this).attr('href');
		if (typeof (localStorage) != 'undefined') {
			localStorage.setItem("activetab", $(this).attr('href'));
		}
		$('.group').hide();
		$(clicked_group).fadeIn();
		evt.preventDefault();

		// Editor Height (needs improvement)
		$('.wp-editor-wrap').each(function () {
			var editor_iframe = $(this).find('iframe');
			if (editor_iframe.height() < 30) {
				editor_iframe.css({'height': 'auto'});
			}
		});

	});

	// START Handle dependent options
	$('input.expandable_option').on('click', unhideHidden);

	function unhideHidden() {
		if ($(this).is(':checked')) {
			$('.' + $(this).attr('id')).removeClass('hidden');
		}
		else {
			$('.' + $(this).attr('id')).addClass('hidden');
		}
	}

	// Check for expandable options - if unchecked hide the elements
	$('input.expandable_option').each(function () {
		if (!$(this).is(':checked')) {
			$('.' + $(this).attr('id')).addClass('hidden');
		}
	});

	$('div.expandable_option input.of-radio-img-radio').each(function () {
		if (!$(this).is(':checked')) {
			$('.' + $(this).attr('id')).addClass('hidden');
		}
	});

	$('div.expandable_option .of-radio-img-img').on('click', function () {
		var allOptions = $(this).siblings('input');
		allOptions.each(function(key, option){$('.' + option.id).fadeOut();});

		var inputToShow = $(this).prevAll('input.of-radio-img-radio');
		$('.' + inputToShow.attr('id')).fadeIn();
	});
	// END handle dependant options

	// Image Options
	$('.of-radio-img-img').on('click', function () {
		$(this).parent().parent().find('.of-radio-img-img').removeClass('of-radio-img-selected');
		$(this).addClass('of-radio-img-selected');
	});

	$('.of-radio-img-label').hide();
	$('.of-radio-img-img').show();
	$('.of-radio-img-radio').hide();

	// Import
	var ask_for_confirmation_flag = true;
	var import_run_attempts = 0;
	$('div.import_lafka_demo span.of-radio-img-img').on('click', function () {
		if (ask_for_confirmation_flag) {
			$import_true = confirm('Warning! Importing the demo may override some of your existing data. Make sure that all required and recommended plugins are activated and do not leave this page while the import is running. Click OK to continue.');
			if ($import_true === false)
			{
				return;
			}
		}

		import_run_attempts++;

		$option_el = $(this).parents('div.section-images');
		// construct the action from option id
		$action = 'lafka_import_' + $option_el.attr('id').substring(15);

		$(this).before('<span id="lafka_import_spinner" class="lafka_import_spinner"></span>');
		$spinner_el = $("#lafka_import_spinner");
		$spinner_el.on('click', function (event) {
			event.preventDefault();
			return false;
		});

		var data = {
			'action': $action,
			'security': (typeof lafka_back_js_params !== 'undefined') ? lafka_back_js_params.import_nonce : ''
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, data, function (response) {

			$spinner_el.remove();

			import_done_indx = response.search("lafka_import_done");
			import_failed_indx = response.search("<strong>Sorry, there has been an error.</strong>");

			if (-1 !== import_done_indx) {
				alert('All Done. The page will reload.');
                location.reload(true);
			} else if (-1 !== import_failed_indx) {
				alert(response.substring(import_failed_indx).replace(/<(?:.|\n)*?>/gm, ''));
			} else {
				alert('Timeout reached. Data is partially imported. Please run the import again.');
			}
		}).fail(function (xhr, textStatus, errorThrown) {
			$spinner_el.remove();
			// If error starts with 5 (server error), rerun max 10 times
			if (xhr.status.toString().startsWith("5") && import_run_attempts <= 10) {
				ask_for_confirmation_flag = false;
				$option_el.find("span.of-radio-img-img").trigger( "click" );
			} else {
				ask_for_confirmation_flag = true;
				alert("Code: " + xhr.status.toString() + "; Message: " + xhr.statusText);
			}
		});
	});

	$('div.import_lafka_demo span.of-radio-img-img').on('mouseenter', function () {
		if (!$('span.lafka_import_spinner').length) {
			$(this).prepend('<span class="lafka_import_btn">Import</span>');
		}
	}).on('mouseleave', function () {
		$(this).find('span.lafka_import_btn').remove();
	});
});