<?php
if (!defined('ABSPATH')) {
    exit;
}
function cma_output_top_bar($is_shortcode = false, $delivery_mode = false, $default_delivery_mode = false, $selection_mandatory = false, $validation = false, $position = null, $border = null, $current_cart = null, $zone_id = null ) {
    $border_class = $border === 'false' ? 'cma-no-border' : '';
    $position_class = $position === 'left' ? 'cma-flex-left' : 'cma-flex-center';
    $position_class = $position === 'right' ? 'cma-flex-right' : $position_class;
    $current_cart_class = $current_cart ==  'true' ? 'cma-current-cart' : '';
    $zone_id_class = $zone_id !=  'all' && ($zone_id ) ?  'cma_zone_id' : '';
    $zone_attribute = $zone_id !=  'all' && ($zone_id ) ?  'data-zone_id='.$zone_id : '';
    
    
    
    ob_start();
    echo '<div id="cma-wrapper" class="' . $position_class . ' ' . $current_cart_class.' '.$zone_id_class.'" '.$zone_attribute.'><div id="cma-top-bar-element" class="cma-element"><div class="cma-top-bar-header ' . $border_class .'">';
    if (class_exists('CMA_Del')) {
        echo CMA_Del::output_delivery_switch_static($is_shortcode, $delivery_mode, $default_delivery_mode, $selection_mandatory, $validation);
    }
    if (get_option('cma_message_format', 'print') == 'popup') {
        echo '<span class="top-bar-place-right">';
        do_action('cma_loop_end_3');
        echo '<span class="top-bar-icon top-bar-info"><a id="cma-pop-min" role="button" tabindex="0" data-placement="auto top" data-html="true"  data-toggle="aropopover" data-trigger="click"  data-content="" ></a></span>';
        echo '</span>';
    }
    echo '</div>';
    if (get_option('cma_message_format', 'print') == 'print') {
        echo '<div id="cma_delivery_notice_wrapper" class="cma-element"></div>';
    }
    echo '</div></div>';
    $output = ob_get_clean();
    return $output;
}
function cma_get_address_form_fractions() {
    $cma_user_country = isset(WC()->customer) ? strtoupper(wc_clean(WC()
        ->customer
        ->get_shipping_country())): '';
    ob_start();
    echo '<form id="cma_address_fractions" class="cma-shipping-form" style="display:none">
  <fieldset id="cma_shipping_address">
		<p class="form-row form-row-wide address-field validate-required " id="cma_bshipping_address_1_field" data-priority="50"> <input id="cma_bshipping_address_1" name="cma_bshipping_address_1" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_bshipping_streetnumber_field" data-priority="50"> <input id="cma_bshipping_streetnumber" name="cma_bshipping_streetnumber" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field" id="cma_bshipping_address_2_field" data-priority="60"> <input id="cma_bshipping_address_2" name="cma_bshipping_address_2" value="" type="hidden"></p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_bshipping_city_field" data-priority="70"> <input id="cma_bshipping_city" name="cma_bshipping_city" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_adm1_short_field" data-priority="50"> <input id="cma_adm1_short" name="cma_adm1_short" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_adm2_short_field" data-priority="50"> <input id="cma_adm2_short" name="cma_adm2_short" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_adm1_long_field" data-priority="50"> <input id="cma_adm1_long" name="cma_adm1_long" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required " id="cma_adm2_long_field" data-priority="50"> <input id="cma_adm2_long" name="cma_adm2_long" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required validate-postcode " id="cma_bshipping_postcode_field" data-priority="65"> <input id="cma_bshipping_postcode" name="cma_bshipping_postcode" value="" type="hidden"> </p>
		<p class="form-row form-row-wide address-field validate-required validate-postcode " id="cma_bshipping_country_field" data-priority="40"> <input id="cma_bshipping_country" name="cma_bshipping_country" value="' . esc_attr($cma_user_country) . '" type="hidden"> </p>
	</fieldset>
</form>';
    return ob_get_clean();
}
function cma_get_address_form($show, $placement) {
   
    $default_address = CMA_Del::config_default_customer_address('get');
    $session_address = WC()
        ->session
        ->get('cma_shipping_address_full', '');
    $session_address = $session_address != '' ? $session_address : $default_address;
    $classes = 'del_address_row ';
    $classes .= $placement == 'extern' ? ' cma-top-bar_address_row' : '';
    $classes .= $placement == 'side_bar' ? ' arorow' : '';
    $classes .= $placement == 'shortcode' ? ' cma-address-shortcode' : '';
    $classes_wrapper = $placement == 'shortcode' ? ' cma-element ' : '';
    $form_classes = $placement == 'extern' || $placement == 'shortcode' ? '' : '';
    $form_classes .= get_option('cma_delivery_colormode', 'no') == 'yes' ? ' cma-form-feedback' : '';
    $positive_color = get_option('cma_color_positive');
    $negative_color = get_option('cma_color_negative');
    ob_start();
?>
 <style>
input.cma-form-feedback.cma-success:focus, input.cma-form-feedback.cma-success {
    border-color: <?php echo $positive_color ?>;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075), 0 0 6px <?php echo $positive_color ?>;
    box-shadow: inset 0 1px 1px rgba(0,0,0,0.075), 0 0 6px <?php echo $positive_color ?>;
}
.cma-text-feedback.cma-success #cma_delivery_notice {
    box-shadow: 0 0 10px 6px <?php echo $positive_color ?>;
    background-color: <?php echo $positive_color ?>;
}
input.cma-form-feedback.cma-fail:focus, input.cma-form-feedback.cma-fail {
    border-color: <?php echo $negative_color ?>;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075), 0 0 6px <?php echo $negative_color ?>;
    box-shadow: inset 0 1px 1px rgba(0,0,0,0.075), 0 0 6px <?php echo $negative_color ?>;
}
.cma-text-feedback.cma-fail #cma_delivery_notice {
    box-shadow: 0 0 10px 6px <?php echo $negative_color ?>;
    background-color: <?php echo $negative_color ?>;
}
.cma-element .aropopover.failure {
	background-color: <?php echo $negative_color ?>;
	color: white;
	border: none;
}

