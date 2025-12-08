<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('CMA_Settings')):
    function CMA_Add_Tab($settings) {
        class CMA_Settings extends WC_Settings_Page {
            public function __construct() {
                $this->id = 'arocma';
                $this->label = __('Check My Address', 'check-my-address');
                add_filter('woocommerce_settings_tabs_array', array(
                    $this,
                    'add_settings_page'
                ) , 20);
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
            }
            public function get_sections() {
                $sections = array(
                    '' => __('Settings', 'check-my-address') ,
                    'advanced' => __('Advanced', 'check-my-address')
                );
                return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
            }
            public function save() {
                global $current_section;
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::save_fields($settings);
            }
            public function output() {
                global $current_section;
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::output_fields($settings);
            }
            public function get_settings($current_section = '') {
                include (plugin_dir_path(__DIR__) . 'includes/start-args.php');
                if ('advanced' == $current_section) {
                    $settings = apply_filters('cma_section1_settings', $settings_args_advanced);
                }
                else {
                    $settings = apply_filters('cma_section1_settings', $settings_args_delivery);
                }
                return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
            }
        }
        $settings[] = new CMA_Settings();
        return $settings;
    }
    add_filter('woocommerce_get_settings_pages', 'CMA_Add_Tab', 15);
endif;
