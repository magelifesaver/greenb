<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Options {
    private static $table_exists = null;
    private static $cache = [];

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_oc_options';
    }

    public static function custom_table_exists(): bool {
        if ( self::$table_exists !== null ) {
            return (bool) self::$table_exists;
        }
        global $wpdb;
        $table = self::table_name();
        $like = $wpdb->esc_like( $table );
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
        self::$table_exists = ( $found === $table );
        return (bool) self::$table_exists;
    }

    public static function get( string $key, $default = null, string $scope = 'global' ) {
        $key = self::sanitize_key( $key );
        $scope = self::sanitize_scope( $scope );
        $ck = $scope . '|' . $key;

        if ( array_key_exists( $ck, self::$cache ) ) {
            return self::$cache[ $ck ];
        }

        $raw = null;

        if ( self::custom_table_exists() ) {
            global $wpdb;
            $table = self::table_name();
            $raw = $wpdb->get_var( $wpdb->prepare(
                "SELECT option_value FROM {$table} WHERE option_key = %s AND scope = %s LIMIT 1",
                $key,
                $scope
            ) );
        } else {
            $raw = get_option( 'ddd_dt_' . $scope . '_' . $key, null );
        }

        if ( $raw === null || $raw === '' ) {
            self::$cache[ $ck ] = $default;
            return $default;
        }

        $value = self::decode_value( $raw );
        self::$cache[ $ck ] = $value;
        return $value;
    }

    public static function set( string $key, $value, string $scope = 'global', int $autoload = 0 ): bool {
        $key = self::sanitize_key( $key );
        $scope = self::sanitize_scope( $scope );
        $raw = self::encode_value( $value );
        $autoload = $autoload ? 1 : 0;

        self::$cache[ $scope . '|' . $key ] = $value;

        if ( ! self::custom_table_exists() ) {
            return update_option( 'ddd_dt_' . $scope . '_' . $key, $raw, (bool) $autoload );
        }

        global $wpdb;
        $table = self::table_name();

        $sql = "INSERT INTO {$table} (option_key, option_value, scope, autoload)
                VALUES (%s, %s, %s, %d)
                ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)";
        $r = $wpdb->query( $wpdb->prepare( $sql, $key, $raw, $scope, $autoload ) );
        return ( $r !== false );
    }

    public static function delete( string $key, string $scope = 'global' ): bool {
        $key = self::sanitize_key( $key );
        $scope = self::sanitize_scope( $scope );
        unset( self::$cache[ $scope . '|' . $key ] );

        if ( ! self::custom_table_exists() ) {
            return delete_option( 'ddd_dt_' . $scope . '_' . $key );
        }

        global $wpdb;
        $table = self::table_name();
        $r = $wpdb->delete( $table, [ 'option_key' => $key, 'scope' => $scope ], [ '%s', '%s' ] );
        return ( $r !== false );
    }

    private static function encode_value( $value ): string {
        if ( is_string( $value ) ) {
            return $value;
        }
        return wp_json_encode( $value );
    }

    private static function decode_value( $raw ) {
        if ( ! is_string( $raw ) ) {
            return $raw;
        }
        $trim = trim( $raw );
        if ( $trim === '' ) {
            return $raw;
        }
        if ( ( $trim[0] === '{' && substr( $trim, -1 ) === '}' ) || ( $trim[0] === '[' && substr( $trim, -1 ) === ']' ) ) {
            $decoded = json_decode( $trim, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $decoded;
            }
        }
        return maybe_unserialize( $raw );
    }

    private static function sanitize_key( string $key ): string {
        $key = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $key );
        return substr( $key, 0, 191 );
    }

    private static function sanitize_scope( string $scope ): string {
        $scope = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $scope );
        $scope = $scope !== '' ? $scope : 'global';
        return substr( $scope, 0, 50 );
    }
}
