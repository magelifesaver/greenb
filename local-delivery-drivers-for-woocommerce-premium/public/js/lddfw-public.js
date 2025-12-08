// Define SVG icons (assuming similar style to existing icons)
var lddfw_loader_icon_svg  = '<svg class="lddfw_status_icon lddfw_loader_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" style="background: none; shape-rendering: auto;" width="20px" height="20px"><circle cx="50" cy="50" r="32" stroke-width="8" stroke="#fff" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform></circle></svg>';
var lddfw_success_icon_svg = '<svg class="lddfw_status_icon lddfw_success_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="20px" height="20px"><circle cx="26" cy="26" r="25" fill="none"   stroke-width="4"/><path fill="none" stroke="#fff" stroke-width="4" d="M14 27l8 8 16-16"/></svg>';
var lddfw_failure_icon_svg = '<svg class="lddfw_status_icon lddfw_failure_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="20px" height="20px"><circle cx="26" cy="26" r="25" fill="none"   stroke-width="4"/><path fill="none" stroke="#fff" stroke-width="4" d="M16 16 36 36 M36 16 16 36"/></svg>';
var lddfw_trash_icon_svg   = '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg>';

(function() {
    "use strict";

    
 

    jQuery(window).bind("pageshow", function(event) {
        lddfw_hide_loader();
    });

    jQuery('body').on('click', '.lddfw_loader', function() {
        lddfw_show_loader(jQuery(this));
    });

    /* <fs_premium_only> */
    function lddfw_get_signature() {
        var lddfw_signature = "";
        if (typeof lddfw_signaturePad === "undefined") {} else {
            if (!lddfw_signaturePad.isEmpty()) {
                var today = new Date();
                var date = today.getFullYear() + '-' + ('0' + (today.getMonth() + 1)).slice(-2) + '-' + ('0' + today.getDate()).slice(-2);
                var time = ('0' + today.getHours()).slice(-2) + ":" + ('0' + today.getMinutes()).slice(-2) + ":" + ('0' + today.getSeconds()).slice(-2);
                var dateTime = date + ' ' + time;
                var lddfw_canvas = document.getElementById('signature-pad');
                var context = lddfw_canvas.getContext('2d');
                var input = document.getElementById('signature_name');
                context.font = '16px arial';
                context.strokeText(dateTime + " " + input.value, 10, 25);
                lddfw_signature = lddfw_signaturePad.toDataURL();
            }
        }
        return lddfw_signature;
    }

    var screen_timer;

    function lddfw_get_assign_to_driver() {
        clearTimeout(screen_timer);
        var lddfw_order_list = '';
        jQuery('.lddfw_order_checkbox .custom-control-input').each(
            function(index, item) {
                if (jQuery(this).prop("checked") == true) {

                    if (lddfw_order_list != "") {
                        lddfw_order_list = lddfw_order_list + ",";
                    }
                    lddfw_order_list = lddfw_order_list + jQuery(this).val();
                }
            });

        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_get_assign_to_driver',
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            },
            success: function(data) {
                jQuery("#lddfw_assign_to_driver_orders").html(data);
                if (lddfw_order_list != "") {
                    const lddfw_order_array = lddfw_order_list.split(',');
                    for (const val of lddfw_order_array) {
                        jQuery("#lddfw_chk_order_id_" + val).prop("checked", true);
                        console.log("#lddfw_chk_order_id_" + val);
                    }
                }
                screen_timer = setTimeout(function() {
                    lddfw_get_assign_to_driver();
                }, 120000);

            },
            error: function(request, status, error) {}
        })
    }

    if (jQuery("#lddfw_page.assign_to_driver").length) {
        screen_timer = setTimeout(function() {
            lddfw_get_assign_to_driver();
        }, 120000);
    }

    function lddfw_next_delivery_service() {
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_next_delivery',
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'json'
            },
            success: function(data) {
                var lddfw_json = JSON.parse(data);
                if (lddfw_json["result"] == "0") {
                    jQuery("#lddfw_next_delivery").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                } else {
                    jQuery("#lddfw_next_delivery").html(lddfw_json["shipping_address"]);
                }
            },
            error: function(request, status, error) {}
        })
    }

    jQuery("#signature-done").click(function() {
        jQuery("#signature-image").html("");

        if (typeof lddfw_signaturePad === "undefined") {} else {
            if (!lddfw_signaturePad.isEmpty()) {
                var lddfw_signature = lddfw_signaturePad.toDataURL();
                lddfw_resizeImage(lddfw_signature, 640, 640).then((result) => {
                    jQuery('#signature-image').html("<span class='lddfw_helper'></span><img src='" + lddfw_signature + "'>");
                });
            }
        }
        jQuery(".signature-wrapper").hide();
    });


    jQuery("#lddfw_order_unassign_button").click(
        function() {

            jQuery("#lddfw_unassign_alert").hide();
            jQuery("#lddfw_unassign_success").hide();
            jQuery("#lddfw_unassign_failed").hide();
            jQuery("#lddfw_unassign_alert .lddfw_notice").html();

            var lddfw_order_unassign_button = jQuery(this);
            var lddfw_unassign_button_loading = lddfw_order_unassign_button.next();
            lddfw_order_unassign_button.hide();
            lddfw_unassign_button_loading.show();
            var lddfw_order_id = lddfw_order_unassign_button.attr("data");

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_unassign_driver',
                    lddfw_orders_list: lddfw_order_id,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {
                    jQuery("#lddfw_unassign_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_unassign_failed").show();
                        jQuery("#lddfw_unassign_failed .lddfw_notice").html("<div class=\"alert alert-warning fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery("#lddfw_unassign_success").show();
                        jQuery("#lddfw_unassign_success .lddfw_notice").html('<h2>' + lddfw_json["error"] + '</h2>');
                    }
                }
            );
            return false;
        });

    jQuery("#lddfw_order_out_for_delivery_button").click(
        function() {
            jQuery("#lddfw_order_out_for_delivery_failed").hide();
            jQuery("#lddfw_order_out_for_delivery_failed_alert").html("");
            var lddfw_order_out_for_delivery_button = jQuery(this);
            var lddfw_order_out_for_delivery_button_loading = lddfw_order_out_for_delivery_button.next();
            lddfw_order_out_for_delivery_button.hide();
            lddfw_order_out_for_delivery_button_loading.show();
            var lddfw_order_id = lddfw_order_out_for_delivery_button.attr("data");

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_out_for_delivery',
                    lddfw_orders_list: lddfw_order_id,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {
                    jQuery("#lddfw_order_out_for_delivery_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_order_out_for_delivery_failed").show();
                        jQuery("#lddfw_order_out_for_delivery_failed_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery("#lddfw_order_out_for_delivery_success").show();
                    }
                }
            );
            return false;

        });


    jQuery("#lddfw_claim_order_button").click(
        function() {
            jQuery("#lddfw_order_claim_alert").hide();
            jQuery("#lddfw_order_claim_failed_alert").html("");
            jQuery("#lddfw_claim_order_button").hide();
            jQuery("#lddfw_claim_order_button_loading").show();
            var lddfw_order_id = jQuery(this).attr("data");
            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_claim_orders',
                    lddfw_orders_list: lddfw_order_id,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {
                    jQuery("#lddfw_order_claim_alert").show();
                    var lddfw_json = JSON.parse(data);
                   
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_order_claim_failed").show();
                        jQuery("#lddfw_order_claim_failed_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                    }

                    if (lddfw_json["result"] == "3") {
                        jQuery("#lddfw_order_claim_failed").show();
                        jQuery("#lddfw_order_claim_failed_alert").html( lddfw_json["error"] );
                    }

                    if (lddfw_json["result"] == "1") {
                        jQuery("#lddfw_order_claim_success").show();
                    }
                }
            );
            return false;
        }
    );

    jQuery("#lddfw_claim_orders_button").click(
        function() {

            jQuery("#lddfw_claim_orders_button").hide();
            jQuery("#lddfw_claim_orders_button_loading").show();

            var lddfw_order_list = '';
            jQuery("#lddfw_alert").html();
            jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                function(index, item) {
                    if (jQuery(this).prop("checked") == true) {

                        if (lddfw_order_list != "") {
                            lddfw_order_list = lddfw_order_list + ",";
                        }
                        lddfw_order_list = lddfw_order_list + jQuery(this).val();
                    }

                }
            );

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_claim_orders',
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {

                    jQuery("#lddfw_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                    }
 
                    if (lddfw_json["result"] == "1" || lddfw_json["result"] == "2" || lddfw_json["result"] == "3" ) {
                        jQuery(this).parents(".lddfw_multi_checkbox").hide();
                        jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                            function(index, item) {
                                if (jQuery(this).prop("checked") == true) {
                                    jQuery(this).parents(".lddfw_multi_checkbox").replaceWith("");
                                }
                            }
                        );
                        jQuery("#lddfw_alert").html(lddfw_json["error"]);
                        if (jQuery('.lddfw_multi_checkbox').length == 0) {
                            jQuery(".lddfw_footer_buttons").hide();
                        }
                    }

                    jQuery("#lddfw_claim_orders_button").show();
                    jQuery("#lddfw_claim_orders_button_loading").hide();
                }
            );
            return false;
        }
    );


    function lddfw_sortroute_on() {
        var lddfw_sortroute_btn = jQuery("#lddfw_sortroute_btn");
        lddfw_sortroute_btn.addClass("lddfw_active");
        lddfw_sortroute_btn.addClass("btn-primary");
        lddfw_sortroute_btn.removeClass("btn-secondary");
        lddfw_sortroute_btn.html(lddfw_sortroute_btn.attr("data-finish"));
        jQuery(".lddfw_handle_column").show();
        jQuery("#route_origin_label").hide();
        jQuery("#route_origin_div").show();
        jQuery(".lddfw_order_view").hide();
        jQuery("#route_destination_label").hide();
        jQuery("#route_destination_div").show();
    }

    function lddfw_sortroute_off() {
        var lddfw_sortroute_btn = jQuery("#lddfw_sortroute_btn");
        lddfw_sortroute_btn.removeClass("lddfw_active");
        lddfw_sortroute_btn.removeClass("btn-primary");
        lddfw_sortroute_btn.addClass("btn-secondary");
        lddfw_sortroute_btn.html(lddfw_sortroute_btn.attr("data-start"));
        jQuery(".lddfw_handle_column").hide();
        jQuery("#route_origin_label").show();
        jQuery("#route_origin_div").hide();
        jQuery("#route_destination_label").show();
        jQuery("#route_destination_div").hide();
        jQuery(".lddfw_order_view").show();
    }


    jQuery("#lddfw_sortroute_btn").click(
        function() {
            jQuery("#lddfw_plain_route_note_info").hide();
            if (jQuery(this).hasClass("lddfw_active")) {
                lddfw_sortroute_off()
            } else {
                lddfw_sortroute_on()
            }
        });

    jQuery("body").on("change", "#route_origin,#route_destination", function() {
        var origin_map_address = jQuery("#route_origin").val();
        var origin_address = jQuery("#route_origin").find('option:selected').text();
        var destination_map_address = jQuery("#route_destination").val();
        var destination_address = jQuery("#route_destination").find('option:selected').text();

        jQuery.ajax({
            url: lddfw_ajax_url,
            type: 'POST',
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_set_route',
                lddfw_origin_map_address: origin_map_address,
                lddfw_origin_address: origin_address,
                lddfw_destination_map_address: destination_map_address,
                lddfw_destination_address: destination_address,
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            }
        }).done(
            function(data) {
                if (data.trim() == '1') {
                    jQuery("#route_origin_label").html(origin_address);
                    jQuery("#route_destination_label").html(destination_address);
                }
            });
    });

    var route_timer;
    jQuery("#lddfw_plainroute_btn").click(
        function() {
            clearTimeout(route_timer);

            var lddfw_origin = jQuery("#route_origin").val();
            var lddfw_destination = jQuery("#route_destination").val();
            var lddfw_plainroute_btn = jQuery(this);
            var lddfw_loading_btn = jQuery("#lddfw_plain_route_row .lddfw_loading_btn");
            var lddfw_done_btn = jQuery("#lddfw_plain_route_row .lddfw_done_btn");
            var lddfw_plain_route_note_info = jQuery("#lddfw_plain_route_note_info");

            lddfw_sortroute_off();
            lddfw_done_btn.hide();
            lddfw_plainroute_btn.hide();
            lddfw_loading_btn.show();
            lddfw_plain_route_note_info.hide();
            jQuery(this).addClass("lddfw_active");
            jQuery("#lddfw_plain_route_note_wait").show();

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_plain_route',
                    lddfw_origin: lddfw_origin,
                    lddfw_destination: lddfw_destination,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html'
                }
            }).done(
                function(data) {
                    jQuery("#lddfw_plain_route_note_wait").hide();
                    jQuery("#lddfw_plain_route_container").html(data);
                    jQuery("#lddfw_plainroute_btn").removeClass("lddfw_active");
                    lddfw_sortroute_off();
                    lddfw_loading_btn.hide();
                    lddfw_done_btn.show();
                    lddfw_plain_route_note_info.show().delay(8000).hide(0);
                    route_timer = setTimeout(function() {
                        lddfw_done_btn.hide();
                        lddfw_plainroute_btn.show();
                    }, 3000);
                }
            ).fail(function (jqXHR, status, errorThrown) {
                jQuery("#lddfw_plain_route_note_wait").hide();
                lddfw_loading_btn.hide();
                lddfw_plainroute_btn.show();
                jQuery("#lddfw_plainroute_btn").removeClass("lddfw_active");
                window.alert(lddfw_alert_texts.directions_request_failed + ' ' + status);
            });

            return false;
        }
    );

    jQuery("#lddfw_application_frm").validate({
        submitHandler: function(form) {

            var lddfw_form = jQuery(form);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");


            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_application',
                    lddfw_application_fullname: jQuery("#lddfw_application_fullname").val(),
                    lddfw_application_phone: jQuery("#lddfw_application_phone").val(),
                    lddfw_application_email: jQuery("#lddfw_application_email").val(),
                    lddfw_application_message: jQuery("#lddfw_application_message").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_application_thankyou").show();

                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    });

    jQuery("#lddfw_route_btn").click(
        function() {
            lddfw_sortroute_off();
            if (jQuery("#lddfw_google_map_script").length == 0) {
                jQuery("body").append(
                    "<script " +
                      "id='lddfw_google_map_script' " +
                      "async defer " +
                      "src='https://maps.googleapis.com/maps/api/js" +
                        "?key="       + lddfw_google_api_key   +
                        "&language="  + lddfw_map_language    +
                        "&callback="  + "lddfw_initMap"       +
                        "&loading=async" +                     
                    "'></script>"
                  );
            } else {
                lddfw_initMap();
            }
            jQuery(".lddfw_page_content").hide();
            jQuery("#lddfw_directions").show();
            return false;
        }
    );
    jQuery("#lddfw_hide_map_btn").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery("#lddfw_directions").hide();
            return false;

        }
    );

    function lddfw_route_update() {

        var lddfw_couter = 1;
        var lddfw_order_list = "";
        var lddfw_origin = jQuery("#route_origin").val();
        jQuery("#lddfw_orders_table .lddfw_index").each(
            function() {
                jQuery(this).html(lddfw_couter);
                lddfw_couter = lddfw_couter + 1;
                lddfw_order_list = lddfw_order_list + jQuery(this).parent().find(".lddfw_address_chk").attr("orderid") + ",";
            }
        );
        jQuery.ajax({
            url: lddfw_ajax_url,
            type: 'POST',
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_sort_orders',
                lddfw_orders_list: lddfw_order_list,
                lddfw_origin: lddfw_origin,
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            },
            success: function(data) {
                if (data.trim() != "1") {
                    jQuery("#lddfw_plain_route_container").html(data);
                }
            }
        });

    }

    function lddfw_route_handle() {
        jQuery("#lddfw_plain_route_container .lddfw_handle_column a").show();
    }

    jQuery("body").on("click", "#lddfw_plain_route_container .lddfw_sort-up", function() {
        clearTimeout(route_timer);
        jQuery("#lddfw_orders_table .lddfw_box").removeClass("lddfw_active");
        var lddfw_elem = jQuery(this).closest(".lddfw_box");
        lddfw_elem.prev().before(lddfw_elem)
        lddfw_elem.addClass("lddfw_active");
        route_timer = setTimeout(function() {
            lddfw_elem.removeClass("lddfw_active");
        }, 2000);
        lddfw_route_update();
        lddfw_route_handle();
    });

    jQuery("body").on("click", "#lddfw_plain_route_container .lddfw_sort-down", function() {
        clearTimeout(route_timer);
        jQuery("#lddfw_orders_table .lddfw_box").removeClass("lddfw_active");
        var lddfw_elem = jQuery(this).closest(".lddfw_box");
        lddfw_elem.next().after(lddfw_elem)
        lddfw_elem.addClass("lddfw_active");
        route_timer = setTimeout(function() {
            lddfw_elem.removeClass("lddfw_active");
        }, 2000);
        lddfw_route_update();
        lddfw_route_handle();
    });


    jQuery(".lddfw_product_line").click(function() {
        jQuery(this).parent().find(".lddfw_lightbox").show();
    });

    /* </fs_premium_only> */

    jQuery("body").on("click", "#lddfw-panel-listing-toggle", function() {
        jQuery("#lddfw_directions-panel-lightbox").show();

        return false;
    });

    jQuery(".lddfw_premium-feature button").click(function() {
        jQuery(this).parent().find(".lddfw_lightbox").show();
    });

    jQuery("#lddfw_out_for_delivery_button").click(
        function() {
            jQuery("#lddfw_out_for_delivery_button").hide();
            jQuery("#lddfw_out_for_delivery_button_loading").show();

            var lddfw_order_list = '';
            jQuery("#lddfw_alert").html();
            jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                function(index, item) {
                    if (jQuery(this).prop("checked") == true) {
                        if (lddfw_order_list != "") {
                            lddfw_order_list = lddfw_order_list + ",";
                        }
                        lddfw_order_list = lddfw_order_list + jQuery(this).val();
                    }
                }
            );
            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_out_for_delivery',
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {

                    jQuery("#lddfw_out_for_delivery_button").show();
                    jQuery("#lddfw_out_for_delivery_button_loading").hide();

                    jQuery("#lddfw_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                    }
                    if (lddfw_json["result"] == "1") {

                        jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                            function(index, item) {
                                if (jQuery(this).prop("checked") == true) {
                                    jQuery(this).parents(".lddfw_multi_checkbox").replaceWith("");
                                }
                            }
                        );
                        jQuery("#lddfw_alert").html(lddfw_json["error"]);
                        if (jQuery('.lddfw_multi_checkbox').length == 0) {
                            jQuery(".lddfw_footer_buttons").hide();
                        }
                    }
                }
            );
            return false;
        }
    );

    jQuery(".lddfw_multi_checkbox .lddfw_wrap").click(
        function() {
            var lddfw_chk = jQuery(this).find(".custom-control-input");
            if (lddfw_chk.prop("checked") == true) {
                jQuery(this).parents(".lddfw_multi_checkbox").removeClass("lddfw_active");
                lddfw_chk.prop("checked", false);
            } else {
                jQuery(this).parents(".lddfw_multi_checkbox").addClass("lddfw_active");
                lddfw_chk.prop("checked", true);
            }
        }
    );

    jQuery("#lddfw_start").click(
        function() {
            jQuery("#lddfw_home").hide();
            jQuery("#lddfw_login").show();
        }
    );

    jQuery("#lddfw_login_button").click(
        function() {
            // hide the sign up button
            jQuery("#lddfw_signup_button").hide();
            // show the login form
            jQuery("#lddfw_login_wrap").toggle();
            return false;
        }
    );

    jQuery("#lddfw_availability").click(
        function() {
            if (jQuery(this).hasClass("lddfw_active")) {
                jQuery(this).removeClass("lddfw_active");
                jQuery(this).html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg>');
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("unavailable"));
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-success");
                jQuery("#lddfw_menu .lddfw_availability").addClass("text-danger");
                jQuery.post(
                    lddfw_ajax_url, {
                        action: 'lddfw_ajax',
                        lddfw_service: 'lddfw_availability',
                        lddfw_availability: "0",
                        lddfw_driver_id: lddfw_driver_id,
                        lddfw_wpnonce: lddfw_nonce.nonce,
                        lddfw_data_type: 'html'
                    }
                );
            } else {
                jQuery(this).addClass("lddfw_active");
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("available"));
                jQuery(this).html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg>');
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-danger");
                jQuery("#lddfw_menu .lddfw_availability").addClass("text-success");
                jQuery.post(
                    lddfw_ajax_url, {
                        action: 'lddfw_ajax',
                        lddfw_service: 'lddfw_availability',
                        lddfw_availability: "1",
                        lddfw_driver_id: lddfw_driver_id,
                        lddfw_wpnonce: lddfw_nonce.nonce,
                        lddfw_data_type: 'html'
                    }
                );
            }
            return false;
        }
    );

    jQuery("#lddfw_dates_range").change(
        function() {
            var lddfw_location = jQuery(this).attr("data") + '&lddfw_dates=' + this.value;
            lddfw_show_loader(jQuery(this));
            window.location.replace(lddfw_location);
            return false;
        }
    );

    if (typeof lddfw_dates !== 'undefined') {
        if (lddfw_dates != "") {
            jQuery("#lddfw_dates_range").val(lddfw_dates);
        }
    }


    function lddfw_delivered_screen_open() {
        jQuery("#lddfw_driver_complete_btn").show();
        jQuery(".lddfw_page_content").hide();
        jQuery("#lddfw_delivery_signature").hide();
        jQuery("#lddfw_delivery_photo").hide();
        jQuery("#lddfw_delivered_form").hide();
        jQuery("#lddfw_failed_delivery_form").hide();
        jQuery(".delivery_proof_bar a").removeClass("active");
        jQuery(".delivery_proof_bar a").eq(0).addClass("active");
    }

    jQuery("#lddfw_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "success");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_delivered_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_delivered_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery("#lddfw_failed_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "failed");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_failed_delivery_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_failed_delivery_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery(".lddfw_dashboard .lddfw_box a").click(function() {
        jQuery(this).parent().addClass("lddfw_active");
    });

    jQuery(".lddfw_confirmation .lddfw_cancel").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            return false;
        }
    );

   

    // Function to process the confirmation click (delivered or failed)
    function lddfw_process_delivery_confirmation(status_attribute, reason_form_selector, reason_radio_name, reason_other_id, note_input_selector, confirmation_div_selector, error_context_message) {
        // Get reason/note
        var lddfw_reason = jQuery('input[name=' + reason_radio_name + ']:checked', reason_form_selector);
        if (lddfw_reason.attr("id") != reason_other_id) {
            jQuery(note_input_selector).val(lddfw_reason.val());
        }

        // Update UI
        jQuery(confirmation_div_selector).hide();
        jQuery("#lddfw_thankyou").show();

        // Get common data
        var lddfw_orderid = jQuery("#lddfw_driver_complete_btn").attr("order_id");
        var lddfw_signature = '';
        var lddfw_delivery_image = '';
        /* <fs_premium_only> */
        lddfw_signature = lddfw_get_signature();
        lddfw_resizeImage(lddfw_signature, 640, 640).then((result) => {
            lddfw_signature = result; // Note: Potential race condition if AJAX starts before resize finishes
        });
        lddfw_delivery_image = jQuery('#delivery_image').val();
        /* </fs_premium_only> */

        // Make the main status update AJAX call
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_status',
                lddfw_order_id: lddfw_orderid,
                lddfw_order_status: jQuery("#lddfw_driver_complete_btn").attr(status_attribute),
                lddfw_driver_id: lddfw_driver_id,
                lddfw_note: jQuery(note_input_selector).val(),
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_signature: lddfw_signature,
                lddfw_data_type: 'html' // Main status update doesn't need JSON response
                // Proofs handled separately in success callback
            },
            success: function(data) {
                /* <fs_premium_only> */
                // Call the shared function to handle proofs
                lddfw_next_delivery_service();
                lddfw_watch_position();
                /* </fs_premium_only> */
            },
            error: function(request, status, error) {
                // Handle error for the main status update AJAX call
                console.error('Error updating order status ' + error_context_message + ':', status, error);
            }
        });

        return false; // Prevent default form submission/link behavior
    }

    jQuery("#lddfw_delivered_confirmation .lddfw_ok").click(
        function() {
            lddfw_process_delivery_confirmation(
                'delivered_status',                 // status_attribute
                '#lddfw_delivered_form',            // reason_form_selector
                'lddfw_delivery_dropoff_location', // reason_radio_name
                'lddfw_delivery_dropoff_other',     // reason_other_id
                '#lddfw_driver_delivered_note',     // note_input_selector
                '#lddfw_delivered',                 // confirmation_div_selector
                '(delivered)'                       // error_context_message
            );
            return false; // Keep return false here as well
        }
    );

    if (jQuery("#lddfw_delivered_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_delivered_form .custom-control.custom-radio").hide();
    }
    if (jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").hide();
    }


    jQuery(".lddfw_alert_screen .lddfw_ok").click(function() {
        jQuery(".lddfw_alert_screen .lddfw_lightbox_close").trigger("click");
    });


    jQuery("#lddfw_driver_complete_btn").click(
        function() {

            /* <fs_premium_only> */
            jQuery(".lddfw_alert_screen").hide();
            if (jQuery(this).attr("delivery") == "success") {
                let lddfw_mandatory_signature = jQuery(this).attr("signature");
                let lddfw_mandatory_photo = jQuery(this).attr("photo");
                let lddfw_mandatory_pod = jQuery(this).attr("pod");

                let lddfw_signature = jQuery("#signature-image").html();
                let lddfw_delivery_image = jQuery("#delivery_image").val();

                if (lddfw_mandatory_signature == '1' && lddfw_signature == '') {
                    // Signature is mandatory.
                    jQuery("#lddfw_alert_signature").show();
                    return false;
                }

                if (lddfw_mandatory_photo == '1' && lddfw_delivery_image == '') {
                    // Photo is mandatory.
                    jQuery("#lddfw_alert_photo").show();
                    return false;
                }

                if (lddfw_mandatory_pod == '1' && lddfw_delivery_image == '' && lddfw_signature == '') {
                    // Photo or signature is mandatory.
                    jQuery("#lddfw_alert_pod").show();
                    return false;
                }



            }
            /* </fs_premium_only> */


            jQuery("#lddfw_delivery_screen").hide();
            if (jQuery(this).attr("delivery") == "success") {
                jQuery("#lddfw_delivered_confirmation").show();
            } else {
                jQuery("#lddfw_failed_delivery_confirmation").show();
            }
            return false;
        }
    );
    jQuery("#lddfw_failed_delivery_confirmation .lddfw_ok").click(
        function() {
            lddfw_process_delivery_confirmation(
                'failed_status',                    // status_attribute
                '#lddfw_failed_delivery_form',      // reason_form_selector
                'lddfw_delivery_failed_reason',    // reason_radio_name
                'lddfw_delivery_failed_6',         // reason_other_id
                '#lddfw_driver_note',              // note_input_selector
                '#lddfw_failed_delivery',          // confirmation_div_selector
                '(failed delivery)'                 // error_context_message
            );
            return false; // Keep return false here as well
        }
    );

    jQuery("#lddfw_delivered_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_delivered_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_dropoff_other") {
                jQuery("#lddfw_driver_delivered_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_delivered_note_wrap").hide();
            }
        }
    );

    jQuery("#lddfw_failed_delivery_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_failed_6") {
                jQuery("#lddfw_driver_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_note_wrap").hide();
            }
        }
    );

    jQuery(".lddfw_lightbox_close,#lddfw_driver_cancel_btn").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            return false;
        }
    );

    jQuery("#lddfw_login_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            var lddfw_nextpage = lddfw_form.attr('nextpage');

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");

            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_login',
                    lddfw_login_email: jQuery("#lddfw_login_email").val(),
                    lddfw_login_password: jQuery("#lddfw_login_password").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        window.location.replace(lddfw_nextpage);
                    }
                },
                error: function(request, status, error) {
                    lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + status + ' ' + error + "</div>");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_back_to_forgot_password_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_login_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_reset_link").click(
        function() {
            jQuery("#lddfw_create_new_password").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_forgot_password_link").click(
        function() {
            jQuery("#lddfw_login").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery(".lddfw_back_to_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();

        }
    );
    jQuery("#lddfw_resend_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_application_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_application").show();
        }
    );

    jQuery("#lddfw_forgot_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_forgot_password',
                    lddfw_user_email: jQuery("#lddfw_user_email").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'

                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);

                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_forgot_password_email_sent").show();

                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_new_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_newpassword',
                    lddfw_new_password: jQuery("#lddfw_new_password").val(),
                    lddfw_confirm_password: jQuery("#lddfw_confirm_password").val(),
                    lddfw_reset_key: jQuery("#lddfw_reset_key").val(),
                    lddfw_reset_login: jQuery("#lddfw_reset_login").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },

                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_new_password_created").show();

                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("body").on("click", "#lddfw_orders_table .lddfw_box a", function() {
        jQuery(this).closest(".lddfw_box").addClass("lddfw_active");
    });
    /* <fs_premium_only> */
    jQuery("body").on("click", "#delivery-image-clear", function() {
          // Iterate through each delete icon within the wrap
          jQuery("#delivery_image_wrap .lddfw_delete_image").each(function() {
            // Trigger the click event on the delete icon
            // This will call the lddfw_delete_image function for each image
            jQuery(this).trigger('click');
        });

        // Reset file input to allow selecting the same file again if needed
        jQuery("#upload_image").val("");

        // Prevent default button behavior
        return false;
    });
    /* </fs_premium_only> */

})(jQuery);


