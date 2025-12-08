<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('TPFW_Settings')):
    function TPFW_Add_Tab($settings)
    {
        class TPFW_Settings extends WC_Settings_Page
        {
            public function __construct()
            {
                $this->id = 'tpfw';
                $this->label = __('Checkout Time picker for WooCommerce', 'checkout-time-picker-for-woocommerce');
                add_filter('woocommerce_settings_tabs_array', array(
                    $this,
                    'add_settings_page'
                ), 20);
                add_action('woocommerce_settings_' . $this->id, array(
                    $this,
                    'output'
                ));
                add_action('woocommerce_settings_save_' . $this->id, array(
                    $this,
                    'save'
                ));
                add_action('woocommerce_sections_' . $this->id, array(
                    $this,
                    'output_sections'
                ));

                add_action('woocommerce_admin_field_tpfw_ava_schedule', array(
                    $this,
                    'tpfworder_admin_field_tpfw_ava_schedule'
                ));

                add_action('woocommerce_admin_field_tpfwfixedordertime', array(
                    $this,
                    'tpfworder_admin_field_tpfw_tpfw_order_time'
                ));
                add_filter('woocommerce_admin_settings_sanitize_option', array(
                    $this,
                    'sanitize_callback'
                ), 10, 3);
            }
            public function get_sections()
            {
                $sections = array(
                    'tpfw_time_picker' => __('Time Picker', 'checkout-time-picker-for-woocommerce'),
                    'tpfw_ava_schedule' => __('Schedules', 'checkout-time-picker-for-woocommerce'),
                    'tpfw_order_time' => __('Order Time Management', 'checkout-time-picker-for-woocommerce'),





                );
                return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
            }
            public function sanitize_callback($value, $option, $raw_value)
            {
                global $current_section;
                if ('tpfw_time_picker' == $current_section) {
                    return $value;
                } else {
                    return $value;
                }
            }
            public function save()
            {
                global $current_section;
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::save_fields($settings);
            }
            public function output()
            {
                global $current_section;
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::output_fields($settings);
            }
            public function get_settings($current_section = '')
            {
                if ('' == $current_section) {
                    include(plugin_dir_path(__DIR__) . 'includes/start-args.php');
                    $settings = apply_filters('tpfw_section1_settings', $settings_args_timepicker);
                } else if ('tpfw_ava_schedule' == $current_section) {
                    $settings = apply_filters('tpfw_section3_settings', array(
                        array(
                            'name' => __('Timepicker Schedules', 'checkout-time-picker-for-woocommerce'),
                            'type' => 'title',
                            'desc_tip' => true,
                            'id' => 'tpfw_ava_schedule'
                        ),
                        array(
                            'type' => 'tpfw_ava_schedule',
                            'id' => 'tpfw_ava_schedule'
                        ),
                        array(
                            'type' => 'sectionend',
                            'id' => 'tpfw_ava_schedule'
                        )
                    ));
                } else if ('tpfw_order_time' == $current_section) {
                    include(plugin_dir_path(__DIR__) . 'includes/start-args.php');
                    $settings = apply_filters('tpfw_section1_settings', $settings_args_third);
                } else if ('tpfw_time_picker' == $current_section) {
                    include(plugin_dir_path(__DIR__) . 'includes/start-args.php');
                    $settings = apply_filters('tpfw_section1_settings', $settings_args_timepicker);
                }

                return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
            }
            public function tpfworder_admin_field_tpfw_ava_schedule($value)
            {
                $option = json_decode(get_option('tpfw_ava_schedule'), true);
                $terms = get_terms('product_tag');

                wp_localize_script('tpfw-admin-availability-script', 'tpfwAvailibilityLocalizeScript', array(
                    'tags' => $terms,
                    'categories' => TPFW::get_all_categories('all', 1),

                    'classes' => $option,
                    'default_shipping_class' => array(
                        'term_id' => 0,
                        'name' => '',
                        'description' => '',
                    ),
                ));
                wp_enqueue_script('tpfw-admin-availability-script');
                $settings_columns = array(
                    'wc-shipping-class-time_from' => __('From', 'checkout-time-picker-for-woocommerce'),
                    'wc-shipping-class-time_to' => __('To', 'checkout-time-picker-for-woocommerce'),

                    'wc-shipping-class-mode' => __('Mode', 'checkout-time-picker-for-woocommerce'),

                    'wc-shipping-class-weekday' => __('Weekday', 'checkout-time-picker-for-woocommerce'),
                    'wc-shipping-class-date' => __('Single Date', 'checkout-time-picker-for-woocommerce'),
                    'wc-shipping-class-cats' => __('Product Categories', 'checkout-time-picker-for-woocommerce'),
                    'wc-shipping-class-tags' => __('Product Tags', 'checkout-time-picker-for-woocommerce'),
                );
                include_once TPFW_PLUGINDIRPATH . 'includes/views/html-ava.php';
            }

            public function tpfworder_admin_field_tpfw_tpfw_order_time($value)
            {
                $option = json_decode(get_option('tpfw_ordertime_per_cats'), true);
                $terms = get_terms('product_tag');
                wp_localize_script('tpfw-admin-ordertime-script', 'tpfwOrdertimeLocalizeScript', array(
                    'tags' => $terms,
                    'categories' => TPFW::get_all_categories('all', 1),
                    'classes' => $option,
                    'default_shipping_class' => array(
                        'term_id' => 0,
                        'name' => '',
                        'description' => '',
                    ),
                ));
                wp_enqueue_script('tpfw-admin-ordertime-script');
                $settings_columns = array(
                    'wc-shipping-class-cats' => __('Product Categories', 'checkout-time-picker-for-woocommerce'),
                    'wc-shipping-class-rate' => __('Preperation Time', 'checkout-time-picker-for-woocommerce'),
                );
                include_once TPFW_PLUGINDIRPATH . 'includes/views/html-ordertime.php';
            }


        }
        $settings[] = new TPFW_Settings();
        return $settings;
    }
    add_filter('woocommerce_get_settings_pages', 'TPFW_Add_Tab', 15);
endif;

