jQuery(document).ready(
    function($) {

        $("body").on("click", ".pwddm_premium_close", function() {
            $(this).parent().hide();
            return false;
        });
        $("body").on("click", ".pwddm_star_button", function() {
            if ($(this).next().is(":visible")) {
                $(this).next().hide();
            } else {
                $(".pwddm_premium_feature_note").hide();
                $(this).next().show();
            }
            return false;
        });

        function pwddm_dates_range() {
            var $pwddm_this = $("#pwddm_dates_range");
            if ($pwddm_this.val() == "custom") {
                $("#pwddm_dates_custom_range").show();
            } else {
                var pwddm_fromdate = $('option:selected', $pwddm_this).attr('fromdate');
                var pwddm_todate = $('option:selected', $pwddm_this).attr('todate');
                $("#pwddm_dates_custom_range").hide();
                $("#pwddm_dates_range_from").val(pwddm_fromdate);
                $("#pwddm_dates_range_to").val(pwddm_todate);
            }
        }

        $("#pwddm_dates_range").change(
            function() {
                pwddm_dates_range()
            }
        );

        if ($("#pwddm_dates_range").length) {
            pwddm_dates_range();
        }

        function pwddm_manager_commission_type() {
            $("#pwddm_manager_commission_symbol_currency").hide();
            $("#pwddm_manager_commission_symbol_percentage").hide();
            var $pwddm_val = $("#pwddm_manager_commission_type").val();
            if ($pwddm_val == "") {
                $("#pwddm_manager_commission_value_wrap").hide();
                $("#pwddm_manager_commission_value").val("");
            } else {
                $("#pwddm_manager_commission_value_wrap").show();
                if ($pwddm_val == "fixed") {
                    $("#pwddm_manager_commission_symbol_currency").show();
                } else {
                    $("#pwddm_manager_commission_symbol_percentage").show();
                }
            }
        }

        $("#pwddm_manager_commission_type").change(
            function() {
                pwddm_manager_commission_type()
            }
        );

        if ($("#pwddm_manager_commission_type").length) {
            pwddm_manager_commission_type();
        }

        $(".pwddm_media_delete").click(
            function() {
                var pwddm_object_id = $(this).attr("data");
                $("#" + pwddm_object_id).val("");
                $("#" + pwddm_object_id + "_preview").html("");
            }
        );

        $('.pwddm_media_manager').click(
            function(e) {
                var pwddm_object_id = $(this).attr("data");
                e.preventDefault();
                var pwddm_image_frame;
                if (pwddm_image_frame) {
                    pwddm_image_frame.open();
                }
                // Define image_frame as wp.media object
                pwddm_image_frame = wp.media({
                    title: 'Select Media',
                    multiple: false,
                    library: {
                        type: 'image',
                    }
                });

                pwddm_image_frame.on(
                    'close',
                    function() {
                        var pwddm_selection = pwddm_image_frame.state().get('selection');
                        var pwddm_gallery_ids = new Array();
                        var pwddm_index = 0;
                        pwddm_selection.each(
                            function(attachment) {
                                pwddm_gallery_ids[pwddm_index] = attachment['id'];
                                pwddm_index++;
                            }
                        );
                        var pwddm_ids = pwddm_gallery_ids.join(",");
                        jQuery('input#' + pwddm_object_id).val(pwddm_ids);
                        pwddm_refresh_image(pwddm_ids, pwddm_object_id);
                    }
                );

                pwddm_image_frame.on(
                    'open',
                    function() {
                        var pwddm_selection = pwddm_image_frame.state().get('selection');
                        var pwddm_ids = jQuery('input#' + pwddm_object_id).val().split(',');
                        pwddm_ids.forEach(
                            function(id) {
                                var pwddm_attachment = wp.media.attachment(id);
                                pwddm_attachment.fetch();
                                pwddm_selection.add(pwddm_attachment ? [pwddm_attachment] : []);
                            }
                        );

                    }
                );

                pwddm_image_frame.open();
            }
        );

        if ($(".pwddm-color-picker").length) {
            $(".pwddm-color-picker").wpColorPicker();
        }
        if ($(".pwddm-datepicker").length) {
            $(".pwddm-datepicker").datepicker({ dateFormat: "yy-mm-dd" });
        }

        $(".pwddm_account_icon").click(
            function() {
                var pwddm_manager_id = $(this).attr("manager_id");
                if ($(this).hasClass("pwddm_active")) {
                    $(this).removeClass("pwddm_active");
                    $(this).html("<i class='pwddm-toggle-off'></i>");
                    jQuery.post(
                        pwddm_ajax.ajaxurl, {
                            action: 'pwddm_ajax',
                            pwddm_service: 'pwddm_account_status',
                            pwddm_account_status: "0",
                            pwddm_manager_id: pwddm_manager_id,
                            pwddm_wpnonce: pwddm_nonce.nonce,
                            pwddm_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("pwddm_active");
                    $(this).html("<i class='pwddm-toggle-on'></i>");
                    jQuery.post(
                        pwddm_ajax.ajaxurl, {
                            action: 'pwddm_ajax',
                            pwddm_service: 'pwddm_account_status',
                            pwddm_account_status: "1",
                            pwddm_manager_id: pwddm_manager_id,
                            pwddm_wpnonce: pwddm_nonce.nonce,
                            pwddm_data_type: 'html'
                        }
                    );
                }
                return false;
            }
        );

        function checkbox_toggle(element) {
            if (!element.is(':checked')) {
                element.parent().next().hide();
            } else {
                element.parent().next().show();
            }

        }

        $(".checkbox_toggle input").click(
            function() {
                checkbox_toggle($(this))

            }
        );
        $(".checkbox_toggle input").each(
            function() {
                checkbox_toggle($(this))
            }
        );

        $(".pwddm_copy_template_to_textarea").click(
            function() {
                var textarea_id = $(this).parent().parent().find("textarea").attr("id");

                var text = $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        $(".pwddm_copy_tags_to_textarea a").click(
            function() {
                var textarea_id = $(this).parent().attr("data-textarea");
                var text = $("#" + textarea_id).val() + $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        $("#pwddm_custom_fields_new").click(
            function() {
                $("#pwddm_custom_fields_raw").clone().appendTo("#pwddm_custom_fields_table");
                return false;
            }
        );

    }
);




// Ajax request to refresh the image preview
function pwddm_refresh_image(the_id, div_id) {
    var data = {
        action: 'pwddm_ajax',
        pwddm_service: 'pwddm_set_image',
        pwddm_image_id: the_id,
        pwddm_wpnonce: pwddm_nonce.nonce,
    };
    jQuery.post(
        ajaxurl,
        data,
        function(response) {

            if (response.success === true) {
                jQuery('#' + div_id + '_preview').html(response.data.image);
            }
        }
    );
}