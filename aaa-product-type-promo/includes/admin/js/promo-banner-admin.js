/**
 * Admin script for the promo banner image uploader.  Handles media frame
 * interactions and updates the preview/hidden field accordingly.
 */
(function($){
    'use strict';
    var frame;
    $(document).on('click', '#promo-banner-upload', function(e){
        e.preventDefault();
        // Reuse the frame if it exists.
        if(frame){
            frame.open();
            return;
        }
        frame = wp.media({
            title: wp.i18n ? wp.i18n.__('Select Banner Image', 'aaa') : 'Select Banner Image',
            button: {
                text: wp.i18n ? wp.i18n.__('Use this image', 'aaa') : 'Use this image'
            },
            multiple: false
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#promo-banner-image-id').val(attachment.id);
            $('#promo-banner-preview').attr('src', attachment.url).show();
            $('#promo-banner-remove').show();
        });
        frame.open();
    });
    $(document).on('click', '#promo-banner-remove', function(e){
        e.preventDefault();
        $('#promo-banner-image-id').val('');
        $('#promo-banner-preview').hide().attr('src', '');
        $(this).hide();
    });
})(jQuery);
