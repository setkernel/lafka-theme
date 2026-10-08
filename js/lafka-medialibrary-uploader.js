// USED FOR THEME OPTIONS
(function ($) {
    $(document).ready(function () {
        // Uploading files
        var file_frame;

        jQuery(document.body).on('click', '.lafka_upload_image_button', function (event) {

            event.preventDefault();
            if (typeof file_frame != 'undefined') {
                file_frame.close();
            }
            // get the id of the option
            optionId = jQuery(this).attr('id').substr(7);

            var has_multiple_images = false;
            var is_upload_link = false;

            if ($(this).hasClass('is_multiple')) {
                has_multiple_images = true;
            }

            if ($(this).hasClass('is_upload_link')) {
                is_upload_link = true;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: jQuery(this).data('uploader_title'),
                button: {
                    text: jQuery(this).data('uploader_button_text')
                },
                multiple: has_multiple_images, // Set to true to allow multiple files to be selected
                library: {
                    type: ['image', 'image/x-icon']
                },
                editing: true,
            });

            file_frame.on('open', function () {
                var selection = file_frame.state().get('selection');

                if (has_multiple_images) {

                    //Get ids array from
                    ids = jQuery('#' + optionId).val().split(';');
                    ids.forEach(function (id) {
                        attachment = wp.media.attachment(id);
                        attachment.fetch();
                        if (attachment.id) {
                            selection.add(attachment ? [attachment] : []);
                        }
                    });
                } else {
                    id = jQuery('#' + optionId).val();
                    attachment = wp.media.attachment(id);
                    attachment.fetch();

                    if (attachment.id) {
                        selection.add(attachment ? [attachment] : []);
                    }
                }
            });

            // When an image is selected, run a callback.
            file_frame.on('select', function () {
                var selection = file_frame.state().get('selection');

                // Clear previous images
                jQuery('#' + optionId).val('');
                jQuery('#' + optionId + '_images').html('');
                jQuery('#' + optionId + '_remove_link').hide();

                selection.map(function (attachment) {

                    attachment = attachment.toJSON();

                    // store the id
                    jQuery('#' + optionId).val(jQuery('#' + optionId).val() + attachment.id + ';');

                    if (attachment.subtype === 'x-icon') {
                        jQuery('#' + optionId + '_images').append('<img alt="" src="' + attachment.url + '">');
                    }
                    else if (optionId === 'lafka_super_slider_ids' && attachment.sizes.lafka_general_small_size) {
                        jQuery('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.lafka_general_small_size.url + '">');
                    }
                    else if (attachment.sizes.medium) {
                        jQuery('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.medium.url + '">');
                    }
                    else if (attachment.sizes.thumbnail) {
                        jQuery('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.thumbnail.url + '">');
                    }
                    else {
                        jQuery('#' + optionId + '_images').append('<img alt="" src="' + attachment.url + '">');
                    }

                    jQuery('#' + optionId + '_remove_link').show();
                    // For the background option type
                    backgroundProps = jQuery('#' + optionId + '_images').nextAll('div.of-background-properties');
                    if(backgroundProps.length) {
                        backgroundProps.removeClass('hide');
                    }

                });

                // Remove the trailing ';'
                jQuery('#' + optionId).val(jQuery('#' + optionId).val().slice(0, -1));

                if (is_upload_link && jQuery('#' + optionId).val()) {
                    $('#upload_' + optionId).hide();
                    $('#delete_' + optionId).show();
                }

            });

            // When closed and no image selected remove all
            file_frame.on('close', function () {
                var selection = file_frame.state().get('selection');

                if (selection.length == 0) {
                    // No images selected, so empty the hidden
                    jQuery('#' + optionId).val('');
                    jQuery('#' + optionId + '_images').html('');
                    jQuery('#' + optionId + '_remove_link').hide();
                }
            });

            // Finally, open the modal
            file_frame.open();
        });

        jQuery(document.body).on('click', 'a.lafka_remove_image_link', function (event) {
            if (confirm('Remove the image(s)?')) {
                imageIdHidden = $(this).prevAll('input.upload');
                imagePreview = $(this).nextAll('span.screenshot');
                if (imageIdHidden.length && imagePreview.length) {
                    imageIdHidden.val('');
                    imagePreview.html('');
                }
                // For the background option type
                backgroundProps = $(this).nextAll('div.of-background-properties');
                if(backgroundProps.length) {
                    backgroundProps.addClass('hide');
                }

                $(this).hide();
            }
            return false;
        });

        /**
         * Delete an lafka featured image
         */
        jQuery('.lafka_delete_image_button').on('click', function (event) {

            // get the id of the option
            optionId = jQuery(this).attr('id').substr(7);
            event.preventDefault();

            // Clear the image
            jQuery('#' + optionId).val('');
            jQuery('#' + optionId + '_images').html('');

            $(this).hide();
            $('#upload_' + optionId).show();
        });

        /**
         * Sidebars:
         * Adding new sidebar when click on 'Add' button
         */
        jQuery('#add_custom_sidebar').on('click', function (event) {
            event.preventDefault();

            var newSdbr = jQuery('#new_sidebar_ids').val();
            jQuery('#new_sidebar_ids').val('');

            if ($.trim(newSdbr) !== '') {
                var currentSdbrs = $('#sidebar_ids').val();
                if (currentSdbrs.search($.trim(newSdbr)) === -1) {
                    $('#sidebar_ids').val(currentSdbrs + newSdbr + ';');
                    jQuery('#lafka_custom_sidebars_list').append(
                        '<div><a class="lafka_del_sdbr" data-sidebar-name="' + newSdbr + '" href="#"></a>' + newSdbr + '</div>'
                    );
                }
                else {
                    alert('Sidebar "' + newSdbr + '" already exists.');
                }
                if ($.trim($('#sidebar_ids').val()) !== '') {
                    $('.lafka_no_custom_sidebars').html('List of created custom sidebars:');
                }
            }
        });

        /**
         * Sidebars:
         * Delete sidebar
         */
        $('#lafka_custom_sidebars_list').on('click', '.lafka_del_sdbr', function (event) {
            event.preventDefault();

            var sdbr = $(this).data('sidebar-name');
            //var sdbrId = sdbr.slice(4);

            $('#sidebar_ids').val($('#sidebar_ids').val().replace(sdbr + ';', ''));

            $(this).parent('div').remove();
        });

        /**
         * Accordion for the sections
         */
        $( "#lafka-optionsframework" ).accordion({
            header: "h4.lafka-accordion-heading",
            heightStyle: "content",
            collapsible: true,
            active: false,
        });
    });
})(window.jQuery);