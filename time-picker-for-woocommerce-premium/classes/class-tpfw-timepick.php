<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('TPFW_Timepicker')) {
    /**
     * Class TPFW_Timepicker
     *
     * @since 
     */
    class TPFW_Timepicker
    {
        public function __construct()
        {
            add_action('wp_enqueue_scripts', array(
                $this,
                'enqueue_scripts_time'
            ), 101);
            add_action('woocommerce_checkout_process', array(
                $this,
                'make_required'
            ));
            add_action('woocommerce_after_checkout_validation', array(
                $this,
                'full_slots_checkout_validation'
            ));


            add_action('wp_ajax_nopriv_tpfw_get_picktime_args', array(
                $this,
                'tpfw_get_picktime_args'
            ));
            add_action('wp_ajax_tpfw_get_picktime_args', array(
                $this,
                'tpfw_get_picktime_args'
            ));





            add_filter('woocommerce_update_order_review_fragments', array(
                $this,
                'pickup_time_update'
            ));



            $placement = get_option('tpfw_time_picker_placement', 'before_details');

            switch ($placement) {
                case 'none':
                    break;
                case 'before_details':
                    add_action('woocommerce_checkout_before_customer_details', array(
                        $this,
                        'insert_to_checkout'
                    ));
                    break;
                case 'before_payment':

                    add_action('woocommerce_review_order_before_payment', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);

                    break;
                case 'after_order_notes':
                    add_action('woocommerce_after_order_notes', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                case 'first_in_details':
                    add_action('woocommerce_before_checkout_billing_form', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                case 'after_place_order':
                    add_action('woocommerce_review_order_after_payment', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                default:
                    add_action('woocommerce_review_order_before_cart_contents', array(
                        $this,
                        'insert_to_checkout'
                    ));
            }


        }

        function full_slots_checkout_validation($post) {

             
            $nonce_ok = ! empty( $_REQUEST['_tpfwfieldnonce'] ) && wp_verify_nonce( sanitize_text_field( stripslashes_from_strings_only( $_REQUEST['_tpfwfieldnonce'] ) ), 'tpfw_text_field_action' ) == 1;
            if ($nonce_ok && isset($_POST['tpfw-date']) && !empty($_POST['tpfw-date']) && $_POST['tpfw-date'] != '' && isset($_POST['tpfw-time']) && !empty($_POST['tpfw-time']) && $_POST['tpfw-time'] != '') {

                 if (!TPFW::cart_needs_shipping()) {
                    return;
                 }

                 $type = isset($_POST['shipping_method'][0]) ? $_POST['shipping_method'][0] : '';
                 $type = strtok($type, ':');

                $mode = $type == 'flat_rate' || $type == 'free_shipping' || $type == 'szbd-shipping-method' ? 'delivery' : 'undefined';
                $mode = $type == 'local_pickup' || $type == 'pickup_location' ? 'pickup' : $mode;
                $full_slots_enable_mode = get_option('tpfw_max_slot_mode','both') == 'both' || (get_option('tpfw_max_slot_mode','both') == 'pickup' && ($mode == 'pickup') ) || (get_option('tpfw_max_slot_mode','both') == 'delivery' && ($mode == 'delivery') ) ;
                if (!$full_slots_enable_mode ) {
                    return;
                 }
                
                $time_ = wc_get_post_data_by_key('tpfw-time');
                $date = wc_get_post_data_by_key('tpfw-date');
                 $time = str_contains($time_, '-') ? substr($time_, 0, strrpos( $time_, '-')) : $time_;
                $string_time = sanitize_text_field($date . ' ' . $time);
                $time = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format') , $string_time, new DateTimeZone(wp_timezone_string()));
                if ($time == false) {
                    $time = DateTimeImmutable::createFromFormat(get_option('date_format') . ' ' . get_option('time_format') , $string_time, new DateTimeZone(wp_timezone_string()));
                }
                if ($time == false) {
                    return;
                }
                $slotsfull = self::get_slots_full();
                $slotsfull = !empty($slotsfull) ? $slotsfull : array();
                foreach ($slotsfull as $slot) {
                    if (($slot['datetime'] instanceof DateTimeImmutable || $slot['datetime'] instanceof DateTime) && $slot['datetime'] == $time) {
                      $dt = ($slot['datetime']);
                        $occupied_slot = $dt->format(get_option('time_format')) . ' ' . wp_date(get_option('date_format') , $dt->getTimestamp() );
                        break;
                    }
                }
            }
            if (isset($occupied_slot)) {
                wc_add_notice(__('Sorry! The selected time is now occupied.', 'checkout-time-picker-for-woocommerce') . ' (' . $occupied_slot . ')', 'error');
            }
        }
        static function do_any_timepicker()
        {

            $do_delivery_time = get_option('tpfw_deliverytime_enable') == 'yes';
            $do_pickup_time = get_option('tpfw_pickuptimes_enable') == 'yes';
            $toreturn = $do_delivery_time || $do_pickup_time;

            return $toreturn;
        }
        static function do_timepicker($return = 'do_time_picker')
        {
            $current_session = '';

            if(is_callable('wc_get_chosen_shipping_method_ids')){

                $current_session_ = wc_get_chosen_shipping_method_ids();
                $current_session = isset($current_session_[0]) ? $current_session_[0] : '';
                
            }
           
           

            $do_delivery_time = ($current_session == 'flat_rate' || $current_session == 'szbd-shipping-method' || $current_session == 'free_shipping') && get_option('tpfw_deliverytime_enable') == 'yes';
            $do_pickup_time = ($current_session == 'local_pickup' || $current_session == 'pickup_location') && get_option('tpfw_pickuptimes_enable') == 'yes';
            $toreturn = $do_delivery_time || $do_pickup_time;
            $toreturn = $return == 'do_pickup_time' ? $do_pickup_time : $toreturn;
            $toreturn = $return == 'do_delivery_time' ? $do_delivery_time : $toreturn;
            return $toreturn;
        }

        static function is_pickup_enabled() {
            return  get_option('tpfw_pickuptimes_enable','no') == 'yes';
             
             
          }
          static function is_delivery_enabled() {
              return  get_option('tpfw_deliverytime_enable','no')  == 'yes';
               
               
            }
        function make_required() {
            $nonce_ok = ! empty( $_REQUEST['_tpfwfieldnonce'] ) && wp_verify_nonce( sanitize_text_field( stripslashes_from_strings_only( $_REQUEST['_tpfwfieldnonce'] ) ), 'tpfw_text_field_action' ) == 1;
            if ($nonce_ok )
             {
                $time = wc_get_post_data_by_key('tpfw-time');
                if(empty($time)){
                    wc_add_notice(__('Please select a time.', 'checkout-time-picker-for-woocommerce') , 'error');

                }
               
            }
        }
        public function enqueue_scripts_time()
        {
            if (
                !is_checkout() || is_wc_endpoint_url('order-pay') || is_wc_endpoint_url('order-received') || WC()
                    ->cart
                    ->needs_shipping() === false
            ) {
                return;
            }
            if (WC_Blocks_Utils::has_block_in_page(get_the_ID(), 'woocommerce/checkout')) {
                return;

            }
            if (self::do_any_timepicker()) {
                wp_enqueue_style('tpfw-prem-timepicker-style', TPFW_PLUGINDIRURL . 'assets/jonthornton-jquery-timepicker/jquery.timepicker.min.css', array(), TPFW_VERSION);
                wp_enqueue_script('tpfw-prem-timepicker-script', TPFW_PLUGINDIRURL . 'assets/jonthornton-jquery-timepicker/jquery.timepicker.min.js', array(
                    'jquery',
                    'underscore'
                ), TPFW_VERSION, true);


                wp_enqueue_script('tpfw-prem-checkout-script', TPFW_PLUGINDIRURL . 'assets/js/tpfw-timepicker.min.js', array(
                    'jquery',
                    'tpfw-prem-timepicker-script'
                ), TPFW_VERSION, true);


                do_action('tpfw_before_get_picktime_args');

                $args = self::get_picktime_args(self::do_timepicker('do_pickup_time'), self::do_timepicker('do_delivery_time'), null);
                $nonce = wp_create_nonce('tpfw-script-nonce');
                $args['nonce'] = $nonce;

                wp_localize_script('tpfw-prem-checkout-script', 'tpfwcheckout', $args);



            }
        }


        function tpfw_get_picktime_args()
        {
            check_ajax_referer('tpfw-script-nonce', 'nonce_ajax');



            do_action('tpfw_before_get_picktime_args');
            $args = self::get_picktime_args(self::do_timepicker('do_pickup_time'), self::do_timepicker('do_delivery_time'), null);

            $nonce = wp_create_nonce('tpfw-script-nonce');
            $args['nonce'] = $nonce;


            wp_send_json($args);
        }




        public static function get_disable_ranges_from_availability($weekday, $mode, $pickup_location, $day_datetime, $check_cart = true)
        {
            TPFW::$schedules = isset(TPFW::$schedules) ? TPFW::$schedules : (json_decode(get_option('tpfw_ava_schedule'), true) !== null ? json_decode(get_option('tpfw_ava_schedule'), true) : array());
            //  echo "<script type='text/javascript'> alert('".json_encode(sizeof(Food_Online::$schedules))."') </script>";
            $schedules = TPFW::$schedules;
            $ranges_start = array();
            $ranges_end = array();
            $open_array = array();
            $close_array = array();
            $time_picker_schedule_exists = false;
            if (is_array($schedules)) {
                $format = 'Y-m-d';
                $single_day_schedules = array();
                $day_datetime = $day_datetime instanceof DateTime ? DateTimeImmutable::createFromMutable($day_datetime) : $day_datetime;
                foreach ($schedules as &$schedule) {
                    $schedule['application'] = 'timepicker';
                }

                unset($schedule);

                foreach ($schedules as $schedule) {


                    $schedule_date = isset($schedule['date']) && $schedule['date'] != '' ? DateTimeImmutable::createFromFormat($format, $schedule['date'], new DateTimeZone(wp_timezone_string())) : false;
                    $is_single_date_today = ($day_datetime instanceof DateTimeImmutable && $schedule_date instanceof DateTimeImmutable) ? $schedule_date->setTime(00, 00, 00, 00) == $day_datetime->setTime(00, 00, 00, 00) : false;
                    if ($is_single_date_today) {
                        $single_day_schedules[] = $schedule['application'];
                    }
                }
                $first = true;
                $approved_cart_items = array();
                foreach ($schedules as $schedule) {
                    $is_weekday_today = is_array($schedule['weekday']) && in_array($weekday, $schedule['weekday']);
                    $schedule_date = isset($schedule['date']) ? DateTimeImmutable::createFromFormat($format, $schedule['date'], new DateTimeZone(wp_timezone_string())) : false;
                    $is_single_date_today = $schedule_date instanceof DateTimeImmutable && $schedule_date->setTime(00, 00, 00, 00) == $day_datetime->setTime(00, 00, 00, 00);
                    if (is_string($schedule['application']) && 'timepicker' == $schedule['application'] && (($is_weekday_today && !in_array($schedule['application'], $single_day_schedules)) || ($is_single_date_today)) && (is_array($schedule['mode']) && (in_array($mode, $schedule['mode']) || in_array('pickup_delivery', $schedule['mode'])))) {
                        $time_to = new DateTime($day_datetime->format($format) . ' ' . $schedule['time_to'], new DateTimeZone(wp_timezone_string()));
                        $time_from = new DateTime($day_datetime->format($format) . ' ' . $schedule['time_from'], new DateTimeZone(wp_timezone_string()));
                        $pickup_location_schedule = !is_null($pickup_location) && isset($schedule['pickuplocation']) ? $schedule['pickuplocation'] : null;
                        $approved_cart_items[] = array(
                            'mode' => $mode,
                            'from' => $time_from,
                            'to' => $time_to,
                            'items' => !WC()
                                ->cart
                                ->is_empty() ? TPFW::get_approved_cart_items($schedule['cats'], $schedule['tags'], $pickup_location_schedule, $pickup_location) : array(
                                'empty_cart'
                            )
                        );
                    }
                }
            }
            $new_range = self::get_ranges_from_cart_validation($approved_cart_items, $mode, $day_datetime, $format);
            if (!isset($new_range['from_range']) || empty($new_range['from_range'])) {

                $a = array(
                    'range' => false,
                    'open' => isset($open[0]) ? $open[0] : 0,
                    'close' => isset($close) && is_array($close) ? end($close) : 0,
                );

                return $a;
            } else {
                $startday = new DateTime($day_datetime->format($format) . ' 00:00', new DateTimeZone(wp_timezone_string()));
                $endday = new DateTime($day_datetime->format($format) . ' 23:59', new DateTimeZone(wp_timezone_string()));
                $from_range = $new_range['from_range'];
                $to_range = $new_range['to_range'];
                $from_range_sorted = TPFW_Time::sort_datetimes($from_range);
                $to_range_sorted = TPFW_Time::sort_datetimes($to_range);
                $sort_start = TPFW_Time::sort_datetimes(array_merge(array(
                    $startday
                ), $to_range));
                $sort_end = TPFW_Time::sort_datetimes(array_merge(array(
                    $endday
                ), $from_range));
                $open = $from_range_sorted;
                $close = $to_range_sorted;
                $range = [];
                for ($i = 0; $i < count($sort_start); $i++) {
                    if ($sort_start[$i] < $sort_end[$i]) {
                        $range[] = array(
                            $sort_start[$i],
                            $sort_end[$i]
                        );
                    }
                }
            }
            $toreturn = array(
                'range' => $range,
                'open' => isset($open[0]) ? $open[0] : 0,
                'close' => is_array($close) ? end($close) : 0,
            );


            return $toreturn;
        }
        public static function get_ranges_from_cart_validation($approved_cart_ranges, $mode, $day_datetime, $format)
        {
            try {
                $items_approved = array();
                $items_in_cart = array();
                $period_collections = array();
                $cart = !WC()
                    ->cart
                    ->is_empty() ? WC()
                        ->cart
                        ->get_cart_contents() : array(
                    ['product_id' => 'empty_cart']
                );
                foreach ($cart as $cart_item) {
                    $items_in_cart[] = $cart_item['product_id'];
                    $new_collection = array();
                    foreach ($approved_cart_ranges as $range) {
                        if (in_array($cart_item['product_id'], $range['items'])) {
                            $items_approved[] = $cart_item['product_id'];
                            $new_collection[] = array(
                                'start' => $range['from'],
                                'stop' => $range['to']
                            );
                        }
                    }
                    if (!empty($new_collection)) {
                        $period_collections[] = $new_collection;
                    }
                }
                // Early return if no schedule is found for a single product. Can be overridden if one like to treat products without schedules as "always available"
                $diff = array_diff($items_in_cart, $items_approved);
                if (!empty($diff)) {
                    return false;
                }
                $union_collection = self::get_union_collection($period_collections);
                $open_range = !is_null($union_collection) ? array_column($union_collection, 'start') : array();
                $close_range = !is_null($union_collection) ? array_column($union_collection, 'stop') : array();
                return array(
                    'from_range' => $open_range,
                    'to_range' => $close_range,
                );
            } catch (Throwable $e) {
            }
        }
        public static function get_union_collection($period_collections)
        {
            $first = array_shift($period_collections);
            $union = self::overlapAll($first, $period_collections);
            return $union;
        }
        public static function overlapAll($first, $others)
        {
            try {
                $overlap = $first;
                if (is_iterable($others)) {
                    foreach ($others as $other) {
                        $overlap = self::overlap($overlap, $other);
                    }
                }
                return $overlap;
            } catch (Throwable $e) {
            }
        }
        // Inspired of https://github.com/spatie/period
        public static function overlap($first, $others)
        {
            try {
                $overlaps = array();
                if (is_iterable($first) && is_iterable($others)) {
                    foreach ($first as $period) {
                        foreach ($others as $other) {
                            $temp = self::overlapPeriod($period, $other);
                            if (is_null($temp) || empty($temp)) {
                                continue;
                            }
                            $overlaps[] = $temp;
                        }
                    }
                }
                return $overlaps;
            } catch (Throwable $e) {
            }
        }
        // Inspired of https://github.com/spatie/period
        public static function overlapPeriod($overlap, $others)
        {
            try {
                $others = array(
                    $others
                );
                if (is_countable($others) && count($others) > 1) {
                    return self::overlapAllPeriod($overlap, $others);
                } else {
                    $other = $others[0];
                }
                $includedStart = $overlap['start'] > $other['start'] ? $overlap['start'] : $other['start'];
                $includedEnd = $overlap['stop'] < $other['stop'] ? $overlap['stop'] : $other['stop'];
                if ($includedStart > $includedEnd) {
                    return null;
                }
                return array(
                    'start' => $includedStart,
                    'stop' => $includedEnd,
                );
            } catch (Throwable $e) {
            }
        }
        // Inspired of https://github.com/spatie/period
        protected static function overlapAllPeriod($first, $periods)
        {
            try {
                $overlap = $first;
                if (!is_countable($periods)) {
                    return $overlap;
                }
                if (is_iterable($periods)) {
                    foreach ($periods as $period) {
                        $overlap = self::overlapPeriod($overlap, $period);
                        if ($overlap === null || empty($overlap)) {
                            return null;
                        }
                    }
                }
                return $overlap;
            } catch (Throwable $e) {
            }
        }
        public static function set_postpone_times()
        {
            $session = array(
                0,
                0,

            );
            $for_pickup = get_option('tpfw_ready_for_pickup_show', 'none');

            $for_delivery = get_option('tpfw_ready_for_delivery_show', 'none');
            if ($for_pickup !== 'none' || $for_delivery !== 'none') {
                $preparation_time = TPFW_Orders::get_preparation_time();
                // For pickup orders
                switch ($for_pickup) {
                    case 'fixedtime':
                        $time = $preparation_time;
                        break;
                    case 'variable':
                        $processing_time = intval(get_option('tpfw_pickup_var', '0')) * intval(TPFW_Orders::get_processing_orders());
                        $time = $preparation_time + $processing_time;
                        break;
                }
                $session[0] = isset($time) ? $time : $session[0];

                // For shipping orders
                switch ($for_delivery) {
                    case 'fixedtime':
                        $del_time = $preparation_time;
                        break;
                    case 'variable':
                        $processing_time_del = intval(get_option('tpfw_pickup_var', '0')) * intval(TPFW_Orders::get_processing_orders());
                        $del_time = $preparation_time + $processing_time_del;
                        break;
                    case 'fixed_ship':

                        $shipping_time = intval(get_option('tpfw_shipping_fixed', '0'));

                        $del_time = $preparation_time + $shipping_time;
                        break;
                    case 'variable_ship':


                        $shipping_time = intval(get_option('tpfw_shipping_fixed', '0'));

                        $processing_time_del = intval(get_option('tpfw_pickup_var', '0')) * intval(TPFW_Orders::get_processing_orders());
                        $del_time = $preparation_time + $processing_time_del + $shipping_time;
                        break;
                }
                $session[1] = isset($del_time) ? $del_time : $session[1];

            }
            WC()
                ->session
                ->set('tpfw_shipping_time', $session);
        }
        public static function get_timepicker_open_close($day, $mode_string, $pickup_location, $datetime_day, $date_today, $datetime_today_with_preptime, $step, $check_cart = true)
        {
            try {
                $temp_ava_array = self::get_disable_ranges_from_availability($day, $mode_string, $pickup_location, $datetime_day, $check_cart);
                if ($temp_ava_array != false) {
                    if (is_array($temp_ava_array['range'])) {
                        $disable_array = array();
                        foreach ($temp_ava_array['range'] as $t) {
                            // Set disable intervals
                            $start = $t[0] instanceof Datetime ? DateTimeImmutable::createFromMutable($t[0]) : $t[0];
                            $disable_array[] = array(
                                'start' => $start->format('H:i') == '00:00' ? $start : $start->modify("+1 minutes"),
                                'stop' => $t[1],
                                'date' => $date_today
                            );
                        }
                    }
                    if (isset($temp_ava_array['open']) && ($temp_ava_array['open'] instanceof DateTime || $temp_ava_array['open'] instanceof DateTimeImmutable)) {
                        $from = self::roundUpToMinuteInterval($temp_ava_array['open'], (int) $step);
                    }
                    if (isset($temp_ava_array['close']) && ($temp_ava_array['close'] instanceof DateTime || $temp_ava_array['close'] instanceof DateTimeImmutable)) {
                        $to = $temp_ava_array['close'];
                    }
                    if (($datetime_today_with_preptime instanceof DateTime || $datetime_today_with_preptime instanceof DateTimeImmutable) && (isset($to) && $datetime_today_with_preptime > $to)) {
                        $to = 0;
                        $from = 0;
                    } else if (($datetime_today_with_preptime instanceof DateTime || $datetime_today_with_preptime instanceof DateTimeImmutable) && (isset($from) && $from < $datetime_today_with_preptime)) {
                        // Set first time to current time + preperation time
                        $from = self::roundUpToMinuteInterval($datetime_today_with_preptime, (int) $step);

                    }

                }
                $arg = array(
                    'from' => isset($from) ? $from : false,
                    'to' => isset($to) ? $to : false,
                    'disable_array' => isset($disable_array) && is_array($disable_array) ? $disable_array : false,
                );
                return $arg;
            } catch (Throwable $e) {
                return array(
                    'from' => isset($from) ? $from : false,
                    'to' => isset($to) ? $to : false,
                    'disable_array' => isset($disable_array) && is_array($disable_array) ? $disable_array : false
                );
            }
        }

        public static function get_picktime_args($do_pickup_time, $do_delivery_time, $pickup_location = null)
        {
            try {
                $time_to_add_ = 0;
                if (($do_pickup_time && get_option('tpfw_pickup_postpone') == 'yes') || ($do_delivery_time && get_option('tpfw_delivery_postpone') == 'yes')) {
                    if (
                        WC()
                            ->session
                            ->get('tpfw_shipping_time') === null || true
                    ) {
                        self::set_postpone_times();
                    }
                    $time_to_add = WC()
                        ->session
                        ->get('tpfw_shipping_time', array(
                            0,
                            0,

                        ));
                    $time_to_add_ = $do_pickup_time ? $time_to_add[0] : $time_to_add[1];
                }
                $mode_string = $do_pickup_time ? 'pickup' : 'del';
                $step = $do_pickup_time ? get_option('tpfw_pickuptime_step') : get_option('tpfw_deliverytime_step');
                // Arrays to pass as timepicker arguments
                $disable_array = array();
                // Today
                $datetime_today_imm = current_datetime();
                $day = $datetime_today_imm->format('w');
                $time_to_add_ = is_numeric($time_to_add_) ? $time_to_add_ : 0;
                $date_time_today = $datetime_today_imm->modify("+" . $time_to_add_ . "  minutes");
                $date_today = $date_time_today->format('Y-m-d');
                $from = 0;
                $to = 0;
                // Tomorrow
                $date_time_tomorrow = $datetime_today_imm->modify('+1 days');
                $day_tomorrow = $date_time_tomorrow->format('w');
                $date_tomorrow = $date_time_tomorrow->format('Y-m-d');


                $rollover_end = new DateTime($date_today);
                $rollover_start = new DateTime(current_datetime()->format('Y-m-d'));
                $interval = $rollover_start->diff($rollover_end);




                $is_rollover = $date_today == current_datetime()->format('Y-m-d') ? 0 : (int) $interval->format('%d');
                $open_close = self::get_timepicker_open_close($day, $mode_string, $pickup_location, $datetime_today_imm, $datetime_today_imm->format('Y-m-d'), $date_time_today, $step);
                $from = $open_close['from'];

                $to = $open_close['to'];
                $time_before_asap = (int) get_option('tpfw_time_before_asap', 60);

                if (is_array($open_close['disable_array'])) {
                    $array = $open_close['disable_array'];
                    foreach ($array as $key => $value) {


                        $array[$key]['start'] = ($value['start'])->format('H:i');
                        $array[$key]['stop'] = ($value['stop'])->format('H:i');
                    }
                    $disable_array = array_merge($array, $disable_array);
                }



                $todayopen_array = array(
                    $from instanceof DateTimeImmutable || $from instanceof DateTime ? ($from)->format('H:i') : 0,
                );
                $todayclose_array = array(
                    $to instanceof DateTimeImmutable || $to instanceof DateTime ? ($to)->format('H:i') : 0,
                );


                if ($from instanceof DateTimeImmutable && $to instanceof DateTime && $datetime_today_imm < $to) {

                    $to_immutable = $from;
                    $opening_time = $to_immutable->modify("-" . $time_before_asap . "  minutes");


                }
                $is_open = isset($opening_time) && ($datetime_today_imm >= $opening_time);

                $actualdate_array = array(
                    current_datetime()->format('Y-m-d'),
                );
                // Dates in future
                $max = get_option('tpfw_timepick_days_qty', 2) <= 1 ? 0 : get_option('tpfw_timepick_days_qty', 2) - 1;
                $max = $is_rollover != 0 && $max < $is_rollover ? $is_rollover : $max;
                for ($i = 0; $i <= $max; $i++) {
                    $date_time_next = $datetime_today_imm->modify('+' . ($i + 1) . ' days');
                    $day_next = $date_time_next->format('w');
                    $date_next = $date_time_next->format('Y-m-d');
                    $from_next = 0;
                    $to_next = 0;
                    $open_close = self::get_timepicker_open_close($day_next, $mode_string, $pickup_location, $date_time_next, $date_next, $date_time_today, $step);
                    $from_next = $open_close['from'];
                    $to_next = $open_close['to'];
                    if (is_array($open_close['disable_array'])) {
                        $array = $open_close['disable_array'];
                        foreach ($array as $key => $value) {
                            $array[$key]['start'] = ($value['start'])->format('H:i');
                            $array[$key]['stop'] = ($value['stop'])->format('H:i');
                        }
                        $disable_array = array_merge($array, $disable_array);
                    }
                    $todayopen_array[] = $from_next instanceof DateTimeImmutable || $from_next instanceof DateTime ? $from_next->format('H:i') : 0;
                    $todayclose_array[] = $to_next instanceof DateTimeImmutable || $to_next instanceof DateTime ? $to_next->format('H:i') : 0;
                    $actualdate_array[] = $date_next;
                }
                $midnight_mutable = DateTime::createFromImmutable(current_datetime());
                $midnight_mutable->setTime(0, 0, 0, 0);
                $interval = $midnight_mutable->diff(current_datetime());

                $seconds = $interval->format('%h') * 3600 + $interval->format('%i') * 60 + $interval->format('%s');

                $full_slots_enable_mode = get_option('tpfw_max_slot_mode','both') == 'both' || (get_option('tpfw_max_slot_mode','both') == 'pickup' && $do_pickup_time ) || (get_option('tpfw_max_slot_mode','both') == 'delivery' && !$do_pickup_time ) ;
                
               
                $args = array(
                    'showDuration' => get_option('tpfw_timepicker_ranges', 'no') == 'yes' ? 1 : 0,
                    'time_format' => get_option('time_format'),
                    'todayopen' => $todayopen_array,
                    'todayclose' => $todayclose_array,
                    'step' => $step,
                    'actual_date' => $actualdate_array,
                    'is_rollover' => $is_rollover,
                    'time_to_add' => $time_to_add_,
                    'full_slots' => $full_slots_enable_mode ? self::get_slots_full() : array(),
                    'disable_array' => $disable_array,
                    'preselect_time' => get_option('tpfw_preselect_time', 'no') == 'yes' ? 1 : 0,
                    'show_asap' => ($do_pickup_time && get_option('tpfw_show_asap_pickup', 'no') == 'yes') || ($do_delivery_time && get_option('tpfw_show_asap_delivery', 'no') == 'yes') ? 1 : 0,
                    'asap_text' => __('As soon as possible', 'checkout-time-picker-for-woocommerce'),
                    'locationfirst_text' => __('Choose a pickup location to view times', 'checkout-time-picker-for-woocommerce'),
                    'closed_text' => _x('Closed', 'datepicker', 'checkout-time-picker-for-woocommerce'),
                    'is_open' => $is_open ? 1 : 0,
                    'secondsfromMidnight' => $seconds,
                    'mode' => $do_pickup_time ? 'pickup' : 'delivery',

                    'ajax_updates' => 1,
                );
                return $args;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }
        public static function roundUpToMinuteInterval($dateTime, $minuteInterval = 10)
        {
            return $dateTime->setTime($dateTime->format('H'), (int) ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval, 0);
        }

        static function isDateBetweenDates($date, $startDate, $endDate)
        {
            return $date > $startDate && $date < $endDate;
        }
        public static function get_slots_full()
        {
            $slots_full = array();
            //Full time slot
            if (get_option('tpfw_max_slot_enable', 'no') !== 'no') {
                $slots_occupied = array();
                $args = array(
                    'date_created' => '>=' . (time() - MONTH_IN_SECONDS),
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'limit' => -1,
                    'return' => 'ids',
                    'meta_key' => 'tpfw_picked_time',
                    'meta_compare' => '!=',
                    'meta_value' => '',
                );
                $orders = wc_get_orders($args);

                $cats_to_count = get_option('tpfw_max_slot_cats', array());
                $counter = 0;
                foreach ($orders as $order_id) {
                    $order2 = wc_get_order($order_id);
                    $meta2 = $order2->get_meta('tpfw_picked_time', true);
                    if (is_string($meta2) && $meta2 != '' && !empty($meta2) && $meta2 !== false) {
                        $time = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format'), $meta2, new DateTimeZone(wp_timezone_string()));
                        if ($time == false) {
                            $time = DateTimeImmutable::createFromFormat(get_option('date_format') . ' ' . get_option('time_format'), $meta2, new DateTimeZone(wp_timezone_string()));
                        }
                        if ($time == false) {
                            continue;
                        }
                        if (current_datetime() <= $time) {
                            $time_format = $time->format('Y-m-d' . ' ' . 'H:i');
                            if (get_option('tpfw_max_slot_enable', 'no') == 'items') {
                                $items_aggr = 0;
                                if ($counter == 0) {
                                    $items_aggr += TPFW_Orders::get_cart_count_by_cats($cats_to_count);
                                    $counter++;
                                }
                                $items_aggr += TPFW_Orders::get_order_items_count_by_cats($order2, $cats_to_count);
                                $slots_occupied[$time_format] = isset($slots_occupied[$time_format]) || array_key_exists($time_format, $slots_occupied) ? $slots_occupied[$time_format] + $items_aggr : $items_aggr;
                            } else {
                                $slots_occupied[$time_format] = isset($slots_occupied[$time_format]) || array_key_exists($time_format, $slots_occupied) ? $slots_occupied[$time_format] + 1 : 1;
                            }
                            if ($slots_occupied[$time_format] >= get_option('tpfw_max_slot', 999) && !in_array($slots_occupied[$time_format], $slots_full)) {
                                $time_end = $time->add(new DateInterval('PT1M'));
                                $time_end = $time_end->format('H:i');
                                $slots_full[] = array(
                                    'date' => $time->format('Y-m-d'),
                                    'time' => $time->format('H:i'),
                                    'time_end' => $time_end,
                                    'datetime' => $time,
                                );
                            }
                        }
                    }
                }
            }
            return $slots_full;
        }
        public function insert_to_checkout()
        {




            if (
                is_wc_endpoint_url('order-pay') || is_wc_endpoint_url('order-received') || !isset(WC()->cart) || (isset(
                    WC()
                        ->cart
                ) && WC()
                        ->cart
                        ->needs_shipping() === false)
            ) {
                return;
            }
            if (self::do_any_timepicker()) {
              
                echo '<div class="tpfw-time-picker">';
                if (self::do_timepicker('do_time_picker')) {

                    $mode = self::do_timepicker('do_pickup_time') ? 'pickup' : 'delivery';
                    $title = $mode == 'pickup' ? __('Select Pickup Time?', 'checkout-time-picker-for-woocommerce') : __('Select Delivery Time?', 'checkout-time-picker-for-woocommerce');
                    $is_before_details = get_option('tpfw_time_picker_placement', 'before_details') == 'before_details';


                    if ($is_before_details) {
                        echo '<div id="tpfw-time_checkout_field" class="shop_table">';
                    }
                    echo '<h3 class="tpfw-checkoutfield-title">' . esc_html($title) . '</h3>';
                    $datetime_today = current_datetime();
                    $today = $datetime_today->format('Y-m-d');
                    // Make date array
                    $date_array = array(
                        $today => __('Today', 'checkout-time-picker-for-woocommerce'),
                    );
                    $max = get_option('tpfw_timepick_days_qty', 2) <= 1 ? 0 : get_option('tpfw_timepick_days_qty', 2) - 1;
                    // Todo, allow rollover when selecteble days = 1
                    //  $max = $max == 0 && $is_rollover == 1 ? $max + 1 : $max;
                    $date_format = get_option('tpfw_timepicker_dateformat', 'default') == 'default' ? get_option('date_format') : get_option('tpfw_timepicker_dateformat_custom', get_option('date_format'));
                    for ($i = 0; $i < $max; $i++) {
                        $temp_day = $datetime_today->modify('+' . ($i + 1) . ' days');
                        $wp_date = wp_date($date_format, $temp_day->getTimestamp());
                        if ($wp_date == false) {
                            continue;
                        }
                        $date_array[$temp_day->format('Y-m-d')] = $wp_date;
                    }
                    if ($is_before_details) {
                        echo '<div  class="form-row form-row-first validate-required">';
                    }
                    woocommerce_form_field('tpfw-date', array(
                        'type' => 'select',
                        'options' => $date_array,
                        'class' => array(
                            $mode
                        ),
                        'required' => true,
                        'default' => $today,
                        'label' => __('Date?', 'checkout-time-picker-for-woocommerce'),
                    ));
                    if ($is_before_details) {
                        echo '</div>';
                        echo '<div  class="form-row form-row-last validate-required">';
                    }
                    woocommerce_form_field('tpfw-time', array(
                        'type' => 'text',
                        'class' => array(
                            'time',
                            'ui-timepicker-input'
                        ),
                        'required' => true,
                        'label' => __('Time?', 'checkout-time-picker-for-woocommerce'),
                        'placeholder' => __('Select time...', 'checkout-time-picker-for-woocommerce'),
                    ));
                    if ($is_before_details) {
                        echo '</div></div>';
                    }

                    $allowed_html = array(
                        'input' => array(
                            'type'  => array(),
                            'class' => array(),
                            'name'  => array(),
                            'id'    => array(),
                            'value' => array(),
                        ),
                    );
                
                   
                    echo wp_kses(
                        '<input type="hidden" class="input-hidden" name="_tpfwfieldnonce" id="_tpfwfieldnonce" value="' .
                       
                        wp_create_nonce( 'tpfw_text_field_action' ) .
                        '" />',
                        $allowed_html
                    );

                }
                echo '</div>';
               
            }

        }

        function pickup_time_update($fragments)
        {
            if (self::do_any_timepicker()) {

                ob_start();

                self::insert_to_checkout();


                $fragments['.tpfw-time-picker'] = ob_get_clean();


            }




            return $fragments;
        }


    }
    $TPFW_Timepicker = new TPFW_Timepicker();
}

// Add order meta to my-account page, [woocommerce_order_tracking] etc
add_action('woocommerce_view_order', 'tpfw_add_picked_time_etc', 99, 1);

function tpfw_add_picked_time_etc($order_id)
{
    $order = wc_get_order($order_id);
    $type = $order->get_meta('tpfw_delivery_mode', true);
    $meta3 = $order->get_meta('tpfw_picked_time', true);
    $meta4 = $order->get_meta('tpfw_picked_time_localized', true);
    $meta5 = $order->get_meta('tpfw_picked_time_range_end_localized', true);
    $location = $order->get_meta('tpfw-pickup-location', true);
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
            echo '<h2>' . esc_html( __('When to pick up?', 'checkout-time-picker-for-woocommerce') ) . '</h2><p>' . esc_html(__('Your order will be ready ', 'checkout-time-picker-for-woocommerce')) . esc_html( $time ) . '</p>';
        }
    } elseif (($type == 'delivery' && get_option('tpfw_deliverytime_enable', 'no') == 'yes')) {
        if (isset($time)) {
            echo '<h2>' . esc_html(__('When will we deliver to you?', 'checkout-time-picker-for-woocommerce') ) . '</h2>';
            echo '<p>' . esc_html( __('Your order will be delivered ', 'checkout-time-picker-for-woocommerce')) . esc_html( $time ) . '</p>';
        }
    }

}
add_action('woocommerce_email_order_meta', 'tpfw_add_picked_time_to_emails', 20, 4);
function tpfw_add_picked_time_to_emails($order, $sent_to_admin, $plain_text, $email)
{
    $type = $order->get_meta('tpfw_delivery_mode', true);
    $meta3 = $order->get_meta('tpfw_picked_time', true);
    $meta4 = $order->get_meta('tpfw_picked_time_localized', true);
    $meta5 = $order->get_meta('tpfw_picked_time_range_end_localized', true);
    $location = $order->get_meta('tpfw-pickup-location', true);
    if (is_string($meta4) && $meta4 != '' && !empty($meta4) && $meta4 !== false && $meta4 != 'undefined') {
        $time = $meta4;
        if (is_string($meta5) && $meta5 != '' && !empty($meta5) && $meta5 !== false && $meta5 != 'undefined') {
            $time .= ' - ' . $meta5;
        }


        if ($type == 'pickup' && get_option('tpfw_pickuptimes_enable', 'no') == 'yes') {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('When to pick up?', 'checkout-time-picker-for-woocommerce')) . '</h2><p>' . esc_html(__('Your order will be ready ', 'checkout-time-picker-for-woocommerce')) . esc_html( $time ) . '</p>';
            }
        } elseif (($type == 'delivery' && get_option('tpfw_deliverytime_enable', 'no') == 'yes')) {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('When will we deliver to you?', 'checkout-time-picker-for-woocommerce')) . '</h2>';
                echo '<p>' . esc_html(__('Your order will be delivered ', 'checkout-time-picker-for-woocommerce')) . esc_html( $time ) . '</p>';
            }
        }

    } else if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {
        $time = TPFW_Time::format_datetime($meta3);
    }
    $type = $order->get_meta('tpfw_delivery_mode', true);
    $store = null;
    if ($email->id == 'customer_processing_order') {
        $store = false;
    } else if ($email->id == 'customer_completed_order') {
        $store = false;
    } else if ($email->id == 'locfw_customer_confirmed_order') {
        $store = false;
    } else if ($email->id == 'new_order') {
        $store = true;
    } else {
        return;
    }
    ob_start();
    if ($store == false) {


        if ($type == 'pickup' && get_option('tpfw_pickuptimes_enable', 'no') == 'yes') {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('When to pick up?', 'checkout-time-picker-for-woocommerce')) . '</h2><p>' . esc_html(__('Your order will be ready ', 'checkout-time-picker-for-woocommerce')) . esc_html($time ) . '</p>';
            }
        } elseif (($type == 'delivery' && get_option('tpfw_deliverytime_enable', 'no') == 'yes')) {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('When will we deliver to you?', 'checkout-time-picker-for-woocommerce')) . '</h2>';
                echo '<p>' . esc_html(__('Your order will be delivered ', 'checkout-time-picker-for-woocommerce')) . esc_html($time) . '</p>';
            }
        }

    } elseif ($store == true) {

        if ($type == 'pickup' && get_option('tpfw_pickuptimes_enable', 'no') == 'yes') {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('Pickup Order', 'checkout-time-picker-for-woocommerce')) . '</h2><p>' . esc_html(__('The customer will pickup the order ', 'checkout-time-picker-for-woocommerce')) . esc_html($time) . '</p>';
            }
        } elseif (($type == 'delivery' && get_option('tpfw_deliverytime_enable', 'no') == 'yes')) {
            if (isset($time)) {
                echo '<h2>' . esc_html(__('Delivery Order', 'checkout-time-picker-for-woocommerce')) . '</h2>';
                echo '<p>' . esc_html(__('Deliver the order ', 'checkout-time-picker-for-woocommerce')) . esc_html($time) . '</p>';
            }
        }

    }
    $message = ob_get_clean();
    echo esc_html($message);
}