.cma-element .aropopover.bottom.failure>.arrow:after,
.cma-element .aropopover.top.failure>.arrow:after {
	border-top-color: <?php echo $negative_color ?>;
	border-bottom-color: <?php echo $negative_color ?>;
}

.cma-element .aropopover.left.failure>.arrow:after {
	border-left-color: <?php echo $negative_color ?>;
}
.cma-element .aropopover.success {
	background-color: <?php echo $positive_color ?>;
	color: white;
	border: none;
}



.cma-element .aropopover.bottom.success>.arrow:after,
.cma-element .aropopover.top.success>.arrow:after {
	border-top-color: <?php echo $positive_color ?>;
	border-bottom-color: <?php echo $positive_color ?>;
}

.cma-element .aropopover.left.success>.arrow:after {
	border-left-color: <?php echo $positive_color ?>;
}



</style>
 <?php
    echo '<div class="' . esc_attr($classes_wrapper) . '" >
	<div class="' . esc_attr($classes) . '" id="cma_adress_row" ' . esc_html($show) . ' >';
    echo '<form id="cma_shipping_form" class="cma-shipping-form">
    <div class="cma-input-wrapper">
		<input  id="autocomplete_cma"  name="autocomplete_cma" value="' . esc_attr($session_address) . '" type="text" class="form-control input-text ' . esc_attr($form_classes) . '" placeholder="' . __('Delivery Address?', 'check-my-address') . '" autocomplete="address-line1">';
 if(get_option('cma_geolocate_position','no') == 'yes'){
  echo '<span class="cma-crosshair" title="'. esc_attr(__('Locate my address', 'check-my-address')) .'"><i class="fas fa-crosshairs cma-crosshair-icon"></i> </span>';
 }
  echo '</div>
  
 
  
 <button type="submit" id="cma_address_button" class="button alt">' . __('Check Address', 'check-my-address') . '</button>
	</form>';
    if (get_option('cma_checkout_address', 'no') == 'yes') {
        echo '<span class="cma-save-address">
  <input type="checkbox"  id="cma_saveaddress_checkbox" name="cma_saveaddress_checkbox" checked>
  <label for="cma_saveaddress_checkbox" >' . __("Use this address at checkout?", 'check-my-address') . '</label>
</span>';
    }
   
    if ($placement == 'side_bar') {
        $text_classes = get_option('cma_delivery_colormode', 'no') == 'yes' ? ' cma-text-feedback' : '';
        echo '<div id="cma_delivery_notice_outer" class="' . $text_classes . '"></div>';
    }
    echo '</div></div>';
    return ob_get_clean();
}

