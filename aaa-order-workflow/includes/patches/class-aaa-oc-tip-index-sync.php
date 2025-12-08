<?php
/**
 * File: wp-content/plugins/aaa-order-workflow/includes/patches/class-aaa-oc-tip-index-sync.php
 * Purpose: Keep _wpslash_tip synchronized into aaa_oc_order_index and aaa_oc_payment_index
 * Notes: Safe on Store API (Blocks) and classic checkout; avoids zeroing non‑empty tips.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_Tip_Index_Sync' ) ) :

class AAA_OC_Tip_Index_Sync {

    const DEBUG_THIS_FILE = true;

    public static function init() {
        // After order is created/updated and when payment completes/changes
        add_action( 'save_post_shop_order', [ __CLASS__, 'sync_from_order_id' ], 80, 2 );
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'sync_from_order' ], 80, 2 );
        add_action( 'woocommerce_store_api_checkout_order_processed', [ __CLASS__, 'sync_from_order' ], 80, 1 );
        add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'sync_on_status_change' ], 10, 4 );
        add_action( 'woocommerce_payment_complete', [ __CLASS__, 'sync_from_order_id' ], 10, 1 );
    }

    /** ------------ Entrypoints ------------ */

    public static function sync_from_order( $order ) {
        if ( $order instanceof WC_Order ) {
            self::sync( $order->get_id() );
        }
    }

    public static function sync_from_order_id( $order_id ) {
        if ( is_numeric( $order_id ) ) {
            self::sync( intval( $order_id ) );
        }
    }

    public static function sync_on_status_change( $order_id, $from, $to, $order ) {
        self::sync_from_order_id( $order_id );
    }

    /** ------------ Core ------------ */

    protected static function log( $m, $d = null ) {
        if ( ! self::DEBUG_THIS_FILE ) return;
        error_log( '[AAA-OC][TipIndexSync] ' . $m . ( $d !== null ? ' :: ' . ( is_scalar( $d ) ? $d : wp_json_encode( $d ) ) : '' ) );
    }

    protected static function get_tip( $order_id ) {
        $raw = get_post_meta( $order_id, '_wpslash_tip', true );
        if ( $raw === '' || $raw === null ) return null;
        $num = preg_replace( '/[^\d\.\-]/', '', (string) $raw );
        if ( $num === '' || ! is_numeric( $num ) ) return null;
        return round( (float) $num, 2 );
    }

    protected static function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
    }

    protected static function has_column( $table, $col ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $col ) );
    }

    public static function sync( $order_id ) {
        $tip = self::get_tip( $order_id );
        if ( $tip === null ) {
            self::log( 'No tip meta on order; nothing to sync.', $order_id );
            return;
        }

        self::log( 'Syncing tip into indexes', [ 'order_id' => $order_id, 'tip' => $tip ] );

        self::update_payment_index( $order_id, $tip );
        self::update_order_index( $order_id, $tip );
    }

    protected static function update_payment_index( $order_id, $tip ) {
        global $wpdb;
        $tbl = $wpdb->prefix . 'aaa_oc_payment_index';
        if ( ! self::table_exists( $tbl ) ) return;

        // ensure row present
        $row_exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM `$tbl` WHERE `order_id` = %d", $order_id ) );
        if ( ! $row_exists ) {
            $wpdb->insert( $tbl, [ 'order_id' => $order_id, 'last_updated' => current_time( 'mysql' ) ], [ '%d', '%s' ] );
        }

        $fields = [];
        $fmts   = [];

	foreach ( [ 'aaa_oc_tip_total' ] as $col ) {
	    if ( self::has_column( $tbl, $col ) ) {
	        $fields[ $col ] = $tip;
	        $fmts[] = '%f';
	    }
	}

        // epayment_detail: append ", Tip: $X" if not present
        if ( self::has_column( $tbl, 'epayment_detail' ) ) {
            $detail = (string) $wpdb->get_var( $wpdb->prepare( "SELECT `epayment_detail` FROM `$tbl` WHERE `order_id`=%d", $order_id ) );
            if ( strpos( $detail, 'Tip:' ) === false ) {
                $detail = rtrim( $detail );
                $append = 'Tip: $' . number_format( $tip, 2 );
                $detail = $detail ? ( $detail . ', ' . $append ) : $append;
                $fields['epayment_detail'] = $detail;
                $fmts[] = '%s';
            }
        }

        $fields['last_updated']   = current_time( 'mysql' ); $fmts[] = '%s';
        $fields['last_updated_by'] = ( is_user_logged_in() ? wp_get_current_user()->user_login : 'system' ); $fmts[] = '%s';

        if ( ! empty( $fields ) ) {
            $wpdb->update( $tbl, $fields, [ 'order_id' => $order_id ], $fmts, [ '%d' ] );
            self::log( 'Payment index updated', [ 'order_id' => $order_id, 'fields' => array_keys( $fields ) ] );
        }
    }

    protected static function update_order_index( $order_id, $tip ) {
        global $wpdb;
        $tbl = $wpdb->prefix . 'aaa_oc_order_index';
        if ( ! self::table_exists( $tbl ) ) return;

        // ensure row present
        $row_exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM `$tbl` WHERE `order_id` = %d", $order_id ) );
        if ( ! $row_exists ) {
            $wpdb->insert( $tbl, [ 'order_id' => $order_id, 'last_updated' => current_time( 'mysql' ) ], [ '%d', '%s' ] );
        }

        $fields = []; $fmts = [];

	foreach ( [ '_wpslash_tip', 'aaa_oc_tip_total' ] as $col ) {
	    if ( self::has_column( $tbl, $col ) ) { $fields[ $col ] = $tip; $fmts[] = '%f'; }
	}
        if ( self::has_column( $tbl, 'last_updated' ) ) { $fields['last_updated'] = current_time( 'mysql' ); $fmts[] = '%s'; }

        if ( ! empty( $fields ) ) {
            $wpdb->update( $tbl, $fields, [ 'order_id' => $order_id ], $fmts, [ '%d' ] );
            self::log( 'Order index updated', [ 'order_id' => $order_id, 'fields' => array_keys( $fields ) ] );
        }
    }
}

add_action( 'plugins_loaded', [ 'AAA_OC_Tip_Index_Sync', 'init' ] );
endif;