function lddfw_show_loader(obj) {

    if (obj.hasClass("lddfw_loader_fixed")) {
        jQuery('#lddfw_loader').show();
                                     } else {
        jQuery(".lddfw_back_link").hide();
        jQuery('#lddfw_loader').appendTo(".lddfw_back_column");
        jQuery('#lddfw_loader').show();
    }
}

function lddfw_hide_loader() {
    jQuery('#lddfw_loader').hide();
    jQuery(".lddfw_back_link").show();
    jQuery('#lddfw_loader').appendTo("body");
}

function lddfw_openNav() {
    jQuery(".lddfw_page_content").hide();
    document.getElementById("lddfw_mySidenav").style.width = "100%";
}

function lddfw_closeNav() {
    jQuery(".lddfw_page_content").show();
    document.getElementById("lddfw_mySidenav").style.width = "0";
}

/* <fs_premium_only> */

function lddfw_tracking_map() {
    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({
        suppressMarkers: true,
        polylineOptions: { strokeColor: "transparent", strokeWeight: 6 }
    });

    var origin_latlng = '';
    var destination_latlng = '';

    var LatLng = { lat: 41.85, lng: -87.65 };

    if (tracking_destination != "") {
        var tracking_destination_array = tracking_destination.split(",");
        destination_latlng = new google.maps.LatLng(parseFloat(tracking_destination_array[0]), parseFloat(tracking_destination_array[1]));
    }

    if (tracking_origin != "") {
        var tracking_origin_array = tracking_origin.split(",");
        origin_latlng = { lat: parseFloat(tracking_origin_array[0]), lng: parseFloat(tracking_origin_array[1]) };
    }

    // Set map center
    if (tracking_destination != "") {
        LatLng = destination_latlng;
                     } else {
        if (tracking_origin != "") {
            LatLng = origin_latlng;
        }
    }

    // Set map
    lddfw_map = new google.maps.Map(
        document.getElementById('lddfw_map123'), {
            center: LatLng,
            travelMode: lddfw_travel_mode,
            zoom: 8,
            disableDefaultUI: true,
            styles: lddfw_map_style(),
        }
    );

    directionsRenderer.setMap(lddfw_map);
    directionsService.route({
            origin: origin_latlng,
            destination: destination_latlng,
            travelMode: lddfw_travel_mode,
            optimizeWaypoints: true,
            transitOptions: { modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'], routingPreference: 'LESS_WALKING' },
        })
        .then((response) => {
            directionsRenderer.setDirections(response);
        })
        .catch((e) => console.log("Directions request failed due to " + e));

    let lddfw_color = "#0057d9";
    if (lddfw_travel_mode == 'bicycling') {
        // bicycle / bike
        var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' + encodeURIComponent(lddfw_color) + '" cx="245.946" cy="-6.426" r="271.25" transform="matrix(1, 0, 0, 0.999999, 234.174728, 285.838593)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M 519.364 181.991 C 538.328 181.991 550.181 160.313 540.697 142.972 C 531.216 125.629 507.511 125.629 498.032 142.972 C 495.87 146.926 494.732 151.412 494.732 155.978 C 494.732 170.345 505.762 181.991 519.364 181.991 Z M 517.313 247.566 C 520.223 250.029 523.846 251.367 527.578 251.359 L 560.419 251.359 C 573.063 251.359 580.961 236.908 574.643 225.346 C 571.709 219.98 566.286 216.675 560.419 216.675 L 533.336 216.675 L 496.782 185.785 C 490.631 180.567 481.829 180.722 475.842 186.154 L 418.364 238.179 C 410.103 245.658 410.89 259.401 419.945 265.776 L 461.889 295.321 L 461.889 355.412 C 461.889 368.763 475.572 377.106 486.521 370.431 C 491.601 367.333 494.732 361.607 494.732 355.412 L 494.732 286.044 C 494.73 280.248 491.988 274.832 487.42 271.616 L 466.194 256.677 L 496.087 229.622 Z M 576.841 268.702 C 526.273 268.702 494.67 326.508 519.956 372.755 C 545.238 418.999 608.445 418.999 633.729 372.755 C 639.495 362.208 642.53 350.247 642.53 338.071 C 642.53 299.759 613.121 268.702 576.841 268.702 Z M 576.841 372.755 C 551.56 372.755 535.757 343.85 548.398 320.729 C 561.041 297.606 592.644 297.606 605.286 320.729 C 608.168 326 609.684 331.98 609.684 338.071 C 609.684 357.227 594.982 372.755 576.841 372.755 Z M 379.779 268.702 C 329.211 268.702 297.607 326.508 322.889 372.755 C 348.174 418.999 411.383 418.999 436.664 372.755 C 442.43 362.208 445.466 350.247 445.466 338.071 C 445.466 299.759 416.056 268.702 379.779 268.702 Z M 379.779 372.755 C 354.493 372.755 338.692 343.85 351.334 320.729 C 363.976 297.606 395.58 297.606 408.219 320.729 C 411.106 326 412.62 331.98 412.62 338.071 C 412.62 357.227 397.918 372.755 379.779 372.755 Z" style="fill: rgba(255, 255, 255, 0.8);"/></svg>';
    } else if (lddfw_travel_mode == 'walking') {
        // walking.
        var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' + encodeURIComponent(lddfw_color) + '" cx="329.832" cy="83.158" r="271.25" transform="matrix(1, 0, 0, 0.999999, 150.363403, 197.037201)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M506.976,189.965c14.908,0,27.005-12.096,27.005-27.005c0-14.908-12.097-27.004-27.004-27.004  c-14.909,0-27.005,12.096-27.005,27.004C479.971,177.869,492.066,189.965,506.976,189.965z M560.141,273.847l-13.108-6.639  l-5.456-16.54c-8.271-25.092-31.337-42.646-57.497-42.7c-20.254-0.057-31.449,5.682-52.489,14.177  c-12.153,4.896-22.11,14.178-27.961,25.992l-3.77,7.65c-4.388,8.89-0.844,19.69,7.989,24.136c8.776,4.442,19.466,0.844,23.911-8.045  l3.77-7.651c1.97-3.938,5.231-7.032,9.282-8.664l15.078-6.076l-8.552,34.149c-2.925,11.701,0.226,24.135,8.382,33.08l33.7,36.793  c4.05,4.443,6.92,9.789,8.383,15.584l10.295,41.237c2.42,9.62,12.209,15.527,21.829,13.108s15.527-12.209,13.107-21.829  l-12.489-50.069c-1.462-5.796-4.331-11.195-8.383-15.585l-25.599-27.961l9.678-38.648l3.095,9.282  c2.981,9.058,9.396,16.541,17.834,20.815l13.107,6.638c8.775,4.444,19.466,0.845,23.909-8.045  C572.518,289.205,568.973,278.291,560.141,273.847L560.141,273.847z M431.363,353.003c-1.8,4.557-4.501,8.663-7.989,12.096  l-28.129,28.187c-7.033,7.032-7.033,18.452,0,25.484c7.032,7.032,18.396,7.032,25.429,0l33.417-33.417  c3.433-3.434,6.133-7.539,7.99-12.096l7.595-19.018c-31.111-33.924-21.772-23.516-26.667-30.211L431.363,353.003z" style="fill: rgb(255, 255, 255);"/></svg>';
    } else {
        // driving
        var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle style="fill: rgb(255, 255, 255);" cx="475.32" cy="274.687" r="271" transform="matrix(1, 0, 0, 1.000002, 4.736413, 5.999592)"/><circle fill="' + encodeURIComponent(lddfw_color) + '" cx="480.579" cy="283.21" r="249.982"/><path d="M 612.159 236.667 L 579.728 236.667 L 570.715 214.134 C 561.454 190.966 539.349 176 514.394 176 L 445.602 176 C 420.653 176 398.542 190.966 389.274 214.133 L 380.26 236.666 L 347.836 236.666 C 343.606 236.666 340.502 240.642 341.531 244.742 L 344.781 257.742 C 345.501 260.635 348.101 262.666 351.086 262.666 L 361.958 262.666 C 354.684 269.02 349.998 278.255 349.998 288.666 L 349.998 314.666 C 349.998 323.396 353.334 331.277 358.665 337.378 L 358.665 366.666 C 358.665 376.235 366.427 383.998 375.998 383.998 L 393.331 383.998 C 402.902 383.998 410.664 376.235 410.664 366.666 L 410.664 349.332 L 549.331 349.332 L 549.331 366.666 C 549.331 376.235 557.094 383.998 566.665 383.998 L 583.998 383.998 C 593.569 383.998 601.331 376.235 601.331 366.666 L 601.331 337.378 C 606.661 331.283 609.998 323.401 609.998 314.666 L 609.998 288.666 C 609.998 278.254 605.312 269.019 598.044 262.666 L 608.915 262.666 C 611.899 262.666 614.499 260.635 615.22 257.742 L 618.47 244.742 C 619.494 240.643 616.39 236.667 612.159 236.667 Z M 421.46 227.009 C 425.409 217.14 434.969 210.667 445.602 210.667 L 514.394 210.667 C 525.029 210.667 534.588 217.14 538.538 227.009 L 549.331 254 L 410.665 254 L 421.46 227.009 Z M 393.332 314.559 C 382.932 314.559 375.999 307.647 375.999 297.28 C 375.999 286.913 382.932 280.002 393.332 280.002 C 403.732 280.002 419.332 295.553 419.332 305.921 C 419.332 316.285 403.731 314.559 393.332 314.559 L 393.332 314.559 Z M 566.665 314.559 C 556.265 314.559 540.665 316.287 540.665 305.918 C 540.665 295.551 556.265 280 566.665 280 C 577.064 280 583.998 286.911 583.998 297.278 C 583.998 307.647 577.064 314.559 566.665 314.559 Z" style="fill: rgb(255, 255, 255);"/></svg>';
    }

    driver_marker = new google.maps.Marker({
        map: lddfw_map,
        icon: icon,
    });

    if (tracking_origin != "") {
        let seller_marker;
        seller_marker = new google.maps.Marker({
            position: origin_latlng,
            map: lddfw_map,
            zIndex: 99999999,
            icon: { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="55" height="55"><defs><path d="M133.95 200.93C133.95 229 142.03 256.69 155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93Z" id="a8icuLH8v"></path><path d="M513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93Z" id="d1BN9HSsyo"></path><path d="M402.85 111.5C400.75 108.09 397.03 106 393.05 106C378.44 106 261.56 106 246.95 106C242.97 106 239.25 108.09 237.15 111.5C234.8 115.3 216.03 145.68 213.68 149.48C201.57 169.09 212.31 196.36 234.94 199.48C236.57 199.7 238.23 199.81 239.9 199.81C250.6 199.81 260.08 195.05 266.59 187.68C273.1 195.05 282.61 199.81 293.27 199.81C303.98 199.81 313.45 195.05 319.96 187.68C326.47 195.05 335.98 199.81 346.65 199.81C357.35 199.81 366.83 195.05 373.34 187.68C379.88 195.05 389.36 199.81 400.03 199.81C401.73 199.81 403.35 199.7 404.98 199.48C427.69 196.4 438.47 169.13 426.32 149.48C421.62 141.88 405.19 115.3 402.85 111.5ZM389.43 210.18C389.43 212.62 389.43 224.82 389.43 246.77L250.57 246.77C250.57 224.82 250.57 212.62 250.57 210.18C247.09 210.99 243.51 211.58 239.9 211.58C237.73 211.58 235.52 211.43 233.39 211.14C231.36 210.84 229.37 210.37 227.46 209.82C227.46 217.03 227.46 274.75 227.46 281.96C227.46 288.45 232.63 293.69 239.03 293.69C255.23 293.69 384.84 293.69 401.04 293.69C407.44 293.69 412.61 288.45 412.61 281.96C412.61 274.75 412.61 217.03 412.61 209.82C410.66 210.4 408.71 210.88 406.68 211.14C404.47 211.43 402.3 211.58 400.1 211.58C396.48 211.58 392.9 211.03 389.43 210.18Z" id="ebvQlA14I"></path></defs><g><g><g><use xlink:href="#a8icuLH8v" opacity="1" fill="#0057d9" fill-opacity="1"></use><g><use xlink:href="#a8icuLH8v" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#d1BN9HSsyo" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#d1BN9HSsyo" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#ebvQlA14I" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#ebvQlA14I" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>') },
        });
    }

    if ('' != tracking_destination) {
        let home_marker;
        var svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="55" height="55"><defs><path d="M133.95 200.93C133.95 229 142.03 256.69 155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93Z" id="c4CdKfyNA"></path><path d="M513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93Z" id="aXIf52Mz"></path><path d="M239.48 218.17C239.48 259.37 239.48 282.26 239.48 286.84C239.48 290.54 242.56 293.55 246.36 293.55C251.18 293.53 289.75 293.44 294.57 293.42C298.36 293.41 301.42 290.41 301.42 286.72C301.42 282.71 301.42 250.63 301.42 246.62C301.42 242.91 304.51 239.91 308.31 239.91C311.06 239.91 333.09 239.91 335.84 239.91C339.64 239.91 342.73 242.91 342.73 246.62C342.73 250.62 342.73 282.68 342.73 286.69C342.71 290.39 345.79 293.4 349.59 293.42C349.59 293.42 349.6 293.42 349.61 293.42C354.43 293.43 392.98 293.53 397.8 293.55C401.61 293.55 404.69 290.54 404.69 286.84C404.69 282.26 404.69 259.35 404.69 218.13C357.1 179.98 330.67 158.78 325.38 154.55C323.46 153.04 320.72 153.04 318.8 154.55C308.22 163.03 281.78 184.24 239.48 218.17ZM408.13 168.92C408.13 134.1 408.13 114.75 408.13 110.88C408.13 108.11 405.82 105.85 402.97 105.85C400.56 105.85 381.28 105.85 378.87 105.85C376.02 105.85 373.71 108.11 373.71 110.88C373.71 112.91 373.71 123.05 373.71 141.31C350.6 122.79 337.76 112.5 335.19 110.44C327.57 104.33 316.57 104.33 308.95 110.44C298.06 119.18 210.93 189.06 200.04 197.79C197.85 199.56 197.54 202.73 199.35 204.87C199.35 204.87 199.36 204.87 199.36 204.87C200.45 206.17 209.23 216.56 210.33 217.86C212.14 220.01 215.39 220.31 217.59 218.55C217.6 218.55 217.6 218.55 217.6 218.54C227.72 210.43 308.68 145.48 318.8 137.37C320.72 135.86 323.46 135.86 325.38 137.37C335.5 145.48 416.47 210.43 426.59 218.54C428.78 220.31 432.04 220.02 433.85 217.88C433.86 217.88 433.86 217.87 433.86 217.87C434.95 216.57 443.73 206.18 444.83 204.88C446.64 202.74 446.32 199.57 444.12 197.81C444.11 197.8 444.1 197.8 444.1 197.79C439.3 193.94 427.31 184.32 408.13 168.92Z" id="e8LjRMauUK"></path></defs><g><g><g><use xlink:href="#c4CdKfyNA" opacity="1" fill="#00A300" fill-opacity="1"></use><g><use xlink:href="#c4CdKfyNA" opacity="1" fill-opacity="0" stroke="#fff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#aXIf52Mz" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#aXIf52Mz" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#e8LjRMauUK" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#e8LjRMauUK" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>';
        home_marker = new google.maps.Marker({
            map: lddfw_map,
            zIndex: 99999999,
            icon: { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg) },
            position: destination_latlng,
        });
    }

    if (lddfw_drivers_tracking_timing == '1') {
        lddfw_tracking();
    }

}

function lddfw_tracking() {
    clearTimeout(tracking_timer);
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
            lddfw_service: 'lddfw_delivery_track',
                    lddfw_driver_id: lddfw_driver_id,
            lddfw_orderid: lddfw_order_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
            lddfw_data_type: 'json'
                },
                success: function(data) {},
                error: function(request, status, error) {}
    }).done(function(data) {
        try {

            var lddfw_json = JSON.parse(data);
            var tracking_order = lddfw_json["order"];
            var tracking_status = lddfw_json["tracking_status"];
            var tracking_latitude = lddfw_json["driver_latitude"];
            var tracking_longitude = lddfw_json["driver_longitude"];
            var tracking_speed = lddfw_json["driver_speed"];
            var tracking_note = lddfw_json["note"];
            var countdown_seconds = lddfw_json["countdown_seconds"];

            if (tracking_status == "1") {

                if (tracking_latitude != "" && tracking_longitude != "") {
                    var latlng = { lat: parseFloat(tracking_latitude), lng: parseFloat(tracking_longitude) };
                    if (driver_marker) {
                        driver_marker.setMap(lddfw_map);
                        driver_marker.setPosition(latlng);
                    }
                }

                // For the last 5 minutes the tracking will be every 30 seconds.
                if (countdown_seconds < 300 && countdown_seconds != "-1" && tracking_milliseconds > 30000) {
                    tracking_milliseconds = 30000;
                }

                if (countdown_seconds > -1) {
                    if (countdown_started == 0) {

                        TIME_LIMIT = countdown_seconds;
                        timeLeft = TIME_LIMIT;

                        document.getElementById("lddfw_counter").innerHTML = `
							<div class="base-timer">
								<svg class="base-timer__svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
									<g class="base-timer__circle">
									<circle class="base-timer__path-elapsed" cx="50" cy="50" r="45"></circle>
									<path
										id="base-timer-path-remaining"
										stroke-dasharray="283"
										class="base-timer__path-remaining ${remainingPathColor}"
										d="
										M 50, 50
										m -45, 0
										a 45,45 0 1,0 90,0
										a 45,45 0 1,0 -90,0
										"
									></path>
									</g>
								</svg>
								<span id="base-timer-label" class="base-timer__label">${formatTime(timeLeft)}</span>
							</div>
							`;
                        countdown_started = 1;
                        startTimer();
                    }
                } else {
                    jQuery("#lddfw_counter").html('<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="clock" class="lddfw_tracking_svg svg-inline--fa fa-clock fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg>');
                }
            } else {
                // Hide driver location.
                if (driver_marker) {
                    driver_marker.setMap(null);
                }
                countdown_started = 0;
            }

            if (tracking_status == "2") {
                jQuery("#lddfw_counter").html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="lddfw_tracking_svg  svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>');
            }

            if (tracking_status == "3") {
                jQuery("#lddfw_counter").html('<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="lddfw_tracking_svg  svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>');
            }

            if (tracking_status == "4") {
                jQuery("#lddfw_counter").html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="box" class="lddfw_tracking_svg  svg-inline--fa fa-box fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M509.5 184.6L458.9 32.8C452.4 13.2 434.1 0 413.4 0H272v192h238.7c-.4-2.5-.4-5-1.2-7.4zM240 0H98.6c-20.7 0-39 13.2-45.5 32.8L2.5 184.6c-.8 2.4-.8 4.9-1.2 7.4H240V0zM0 224v240c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V224H0z"></path></svg>');
            }

            if (tracking_note != "") {
                jQuery("#lddfw_tracking_order_status h2").html(tracking_note);
            }

        } catch (e) {
            console.log(e);
            return false;
        }

        if (tracking_status != "3" && tracking_status != "2") {
            // Stop tracking on delivered status.
            tracking_timer = setTimeout(function() {
                lddfw_tracking();
            }, tracking_milliseconds);
        }

    });
}


