<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('TPFW_Orders')) {
    /**
     * Main Class TPFW_Orders
     *
     * @since 1.0
     */
    class TPFW_Orders
    {
        private static $processing_orders;
        private static $processing_items;
        // The Constructor
        public function __construct()
        {


        }





        public static function get_dynamic_time()
        {

            $per_order_or_item = get_option('tpfw_preptime_order_item', 'order');



            $cart_cats = array();
            $cart_cats_quantity = array();
            foreach (WC()->cart->get_cart_contents() as $cart_item) {
                $cart_product = wc_get_product($cart_item['product_id']);
                if ($cart_product !== null) {
                    if ($per_order_or_item == 'order') {
                        $cart_cats = array_merge($cart_cats, $cart_product->get_category_ids());
                    } else if ($per_order_or_item == 'item') {
                        $cart_cats_quantity[] = array(

                            'cats' => $cart_product->get_category_ids(),
                            'qty' => $cart_item['quantity'],
                            'time' => 0,

                        );
                    }



                }
            }

            $cat_times = json_decode(get_option('tpfw_ordertime_per_cats'), true) !== null ? json_decode(get_option('tpfw_ordertime_per_cats'), true) : array();

            $order_times = array(0);
            if (is_array($cat_times)) {
                $cats_to_count = array();

                foreach ($cat_times as $cat) {
                    if ($per_order_or_item == 'order') {


                        $order_times[] = in_array('tpfwallcategories', $cat['cats']) || !empty(array_intersect($cat['cats'], $cart_cats)) ? (int) $cat['rate'] : 0;
                    } else if ($per_order_or_item == 'item') {

                        foreach ($cart_cats_quantity as $cart_i => &$c) {




                            $c['time'] = in_array('tpfwallcategories', $cat['cats']) || !empty(array_intersect($cat['cats'], $c['cats'])) ? max(array((int) $c['qty'] * (int) $cat['rate'], (int) $c['time'])) : (int) $c['time'];

                        }
                        unset($c);

                    }
                }
            }
            $dynamic_order_time = $per_order_or_item == 'order' ? max($order_times) : array_sum(array_column($cart_cats_quantity, 'time'));





            return $dynamic_order_time;



        }
        public static function get_preparation_time()
        {

            $time = get_option('tpfw_preperation_time_mode') == 'dynamic' ? self::get_dynamic_time() : intval(get_option('tpfw_pickup_fixed', ''));


            return $time;
        }





        public static function get_order_items_count_by_cats($order, $cats_to_count)
        {
            $items_aggr = 0;
            foreach ($order->get_items() as $item_id => $item) {

                $product = $item->get_product();
                $cat_ids = $product->get_category_ids();

                if (is_array($cat_ids) && !empty(array_intersect($cat_ids, $cats_to_count))) {
                    $quantity = $item->get_quantity();
                    $items_aggr += $quantity;
                }

            }
            return $items_aggr;
        }
        public static function get_cart_count_by_cats($cats_to_count)
        {

            $items_aggr = 0;
            foreach (WC()->cart->get_cart_contents() as $cart_item) {
                $cart_product = wc_get_product($cart_item['product_id']);
                if ($cart_product !== null) {
                    $cart_cats = $cart_product->get_category_ids();
                    $items_aggr += is_array($cart_cats) && !empty(array_intersect($cart_cats, $cats_to_count)) ? $cart_item['quantity'] : 0;

                }
            }
            return $items_aggr;
        }
        public static function get_processing_orders()
        {
            $count_items = get_option('tpfw_extratime_mode', 'orders') == 'items';
            if (!isset(self::$processing_orders)) {
                $args = array(
                    'status' => array('wc-processing', 'wc-on-hold', 'fulfillment_con', 'wc-fulfillment_con'),
                    'limit' => -1,
                    'date_created' => '>=' . (time() - MONTH_IN_SECONDS),
                );

                $orders = wc_get_orders($args);
                $cats_to_count = get_option('tpfw_processing_cats', array());
                $limitorders = array();
                $items_aggregate = 0;

                $is_timepicker = (get_option('tpfw_pickuptimes_enable') == 'yes' || get_option('tpfw_deliverytime_enable') == 'yes');

                if ($is_timepicker) {
                    $postpone_limit_delivery = get_option('tpfw_delivery_start_count_processing', 1440);
                    $postpone_limit_pickup = get_option('tpfw_pickup_start_count_processing', 1440);

                    $current_datetime = current_datetime();

                    foreach ($orders as $order) {
                        $meta3 = $order->get_meta('tpfw_delivery_mode', true);


                        $meta2 = $order->get_meta('tpfw_picked_time', true);


                        if ((is_string($meta2) && $meta2 != '' && !empty($meta2) && $meta2 !== false) && (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined')) {
                            $time = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format'), $meta2, new DateTimeZone(wp_timezone_string()));

                            if ($time instanceof DateTimeImmutable) {
                                $postpone_limit = $meta3 == 'delivery' ? $postpone_limit_delivery : 0;
                                $postpone_limit = $meta3 == 'pickup' ? $postpone_limit_pickup : $postpone_limit;


                                if ($current_datetime >= $time->modify("-" . (int) $postpone_limit . "  minutes")) {
                                    $limitorders[] = $order;

                                    if ($count_items) {
                                        $items_aggregate += self::get_order_items_count_by_cats($order, $cats_to_count);

                                    }


                                }

                            }

                        } else {
                            $limitorders[] = $order;

                            if ($count_items) {
                                $items_aggregate += self::get_order_items_count_by_cats($order, $cats_to_count);
                            }
                        }
                    }

                    $orders = $limitorders;
                } else {

                    if ($count_items) {
                        foreach ($orders as $order) {



                            $items_aggregate += self::get_order_items_count_by_cats($order, $cats_to_count);
                        }

                    }



                }
                self::$processing_orders = is_countable($orders) ? count($orders) : 0;
                self::$processing_items = $items_aggregate;
            }
            return $count_items ? self::$processing_items : self::$processing_orders;
        }

    }
}
