<?php
if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;


if (!class_exists('TPFW_Time')) {
    /**
     * Class TPFW_Time
     *
     * @since 1.0
     */
    class TPFW_Time
    {

        public function __construct()
        {
        }

        public static function format_datetime_range_end($time, $range_size)
        {
            $time_ = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format'), $time, new DateTimeZone(wp_timezone_string()));
            if ($time_ instanceof DateTimeImmutable) {
                $end_time = $time_->modify("+" . (int) $range_size . "  minutes");
                $to_return = wp_date(get_option('date_format'), $time_->getTimestamp()) == wp_date(get_option('date_format'), $end_time->getTimestamp()) ? '' : wp_date(get_option('date_format'), $end_time->getTimestamp()) . ' ';
                $to_return .= $end_time->format(get_option('time_format'));
            }
            return isset($to_return) ? $to_return : '';
        }
        public static function format_datetime($time, $date_string = '', $time_string = '')
        {
            $time_ = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format'), $time, new DateTimeZone(wp_timezone_string()));
            if ($time_ instanceof DateTimeImmutable) {
                $toreturn = wp_date(get_option('date_format'), $time_->getTimestamp()) . ' ' . $time_->format(get_option('time_format'));
            } else if ($date_string != '' && $time_string != '' && DateTimeImmutable::createFromFormat('Y-m-d', $date_string, new DateTimeZone(wp_timezone_string())) instanceof DateTimeImmutable) {
                $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_string, new DateTimeZone(wp_timezone_string()));
                $toreturn = wp_date(get_option('date_format'), $date->getTimestamp()) . ' ' . $time_string;
            }
            return isset($toreturn) ? $toreturn : $time;
        }

        public static function get_timestamp($time)
        {
            $time_ = DateTimeImmutable::createFromFormat('Y-m-d' . ' ' . get_option('time_format'), $time, new DateTimeZone(wp_timezone_string()));
            if ($time_ instanceof DateTimeImmutable) {
                $toreturn = $time_->getTimestamp();
            }
            return isset($toreturn) ? $toreturn : (current_datetime())->getTimestamp();
        }
        static function sort_datetimes($arr)
        {
            usort($arr, function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return $a < $b ? -1 : 1;
            });
            return $arr;
        }

    }
    $TPFW_Time = new TPFW_Time();
}