function lddfw_initMap() {
    var lddfw_directionsService = new google.maps.DirectionsService();
    var lddfw_directionsRenderer = new google.maps.DirectionsRenderer();

        var LatLng = { lat: 41.85, lng: -87.65 };

        if (lddfw_map_center != "") {
            var lddfw_map_center_array = lddfw_map_center.split(",");
            LatLng = new google.maps.LatLng(parseFloat(lddfw_map_center_array[0]), parseFloat(lddfw_map_center_array[1]));
        }

    var lddfw_map = new google.maps.Map(
        document.getElementById('lddfw_map123'), {
            zoom: 6,
            center: LatLng,
            styles: lddfw_map_style(),
            disableDefaultUI: true,
        }
    );
    lddfw_directionsRenderer.setMap(lddfw_map);
    lddfw_calculateAndDisplayRoute(lddfw_directionsService, lddfw_directionsRenderer);
}




function lddfw_calculateAndDisplayRoute(directionsService, directionsRenderer) {
    var lddfw_waypts = [];
    var lddfw_orders_count = jQuery('.lddfw_address_chk').length;
    var lddfw_last_waypoint = 0;

    var lddfw_destination_address = jQuery("#route_destination").val();
    if (lddfw_destination_address == '' || lddfw_destination_address == 'last_address_on_route') {
        lddfw_destination_address = jQuery('.lddfw_address_chk').eq(jQuery('.lddfw_address_chk').length - 1).val();
    }

    var lddfw_origin_address = jQuery('#route_origin').val();
    if (lddfw_origin_address != '') {
        lddfw_google_api_origin = lddfw_origin_address;
    }
    jQuery('.lddfw_address_chk').each(
        function(index, item) {
            if (jQuery(this).val() != lddfw_destination_address) {
                lddfw_waypts.push({
                    location: jQuery(this).val(),
                    stopover: true
                });
            }
        }
    );
        directionsService.route({
            origin: lddfw_google_api_origin,
            destination: lddfw_destination_address,
            waypoints: lddfw_waypts,
            optimizeWaypoints: lddfw_optimizeWaypoints_flag,
            travelMode: lddfw_driver_travel_mode,
                transitOptions: { modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'], routingPreference: 'LESS_WALKING' },
        },
        function(response, status) {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
                var lddfw_route = response.routes[0];
                var lddfw_summaryPanel = document.getElementById('lddfw_directions-panel-listing');
                lddfw_summaryPanel.innerHTML = '<div id="lddfw_total_route"></div>';
                var lddfw_last_address = '';
                // For each route, display summary information.
                for (var i = 0; i < lddfw_route.legs.length; i++) {
                    var lddfw_routeSegment = i + 1;
                    if (lddfw_last_address != lddfw_route.legs[i].start_address) {
                        lddfw_summaryPanel.innerHTML += '<div class="row lddfw_address"><div class="col-2 text-center" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter(lddfw_routeSegment) + '</span></div><div class="col-10">' + lddfw_route.legs[i].start_address + '</div></div>';
                    }
                    lddfw_summaryPanel.innerHTML += '<div class="row lddfw_drive"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa fa-ellipsis-v up fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="car" class="svg-inline--fa fa-car fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M499.99 176h-59.87l-16.64-41.6C406.38 91.63 365.57 64 319.5 64h-127c-46.06 0-86.88 27.63-103.99 70.4L71.87 176H12.01C4.2 176-1.53 183.34.37 190.91l6 24C7.7 220.25 12.5 224 18.01 224h20.07C24.65 235.73 16 252.78 16 272v48c0 16.12 6.16 30.67 16 41.93V416c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-32h256v32c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-54.07c9.84-11.25 16-25.8 16-41.93v-48c0-19.22-8.65-36.27-22.07-48H494c5.51 0 10.31-3.75 11.64-9.09l6-24c1.89-7.57-3.84-14.91-11.65-14.91zm-352.06-17.83c7.29-18.22 24.94-30.17 44.57-30.17h127c19.63 0 37.28 11.95 44.57 30.17L384 208H128l19.93-49.83zM96 319.8c-19.2 0-32-12.76-32-31.9S76.8 256 96 256s48 28.71 48 47.85-28.8 15.95-48 15.95zm320 0c-19.2 0-48 3.19-48-15.95S396.8 256 416 256s32 12.76 32 31.9-12.8 31.9-32 31.9z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa down fa-ellipsis-v fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg> </div><div class="col-10"  ><b>' + lddfw_route.legs[i].duration.text + "</b><br>" + lddfw_route.legs[i].distance.text + '</div></div></div>';
                    lddfw_summaryPanel.innerHTML += '<div class="row lddfw_address"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter((lddfw_routeSegment + 1) * 1) + '</span></div><div class="col-10">' + lddfw_route.legs[i].end_address + '</div></div>';
                    lddfw_last_address = lddfw_route.legs[i].end_address;
                }
                lddfw_computeTotalDistance(response);
            } else {
                window.alert(lddfw_alert_texts.directions_request_failed + ' ' + status);
            }
        }
    );
}
    
