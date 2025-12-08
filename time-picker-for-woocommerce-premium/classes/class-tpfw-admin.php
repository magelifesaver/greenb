<?php
if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;


if (!class_exists('TPFW_Admin')) {
    /**
     * Class TPFW_Admin
     *
     * @since 1.0
     */
    class TPFW_Admin
    {





        public function __construct()
        {



            if (is_admin() && is_user_logged_in()) {

                add_action('woocommerce_admin_order_preview_start', array(
                    $this,
                    'tpfw_preview_time'
                ));
            }

            add_action('pre_get_posts', array(
                $this,
                'shop_order_column_meta_field_sortable_orderby'
            ));

            add_filter('woocommerce_email_recipient_new_order', array(
                $this,
                'new_order_filter_recipient'
            ), 999, 2);





            // Compatibility with HPOS Orders
            if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled()) {


                add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_delivery_mode_order_column'), 20);
                add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'tpfw_add_new_order_admin_list_column_content'), 20, 2);
                add_filter('manage_woocommerce_page_wc-orders_sortable_columns', array($this, 'shop_order_column_meta_field_sortable'), 20);

                add_filter('woocommerce_order_list_table_prepare_items_query_args', array($this, 'hpos_orderby_var'), 20, 1);
                add_filter('woocommerce_shop_order_list_table_sortable_columns', array($this, 'hpos_is_sortable'), 20, 1);

               


              



            } else {

                add_filter('manage_edit-shop_order_columns', array(
                    $this,
                    'add_delivery_mode_order_column'
                ));
                add_action('manage_shop_order_posts_custom_column', array(
                    $this,
                    'tpfw_add_new_order_admin_list_column_content'
                ), 10, 2);
                add_filter("manage_edit-shop_order_sortable_columns", array(
                    $this,
                    'shop_order_column_meta_field_sortable'
                ));

               

            }



        }






        function tpfw_add_new_order_admin_list_column_content($column, $order_id)
        {



            if ('tpfw_order_type' === $column) {
                $order = wc_get_order($order_id);
                $meta3 = $order->get_meta('tpfw_delivery_mode', true);
                if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {
                    $string = '';
                    if ($meta3 == 'delivery') {
                        $string = __('Delivery', 'checkout-time-picker-for-woocommerce');
                    } else if ($meta3 == 'pickup') {
                        $string = __('Pickup', 'checkout-time-picker-for-woocommerce');
                    }
                    echo esc_html($string);
                }

            } else if ('tpfw_order_time' === $column) {
                $order = wc_get_order($order_id);
                $meta3 = $order->get_meta('tpfw_picked_time', true);
                $meta4 = $order->get_meta('tpfw_picked_time_localized', true);
                $meta5 = $order->get_meta('tpfw_picked_time_range_end_localized', true);
                if (is_string($meta4) && $meta4 != '' && !empty($meta4) && $meta4 !== false && $meta4 != 'undefined') {
                    $echo = $meta4;
                    if (is_string($meta5) && $meta5 != '' && !empty($meta5) && $meta5 !== false && $meta5 != 'undefined') {
                        $echo .= ' - ' . $meta5;
                    }
                    echo esc_html($echo);
                } else if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {
                    echo esc_html(TPFW_Time::format_datetime($meta3));
                }
            }




        }

        function shop_order_column_meta_field_sortable_orderby($query)
        {
            global $pagenow;
            if (is_admin() && (('admin.php' === $pagenow ) || ($query->is_main_query() && 'edit.php' === $pagenow  ))) {
                $orderby = $query->get('orderby');
               
              

                if ('tpfw_picked_time' === $orderby) {
                    $meta_key = 'tpfw_picked_time_timestamp';
                    $query->set('meta_key', $meta_key);
                    $query->set('orderby', 'meta_value_num');
                }

            }

        }




       

       




        function new_order_filter_recipient($recipient, $order)
        {

            if (!$order instanceof WC_Order) {
                return $recipient;
            }
            if (!is_null($order) && !OrderUtil::is_order($order->get_id(), wc_get_order_types())) {
                return $recipient;
            }


            $meta = $order->get_meta('tpfw-pickup-location', true);
            if (isset($meta) && is_array($meta) && $meta != '' && !empty($meta) && $meta !== false) {

                if (isset($meta['email']) && is_string($meta['email']) && $meta['email'] != '' && is_email($meta['email'])) {
                    $recipient = $meta['email'];
                }

            }


            return $recipient;
        }
       
      









        function hpos_is_sortable($args)
        {

            $args['tpfw_picked_time'] = 'tpfw_picked_time';

            return $args;
        }

        function hpos_orderby_var($request)
        {

            if ($request['orderby'] === 'tpfw_picked_time') {
                $request['orderby'] = 'meta_value_num';
                $request['meta_key'] = 'tpfw_picked_time_timestamp';

            }


            return $request;
        }

        function add_delivery_mode_order_column($columns)
        {

            $columns['tpfw_order_type'] = __('Order Type', 'checkout-time-picker-for-woocommerce');
            if (get_option('tpfw_pickuptimes_enable', 'no') == 'yes' || get_option('tpfw_deliverytime_enable', 'no') == 'yes') {
                $order_actions = isset($columns['wc_actions']) ? $columns['wc_actions'] : null;
                if ($order_actions != null) {


                    unset($columns['wc_actions']);
                    $columns['wc_actions'] = $order_actions;
                }
                $columns['tpfw_order_time'] = __('Selected Time', 'checkout-time-picker-for-woocommerce');

            }

            return $columns;
        }

        function shop_order_column_meta_field_sortable($columns)
        {

            $meta_key = 'tpfw_picked_time';
            $args = wp_parse_args(array(
                'tpfw_order_time' => $meta_key,




            ), $columns);

            return $args;
        }

        function tpfw_preview_time()
        {



            ?>



            <# var tpfwmeta=data.data.meta_data; var tpfw_date_localized=false; var tpfw_date=false; 
             var tpfw_range_end='' ; _.each(tpfwmeta, function(el3, i2) { if (el3.key=='tpfw_picked_time_range_end_localized' ) {
                tpfw_range_end=' - ' + el3.value; } }); _.each(data.data.meta_data, function(el, i) { 
                     if(el.key=='tpfw_delivery_mode' ){ if (el.value=='pickup' ){#>

                    <div class="wc-order-preview-addresses" style="padding-bottom:0px">

                        <div class="wc-order-preview-address">

                            <h1><?php esc_html_e('Pickup Order', 'checkout-time-picker-for-woocommerce'); ?></h1>



                        </div>
                    </div>

                    <# } else if (el.value=='delivery' ){#>

                        <div class="wc-order-preview-addresses" style="padding-bottom:0px">

                            <div class="wc-order-preview-address">

                                <h1><?php esc_html_e('Delivery Order', 'checkout-time-picker-for-woocommerce'); ?></h1>



                            </div>



                        </div>





                        <# } }  if ( el.key=='tpfw_picked_time_localized' ) { tpfw_date_localized=el.value; }else
                                if(el.key=='tpfw_picked_time' ){ tpfw_date=el.value; } }); if( tpfw_date_localized !=false ){ #>

                                <div class="wc-order-preview-addresses">

                                    <div class="wc-order-preview-address">

                                        <h2>
                                            <?php esc_html_e('Selected Time', 'checkout-time-picker-for-woocommerce');
                                            ?>
                                        </h2>



                                        {{tpfw_date_localized + ' ' + tpfw_range_end}}

                                    </div>
                                </div>

                                <# }else if(tpfw_date !=false ){ #>



                                    <div class="wc-order-preview-addresses">

                                        <div class="wc-order-preview-address">

                                            <h2>
                                                <?php esc_html_e('Selected Time', 'checkout-time-picker-for-woocommerce');
                                                ?>
                                            </h2>



                                            {{tpfw_date + ' ' + tpfw_range_end}}

                                        </div>
                                    </div>

                                    <# } #>





                                        <?php
        }












    }
    $TPFW_Admin = new TPFW_Admin();
}






add_action('woocommerce_checkout_update_order_meta', 'tpfw_checkout_field_update_order_meta', 2, 10);
function tpfw_checkout_field_update_order_meta($order_id, $data)
{

    

    if (!TPFW::cart_needs_shipping()) {
        return;
    }

    $type = isset($data['shipping_method'][0]) ? $data['shipping_method'][0] : '';
    $type = strtok($type, ':');

    $do_save = false;
    $order = wc_get_order($order_id);


    $mode = $type == 'flat_rate' || $type == 'free_shipping' || $type == 'szbd-shipping-method' ? 'delivery' : 'undefined';
    $mode = $type == 'local_pickup' || $type == 'pickup_location' ? 'pickup' : $mode;

    $order->update_meta_data('tpfw_delivery_mode', $mode);
    $do_save = true;

    $nonce_ok = ! empty( $_REQUEST['_tpfwfieldnonce'] ) && wp_verify_nonce( sanitize_text_field( stripslashes_from_strings_only( $_REQUEST['_tpfwfieldnonce'] ) ), 'tpfw_text_field_action' ) == 1;

    if ($nonce_ok && isset($_POST['tpfw-date']) && !empty($_POST['tpfw-date']) && $_POST['tpfw-date'] != '' && isset($_POST['tpfw-time']) && !empty($_POST['tpfw-time']) && $_POST['tpfw-time'] != '') {

        $time_ = wc_get_post_data_by_key('tpfw-time');
        $date = wc_get_post_data_by_key('tpfw-date');
        $time = str_contains($time_, '-') ? substr($time_, 0, strrpos($time_, '-')) : $time_;
        $order->update_meta_data('tpfw_picked_time_localized', stripslashes(TPFW_Time::format_datetime(sanitize_text_field($date . ' ' . $time), $date, $time)));
        $order->update_meta_data('tpfw_picked_time', stripslashes(sanitize_text_field($date . ' ' . $time)));

        $order->update_meta_data('tpfw_picked_time_timestamp', stripslashes(TPFW_Time::get_timestamp(sanitize_text_field($date . ' ' . $time))));
        $do_save = true;
        if (get_option('tpfw_timepicker_ranges', 'no') == 'yes') {
            $range_size = $mode == 'pickup' ? get_option('tpfw_pickuptime_step', 0) : ($mode == 'delivery' ? get_option('tpfw_deliverytime_step', 0) : 0);
            $order->update_meta_data('tpfw_picked_time_range_end_localized', stripslashes(TPFW_Time::format_datetime_range_end(sanitize_text_field($date . ' ' . $time), $range_size)));
        }
    }

    if ($do_save) {
        $order->save();
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'tpfw_show_checkout_field_admin_order_meta', 10, 1);
function tpfw_show_checkout_field_admin_order_meta($order)
{
    $meta3 = $order->get_meta('tpfw_delivery_mode', true);
    $meta2 = $order->get_meta('tpfw_picked_time', true);
    $meta4 = $order->get_meta('tpfw_picked_time_localized', true);
    $meta5 = $order->get_meta('tpfw_picked_time_range_end_localized', true);
    if (is_string($meta4) && $meta4 != '' && !empty($meta4) && $meta4 !== false && $meta4 != 'undefined') {
        $time = $meta4;
        if (is_string($meta5) && $meta5 != '' && !empty($meta5) && $meta5 !== false && $meta5 != 'undefined') {
            $time .= ' - ' . $meta5;
        }
    } else if (is_string($meta2) && $meta2 != '' && !empty($meta2) && $meta2 !== false && $meta2 != 'undefined') {
        $time = TPFW_Time::format_datetime($meta2);
    }
    if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') { {
            if ($meta3 == 'pickup') {
                if (isset($time)) {
                    $order_string = $order->has_status(array('fulfillment_con')) ? __('Fulfillment Time ', 'checkout-time-picker-for-woocommerce') : __('Selected Pickup Time ', 'checkout-time-picker-for-woocommerce');
                    echo '<h3>' . esc_html(__('Pickup Order', 'checkout-time-picker-for-woocommerce') ). '</h3><ul><li>' .esc_html( $order_string) . esc_html($time) . '</li></ul>';
                } else {
                    echo '<h3>' . esc_html( __('Pickup Order', 'checkout-time-picker-for-woocommerce')) . '</h3>';
                }
            } else if ($meta3 == 'delivery') {
                if (isset($time)) {
                    $order_string = $order->has_status(array('fulfillment_con')) ? __('Fulfillment Time ', 'checkout-time-picker-for-woocommerce') : __('Selected Delivery Time ', 'checkout-time-picker-for-woocommerce');

                    echo '<h3>' . esc_html( __('Delivery Order', 'checkout-time-picker-for-woocommerce')) . '</h3>';
                    echo esc_html($order_string) . esc_html($time);
                } else {
                    echo '<h3>' . esc_html( __('Delivery Order', 'checkout-time-picker-for-woocommerce')) . '</h3>';
                }
            }
        }
    }

}



