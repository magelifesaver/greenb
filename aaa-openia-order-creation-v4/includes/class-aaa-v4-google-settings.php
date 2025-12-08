<?php
// File: /includes/class-aaa-v4-google-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_V4_Google_Settings {
    const OPTION_KEY = 'aaa_v4_google_api_settings';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_submenu_page(
            'aaa-openia-order-creation-v4',
            'Google API Settings',
            'Google API',
            'manage_woocommerce',
            'aaa-v4-google-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'aaa_v4_google_group', self::OPTION_KEY );
        add_settings_section( 'aaa_v4_google_main', 'Google Places API', null, 'aaa-v4-google-settings' );

        add_settings_field(
            'google_api_key',
            'Google API Key',
            function() {
                $opts  = get_option( self::OPTION_KEY, [] );
                $value = esc_attr( $opts['google_api_key'] ?? '' );
                echo '<input type="text" name="' . self::OPTION_KEY . '[google_api_key]" value="' . $value . '" style="width:400px;">';
                echo '<p class="description">Enter your Google Places API key.</p>';
            },
            'aaa-v4-google-settings',
            'aaa_v4_google_main'
        );
    }

    public static function render_settings_page() {
        echo '<div class="wrap"><h1>Google API Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'aaa_v4_google_group' );
        do_settings_sections( 'aaa-v4-google-settings' );
        submit_button();
        echo '</form></div>';
    }

    public static function get_api_key() {
        $opts = get_option( self::OPTION_KEY, [] );
        return $opts['google_api_key'] ?? '';
    }
}
