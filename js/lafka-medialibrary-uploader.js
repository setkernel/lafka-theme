// Media-library picker for the image fields in the admin (post metaboxes, term screens).
(function ($) {
    $(document).ready(function () {
        // Uploading files
        let file_frame;

        $(document.body).on('click', '.lafka_upload_image_button', function (event) {

            event.preventDefault();
            if (typeof file_frame !== 'undefined') {
                file_frame.close();
            }
            // get the id of the option
            const optionId = $(this).attr('id').substr(7);

            let has_multiple_images = false;
            let is_upload_link = false;

            if ($(this).hasClass('is_multiple')) {
                has_multiple_images = true;
            }

            if ($(this).hasClass('is_upload_link')) {
                is_upload_link = true;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: $(this).data('uploader_title'),
                button: {
                    text: $(this).data('uploader_button_text')
                },
                multiple: has_multiple_images, // Set to true to allow multiple files to be selected
                library: {
                    type: ['image', 'image/x-icon']
                },
                editing: true,
            });

            file_frame.on('open', function () {
                const selection = file_frame.state().get('selection');
                let attachment;

                if (has_multiple_images) {

                    // The stored ids, separated by semicolons.
                    const ids = $('#' + optionId).val().split(';');
                    ids.forEach(function (id) {
                        attachment = wp.media.attachment(id);
                        attachment.fetch();
                        if (attachment.id) {
                            selection.add([attachment]);
                        }
                    });
                } else {
                    attachment = wp.media.attachment($('#' + optionId).val());
                    attachment.fetch();

                    if (attachment.id) {
                        selection.add([attachment]);
                    }
                }
            });

            // When an image is selected, run a callback.
            file_frame.on('select', function () {
                const selection = file_frame.state().get('selection');

                // Clear previous images
                $('#' + optionId).val('');
                $('#' + optionId + '_images').html('');
                $('#' + optionId + '_remove_link').hide();

                selection.map(function (selected) {

                    const attachment = selected.toJSON();

                    // store the id
                    $('#' + optionId).val($('#' + optionId).val() + attachment.id + ';');

                    if (attachment.subtype === 'x-icon') {
                        $('#' + optionId + '_images').append('<img alt="" src="' + attachment.url + '">');
                    }
                    else if (optionId === 'lafka_super_slider_ids' && attachment.sizes.lafka_general_small_size) {
                        $('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.lafka_general_small_size.url + '">');
                    }
                    else if (attachment.sizes.medium) {
                        $('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.medium.url + '">');
                    }
                    else if (attachment.sizes.thumbnail) {
                        $('#' + optionId + '_images').append('<img alt="" src="' + attachment.sizes.thumbnail.url + '">');
                    }
                    else {
                        $('#' + optionId + '_images').append('<img alt="" src="' + attachment.url + '">');
                    }

                    $('#' + optionId + '_remove_link').show();
                    // For the background option type
                    const backgroundProps = $('#' + optionId + '_images').nextAll('div.of-background-properties');
                    if (backgroundProps.length) {
                        backgroundProps.removeClass('hide');
                    }

                });

                // Remove the trailing ';'
                $('#' + optionId).val($('#' + optionId).val().slice(0, -1));

                if (is_upload_link && $('#' + optionId).val()) {
                    $('#upload_' + optionId).hide();
                    $('#delete_' + optionId).show();
                }

            });

            // When closed and no image selected remove all
            file_frame.on('close', function () {
                const selection = file_frame.state().get('selection');

                if (selection.length === 0) {
                    // No images selected, so empty the hidden
                    $('#' + optionId).val('');
                    $('#' + optionId + '_images').html('');
                    $('#' + optionId + '_remove_link').hide();
                }
            });

            // Finally, open the modal
            file_frame.open();
        });

        $(document.body).on('click', 'a.lafka_remove_image_link', function () {
            if (confirm('Remove the image(s)?')) {
                const imageIdHidden = $(this).prevAll('input.upload');
                const imagePreview = $(this).nextAll('span.screenshot');
                if (imageIdHidden.length && imagePreview.length) {
                    imageIdHidden.val('');
                    imagePreview.html('');
                }
                // For the background option type
                const backgroundProps = $(this).nextAll('div.of-background-properties');
                if (backgroundProps.length) {
                    backgroundProps.addClass('hide');
                }

                $(this).hide();
            }
            return false;
        });

        /**
         * Delete a featured image
         */
        $('.lafka_delete_image_button').on('click', function (event) {

            // get the id of the option
            const optionId = $(this).attr('id').substr(7);
            event.preventDefault();

            // Clear the image
            $('#' + optionId).val('');
            $('#' + optionId + '_images').html('');

            $(this).hide();
            $('#upload_' + optionId).show();
        });
    });
})(window.jQuery);