function lddfw_readURL(input) {
      
    if (input.files && input.files.length > 0) {
        var imageContainer = jQuery('#delivery_image_wrap');
        var imageValues = [];
        
        // Store initial image values to potentially restore if something fails
        var initialImageValues = '';
        
        // Get existing image values if any
        var existingImages = jQuery('#delivery_image').val();
        
        // Count existing images
        var existingImageCount = 0;
        if (existingImages && existingImages !== '') {
            try {
                imageValues = JSON.parse(existingImages);
                existingImageCount = imageValues.length;
            } catch (e) {
                imageValues = [];
            }
        }
        
        // Check if adding new images would exceed the 3 image limit
        var totalImagesAfterUpload = existingImageCount + input.files.length;
        if (totalImagesAfterUpload > lddfw_proof_of_delivery_max_images) {
            // Use localized alert message from PHP
            alert(lddfw_alert_texts.max_images);
            // Reset the file input
            jQuery('#upload_image').val('');
            return;
        }
        
        // Always clear container when uploading new images to prevent display issues
        imageContainer.empty();
        
        if (existingImages && existingImages !== '') {
            initialImageValues = existingImages;
            try {
                // Recreate existing image elements
                for (var j = imageValues.length - 1; j >= 0; j--) {
                   
                    imageContainer.append("<div class='lddfw_image_item' data-url='"+ imageValues[j]  +"' data-index='" + j + "'><span class='lddfw_helper'></span><img src='" + imageValues[j] + "'><span class='lddfw_delete_image' onclick='lddfw_delete_image(this)'>" + lddfw_trash_icon_svg + "</span></div>");
                }
            } catch (e) {
                imageContainer.empty();
                initialImageValues = '';
            }
        }
        
        // Create a copy of the files array for processing
        var filesToProcess = [];
        for (var i = 0; i < input.files.length; i++) {
            filesToProcess.push(input.files[i]);
        }
        
        // Keep a backup of current values
        var tempImageValues = [...imageValues];
        
        // Process files one by one sequentially
        function processNextFile(index) {
            if (index >= filesToProcess.length) {
                // All files processed
                return;
            }
            
            var file = filesToProcess[index];
            var lddfw_reader = new FileReader();
            
            lddfw_reader.onload = function(e) {
                // Use smaller dimensions (640x640) to reduce size issues
                lddfw_resizeImage(e.target.result, 640, 640).then((result_base64) => { // Renamed for clarity

                    // --- BEGIN MODIFIED CODE ---
                    // Show a temporary loader icon while uploading this specific file
                    var loaderId = 'loader-' + Date.now() + '-' + Math.random().toString(36).substring(2); // Unique ID
                    var loaderHtml = "<div id='" + loaderId + "' class='lddfw_image_item lddfw_uploading'><span class='lddfw_image_status'>" + lddfw_loader_icon_svg + "</span> </div>";
                    imageContainer.prepend(loaderHtml);
                    var $loaderElement = jQuery('#' + loaderId);

                    // Save the individual image via AJAX
                    jQuery.ajax({
                        type: "POST",
                        url: lddfw_ajax_url,
                        data: {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_save_delivery_image',
                            lddfw_order_id: lddfw_order_id, // Assumes lddfw_order_id is globally available
                            lddfw_driver_id: lddfw_driver_id, // Assumes lddfw_driver_id is globally available
                            lddfw_delivery_image_data: result_base64, // Send the resized base64 data
                            lddfw_wpnonce: lddfw_nonce.nonce, // Assumes lddfw_nonce is globally available
                            lddfw_data_type: 'html' // Expect URL string or '0'
                        },
                        success: function(saveResponse) {
                            $loaderElement.remove(); // Remove the loader for this file

                            if (saveResponse && saveResponse !== '0') { // Check if response is not '0' (treat as URL)
                                var imageUrl = saveResponse; // The response is the URL
                                console.log('Delivery image saved successfully for order ' + lddfw_order_id + '. URL: ' + imageUrl);

                               
                                // Use the returned URL in the img src attribute
                                var newItemHtml = "<div class='lddfw_image_item' data-url='" + imageUrl + "'><span class='lddfw_helper'></span><img src='" + imageUrl + "'><span class='lddfw_delete_image' onclick='lddfw_delete_image(this)'>" + lddfw_trash_icon_svg + "</span></div>";
                                imageContainer.prepend(newItemHtml); // Add the successful item

                                // Update the hidden input field with the array of URLs
                                var currentUrls = [];
                                var existingUrlData = jQuery('#delivery_image').val();
                                if (existingUrlData) {
                                    try {
                                        currentUrls = JSON.parse(existingUrlData);
                                        if (!Array.isArray(currentUrls)) currentUrls = [];
                                    } catch (e) { currentUrls = []; }
                                }
                                currentUrls.push(imageUrl);
                                jQuery('#delivery_image').val(JSON.stringify(currentUrls));

                            } else { // Handle failure (response is '0' or empty/falsy)
                                console.error('Error saving delivery image: Server response indicates failure (received: ' + saveResponse + ').');
                                // Show an alert instead of adding a failed item
                                alert(lddfw_alert_texts.error_saving_server || 'Failed to save image on server.'); // Use localized text
                            }

                        },
                        error: function(request, status, error) {
                            $loaderElement.remove(); // Remove the loader on error too
                            console.error('AJAX Error saving delivery image:', status, error);
                            // Show an alert on AJAX error
                             alert(lddfw_alert_texts.error_saving_ajax || 'Failed to contact server to save image.'); // Use localized text
                        },
                        complete: function() {
                             // Process next file regardless of success/error of previous one
                            setTimeout(function() {
                                processNextFile(index + 1);
                            }, 100); // Still keep a small delay
                        }
                    });
                    // --- END MODIFIED CODE ---

                }).catch(function(error) {
                    console.error("Error resizing image:", error);
                     // Process next file even if resizing fails
                    processNextFile(index + 1);
                });
            };
            
            lddfw_reader.onerror = function() {
                processNextFile(index + 1);
            };
            
            lddfw_reader.readAsDataURL(file); // convert to base64 string
        }
        
        // Start processing files
        processNextFile(0);
    }
}