add_action('woocommerce_thankyou', 'tpfw_add_picked_location_time_to_thankyou_page', 99, 1);
function tpfw_add_picked_location_time_to_thankyou_page($order_id)
{
    $order = wc_get_order($order_id);
    $type = $order->get_meta('tpfw_delivery_mode', true);
    ob_start();


    $meta3 = $order->get_meta('tpfw_picked_time', true);
    $meta4 = $order->get_meta('tpfw_picked_time_localized', true);
    $meta5 = $order->get_meta('tpfw_picked_time_range_end_localized', true);
    if (is_string($meta4) && $meta4 != '' && !empty($meta4) && $meta4 !== false && $meta4 != 'undefined') {
        $time = $meta4;
        if (is_string($meta5) && $meta5 != '' && !empty($meta5) && $meta5 !== false && $meta5 != 'undefined') {
            $time .= ' - ' . $meta5;
        }
    } else if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {
        $time = TPFW_Time::format_datetime($meta3);
    }


    if ($type == 'pickup' && get_option('tpfw_pickuptimes_enable', 'no') == 'yes') {
        if (isset($time)) {
            echo '<h2>' . esc_html( __('When to pick up?', 'checkout-time-picker-for-woocommerce')) . '</h2><ul><li>' .esc_html(  __('Your order will be ready ', 'checkout-time-picker-for-woocommerce')) . esc_html($time) . '</li></ul>';
        }
    } elseif ($type == 'delivery' && get_option('tpfw_deliverytime_enable', 'no') == 'yes') {
        if (isset($time)) {
            echo '<h2>' . esc_html( __('When will we deliver to you?', 'checkout-time-picker-for-woocommerce') ). '</h2>';
            echo '<p>' . esc_html( __('Your order will be delivered ', 'checkout-time-picker-for-woocommerce')) . esc_html( $time) . '</p>';
        }
    }


    $message = ob_get_clean();
    echo wp_kses( $message,array('h2'=> array(),'ul'=>array(),'li'=>array(),'p'=>array()) ) ;
}

