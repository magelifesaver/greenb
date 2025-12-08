<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!isset($settings_args_delivery)) {
    $settings_args_delivery = array(
        array(
            'name' => __('Settings', 'check-my-address') ,
            'type' => 'title',
            'id' => 'cma_delivery_swithcher',
            'desc' => __('Insert the Address Checker with shortcode [checkmyaddress] or [checkmyaddress current_cart="true"] if you like to the validation result to reflect the actual cart contents.', 'check-my-address')
        ) ,
        array(
            'name' => __('Google Maps API Key', 'check-my-address') ,
            'id' => 'cma_google_maps_api',
            'type' => 'text',
           
            'desc' => __(' <p><a href="https://cloud.google.com/maps-platform/#get-started" target="_blank">Visit Google to get your API Key &raquo;</a></p>', 'check-my-address')
        ) ,
        array(
            'name' => __('Pick Delivery Location from Map', 'check-my-address') ,
            'id' => 'cma_pick_delivery_location',
            'default' => 'no',
            'type' => 'select',
            'options' => array(
                'no' => __('Disable', 'check-my-address') ,
                'at_fail' => __('When address geolocation fails', 'check-my-address') ,
                //  'as_option' => __('As an alternative to address validation','check-my-address'),

            ) ,
        ) ,
        
         array(
            'name' => __('Geolocate user position?', 'check-my-address') ,
            'id' => 'cma_geolocate_position',
            'css' => 'max-width:100px;',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Add the ability for users to geolocate their position.', 'check-my-address') ,
        ) ,
          array(
            'name' => __('Geolocation accuracy', 'check-my-address') ,
            'id' => 'cma_max_accuracy',
            'type' => 'number',
            'default' => 1000,
            'desc' => __('Maximum accepted accuracy in meters.', 'check-my-address') ,
           
        ) ,
          array(
            'name' => __('Do address validation if geolocation succeeds?', 'check-my-address') ,
            'id' => 'cma_validation_after_geolocation',
            'css' => 'max-width:100px;',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Directly validate the address found by a user position request.', 'check-my-address') ,
        ) ,
        array(
            'name' => __('Use Address at checkout?', 'check-my-address') ,
            'id' => 'cma_checkout_address',
            'css' => 'max-width:100px;',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Let users choose if they like to use a validated address at checkout. The address will then be the default address at checkout page.', 'check-my-address') ,
        ) ,
        array(
            'name' => __('Show colors as validation feedback?', 'check-my-address') ,
            'id' => 'cma_delivery_colormode',
            'css' => 'max-width:100px;',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Color code delivery messages and forms depending on positive or negative validation result.', 'check-my-address')
        ) ,
        array(
            'name' => __('Positive Feedback Color', 'check-my-address') ,
            'id' => 'cma_color_positive',
            'type' => 'text',
            'class' => 'cma-color-picker',
            'default' => '#6dd26f'
        ) ,
        array(
            'name' => __('Negative Feedback Color', 'check-my-address') ,
            'id' => 'cma_color_negative',
            'type' => 'text',
            'class' => 'cma-color-picker',
            'default' => '#ec5c5c'
        ) ,
        array(
            'name' => __('Delivery message format', 'check-my-address') ,
            'id' => 'cma_message_format',
            'default' => 'print',
            'type' => 'select',
            'options' => array(
                'popup' => __('Popup', 'check-my-address') ,
                'print' => __('Print out', 'check-my-address') ,
            ) ,
            'desc' => __('Choose how to show standard delivery messages.', 'check-my-address') ,
            'desc_tip' => true,
        ) ,
        array(
            'name' => __('Default Popup Message', 'check-my-address') ,
            'id' => 'cma_top_bar_info',
            'type' => 'text',
        ) ,
        array(
            'name' => __('Add shipping cost suffix to the delivery notice?', 'check-my-address') ,
            'id' => 'cma_del_message_price_suffix',
            'css' => 'max-width:100px;',
            'default' => 'yes',
            'type' => 'checkbox',
            'desc' => __('Add shipping cost suffix to the delivery notice that shows after address validation.', 'check-my-address')
        ) ,
        array(
            'type' => 'sectionend',
            'id' => 'cma_delivery_swithcher'
        ) ,
    );
}
if (!isset($settings_args_advanced)) {
    $settings_args_advanced = array(
        array(
            'name' => __('Advanced Settings', 'check-my-address') ,
            'type' => 'title',
            'id' => 'cma_advanced'
        ) ,
         array(
            'name' => __('Simulate cart quantity?', 'check-my-address') ,
            'id' => 'cma_simulate_quantity',
            'default' => 'no',
            'type' => 'checkbox',
            'css' => 'max-width:300px;',
            'desc' => __('When doing address validation not using shortcode argument "currrent_cart" set to "true" or when cart is empty it is not possible to perform shipping cost calculations with regards to [qty] shortcode. This option will simulate cost calculation with cart quantity set to 1.', 'check-my-address') ,
            'desc_tip'=> true,
        ) ,
       
        array(
            'name' => __('Do not restrict Google Autocomplete to IP-located country', 'check-my-address') ,
            'id' => 'cma_restrict_country',
            'default' => 'no',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Allow Google Autocomplete to suggest addresses from whole world', 'check-my-address') ,
        ) ,
        array(
            'name' => __('Geolocate user for improved Autocomplete suggestions', 'check-my-address') ,
            'id' => 'cma_browser_geolocation',
            'default' => 'yes',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Let browser geolocate the user for improved address suggestions.', 'check-my-address') ,
        ) ,
        array(
            'name' => __('Advanced Google Response Options', 'check-my-address') ,
            'id' => 'cma_address_advanced',
            'default' => 'no',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('View advanced options', 'check-my-address')
        ) ,
        array(
            'name' => __('Autocomplete Suggestion Types', 'check-my-address') ,
            'id' => 'cma_autocomplete_types',
            'default' => array(
                'geocode'
            ) ,
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'options' => array(
                'geocode' => __('geocode (Recommended)', 'check-my-address') ,
                'address' => __('address', 'check-my-address') ,
                'establishment' => __('establishment', 'check-my-address') ,
            ) ,
            'desc' => __('You may restrict results from a address autocomplete request to be of a certain type.', 'check-my-address') . __('<p><a href="https://developers.google.com/maps/documentation/places/web-service/supported_types#table3" target="_blank">Learn more.</a></p>')
        ) ,
        array(
            'name' => __('Geocode Response Types', 'check-my-address') ,
            'id' => 'cma_result_types',
            'default' => array(
                "establishment",
                "subpremise",
                "premise",
                "street_address"
            ) ,
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'options' => array(
                'street_address' => esc_html('street_address (Recommended)') ,
                'premise' => esc_html('premise (Recommended)') ,
                'subpremise' => esc_html('subpremise (Recommended)') ,
                'establishment' => esc_html('establishment (Recommended)') ,
                'intersection' => esc_html('intersection') ,
                'neighborhood' => esc_html('neighborhood') ,
                'point_of_interest' => esc_html('point_of_interest') ,
                'postal_code' => esc_html('postal_code') ,
                'restaurant' => esc_html('restaurant') ,
                'plus_code' => esc_html('plus_code') ,
                'route' => esc_html('route') ,
                'locality' => esc_html('locality') ,
            ) ,
            'desc' => __('You may restrict results from a address geocode request to be of a certain type.', 'check-my-address') . __('<p><a href="https://developers.google.com/maps/documentation/geocoding/overview#Types" target="_blank">Learn more.</a></p>')
        ) ,
        array(
            'name' => __('Delete settings?', 'check-my-address') ,
            'id' => 'cma_clean_settings',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Delete all settings on plugin removal?', 'check-my-address') ,
            'default' => 'no'
        ) ,
        array(
            'type' => 'sectionend',
            'id' => 'cma_advanced'
        )
    );
}