function lddfw_resizeImage(base64Str, maxWidth = 1000, maxHeight = 1000) {
    return new Promise((resolve) => {
        let lddfw_img = new Image()
        lddfw_img.src = base64Str
        lddfw_img.onload = () => {
            let lddfw_image_canvas = document.createElement('canvas')
            const MAX_WIDTH = maxWidth
            const MAX_HEIGHT = maxHeight
            let lddfw_width = lddfw_img.width
            let lddfw_height = lddfw_img.height

            if (lddfw_width > lddfw_height) {
                if (lddfw_width > MAX_WIDTH) {
                    lddfw_height *= MAX_WIDTH / lddfw_width
                    lddfw_width = MAX_WIDTH
                }
            } else {
                if (lddfw_height > MAX_HEIGHT) {
                    lddfw_width *= MAX_HEIGHT / lddfw_height
                    lddfw_height = MAX_HEIGHT
                }
            }
            lddfw_image_canvas.width = lddfw_width
            lddfw_image_canvas.height = lddfw_height
            let lddfw_ctx = lddfw_image_canvas.getContext('2d')
            lddfw_ctx.drawImage(lddfw_img, 0, 0, lddfw_width, lddfw_height)
            resolve(lddfw_image_canvas.toDataURL())
        }
    })
}

