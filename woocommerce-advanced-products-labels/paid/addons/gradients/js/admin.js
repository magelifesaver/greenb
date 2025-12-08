(function ($){
    $(document).ready( function () {
        $(document).on('change', '#br_gradient_orientation, #br_gradient_use', showGradientOptions );
        $(document).on('change', '[name="br_labels[template]"]', function() {
            setTimeout(showGradientOptions, 20);
        });
        setTimeout(showGradientOptions, 210);

        $(document).on( 'change', '.berocket_label_content_type, .berocket_labels_attribute_type_select', function () {
            if ( $('[name="br_labels[content_type]"]').val() == 'attribute' 
                && ['color', 'image'].includes( $('[name="br_labels[attribute_type]"]').val() ) ) {
                let background = $('.berocket_alabel_id_demo span').css('background');
                if ( typeof background !== 'undefined' && background.includes('gradient') ) {
                    $('#br_gradient_use').click();
                }
                $('#br_gradient_use').attr('disabled', 'disabled').prop( "checked", false );
                $('.br_gradient_option').closest('tr').hide();
            } else {
                $('#br_gradient_use').removeAttr("disabled");
            }
        });
    });

    function showGradientOptions() {
        var usebg = ( jQuery('#br_gradient_use').length > 0 && Array.isArray(jQuery('#br_gradient_use').data('usebg')) 
        && jQuery('.br_label_css_templates input:checked').length > 0 && jQuery('#br_gradient_use').data('usebg').indexOf(jQuery('.br_label_css_templates input:checked').val()) != -1 );
        if ( $('#br_gradient_use').prop('checked') ) {
            if( ! usebg ) {
                $('.br_label_backcolor_use').closest('tr').hide();
                $('.br_label_backcolor').closest('tr').hide();
            }
        } else {
            if( ! usebg ) {
                $('.br_label_backcolor_use').closest('tr').show();
                br_each_parent_tr('.br_label_backcolor', ! $('.br_label_backcolor_use').prop('checked'), false);
            }
            return;
        }
        if ( $('#br_gradient_orientation').val() == 'linear' ) {
            $('.br_gradient_radial_option').closest('tr').addClass('br_hidden_option').hide();
            $('.br_gradient_linear_option').closest('tr').removeClass('br_hidden_option').show();
        } else {
            $('.br_gradient_radial_option').closest('tr').removeClass('br_hidden_option').show();
            $('.br_gradient_linear_option').closest('tr').addClass('br_hidden_option').hide();
        }
    }
})(jQuery);
