<?php
if (!defined('ABSPATH')) {
    exit;
}


class CMA_Shortcode_Address {
    
    
    public static function init() {
        
    add_shortcode(apply_filters("{checkmyaddress}_shortcode_tag", 'checkmyaddress') , 'CMA_Shortcode_Address::checkmyaddress');

    }
    public static function checkmyaddress($atts) {
        if (is_admin() || (is_user_logged_in() && !isset(WC()->session)) ||  is_wc_endpoint_url( 'order-received' )   ) {
            return;
        }
        self::enqueue_inline();
        $options = shortcode_atts(array(
            'position' => null,
            'border' => null,
            'current_cart' => null,
            'zone_id' => null,
        ) , $atts);
        $position = empty($options['position']) ? 'center' : $options['position'];
        $border = empty($options['border']) ? 'true' : $options['border'];
        $current_cart = empty($options['current_cart']) ? 'false' : $options['current_cart'];
        $zone_id = empty($options['zone_id']) ? 'all' : $options['zone_id'];
       
         
        ob_start();
        echo cma_output_top_bar(false, 'yes', 'delivery', false, 'address', $position, $border, $current_cart, $zone_id);
        
        return ob_get_clean();
    }
    static function enqueue_inline() {
        if (!wp_script_is('cma-script', 'enqueued')) {
            //CMA_Del::output_delivery_aromodals();
            CMA_Del::enqueue_main_script();
            Delivery_Checker_Address::cma_autocomplete_cmascripts();
            add_action('wp_footer', array(
                'CMA_Del',
                'output_delivery_aromodals'
            ));
        }
    }
}