function lddfw_resizeCanvas() {
    if (typeof lddfw_signaturePad === "undefined") {} else {
        if (lddfw_signaturePad.isEmpty()) {
            var lddfw_ratio = Math.max(window.devicePixelRatio || 1, 1);
            lddfw_canvas.width = lddfw_canvas.offsetWidth;
            lddfw_canvas.height = lddfw_canvas.offsetHeight;
            lddfw_canvas.getContext("2d").scale(1, 1);
            lddfw_signaturePad.clear();
        }
    }
}

if (jQuery("#signature-pad").length) {
    var lddfw_canvas = document.getElementById('signature-pad');
    var lddfw_signaturePad = new SignaturePad(lddfw_canvas, {
        backgroundColor: '#ffffff'
    });

    jQuery(".signature-clear").click(function() {
        jQuery("#signature-image").html("");
        lddfw_signaturePad.clear();
        lddfw_resizeCanvas();
                return false;
    });

    window.onresize = lddfw_resizeCanvas;
    lddfw_resizeCanvas();
}

jQuery("#upload_image").change(function() {
    // Check if there are files selected
    if (this.files && this.files.length > 0) {
        // Get existing images count
        var existingCount = 0;
        var existingImages = jQuery('#delivery_image').val();
        if (existingImages && existingImages !== '') {
            try {
                var imageValues = JSON.parse(existingImages);
                existingCount = imageValues.length;
            } catch (e) {
                existingCount = 0;
            }
        }
        
        // Check if total would exceed limit
        var totalAfterUpload = existingCount + this.files.length;
        if (totalAfterUpload > lddfw_proof_of_delivery_max_images) {
            // Use localized alert message from PHP
            alert(lddfw_alert_texts.max_images_total);
            // Reset the file input
            jQuery(this).val('');
            return;
        }
        
        // If within limits, proceed with upload
        lddfw_readURL(this);
    }
});


