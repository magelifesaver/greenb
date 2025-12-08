(function($) {
    "use strict";





    $("body").on("mouseenter", ".pwddm_open_tooltip", function() {
        $(this).next().show();
    });
    $("body").on("mouseleave", ".pwddm_open_tooltip", function() {
        $(this).next().hide();
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

    if ($(".pwddm-datepicker").length) {
        $(".pwddm-datepicker").datepicker({ dateFormat: "yy-mm-dd" });
    }

    function pwddm_counters() {
        if (jQuery("#pwddm_page.dashboard").length) {
            var pwddm_unavailable_counter = jQuery(".pwddm_availability .text-danger").length;
            var pwddm_available_counter = jQuery(".pwddm_availability .text-success").length;
            var pwddm_unclaim_counter = jQuery(".pwddm_claim_permission .text-danger").length;
            var pwddm_claim_counter = jQuery(".pwddm_claim_permission .text-success").length;
            jQuery("#pwddm_available_counter").html(pwddm_available_counter);
            jQuery("#pwddm_claim_counter").html(pwddm_claim_counter);
            jQuery("#pwddm_unavailable_counter").html(pwddm_unavailable_counter);
            jQuery("#pwddm_unclaim_counter").html(pwddm_unclaim_counter);
        }
    }

    pwddm_counters();

    $(".orders #pwddm_orders_filter_btn").on(
        "click",
        function() {
            window.location.replace($("#pwddm_orders_form").attr("action") + "&pwddm_orders_filter=" + $("#pwddm_orders_filter").val() + "&pwddm_orders_status=" + $("#pwddm_orders_status").val() + "&pwddm_dates_range=" + $("#pwddm_dates_range").val());
            return false;
        }
    );

    jQuery("#cancel_password_button").on(
        "click",
        function() {
            jQuery("#pwddm_password_holder").hide();
            jQuery("#pwddm_password").val("");
        }
    );

    jQuery("#new_password_button").on(
        "click",
        function() {
            jQuery("#pwddm_password_holder").show();
            jQuery("#pwddm_password").val(Math.random().toString(36).slice(2));
        }
    );

    jQuery("#billing_state_select").on(
        "change",
        function() {
            jQuery("#billing_state_input").val(jQuery(this).val());
        }
    );
    jQuery("#billing_country").on(
        "change",
        function() {
            if (jQuery(this).val() == "US") {
                jQuery("#billing_state_select").show();
                jQuery("#billing_state_input").hide();
            } else {
                jQuery("#billing_state_input").show();
                jQuery("#billing_state_select").hide();
            }
        }
    );
    if (jQuery("#billing_country").length) {
        jQuery("#billing_country").trigger("change");
    }

    jQuery(".pwddm_user_icon").click(
        function() {
            var pwddm_driver_id = jQuery(this).attr("driver_id");
            var pwddm_service = jQuery(this).attr("service");
            var pwddm_icon = jQuery(this).find("svg")
            if (pwddm_icon.hasClass("text-success")) {

                pwddm_icon.removeClass("text-success");
                pwddm_icon.addClass("text-danger");
                jQuery.post(
                    pwddm_ajax_url, {
                        action: 'pwddm_ajax',
                        pwddm_service: pwddm_service,
                        pwddm_status: "0",
                        pwddm_manager_id: pwddm_manager_id,
                        pwddm_driver_id: pwddm_driver_id,
                        pwddm_wpnonce: pwddm_nonce,
                        pwddm_data_type: 'html'
                    }
                );
            } else {
                pwddm_icon.addClass("text-success");
                pwddm_icon.removeClass("text-danger");

                jQuery.post(
                    pwddm_ajax_url, {
                        action: 'pwddm_ajax',
                        pwddm_service: pwddm_service,
                        pwddm_status: "1",
                        pwddm_manager_id: pwddm_manager_id,
                        pwddm_driver_id: pwddm_driver_id,
                        pwddm_wpnonce: pwddm_nonce,
                        pwddm_data_type: 'html'
                    }
                );
            }
            pwddm_counters();
            return false;
        }
    );

    function scrolltoelement(element) {
        jQuery('html, body').animate({
                scrollTop: element.offset().top - 300
            },
            1000
        );
    }



    jQuery(".pwddm_form").validate({
        submitHandler: function(form) {
            var pwddm_form = jQuery(form);
            var pwddm_loading_btn = pwddm_form.find(".pwddm_loading_btn")
            var pwddm_submit_btn = pwddm_form.find(".pwddm_submit_btn")
            var pwddm_alert_wrap = pwddm_form.find(".pwddm_alert_wrap");
            var pwddm_service = pwddm_form.attr("service");
            pwddm_submit_btn.hide();
            pwddm_loading_btn.show();
            pwddm_alert_wrap.html("");
            jQuery.ajax({
                type: "POST",
                url: pwddm_ajax_url,
                data: pwddm_form.serialize() + '&action=pwddm_ajax&pwddm_service=' + pwddm_service + '&pwddm_wpnonce=' + pwddm_nonce + '&pwddm_data_type=json',
                success: function(data) {
                    try {
                        var pwddm_json = JSON.parse(data);
                        if (pwddm_json["result"] == "0") {
                            pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + pwddm_json["error"] + "</div>");
                            pwddm_submit_btn.show();
                            pwddm_loading_btn.hide();
                            scrolltoelement(pwddm_alert_wrap);
                        }
                        if (pwddm_json["result"] == "1") {
                            var pwddm_hide_on_success = pwddm_form.find(".pwddm_hide_on_success");
                            if (pwddm_hide_on_success.length) {
                                pwddm_hide_on_success.replaceWith("");
                            }
                            pwddm_alert_wrap.html("<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">" + pwddm_json["error"] + "</div>");
                            pwddm_submit_btn.show();
                            pwddm_loading_btn.hide();
                            scrolltoelement(pwddm_alert_wrap);
                        }

                    } catch (e) {
                        pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                        pwddm_submit_btn.show();
                        pwddm_loading_btn.hide();
                        scrolltoelement(pwddm_alert_wrap);
                    }
                },
                error: function(request, status, error) {
                    pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                    pwddm_submit_btn.show();
                    pwddm_loading_btn.hide();
                    scrolltoelement(pwddm_alert_wrap);
                }
            });

            return false;
        }
    });

    $("body").on("change", "#pwddm_orders_status", function() {
        $(".pwddm_dates_range_col").hide();
        if ($(this).val() == $("#pwddm_orders_status option:last").val()) {
            $(".pwddm_dates_range_col").show();
        }
    });

    $("#pwddm_multi_checkbox").click(
        function() {
            var pwddm_chk_class = jQuery(this).attr("data");
            if ($(this).prop("checked") == true) {
                $('.' + pwddm_chk_class).each(
                    function() {
                        $(this).prop("checked", true);
                    }
                );
            } else {
                $('.' + pwddm_chk_class).each(
                    function() {
                        $(this).prop("checked", false);
                    }
                );
            }

        }
    );

    jQuery(".pwddm_multi_checkbox .pwddm_wrap").click(
        function() {
            var pwddm_chk = jQuery(this).find(".custom-control-input");
            if (pwddm_chk.prop("checked") == true) {
                jQuery(this).parents(".pwddm_multi_checkbox").removeClass("pwddm_active");
                pwddm_chk.prop("checked", false);
            } else {
                jQuery(this).parents(".pwddm_multi_checkbox").addClass("pwddm_active");
                pwddm_chk.prop("checked", true);
            }
        }
    );

    jQuery("#pwddm_start").click(
        function() {
            jQuery("#pwddm_home").hide();
            jQuery("#pwddm_login").show();
        }
    );

    jQuery("#pwddm_login_button").click(
        function() {
            // hide the sign up button
            jQuery("#pwddm_signup_button").hide();
            // show the login form
            jQuery("#pwddm_login_wrap").toggle();
            return false;
        }
    );

    /*
    	jQuery("#pwddm_dates_range").change(
    		function() {
    			var pwddm_location = jQuery(this).attr("data") + '&pwddm_dates=' + this.value;
    			window.location.replace(pwddm_location);
    			return false;
    		}
    	);
    */
    if (pwddm_dates != "") {
        jQuery("#pwddm_dates_range").val(pwddm_dates);
    }

    function pwddm_delivered_screen_open() {
        jQuery("#pwddm_manager_complete_btn").show();
        jQuery(".pwddm_page_content").hide();
        jQuery("#pwddm_delivery_signature").hide();
        jQuery("#pwddm_delivery_photo").hide();
        jQuery("#pwddm_delivered_form").hide();
        jQuery("#pwddm_failed_delivery_form").hide();
        jQuery(".delivery_proof_bar a").removeClass("active");
        jQuery(".delivery_proof_bar a").eq(0).addClass("active");
    }

    jQuery("#pwddm_delivered_screen_btn").click(
        function() {
            jQuery("#pwddm_manager_complete_btn").attr("delivery", "success");
            jQuery(".delivery_proof_notes").attr("href", "pwddm_delivered_form");
            pwddm_delivered_screen_open();
            jQuery("#pwddm_delivered_form").show();
            jQuery("#pwddm_delivery_screen").show();
            return false;
        }
    );

    jQuery("#pwddm_failed_delivered_screen_btn").click(
        function() {
            jQuery("#pwddm_manager_complete_btn").attr("delivery", "failed");
            jQuery(".delivery_proof_notes").attr("href", "pwddm_failed_delivery_form");
            pwddm_delivered_screen_open();
            jQuery("#pwddm_failed_delivery_form").show();
            jQuery("#pwddm_delivery_screen").show();
            return false;
        }
    );

    jQuery(".pwddm_dashboard .pwddm_box a").click(
        function() {
            jQuery(this).parent().addClass("pwddm_active");
        }
    );

    jQuery(".pwddm_confirmation .pwddm_cancel").click(
        function() {
            jQuery(".pwddm_page_content").show();
            jQuery(this).parents(".pwddm_lightbox").hide();
            return false;
        }
    );

    if (jQuery("#pwddm_delivered_form .custom-control.custom-radio").length == 1) {
        jQuery("#pwddm_delivered_form .custom-control.custom-radio").hide();
    }
    if (jQuery("#pwddm_failed_delivery_form .custom-control.custom-radio").length == 1) {
        jQuery("#pwddm_failed_delivery_form .custom-control.custom-radio").hide();
    }

    jQuery("#pwddm_manager_complete_btn").click(
        function() {
            jQuery("#pwddm_delivery_screen").hide();
            if (jQuery(this).attr("delivery") == "success") {
                jQuery("#pwddm_delivered_confirmation").show();
            } else {
                jQuery("#pwddm_failed_delivery_confirmation").show();
            }
            return false;
        }
    );
    jQuery("#pwddm_failed_delivery_confirmation .pwddm_ok").click(
        function() {

            var pwddm_reason = jQuery('input[name=pwddm_delivery_failed_reason]:checked', '#pwddm_failed_delivery_form');
            if (pwddm_reason.attr("id") != "pwddm_delivery_failed_6") {
                jQuery("#pwddm_manager_note").val(pwddm_reason.val());
            }

            jQuery("#pwddm_failed_delivery").hide();
            jQuery("#pwddm_thankyou").show();

            var pwddm_orderid = jQuery("#pwddm_manager_complete_btn").attr("order_id");

            var pwddm_signature = '';
            var pwddm_delivery_image = '';
            /* <fs_premium_only> */
            pwddm_signature = pwddm_get_signature();
            pwddm_resizeImage(pwddm_signature, 640, 640).then(
                (result) => {
                    pwddm_signature = result;
                }
            );
            pwddm_delivery_image = jQuery('#delivery_image').val();
            /* </fs_premium_only> */

            jQuery.ajax({
                type: "POST",
                url: pwddm_ajax_url,
                data: {
                    action: 'pwddm_ajax',
                    pwddm_service: 'pwddm_status',
                    pwddm_order_id: pwddm_orderid,
                    pwddm_order_status: jQuery("#pwddm_manager_complete_btn").attr("failed_status"),
                    pwddm_manager_id: pwddm_manager_id,
                    pwddm_note: jQuery("#pwddm_manager_note").val(),
                    pwddm_wpnonce: pwddm_nonce,
                    pwddm_data_type: 'html',
                    pwddm_signature: pwddm_signature,
                    pwddm_delivery_image: pwddm_delivery_image
                },
                success: function(data) {
                    /* <fs_premium_only> */
                    pwddm_next_delivery_service();
                    /* </fs_premium_only> */
                },
                error: function(request, status, error) {}
            });

            return false;
        }
    );

    jQuery("#pwddm_delivered_form input[type=radio]").click(
        function() {
            jQuery("#pwddm_manager_delivered_note").val("");
            if (jQuery(this).attr("id") == "pwddm_delivery_dropoff_other") {
                jQuery("#pwddm_manager_delivered_note_wrap").show();
            } else {
                jQuery("#pwddm_manager_delivered_note_wrap").hide();
            }
        }
    );

    jQuery("#pwddm_failed_delivery_form input[type=radio]").click(
        function() {
            jQuery("#pwddm_manager_note").val("");
            if (jQuery(this).attr("id") == "pwddm_delivery_failed_6") {
                jQuery("#pwddm_manager_note_wrap").show();
            } else {
                jQuery("#pwddm_manager_note_wrap").hide();
            }
        }
    );

    jQuery(".pwddm_lightbox_close,#pwddm_manager_cancel_btn").click(
        function() {
            jQuery(".pwddm_page_content").show();
            jQuery(this).parents(".pwddm_lightbox").hide();
            return false;
        }
    );



    jQuery("#pwddm_edit_driver").submit(
        function(e) {
            var pwddm_form = jQuery(this);
            var pwddm_loading_btn = pwddm_form.find(".pwddm_loading_btn")
            var pwddm_submit_btn = pwddm_form.find(".pwddm_submit_btn")
            var pwddm_alert_wrap = pwddm_form.find(".pwddm_alert_wrap");



            pwddm_submit_btn.hide();
            pwddm_loading_btn.show();
            pwddm_alert_wrap.html("");
        });

    jQuery("#pwddm_login_frm").submit(
        function(e) {
            e.preventDefault();

            var pwddm_form = jQuery(this);
            var pwddm_loading_btn = pwddm_form.find(".pwddm_loading_btn")
            var pwddm_submit_btn = pwddm_form.find(".pwddm_submit_btn")
            var pwddm_alert_wrap = pwddm_form.find(".pwddm_alert_wrap");

            var pwddm_nextpage = pwddm_form.attr('nextpage');

            pwddm_submit_btn.hide();
            pwddm_loading_btn.show();
            pwddm_alert_wrap.html("");

            jQuery.ajax({
                type: "POST",
                url: pwddm_ajax_url,
                data: {
                    action: 'pwddm_ajax',
                    pwddm_service: 'pwddm_login',
                    pwddm_login_email: jQuery("#pwddm_login_email").val(),
                    pwddm_login_password: jQuery("#pwddm_login_password").val(),
                    pwddm_wpnonce: pwddm_nonce,
                    pwddm_data_type: 'json'
                },
                success: function(data) {
                    var pwddm_json = JSON.parse(data);
                    if (pwddm_json["result"] == "0") {
                        pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + pwddm_json["error"] + "</div>");
                        pwddm_submit_btn.show();
                        pwddm_loading_btn.hide();
                    }
                    if (pwddm_json["result"] == "1") {
                        window.location.replace(pwddm_nextpage);
                    }
                },
                error: function(request, status, error) {
                    pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + status + ' ' + error + "</div>");
                    pwddm_submit_btn.show();
                    pwddm_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#pwddm_back_to_forgot_password_link").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_forgot_password").show();
        }
    );
    jQuery("#pwddm_login_button").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_login").show();
        }
    );
    jQuery("#pwddm_new_password_login_link").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_login").show();
        }
    );
    jQuery("#pwddm_new_password_reset_link").click(
        function() {
            jQuery("#pwddm_create_new_password").hide();
            jQuery("#pwddm_forgot_password").show();
        }
    );
    jQuery("#pwddm_forgot_password_link").click(
        function() {
            jQuery("#pwddm_login").hide();
            jQuery("#pwddm_forgot_password").show();
        }
    );
    jQuery(".pwddm_back_to_login_link").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_login").show();

        }
    );
    jQuery("#pwddm_resend_button").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_forgot_password").show();
        }
    );
    jQuery("#pwddm_application_link").click(
        function() {
            jQuery(".pwddm_page").hide();
            jQuery("#pwddm_application").show();
        }
    );

    jQuery("#pwddm_forgot_password_frm").submit(
        function(e) {
            e.preventDefault();

            var pwddm_form = jQuery(this);
            var pwddm_loading_btn = pwddm_form.find(".pwddm_loading_btn");
            var pwddm_submit_btn = pwddm_form.find(".pwddm_submit_btn");
            var pwddm_alert_wrap = pwddm_form.find(".pwddm_alert_wrap");

            pwddm_submit_btn.hide();
            pwddm_loading_btn.show();
            pwddm_alert_wrap.html("");

            var pwddm_nextpage = pwddm_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: pwddm_ajax_url,
                data: {
                    action: 'pwddm_ajax',
                    pwddm_service: 'pwddm_forgot_password',
                    pwddm_user_email: jQuery("#pwddm_user_email").val(),
                    pwddm_wpnonce: pwddm_nonce,
                    pwddm_data_type: 'json'

                },
                success: function(data) {
                    var pwddm_json = JSON.parse(data);

                    if (pwddm_json["result"] == "0") {
                        pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + pwddm_json["error"] + "</div>");
                        pwddm_submit_btn.show();
                        pwddm_loading_btn.hide();
                    }
                    if (pwddm_json["result"] == "1") {
                        jQuery(".pwddm_page").hide();
                        jQuery("#pwddm_forgot_password_email_sent").show();

                        pwddm_submit_btn.show();
                        pwddm_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    pwddm_submit_btn.show();
                    pwddm_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#pwddm_new_password_frm").submit(
        function(e) {
            e.preventDefault();

            var pwddm_form = jQuery(this);
            var pwddm_loading_btn = pwddm_form.find(".pwddm_loading_btn");
            var pwddm_submit_btn = pwddm_form.find(".pwddm_submit_btn");
            var pwddm_alert_wrap = pwddm_form.find(".pwddm_alert_wrap");

            pwddm_submit_btn.hide();
            pwddm_loading_btn.show();
            pwddm_alert_wrap.html("");

            var pwddm_nextpage = pwddm_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: pwddm_ajax_url,
                data: {
                    action: 'pwddm_ajax',
                    pwddm_service: 'pwddm_newpassword',
                    pwddm_new_password: jQuery("#pwddm_new_password").val(),
                    pwddm_confirm_password: jQuery("#pwddm_confirm_password").val(),
                    pwddm_reset_key: jQuery("#pwddm_reset_key").val(),
                    pwddm_reset_login: jQuery("#pwddm_reset_login").val(),
                    pwddm_wpnonce: pwddm_nonce,
                    pwddm_data_type: 'json'
                },

                success: function(data) {
                    var pwddm_json = JSON.parse(data);
                    if (pwddm_json["result"] == "0") {
                        pwddm_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + pwddm_json["error"] + "</div>");
                        pwddm_submit_btn.show();
                        pwddm_loading_btn.hide();
                    }
                    if (pwddm_json["result"] == "1") {
                        jQuery(".pwddm_page").hide();
                        jQuery("#pwddm_new_password_created").show();

                    }
                },
                error: function(request, status, error) {
                    pwddm_submit_btn.show();
                    pwddm_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("body").on(
        "click",
        "#pwddm_orders_table .pwddm_box a",
        function() {
            jQuery(this).closest(".pwddm_box").addClass("pwddm_active");
        }
    );
    /* <fs_premium_only> */
    jQuery("body").on(
        "click",
        "#delivery-image-clear",
        function() {
            jQuery("#delivery_image_wrap").html("");
            jQuery("#delivery_image").val("");
            return false;
        }
    );
    /* </fs_premium_only> */

})(jQuery);

function pwddm_openNav() {
    jQuery(".pwddm_page_content").hide();
    document.getElementById("pwddm_mySidenav").style.width = "100%";
}

function pwddm_closeNav() {
    jQuery(".pwddm_page_content").show();
    document.getElementById("pwddm_mySidenav").style.width = "0";
}


function pwddm_resizeImage(base64Str, maxWidth = 1000, maxHeight = 1000) {
    return new Promise(
        (resolve) => {
            let pwddm_img = new Image()
            pwddm_img.src = base64Str
            pwddm_img.onload = () => {
                let pwddm_image_canvas = document.createElement('canvas')
                const MAX_WIDTH = maxWidth
                const MAX_HEIGHT = maxHeight
                let pwddm_width = pwddm_img.width
                let pwddm_height = pwddm_img.height

                if (pwddm_width > pwddm_height) {
                    if (pwddm_width > MAX_WIDTH) {
                        pwddm_height *= MAX_WIDTH / pwddm_width
                        pwddm_width = MAX_WIDTH
                    }
                } else {
                    if (pwddm_height > MAX_HEIGHT) {
                        pwddm_width *= MAX_HEIGHT / pwddm_height
                        pwddm_height = MAX_HEIGHT
                    }
                }
                pwddm_image_canvas.width = pwddm_width
                pwddm_image_canvas.height = pwddm_height
                let pwddm_ctx = pwddm_image_canvas.getContext('2d')
                pwddm_ctx.drawImage(pwddm_img, 0, 0, pwddm_width, pwddm_height)
                resolve(pwddm_image_canvas.toDataURL())
            }
        }
    )
}

function pwddm_resizeCanvas() {
    if (pwddm_signaturePad.isEmpty()) {
        var pwddm_ratio = Math.max(window.devicePixelRatio || 1, 1);
        pwddm_canvas.width = pwddm_canvas.offsetWidth;
        pwddm_canvas.height = pwddm_canvas.offsetHeight;
        pwddm_canvas.getContext("2d").scale(1, 1);
        pwddm_signaturePad.clear();
    }
}

if (jQuery("#signature-pad").length) {
    var pwddm_canvas = document.getElementById('signature-pad');
    var pwddm_signaturePad = new SignaturePad(
        pwddm_canvas, {
            backgroundColor: '#ffffff'
        }
    );

    jQuery(".signature-clear").click(
        function() {
            jQuery("#signature-image").html("");
            pwddm_signaturePad.clear();
            pwddm_resizeCanvas();
            return false;
        }
    );

    window.onresize = pwddm_resizeCanvas;
    pwddm_resizeCanvas();
}

jQuery(".pwddm_upload_image").change(
    function(e) {
        var $this = jQuery(this);
        if (this.files && this.files[0]) {
            var pwddm_reader = new FileReader();
            pwddm_reader.onload = function(e) {
                pwddm_resizeImage(e.target.result, 640, 640).then(
                    (result) => {
                        $this.parents(".upload_image_form").find(".upload_image_wrap").html("<span class='pwddm_helper'></span><img src='" + result + "'>");
                        $this.parent().find(".pwddm_image_input").val(result);
                    }
                );
            }
            pwddm_reader.readAsDataURL(this.files[0]);
        }
    }
);

function pwddm_driver_commission_type() {
    console.log("**");
    jQuery("#pwddm_driver_commission_symbol_currency").hide();
    jQuery("#pwddm_driver_commission_symbol_percentage").hide();
    var $pwddm_val = jQuery("#pwddm_driver_commission_type").val();
    if ($pwddm_val == "") {
        jQuery("#pwddm_driver_commission_value_wrap").hide();
        jQuery("#pwddm_driver_commission_value").val("");
    } else {
        jQuery("#pwddm_driver_commission_value_wrap").show();
        if ($pwddm_val == "fixed") {
            jQuery("#pwddm_driver_commission_symbol_currency").show();
        } else {
            jQuery("#pwddm_driver_commission_symbol_percentage").show();
        }
    }
}

jQuery("#pwddm_driver_commission_type").change(
    function() {
        pwddm_driver_commission_type()
    }
);

if (jQuery("#pwddm_driver_commission_type").length) {
    pwddm_driver_commission_type();
}

jQuery(".pwddm_user_icon_disable").click(
    function() {
        return false;
    }
);

var route_timer;

//switch lazyload
jQuery("img.lazyload").each(function() {
    var $lddfw_src = jQuery(this).attr("data-src");
    jQuery(this).attr("src", $lddfw_src);
});
jQuery("iframe.lazyload").each(function() {
    var $lddfw_src = jQuery(this).attr("data-src");
    jQuery(this).attr("src", $lddfw_src);
});