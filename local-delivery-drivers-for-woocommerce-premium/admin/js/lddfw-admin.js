jQuery(document).ready(
    function($) {

        // Check if elements with the class 'lddfw_tagify' exist
        if ($('.lddfw_tagify').length > 0) {
            // Iterate over each element and initialize Tagify
            $('.lddfw_tagify').each(function() {
                new Tagify(this); // 'this' refers to the current DOM element in the loop
            });
        }

        $("body").on("click", "#lddfw_check_google_keys", function() {

            var lddfw_loading = $(this).attr("data-loading");
            var lddfw_title = $(this).attr("data-title");
            var lddfw_alert = $(this).attr("data-alert");
            var lddfw_google_api_key = $("#lddfw_google_api_key").val();
            var lddfw_google_api_key_server = $("#lddfw_google_api_key_server").val();

            $("#lddfw_check_google_keys_wrap").show();
            if (lddfw_google_api_key == "" || lddfw_google_api_key_server == "") {
                $("#lddfw_check_google_keys_wrap").html(lddfw_alert);
                return false;
            }

            $("#lddfw_check_google_keys_wrap").html(lddfw_loading);
            $.post(
                lddfw_ajax.ajaxurl, {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_check_google_keys',
                    lddfw_obj_id: lddfw_google_api_key_server,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                },
                function(data) {
                    $("#lddfw_check_google_keys_wrap").html("");
                    $("#lddfw_check_google_keys_wrap").append('<p class="title">' + lddfw_title + ' <b>' + lddfw_google_api_key_server + '</b></p>');
                    $("#lddfw_check_google_keys_wrap").append(data);

                    $("#lddfw_check_google_keys_wrap").append('<p class="title">' + lddfw_title + ' <b>' + lddfw_google_api_key + '</b></p>');
                    $("#lddfw_check_google_keys_wrap").append('<p>Maps Embed API:</p><iframe width="450" height="250" style="border:0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps/embed/v1/place?key=' + lddfw_google_api_key + '&q=chicago+il"></iframe>');
                    $("#lddfw_check_google_keys_wrap").append('<p>Maps JavaScript API:</p><div style="width:450px;height:250px;" id="ddfw_test_map"></div><script src="https://maps.googleapis.com/maps/api/js?key=' + lddfw_google_api_key + '&callback=initMap&v=weekly" defer></script>');


                    function initMap() {

                        var directionsService = new google.maps.DirectionsService;

                        var directionsDisplay = new google.maps.DirectionsRenderer;
                        var map = new google.maps.Map(document.getElementById('ddfw_test_map'), {
                            zoom: 8,
                            center: { lat: 41.85, lng: -87.65 }
                        });
                        directionsDisplay.setMap(map);

                        directionsService.route({
                            origin: 'oklahoma city, ok',
                            destination: 'chicago, il',
                            travelMode: 'DRIVING'
                        }, function(response, status) {
                            if (status === 'OK') {
                                directionsDisplay.setDirections(response);
                                $("#lddfw_check_google_keys_wrap").append('<p>Directions API: OK');
                            } else {
                                $("#lddfw_check_google_keys_wrap").append('<p>Directions API:' + status);
                            }
                        });

                        var geocoder = new google.maps.Geocoder();
                        var address = 'indiana, in';
                        geocoder.geocode({ 'address': address }, function(results, status) {
                            if (status == 'OK') {
                                $("#lddfw_check_google_keys_wrap").append('<p>Geocoding API: OK');
                                map.setCenter(results[0].geometry.location);
                                var marker = new google.maps.Marker({
                                    map: map,
                                    position: results[0].geometry.location
                                });
                            } else {
                                $("#lddfw_check_google_keys_wrap").append('<p>Geocoding API: ' + status);

                            }
                        });

                    }

                    window.initMap = initMap;

                }
            );
            return false;
        });

        $("body").on("click", ".lddfw_premium_close", function() {
            $(this).parent().hide();
            return false;
        });
        $("body").on("click", ".lddfw_star_button", function() {
            if ($(this).next().is(":visible")) {
                $(this).next().hide();
            } else {
                $(".lddfw_premium_feature_note").hide();
                $(this).next().show();
            }
            return false;
        });

        function lddfw_dates_range() {
            var $lddfw_this = $("#lddfw_dates_range");
            if ($lddfw_this.val() == "custom") {
                $("#lddfw_dates_custom_range").show();
            } else {
                var lddfw_fromdate = $('option:selected', $lddfw_this).attr('fromdate');
                var lddfw_todate = $('option:selected', $lddfw_this).attr('todate');
                $("#lddfw_dates_custom_range").hide();
                $("#lddfw_dates_range_from").val(lddfw_fromdate);
                $("#lddfw_dates_range_to").val(lddfw_todate);
            }
        }

        $("#lddfw_dates_range").change(
            function() {
                lddfw_dates_range()
            }
        );

        if ($("#lddfw_dates_range").length) {
            lddfw_dates_range();
        }

        /* <fs_premium_only> */
        function lddfw_driver_commission_type() {
            $("#lddfw_first_commission_input").hide();
            $("#lddfw_second_commission_input").hide();
            var $lddfw_commission_type = $("#lddfw_driver_commission_type").val();
            var $lddfw_commission_label = $('#lddfw_driver_commission_type').find('option:selected').attr('data-label');
            var $lddfw_commission_description = $('#lddfw_driver_commission_type').find('option:selected').attr('data-description');

            var $lddfw_commission_label_second = $('#lddfw_driver_commission_type').find('option:selected').attr('data-second-label');
            var $lddfw_commission_description_second = $('#lddfw_driver_commission_type').find('option:selected').attr('data-second-description');

            if ($lddfw_commission_label != "" || $lddfw_commission_description != "") {
                $("#lddfw_first_commission_input").show();
                $("#lddfw_first_commission_label").html($lddfw_commission_label);
                $("#lddfw_first_commission_description").html($lddfw_commission_description);
            }

            if ($lddfw_commission_label_second != "" || $lddfw_commission_description_second != "") {
                $("#lddfw_second_commission_input").show();
                $("#lddfw_second_commission_label").html($lddfw_commission_label_second);
                $("#lddfw_second_commission_description").html($lddfw_commission_description_second);
            }

            if ($lddfw_commission_type == '') {
                $("#lddfw_driver_commission_value").val("");
                $("#lddfw_driver_commission_second_value").val("");
            }

        }


        $("#lddfw_driver_commission_type").change(
            function() {
                lddfw_driver_commission_type()
            }
        );

        if ($("#lddfw_driver_commission_type").length) {
            lddfw_driver_commission_type();
        }

        $(".lddfw_media_delete").click(
            function() {
                var lddfw_object_id = $(this).attr("data");
                $("#" + lddfw_object_id).val("");
                $("#" + lddfw_object_id + "_preview").html("");
            }
        );

        $('.lddfw_media_manager').click(
            function(e) {
                var lddfw_object_id = $(this).attr("data");
                e.preventDefault();
                var lddfw_image_frame;
                if (lddfw_image_frame) {
                    lddfw_image_frame.open();
                }
                // Define image_frame as wp.media object
                lddfw_image_frame = wp.media({
                    title: 'Select Media',
                    multiple: false,
                    library: {
                        type: 'image',
                    }
                });

                lddfw_image_frame.on(
                    'close',
                    function() {
                        var lddfw_selection = lddfw_image_frame.state().get('selection');
                        var lddfw_gallery_ids = new Array();
                        var lddfw_index = 0;
                        lddfw_selection.each(
                            function(attachment) {
                                lddfw_gallery_ids[lddfw_index] = attachment['id'];
                                lddfw_index++;
                            }
                        );
                        var lddfw_ids = lddfw_gallery_ids.join(",");
                        jQuery('input#' + lddfw_object_id).val(lddfw_ids);
                        lddfw_refresh_image(lddfw_ids, lddfw_object_id);
                    }
                );

                lddfw_image_frame.on(
                    'open',
                    function() {
                        var lddfw_selection = lddfw_image_frame.state().get('selection');
                        var lddfw_ids = jQuery('input#' + lddfw_object_id).val().split(',');
                        lddfw_ids.forEach(
                            function(id) {
                                var lddfw_attachment = wp.media.attachment(id);
                                lddfw_attachment.fetch();
                                lddfw_selection.add(lddfw_attachment ? [lddfw_attachment] : []);
                            }
                        );

                    }
                );

                lddfw_image_frame.open();
            }
        );

        if ($(".lddfw-color-picker").length) {
            $(".lddfw-color-picker").wpColorPicker();
        }
        if ($(".lddfw-datepicker").length) {
            $(".lddfw-datepicker").datepicker({ dateFormat: "yy-mm-dd" });
        }

        $(".lddfw_account_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_account_status',
                            lddfw_account_status: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_account_status',
                            lddfw_account_status: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                return false;
            }
        );
        $(".lddfw_availability_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_availability',
                            lddfw_availability: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_availability',
                            lddfw_availability: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                lddfw_counters();
                return false;
            }
        );
        $(".lddfw_claim_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_claim_permission',
                            lddfw_claim: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_claim_permission',
                            lddfw_claim: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                lddfw_counters();
                return false;
            }
        );

        /* </fs_premium_only> */


        $("body").on("click",".lddf_button_toggle",
            function() {
                 $(this).next().toggle();
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

        function lddfw_select_toggle(lddfw_toggle_select) {
            var lddfw_toggle_select_value = lddfw_toggle_select.val();
            var lddfw_toggle_select_data_array = lddfw_toggle_select.attr("data").split(',');
            var lddfw_toggle = false;

            $.each(lddfw_toggle_select_data_array, function(key, value) {
                if (value === lddfw_toggle_select_value) {
                    lddfw_toggle = true;
                    return false;
                }
            });

            if (lddfw_toggle) {
                lddfw_toggle_select.parent().next().show();
            } else {
                lddfw_toggle_select.parent().next().hide();
            }
        }

        $(".lddfw_toggle_select").change(function() {
            lddfw_select_toggle($(this));
        });

        /*
			$(".lddfw_toggle_select").each(function() {
				lddfw_select_toggle($(this));
			});
		*/

        $(".lddfw_copy_template_to_textarea").click(
            function() {
                var textarea_id = $(this).parent().parent().find("textarea").attr("id");

                var text = $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        $("body").on("click", ".lddfw_copy_tags_to_textarea a", function() {
        
                var textarea_id = $(this).parent().attr("data-textarea");
                var text = $("#" + textarea_id).val() + $(this).attr("data");
              
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        /* <fs_premium_only> */

        $(".post-type-shop_order #bulk-action-selector-top").change(
            function() {

                if ($(this).val() == "assign_a_driver") {
                    var $this = $(this);
                    if ($("#lddfw_driverid_lddfw_action").length) {
                        $("#lddfw_driverid_lddfw_action").show();
                    } else {
                        $.post(
                            lddfw_ajax.ajaxurl, {
                                action: 'lddfw_ajax',
                                lddfw_service: 'lddfw_get_drivers_list',
                                lddfw_obj_id: 'lddfw_action',
                                lddfw_wpnonce: lddfw_nonce.nonce,
                            },
                            function(data) {
                                $(data).insertAfter($this);
                            }
                        );
                    }
                } else {
                    $("#lddfw_driverid_lddfw_action").hide();
                }
            }
        );

        $(".post-type-shop_order #bulk-action-selector-bottom").change(
            function() {
                if ($(this).val() == "assign_a_driver") {
                    var $this = $(this);
                    if ($("#lddfw_driverid_lddfw_action2").length) {
                        $("#lddfw_driverid_lddfw_action2").show();
                    } else {
                        $.post(
                            lddfw_ajax.ajaxurl, {
                                action: 'lddfw_ajax',
                                lddfw_service: 'lddfw_get_drivers_list',
                                lddfw_obj_id: 'lddfw_action2',
                                lddfw_wpnonce: lddfw_nonce.nonce,
                            },
                            function(data) {
                                $(data).insertAfter($this);
                            }
                        );
                    }
                } else {
                    $("#lddfw_driverid_lddfw_action2").hide();
                }
            }
        );
        $("#lddfw_custom_fields_new").click(
            function() {
                $("#lddfw_custom_fields_raw").clone().appendTo("#lddfw_custom_fields_table");
                return false;
            }
        );
        /* </fs_premium_only> */

    }
);

/* <fs_premium_only> */
function lddfw_counters() {
    var lddfw_unavailable_counter = jQuery(".lddfw_availability_icon").not('.lddfw_active').length;
    var lddfw_available_counter = jQuery(".lddfw_availability_icon.lddfw_active").length;
    var lddfw_unclaim_counter = jQuery(".lddfw_claim_icon").not('.lddfw_active').length;
    var lddfw_claim_counter = jQuery(".lddfw_claim_icon.lddfw_active").length;

    jQuery("#lddfw_available_counter").html(lddfw_available_counter);
    jQuery("#lddfw_claim_counter").html(lddfw_claim_counter);

    jQuery("#lddfw_unavailable_counter").html(lddfw_unavailable_counter);
    jQuery("#lddfw_unclaim_counter").html(lddfw_unclaim_counter);
}

// Ajax request to refresh the image preview
function lddfw_refresh_image(the_id, div_id) {
    var data = {
        action: 'lddfw_ajax',
        lddfw_service: 'lddfw_set_image',
        lddfw_image_id: the_id,
        lddfw_wpnonce: lddfw_nonce.nonce,
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


function lddfw_map_style() {
    let lddfw_map_style = [{
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


    return lddfw_map_style;
}
/* </fs_premium_only> */