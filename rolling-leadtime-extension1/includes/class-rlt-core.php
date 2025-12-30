<?php
/**
 * Core functionality for Rolling Lead‑Time Extension.
 *
 * Hooks into the existing Time Picker for WooCommerce plugin to adjust
 * its postpone time dynamically based on preparation time, dispatch
 * buffer and the travel time between the store and the customer.
 * Also displays an estimated arrival window on product pages and
 * validates user selections at checkout.
 *
 * @package RollingLeadTime
 */

if (!defined('ABSPATH')) {
    exit;
}

class RLT_Core {
    /**
     * Holds the singleton instance.
     *
     * @var RLT_Core|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance.
     *
     * @return RLT_Core
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: registers hooks.
     */
    private function __construct() {
        // Adjust postpone time before Time Picker computes its arguments.
        add_action('tpfw_before_get_picktime_args', array($this, 'set_dynamic_lead_time'));
        // Validate that selected time is not before earliest allowed.
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_selected_time'), 10, 2);
        // Display ETA on product pages.
        add_action('woocommerce_single_product_summary', array($this, 'display_product_eta'), 20);
    }

    /**
     * Computes and sets dynamic lead time into the session used by the
     * Time Picker plugin. It runs each time the picker arguments are
     * requested, ensuring up‑to‑date values.
     */
    public function set_dynamic_lead_time() {
        $options = get_option('rlt_settings', array());
        if (empty($options) || !isset($options['enable']) || $options['enable'] !== 'yes') {
            return;
        }
        // Determine total minutes to add: preparation + dispatch + travel.
        $prep     = isset($options['prep_time']) ? absint($options['prep_time']) : 0;
        $dispatch = isset($options['dispatch_buffer']) ? absint($options['dispatch_buffer']) : 0;
        $travel   = $this->get_travel_time();
        if ($travel === false || $travel < 0) {
            $travel = isset($options['default_travel_time']) ? absint($options['default_travel_time']) : 0;
        }
        $total_minutes = $prep + $dispatch + $travel;

        // Determine earliest slot timestamp based on schedule and lead time.
        $earliest_slot = $this->compute_earliest_slot_timestamp($total_minutes);
        // If no slot could be computed, fall back to simply applying the buffer on now.
        if ($earliest_slot) {
            $now_ts = current_time('timestamp');
            $diff_minutes = (int) ceil(($earliest_slot - $now_ts) / 60);
            if ($diff_minutes < 0) {
                $diff_minutes = 0;
            }
        } else {
            $diff_minutes = $total_minutes;
        }
        $shipping_time = array($diff_minutes, $diff_minutes);
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('tpfw_shipping_time', $shipping_time);
            // Save earliest timestamp for validation.
            $timestamp = $earliest_slot ? $earliest_slot : (current_time('timestamp') + $diff_minutes * 60);
            WC()->session->set('rlt_earliest_timestamp', $timestamp);
        }
    }

    /**
     * Performs the travel time lookup using the Google Distance Matrix API.
     * Returns the travel time in minutes or false on failure. If no API
     * key is provided or an address is missing, the function returns false
     * so that the default travel time can be used instead.
     *
     * @return int|false
     */
    private function get_travel_time() {
        $options = get_option('rlt_settings', array());
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
        if (empty($api_key)) {
            return false;
        }
        // Build origin and destination strings.
        $origin      = $this->get_store_address_string();
        $destination = $this->get_shipping_address_string();
        if (empty($origin) || empty($destination)) {
            return false;
        }
        $origins_param      = rawurlencode($origin);
        $destinations_param = rawurlencode($destination);
        $url                = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=' . $origins_param . '&destinations=' . $destinations_param . '&key=' . rawurlencode($api_key);
        // Use WordPress HTTP API.
        $response = wp_remote_get($url, array('timeout' => 10));
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return false;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['rows'][0]['elements'][0])) {
            return false;
        }
        $element = $data['rows'][0]['elements'][0];
        if (isset($element['status']) && $element['status'] === 'OK' && isset($element['duration']['value'])) {
            $seconds = intval($element['duration']['value']);
            return (int) ceil($seconds / 60);
        }
        return false;
    }

    /**
     * Computes the earliest possible slot timestamp given total lead time in minutes.
     * It respects the current schedule configured in the Time Picker plugin.
     * If the lead time pushes the slot past today’s closing time, it
     * automatically rolls over to the next available day.
     *
     * @param int $lead_minutes Total minutes including preparation, dispatch and travel.
     * @param string $mode Mode string: 'delivery' or 'pickup'. Defaults to 'delivery'.
     * @return int|false A UNIX timestamp for the start of the earliest slot or false on failure.
     */
    private function compute_earliest_slot_timestamp($lead_minutes, $mode = 'delivery') {
        // Ensure Time Picker functions are available.
        if (!class_exists('TPFW_Timepicker')) {
            return false;
        }
        // Determine mode string used by Timepicker.
        $mode_string = $mode === 'pickup' ? 'pickup' : 'del';
        // Use WP timezone.
        $tz = new DateTimeZone(wp_timezone_string());
        $now = new DateTimeImmutable('now', $tz);
        // Determine maximum number of selectable days.
        $max_days = get_option('tpfw_timepick_days_qty', 2);
        $step     = $mode === 'pickup' ? get_option('tpfw_pickuptime_step') : get_option('tpfw_deliverytime_step');
        $step     = $step ? intval($step) : 15;
        // Compute candidate time with lead minutes added.
        $candidate = $now->modify('+' . max(0, (int) $lead_minutes) . ' minutes');
        for ($i = 0; $i < $max_days; $i++) {
            $day_datetime = $now->modify('+' . $i . ' days');
            $date_today   = $day_datetime->format('Y-m-d');
            // For the first day we pass candidate time; for future days we pass candidate as well (not used for open/close calculation but needed for signature).
            $open_close = TPFW_Timepicker::get_timepicker_open_close(
                $day_datetime->format('w'),
                $mode_string,
                null,
                $day_datetime,
                $date_today,
                $candidate,
                $step,
                false
            );
            $open  = isset($open_close['from']) && ($open_close['from'] instanceof DateTimeInterface) ? $open_close['from'] : null;
            $close = isset($open_close['to']) && ($open_close['to'] instanceof DateTimeInterface) ? $open_close['to'] : null;
            // Skip if no opening hours returned.
            if (!$open || !$close) {
                continue;
            }
            // If this is today.
            if ($i === 0) {
                // Round candidate to the next step minute.
                $candidate_rounded = $this->round_up_to_step($candidate, $step);
                // If candidate is before opening, set to open.
                $slot_start = $candidate_rounded < $open ? $open : $candidate_rounded;
                // If resulting slot is beyond closing, skip to next day.
                if ($slot_start > $close) {
                    continue;
                }
                return $slot_start->getTimestamp();
            } else {
                // For future days the earliest slot starts at opening time.
                return $open->getTimestamp();
            }
        }
        // No slot found.
        return false;
    }

    /**
     * Rounds a DateTimeImmutable up to the nearest minute interval (step).
     *
     * @param DateTimeImmutable $dateTime The date/time to round.
     * @param int $minuteInterval Interval in minutes.
     * @return DateTimeImmutable
     */
    private function round_up_to_step(DateTimeImmutable $dateTime, $minuteInterval = 15) {
        $minutes = (int) $dateTime->format('i');
        $mod     = $minutes % $minuteInterval;
        if ($mod === 0) {
            return $dateTime->setTime((int) $dateTime->format('H'), $minutes, 0);
        }
        $new_minutes = $minutes + ($minuteInterval - $mod);
        $hour        = (int) $dateTime->format('H');
        if ($new_minutes >= 60) {
            $hour       += intdiv($new_minutes, 60);
            $new_minutes = $new_minutes % 60;
        }
        return $dateTime->setTime($hour, $new_minutes, 0);
    }

    /**
     * Builds a shipping address string for the current customer. Returns
     * an empty string when no shipping address has been entered yet.
     *
     * @return string
     */
    private function get_shipping_address_string() {
        if (!function_exists('WC')) {
            return '';
        }
        $customer = WC()->customer;
        if (!$customer) {
            return '';
        }
        $parts = array(
            $customer->get_shipping_address(),
            $customer->get_shipping_address_2(),
            $customer->get_shipping_city(),
            $customer->get_shipping_state(),
            $customer->get_shipping_postcode(),
            $customer->get_shipping_country()
        );
        $filtered = array_filter($parts);
        return !empty($filtered) ? implode(', ', $filtered) : '';
    }

    /**
     * Builds the store address string from WooCommerce settings.
     *
     * @return string
     */
    private function get_store_address_string() {
        $parts = array(
            get_option('woocommerce_store_address'),
            get_option('woocommerce_store_address_2'),
            get_option('woocommerce_store_city'),
            get_option('woocommerce_store_state'),
            get_option('woocommerce_store_postcode'),
            get_option('woocommerce_store_country')
        );
        $filtered = array_filter($parts);
        return !empty($filtered) ? implode(', ', $filtered) : '';
    }

    /**
     * Validates the chosen delivery/pickup time at checkout. If the
     * selected time is earlier than the earliest allowable timestamp
     * computed by this plugin, an error is added to prevent checkout.
     *
     * @param array    $data  Validated posted data (unused).
     * @param WP_Error $errors Error object used to add validation messages.
     */
    public function validate_selected_time($data, $errors) {
        if (!function_exists('WC')) {
            return;
        }
        $options = get_option('rlt_settings', array());
        if (empty($options) || !isset($options['enable']) || $options['enable'] !== 'yes') {
            return;
        }
        $earliest_ts = WC()->session ? WC()->session->get('rlt_earliest_timestamp') : false;
        if (!$earliest_ts) {
            return;
        }
        $date = isset($_POST['tpfw-date']) ? sanitize_text_field(wp_unslash($_POST['tpfw-date'])) : '';
        $time = isset($_POST['tpfw-time']) ? sanitize_text_field(wp_unslash($_POST['tpfw-time'])) : '';
        if (!$date || !$time) {
            return;
        }
        // Strip range if present.
        if (strpos($time, '-') !== false) {
            $time = substr($time, 0, strpos($time, '-'));
        }
        // Construct datetime in site timezone.
        $timezone = new DateTimeZone(wp_timezone_string());
        $datetime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
        if (!$datetime) {
            // Try alternative format if user changed date format.
            $datetime = DateTimeImmutable::createFromFormat(get_option('date_format') . ' ' . get_option('time_format'), $date . ' ' . $time, $timezone);
            if (!$datetime) {
                return;
            }
        }
        if ($datetime->getTimestamp() < $earliest_ts) {
            $errors->add('rlt_invalid_time', __('The selected delivery/pickup time is no longer available. Please choose a later time.', 'rlt'));
        }
    }

    /**
     * Displays an estimated delivery window on product pages. This
     * provides customers with a quick estimate before reaching the
     * checkout. The estimate is based on current time plus
     * preparation, dispatch and the default travel time. It does not
     * account for actual cart contents since the user may not yet
     * have provided a shipping address.
     */
    public function display_product_eta() {
        $options = get_option('rlt_settings', array());
        if (empty($options) || !isset($options['enable']) || $options['enable'] !== 'yes') {
            return;
        }
        // Only show on single product pages.
        if (!is_product()) {
            return;
        }
        $prep     = isset($options['prep_time']) ? absint($options['prep_time']) : 0;
        $dispatch = isset($options['dispatch_buffer']) ? absint($options['dispatch_buffer']) : 0;
        $travel   = isset($options['default_travel_time']) ? absint($options['default_travel_time']) : 0;
        $lead     = $prep + $dispatch + $travel;
        $slot_len = isset($options['slot_length']) ? absint($options['slot_length']) : 60;
        $slot_ts  = $this->compute_earliest_slot_timestamp($lead);
        if (!$slot_ts) {
            // If no slot can be computed (perhaps today is closed), show closed message.
            echo '<p class="rlt-eta notice notice-error" style="margin-top:10px;">' . esc_html__('Sorry, no delivery slots are available today.', 'rlt') . '</p>';
            return;
        }
        $start_ts = $slot_ts;
        $end_ts   = $start_ts + ($slot_len * 60);
        $format   = get_option('time_format');
        $start    = wp_date($format, $start_ts);
        $end      = wp_date($format, $end_ts);
        echo '<p class="rlt-eta notice notice-info" style="margin-top:10px;">' .
            sprintf(
                /* translators: 1: start time 2: end time */
                esc_html__('Order now for estimated delivery between %1$s – %2$s.', 'rlt'),
                esc_html($start),
                esc_html($end)
            ) .
            '</p>';
    }
}