jQuery(".lddfw_upload_image").change(function(e) {
    var $this = jQuery(this);
    if (this.files && this.files[0]) {
        var lddfw_reader = new FileReader();
        lddfw_reader.onload = function(e) {
            lddfw_resizeImage(e.target.result, 640, 640).then((result) => {
                $this.parents(".upload_image_form").find(".upload_image_wrap").html("<span class='lddfw_helper'></span><img src='" + result + "'>");
                $this.parent().find(".lddfw_image_input").val(result);
            });
        }
        lddfw_reader.readAsDataURL(this.files[0]);
    }


});

    jQuery("#lddfw_start_delivery_btn").on("click", function() {
        var lddfw_start_delivery_loading_btn = jQuery("#lddfw_start_delivery_loading_btn");
        jQuery("#lddfw_start_delivery_btn").hide();
        lddfw_start_delivery_loading_btn.show();
        var lddfw_orderid = jQuery(this).attr("order_id");
        jQuery("#delivery_image_wrap").html("");
        jQuery("#delivery_image").val("");
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_start_delivery',
                lddfw_driver_id: lddfw_driver_id,
                lddfw_orderid: lddfw_orderid,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'json'
            },
            success: function(data) {
                var lddfw_json = JSON.parse(data);
                if (lddfw_json["result"] == "1") {
                    var duration_text = lddfw_json["duration_text"];
                    var distance_text = lddfw_json["distance_text"];
                    jQuery("#driver_duration_section").show();
                    jQuery("#driver_duration").html(duration_text);
                    jQuery("#lddfw_start_delivery_notice_duration").html(duration_text);
                    jQuery("#lddfw_start_delivery_notice_distance").html(distance_text);
                    lddfw_delivery_timer();
                }
            },
            error: function(request, status, error) {}
        }).done(function() {
            jQuery(".lddfw_delivery_start_button").hide();
            lddfw_start_delivery_loading_btn.hide();
            //jQuery("#lddfw_start_delivery_notice").show();
            //jQuery("#lddfw_start_delivery_notice").delay(4000).hide(0);
            jQuery(".lddfw_order_status_buttons").show(0);
            lddfw_watch_position();
        });


        // jQuery(".lddfw_order_status_buttons").show();
        return false;
    });


    /* </fs_premium_only> */


    jQuery("#cancel_password_button").on("click", function() {
        jQuery("#lddfw_password_holder").hide();
        jQuery("#lddfw_password").val("");
    });

    jQuery("#new_password_button").on("click", function() {
        jQuery("#lddfw_password_holder").show();
        jQuery("#lddfw_password").val(Math.random().toString(36).slice(2));
    });

    jQuery("#billing_state_select").on("change", function() {
        jQuery("#billing_state_input").val(jQuery(this).val());
    });
    jQuery("#billing_country").on("change", function() {
        if (jQuery(this).val() == "US") {
            jQuery("#billing_state_select").show();
            jQuery("#billing_state_input").hide();
        } else {
            jQuery("#billing_state_input").show();
            jQuery("#billing_state_select").hide();
        }
    });
    if (jQuery("#billing_country").length) {
        jQuery("#billing_country").trigger("change");
    }

    function scrolltoelement(element) {
        jQuery('html, body').animate({
            scrollTop: element.offset().top - 100
        }, 1000);
    }

    jQuery(".lddfw_form").validate({
        submitHandler: function(form) {
            var lddfw_form = jQuery(form);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");
            var lddfw_service = lddfw_form.attr("service");
            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: lddfw_form.serialize() + '&action=lddfw_ajax&lddfw_service=' + lddfw_service + '&lddfw_data_type=json',
                success: function(data) {
                    try {
                        var lddfw_json = JSON.parse(data);
                        if (lddfw_json["result"] == "0") {
                            lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                            lddfw_submit_btn.show();
                            lddfw_loading_btn.hide();
                            scrolltoelement(lddfw_alert_wrap);
                        }
                        if (lddfw_json["result"] == "1") {
                            var lddfw_hide_on_success = lddfw_form.find(".lddfw_hide_on_success");
                            if (lddfw_hide_on_success.length) {
                                lddfw_hide_on_success.replaceWith("");
                            }
                            lddfw_alert_wrap.html("<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                            lddfw_submit_btn.show();
                            lddfw_loading_btn.hide();
                            if (lddfw_json["nonce"] != "") {
                                lddfw_form.find("#lddfw_wpnonce").val(lddfw_json["nonce"]);
                                lddfw_nonce = { "nonce": lddfw_json["nonce"] };
                            }

                            //Switch theme mode.
                            if (jQuery("select[name='lddfw_driver_app_mode']").length) {
                                jQuery("body").attr("class", jQuery("select[name='lddfw_driver_app_mode']").val());
                            }

                            scrolltoelement(lddfw_alert_wrap);
                        }

                    } catch (e) {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                        scrolltoelement(lddfw_alert_wrap);
                    }
                },
                error: function(request, status, error) {
                    lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                    scrolltoelement(lddfw_alert_wrap);
                }
            });

            return false;
        }
    });


    jQuery("#lddfw_driver_add_signature_btn").click(function() {

        jQuery(".signature-wrapper").show();
        /* <fs_premium_only> */
        lddfw_resizeCanvas();
        /* </fs_premium_only> */
    });

    jQuery(".delivery_proof_bar a").click(function() {

        var $lddfw_this = jQuery(this);
        var $lddfw_screen_class = $lddfw_this.attr("href")
        $lddfw_this.parents(".delivery_proof_bar").find("a").removeClass("active");
        $lddfw_this.addClass("active");
        $lddfw_this.parents(".lddfw_lightbox").find(".screen_wrap").hide();
        $lddfw_this.parents(".lddfw_lightbox").find("." + $lddfw_screen_class).show();

        /* <fs_premium_only> */
        lddfw_resizeCanvas();
        /* </fs_premium_only> */
        return false;
    });

    //switch lazyload
    jQuery("img.lazyload").each(function() {
        var $lddfw_src = jQuery(this).attr("data-src");
        jQuery(this).attr("src", $lddfw_src);
    });
    jQuery("iframe.lazyload").each(function() {
        var $lddfw_src = jQuery(this).attr("data-src");
        jQuery(this).attr("src", $lddfw_src);
    });

    /* <fs_premium_only> */
    jQuery(".submenu-item").click(function() {
        var lddfw_submenu = '#' + jQuery(this).attr("data");
        if (jQuery(lddfw_submenu).hasClass("active")) {
            jQuery(this).removeClass("active");
            jQuery(lddfw_submenu).removeClass("active");
        } else {
            jQuery(lddfw_submenu).addClass("active");
            jQuery(this).addClass("active");
        }
    });


    function lddfw_watch_position_success(pos) {
        var coordination = pos.coords;
        if (lddfw_last_latitude != coordination.latitude && coordination.longitude != lddfw_last_longitude) {
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_driver_tracking_position',
                    lddfw_latitude: coordination.latitude,
                    lddfw_longitude: coordination.longitude,
                    lddfw_speed: coordination.speed,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html'
                },
                success: function(data) {},
                error: function(request, status, error) {}
            });
            lddfw_last_latitude = coordination.latitude;
            lddfw_last_longitude = coordination.longitude;
        }
    }


    function lddfw_watch_position_start() {
        if ('1' == lddfw_drivers_tracking_timing) {
            lddfw_watch_position_id = setInterval(lddfw_watch_position, tracking_milliseconds);
        }
    }

    function lddfw_watch_position_error(err) {
        console.log(err);
    }

    function lddfw_watch_position() {
        if (lddfw_tracking_status == "1") {
            navigator.geolocation.getCurrentPosition(lddfw_watch_position_success, lddfw_watch_position_error, { enableHighAccuracy: true });
        }
    }

    function lddfw_watch_position_end() {
        clearInterval(lddfw_watch_position_id);
    }

    function lddfw_driver_tracking_status(lddfw_status) {
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_driver_tracking_status',
                lddfw_status: lddfw_status,
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            },
            success: function(data) {},
            error: function(request, status, error) {}
        });
    }

    function lddfw_switch_tracking_icon(status) {
        if (status == "1") {
            jQuery("#lddfw_trackme .lddfw_trackme_off").hide();
            jQuery("#lddfw_trackme .lddfw_trackme_on").show();
            lddfw_tracking_status = "1";
        } else {
            jQuery("#lddfw_trackme .lddfw_trackme_on").hide();
            jQuery("#lddfw_trackme .lddfw_trackme_off").show();
            lddfw_tracking_status = "0";
        }
    }

    function geolocation_success(position) {
        lddfw_switch_tracking_icon("1");
        lddfw_driver_tracking_status("1");
        lddfw_watch_position_end();
        lddfw_watch_position_start();
    }

    function geolocation_error(err) {
        jQuery("#tracking_alert").html("<div style='margin:0px' class='alert alert-danger'>" + err.message + "</div>");
    }
    jQuery("#lddfw_trackme").click(function() {
        if (jQuery("#lddfw_trackme .lddfw_trackme_on").is(":visible")) {
            lddfw_switch_tracking_icon("0");
            lddfw_driver_tracking_status("0");
            lddfw_watch_position_end();
        } else {
            if (!navigator.geolocation) {
                jQuery("#tracking_alert").html("Geolocation is not supported by your browser");
            } else {
                navigator.geolocation.getCurrentPosition(geolocation_success, geolocation_error);
            }
        }
        return false;
    });

    /* </fs_premium_only> */


    function lddfw_order_map() {
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer();

        var LatLng = { lat: 41.85, lng: -87.65 };

        if (lddfw_map_center != "") {
            var lddfw_map_center_array = lddfw_map_center.split(",");
            LatLng = new google.maps.LatLng(parseFloat(lddfw_map_center_array[0]), parseFloat(lddfw_map_center_array[1]));
        }

        const map = new google.maps.Map(document.getElementById("google_map"), {
            zoom: 7,
            center: LatLng,
            styles: lddfw_map_style(),
            disableDefaultUI: true,
        });

        directionsRenderer.setMap(map);

        /* <fs_premium_only> */
        directionsRenderer.setPanel(document.getElementById("lddfw_directions-panel-direction"));
        /* </fs_premium_only> */

        directionsService.route({
                origin: driver_origin,
                destination: driver_destination,
                travelMode: driver_travel_mode,
                optimizeWaypoints: true,
                transitOptions: { modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'], routingPreference: 'LESS_WALKING' },
            })
            .then((response) => {
                directionsRenderer.setDirections(response);

                /* <fs_premium_only> */
                var lddfw_route = response.routes[0];
                var lddfw_last_address = '';

                for (var i = 0; i < lddfw_route.legs.length; i++) {
                    var lddfw_routeSegment = i + 1;
                    if (lddfw_last_address != lddfw_route.legs[i].start_address) {
                        jQuery("#lddfw_directions-origin").html('<div style="width:50px;position:relative;bottom:-13px;" class="text-center" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter(lddfw_routeSegment) + '</span></div><div style="padding-left: 50px;">' + lddfw_route.legs[i].start_address + '</div>');
                    }
                    jQuery("#lddfw_directions-destination").html('<div style="width:50px;position:relative;bottom:-13px;" class="text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter((lddfw_routeSegment + 1) * 1) + '</span></div><div style="padding-left: 50px;">' + lddfw_route.legs[i].end_address + '</div>');
                    lddfw_last_address = lddfw_route.legs[i].end_address;
                }

                lddfw_computeTotalDistance(response);
                jQuery('#lddfw_total_route').append('<a href="#" id="lddfw-panel-listing-toggle"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M497.1 222.1l-208.1-208.1c-9.364-9.364-21.62-14.04-33.89-14.03C243.7 .0092 231.5 4.686 222.1 14.03L14.03 222.1C4.676 231.5 .0002 243.7 .0004 255.1c.0002 12.26 4.676 24.52 14.03 33.87l208.1 208.1C231.5 507.3 243.7 511.1 256 511.1c12.26 0 24.52-4.677 33.87-14.03l208.1-208.1c9.352-9.353 14.03-21.61 14.03-33.87C511.1 243.7 507.3 231.5 497.1 222.1zM410.5 252l-96 84c-10.79 9.545-26.53 .9824-26.53-12.03V272H223.1l-.0001 48C223.1 337.6 209.6 352 191.1 352S159.1 337.6 159.1 320V240c0-17.6 14.4-32 32-32h95.1V156c0-13.85 16.39-20.99 26.53-12.03l96 84C414 231 415.1 235.4 415.1 240S414 249 410.5 252z"/></svg></a>');
                jQuery('#google_map_direction').show();
                /* </fs_premium_only> */

            })
            .catch((e) => console.log("Directions request failed due to " + e));
    }



    function lddfw_computeTotalDistance(result) {
        var lddfw_totalDist = 0;
        var lddfw_totalTime = 0;
        var lddfw_distance_text = '';
        var lddfw_distance_array = '';
        var lddfw_distance_type = '';

        var lddfw_myroute = result.routes[0];
        for (i = 0; i < lddfw_myroute.legs.length; i++) {
            lddfw_totalTime += lddfw_myroute.legs[i].duration.value;
            lddfw_distance_text = lddfw_myroute.legs[i].distance.text;
            lddfw_distance_array = lddfw_distance_text.split(" ");
            lddfw_totalDist += parseFloat(lddfw_distance_array[0]);
            lddfw_distance_type = lddfw_distance_array[1];
        }
        lddfw_totalTime = (lddfw_totalTime / 60).toFixed(0);
        lddfw_TotalTimeText = lddfw_timeConvert(lddfw_totalTime);
        document.getElementById("lddfw_total_route").innerHTML = "<b>" + lddfw_TotalTimeText + "</b> <span>(" + (lddfw_totalDist).toFixed(1) + " " + lddfw_distance_type + ")</span> ";
    }


    function lddfw_timeConvert(n) {
        var lddfw_num = n;
        var lddfw_hours = (lddfw_num / 60);
        var lddfw_rhours = Math.floor(lddfw_hours);
        var lddfw_minutes = (lddfw_hours - lddfw_rhours) * 60;
        var lddfw_rminutes = Math.round(lddfw_minutes);
        var lddfw_result = '';
        if (lddfw_rhours > 1) {
            lddfw_result = lddfw_rhours + " " + lddfw_hours_text + " ";
        }
        if (lddfw_rhours == 1) {
            lddfw_result = lddfw_rhours + " " + lddfw_hour_text + " ";
        }
        if (lddfw_rminutes > 0) {
            lddfw_result += lddfw_rminutes + " " + lddfw_mins_text;
        }
        return lddfw_result;
    }


    /* <fs_premium_only> */
    function onTimesUp() {
        setTimeout(function() {
            lddfw_tracking();
        }, 3000);
        clearInterval(timerInterval);
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            timePassed = timePassed += 1;
            timeLeft = TIME_LIMIT - timePassed;
            document.getElementById("base-timer-label").innerHTML = formatTime(
                timeLeft
            );
            setCircleDasharray();
            setRemainingPathColor(timeLeft);

            if (timeLeft <= 0) {
                onTimesUp();
            }
        }, 1000);
    }

    function setCircleDasharray() {
        const circleDasharray = `${(
			calculateTimeFraction() * FULL_DASH_ARRAY
		).toFixed(0)} 283`;
        document
            .getElementById("base-timer-path-remaining")
            .setAttribute("stroke-dasharray", circleDasharray);
    }

    function calculateTimeFraction() {
        const rawTimeFraction = timeLeft / TIME_LIMIT;
        return rawTimeFraction - (1 / TIME_LIMIT) * (1 - rawTimeFraction);
    }


    function formatTime(time) {
        let minutes = Math.floor(time / 60);
        let seconds = time % 60;

        if (seconds < 10) {
            seconds = `0${seconds}`;
        }
        if (minutes < 0) {
            minutes = 0;
        }
        return `<span class="tracking_min">${minutes}</span>` + tracking_min_text;
    }


    function setRemainingPathColor(timeLeft) {
        const { alert, warning, info } = COLOR_CODES;
        if (timeLeft <= alert.threshold) {
            document
                .getElementById("base-timer-path-remaining")
                .classList.remove(warning.color);
            document
                .getElementById("base-timer-path-remaining")
                .classList.add(alert.color);
        } else if (timeLeft <= warning.threshold) {
            document
                .getElementById("base-timer-path-remaining")
                .classList.remove(info.color);
            document
                .getElementById("base-timer-path-remaining")
                .classList.add(warning.color);
        }
    }

    /* </fs_premium_only> */


    function lddfw_numtoletter(lddfw_num) {
        var lddfw_s = '',
            lddfw_t;

        while (lddfw_num > 0) {
            lddfw_t = (lddfw_num - 1) % 26;
            lddfw_s = String.fromCharCode(65 + lddfw_t) + lddfw_s;
            lddfw_num = (lddfw_num - lddfw_t) / 26 | 0;
        }
        return lddfw_s || undefined;
    }

    function lddfw_map_style() {
        let lddfw_dark_mode_style = [{
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "poi",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "road",
                "elementType": "labels.icon",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "transit",
                "stylers": [{
                    "visibility": "off"
                }]
            }
        ];

        if (jQuery("body").hasClass("dark")) {
            lddfw_dark_mode_style = [{
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#242f3e"
                    }]
                },
                {
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#746855"
                    }]
                },
                {
                    "elementType": "labels.text.stroke",
                    "stylers": [{
                        "color": "#242f3e"
                    }]
                },
                {
                    "featureType": "administrative",
                    "elementType": "geometry",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "administrative.locality",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "poi",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "poi",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#263c3f"
                    }]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#6b9a76"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#38414e"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry.stroke",
                    "stylers": [{
                        "color": "#212a37"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.icon",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#9ca5b3"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#746855"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry.stroke",
                    "stylers": [{
                        "color": "#1f2835"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#f3d19c"
                    }]
                },
                {
                    "featureType": "transit",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "transit",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#2f3948"
                    }]
                },
                {
                    "featureType": "transit.station",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#17263c"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#515c6d"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "labels.text.stroke",
                    "stylers": [{
                        "color": "#17263c"
                    }]
                }
            ];
        }
        return lddfw_dark_mode_style;
    }


    function lddfw_delivery_timer() {

        /* <fs_premium_only> */
        clearTimeout(tracking_timer);
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_delivery_track',
                lddfw_driver_id: lddfw_driver_id,
                lddfw_orderid: lddfw_order_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'json'
            },
            success: function(data) {},
            error: function(request, status, error) {}
        }).done(function(data) {
            try {

                var lddfw_json = JSON.parse(data);
                var tracking_status = lddfw_json["tracking_status"];
                var countdown_seconds = lddfw_json["countdown_seconds"];
                if (tracking_status == "1") {

                    if (countdown_seconds > -1) {
                        if (countdown_started == 0) {

                            TIME_LIMIT = countdown_seconds;
                            timeLeft = TIME_LIMIT;

                            document.getElementById("lddfw_delivery_counter").innerHTML = `
								<div class="base-timer">
									<svg class="base-timer__svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
										<g class="base-timer__circle">
										<circle class="base-timer__path-elapsed" cx="50" cy="50" r="45"></circle>
										<path
											id="base-timer-path-remaining"
											stroke-dasharray="283"
											class="base-timer__path-remaining ` + remainingPathColor + `"
											d="
											M 50, 50
											m -45, 0
											a 45,45 0 1,0 90,0
											a 45,45 0 1,0 -90,0
											"
										></path>
										</g>
									</svg>
								</div>
								`;
                            document.getElementById("lddfw_delivery_counter_text").innerHTML = '<span id="base-timer-label" class="base-timer__label">' + formatTime(timeLeft) + '</span>';

                            countdown_started = 1;
                            startTimer();
                        }
                    }
                } else {
                    countdown_started = 0;
                }

            } catch (e) {
                console.log(e);
                return false;
            }

        });
        /* </fs_premium_only> */

    }



    if ( jQuery("#lddfw_page").hasClass("order") ) {
        if ( jQuery("#google_map").length ) {
          jQuery("body").append(
            "<script " +
              "id='lddfw_google_map_script' " +
              "async defer " +
              "src='https://maps.googleapis.com/maps/api/js" +
                "?key="   + lddfw_google_api_key   +
                "&language=" + lddfw_map_language +
                "&callback=lddfw_order_map"        +
                "&loading=async"                   +  
            "'></script>"
          );
        }
        lddfw_delivery_timer();
      }
      

    /* <fs_premium_only> */
    if ( jQuery("#lddfw_tracking_page").length ) {
        if ( jQuery("#lddfw_map123").length ) {
            jQuery("body").append(
                "<script " +
                    "id='lddfw_google_map_script' " +
                    "async defer " +
                    "src='https://maps.googleapis.com/maps/api/js" +
                        "?key="       + lddfw_google_api_key   +
                        "&language="  + lddfw_map_language    +
                        "&callback="  + "lddfw_tracking_map"  +
                        "&loading=async" +   
                "'></script>"
            );
        }
    }
    

    if (jQuery("#lddfw_trackme").length) {
        if (lddfw_tracking_status == "1") {
            lddfw_switch_tracking_icon("1");
        } else {
            lddfw_switch_tracking_icon("0");
        }
    }
    

    // Function to delete an image from the container
    function lddfw_delete_image(deleteButtonElement) {
        var $deleteButton = jQuery(deleteButtonElement); // The span element
        var $itemElement = $deleteButton.closest('.lddfw_image_item');
        var imageUrlToDelete = $itemElement.data('url');

        // Store original icon HTML and show loader
        var originalIconHtml = $deleteButton.html();
        $deleteButton.html(lddfw_loader_icon_svg); // Show loader
        $deleteButton.css('pointer-events', 'none'); // Prevent multiple clicks

        if (!imageUrlToDelete) {
            console.error("Could not find image URL to delete.");
            // Optionally remove the item anyway or show an error
            $itemElement.remove();
             // Restore icon and re-enable click if deletion fails client-side
             $deleteButton.html(originalIconHtml);
             $deleteButton.css('pointer-events', 'auto');
            return;
        }

        // Get current URLs from hidden input
        var currentUrlData = jQuery('#delivery_image').val();
        var imageUrls = [];
        if (currentUrlData) {
            try {
                imageUrls = JSON.parse(currentUrlData);
                if (!Array.isArray(imageUrls)) imageUrls = [];
            } catch (e) {
                console.error("Error parsing image URL JSON during delete:", e);
                imageUrls = []; // Reset if parsing fails
            }
        }

        // Filter out the URL to delete
        var updatedImageUrls = imageUrls.filter(url => url !== imageUrlToDelete);

        // Update hidden field immediately (optimistic UI update for array)
        jQuery('#delivery_image').val(updatedImageUrls.length > 0 ? JSON.stringify(updatedImageUrls) : '');
        // Clear the file input to allow re-uploading the same files
        jQuery('#upload_image').val('');

        // AJAX call to delete the image on the server
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url, // Assumes lddfw_ajax_url is globally available
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_delete_delivery_image', // Server handler for deletion
                lddfw_order_id: lddfw_order_id, // Assumes lddfw_order_id is globally available
                lddfw_driver_id: lddfw_driver_id, // Assumes lddfw_driver_id is globally available
                lddfw_delivery_image_url: imageUrlToDelete, // Send the actual image URL
                lddfw_wpnonce: lddfw_nonce.nonce, // Assumes lddfw_nonce is globally available
                lddfw_data_type: 'html' // Or 'json' if expecting a detailed response
            },
            success: function(response) {
                // Log success or handle response if needed
                if (response == '1') { // Assuming server returns '1' on success
                    console.log('Delivery image deleted successfully on server: ' + imageUrlToDelete);
                    // Remove the image element from the DOM only after server confirms
                    $itemElement.remove();
                } else {
                    console.warn('Server responded with an issue deleting image URL: ', imageUrlToDelete, response);
                    // Optional: Revert hidden field update or notify user of server error
                    // Re-add the URL if server deletion failed? Could be complex.
                    // For simplicity, we'll leave the hidden field updated but log the warning.
                     alert(lddfw_alert_texts.error_deleting_server || 'Error deleting image on server.'); // Use a localized text

                    // Restore icon on failure
                    $deleteButton.html(originalIconHtml);
                    // Re-enable clicks
                     $deleteButton.css('pointer-events', 'auto');
                    // Restore the URL in the hidden input if deletion failed
                    imageUrls.push(imageUrlToDelete); // Add it back
                    jQuery('#delivery_image').val(imageUrls.length > 0 ? JSON.stringify(imageUrls) : '');

                }
            },
            error: function(request, status, error) {
                // Log error
                console.error('Error sending delete request for delivery image URL:', imageUrlToDelete, status, error);
                 alert(lddfw_alert_texts.error_deleting_ajax || 'Could not contact server to delete image.'); // Use a localized text
                // Optional: Revert hidden field update or notify user

                 // Restore icon on error
                 $deleteButton.html(originalIconHtml);
                // Re-enable clicks
                 $deleteButton.css('pointer-events', 'auto');
                 // Restore the URL in the hidden input if deletion failed
                 imageUrls.push(imageUrlToDelete); // Add it back
                 jQuery('#delivery_image').val(imageUrls.length > 0 ? JSON.stringify(imageUrls) : '');
            }
            // No 'complete' needed here as success/error handles restoration
        });
    }
     /* </fs_premium_only> */