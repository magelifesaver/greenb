<?php
/**
 * Filepath: sfwf/settings/class-wf-sfwf-settings.php
 * ---------------------------------------------------------------------------
 * Settings manager for AAA Stock Forecast Workflow.
 * Stores settings in the shared custom options table: wp_aaa_wf_options
 * Schema: option_key, option_value, autoload, scope, updated_at
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Settings {

    protected static $table = '';
    protected static $scope = 'sfwf';

    /**
     * Initialize table name.
     */
    protected static function init() {
        global $wpdb;
        self::$table = $wpdb->prefix . 'aaa_wf_options';
    }

    /**
     * Retrieve a setting value.
     */
    public static function get( $key, $default = '' ) {
        self::init();
        global $wpdb;

        $value = $wpdb->get_var( $wpdb->prepare(
            "SELECT option_value FROM " . self::$table . " WHERE option_key = %s AND scope = %s LIMIT 1",
            $key,
            self::$scope
        ) );

        return ( $value !== null ) ? maybe_unserialize( $value ) : $default;
    }

    /**
     * Save or update a setting value.
     */
    public static function set( $key, $value, $autoload = true ) {
        self::init();
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table . " WHERE option_key = %s AND scope = %s",
            $key,
            self::$scope
        ) );

        if ( $exists ) {
            $wpdb->update(
                self::$table,
                [ 'option_value' => maybe_serialize( $value ), 'autoload' => $autoload ? 1 : 0 ],
                [ 'option_key' => $key, 'scope' => self::$scope ],
                [ '%s', '%d' ],
                [ '%s', '%s' ]
            );
        } else {
            $wpdb->insert(
                self::$table,
                [
                    'option_key'   => $key,
                    'option_value' => maybe_serialize( $value ),
                    'autoload'     => $autoload ? 1 : 0,
                    'scope'        => self::$scope,
                ],
                [ '%s', '%s', '%d', '%s' ]
            );
        }
    }

    /**
     * Return all settings for the plugin scope.
     */
    public static function all() {
        self::init();
        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT option_key, option_value FROM " . self::$table . " WHERE scope = %s",
            self::$scope
        ), OBJECT_K );

        $settings = [];
        foreach ( $rows as $key => $row ) {
            $settings[ $key ] = maybe_unserialize( $row->option_value );
        }

        return $settings;
    }
}
