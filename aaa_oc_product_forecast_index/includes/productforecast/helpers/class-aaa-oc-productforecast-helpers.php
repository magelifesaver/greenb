<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/helpers/class-aaa-oc-productforecast-helpers.php
 * Purpose: Helper methods for ProductForecast index module (option access, debug logging, table names).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductForecast_Helpers {

    /**
     * Scope used in shared options table.
     */
    const OPT_SCOPE = 'modules';

    protected static $options_table_checked = false;
    protected static $options_table_ok = false;
    protected static $options_table_cols = [];

    public static function log( $msg ) {
        if ( defined( 'AAA_OC_PRODUCTFORECAST_DEBUG' ) && AAA_OC_PRODUCTFORECAST_DEBUG ) {
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( '[PRODUCTFORECAST] ' . ( is_string( $msg ) ? $msg : wp_json_encode( $msg ) ) );
            } else {
                error_log( '[PRODUCTFORECAST] ' . ( is_string( $msg ) ? $msg : wp_json_encode( $msg ) ) );
            }
        }
    }

    /**
     * Shared custom options table used by AAA Order Workflow.
     * Expected schema (minimum): option_key, option_value, scope
     *
     * NOTE: We do not create this table here. If the workflow core provides
     * aaa_oc_get_option/aaa_oc_set_option, those will be used. Otherwise we
     * attempt direct read/write only if the table exists and has expected columns.
     */
    public static function options_table() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_oc_options';
    }

    protected static function options_table_is_usable() {
        if ( self::$options_table_checked ) {
            return self::$options_table_ok;
        }

        self::$options_table_checked = true;
        self::$options_table_ok = false;
        self::$options_table_cols = [];

        global $wpdb;
        $table = self::options_table();

        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return false;
        }

        $cols = (array) $wpdb->get_col( "SHOW COLUMNS FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ( empty( $cols ) || ! in_array( 'option_key', $cols, true ) || ! in_array( 'option_value', $cols, true ) ) {
            return false;
        }

        self::$options_table_cols = $cols;
        self::$options_table_ok = true;
        return true;
    }

    public static function get_opt( $key, $default = null ) {
        if ( function_exists( 'aaa_oc_get_option' ) ) {
            return aaa_oc_get_option( $key, self::OPT_SCOPE, $default );
        }

        if ( self::options_table_is_usable() ) {
            global $wpdb;
            $table = self::options_table();
            $has_scope = in_array( 'scope', self::$options_table_cols, true );

            if ( $has_scope ) {
                $val = $wpdb->get_var( $wpdb->prepare(
                    "SELECT option_value FROM {$table} WHERE option_key = %s AND scope = %s LIMIT 1",
                    $key,
                    self::OPT_SCOPE
                ) );
            } else {
                $val = $wpdb->get_var( $wpdb->prepare(
                    "SELECT option_value FROM {$table} WHERE option_key = %s LIMIT 1",
                    $key
                ) );
            }

            return ( $val !== null ) ? maybe_unserialize( $val ) : $default;
        }

        // Last-resort fallback.
        return get_option( $key, $default );
    }

    public static function set_opt( $key, $value ) {
        if ( function_exists( 'aaa_oc_set_option' ) ) {
            return aaa_oc_set_option( $key, $value, self::OPT_SCOPE );
        }

        if ( self::options_table_is_usable() ) {
            global $wpdb;
            $table = self::options_table();
            $has_scope = in_array( 'scope', self::$options_table_cols, true );

            $data = [
                'option_key'   => $key,
                'option_value' => maybe_serialize( $value ),
            ];
            $where = [ 'option_key' => $key ];
            $fmt_data = [ '%s', '%s' ];
            $fmt_where = [ '%s' ];

            if ( $has_scope ) {
                $data['scope'] = self::OPT_SCOPE;
                $where['scope'] = self::OPT_SCOPE;
                $fmt_data[] = '%s';
                $fmt_where[] = '%s';
            }

            $exists = $has_scope
                ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE option_key = %s AND scope = %s", $key, self::OPT_SCOPE ) )
                : $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE option_key = %s", $key ) );

            if ( $exists ) {
                $wpdb->update( $table, $data, $where, $fmt_data, $fmt_where );
                return true;
            }

            $wpdb->insert( $table, $data, $fmt_data );
            return true;
        }

        // Last-resort fallback.
        return update_option( $key, $value );
    }

    public static function table_index() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_oc_productforecast_index';
    }

    public static function table_log() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_oc_productforecast_log';
    }
}
