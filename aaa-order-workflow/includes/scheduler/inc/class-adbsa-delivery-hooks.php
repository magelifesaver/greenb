<?php
/**
 * File: includes/scheduler/inc/class-adbsa-delivery-hooks.php
 * Purpose: Frontend + email renderers and order meta saves for Delivery (Blocks + Classic).
 * Version: 1.3.0
 *
 * What’s new in 1.3.0:
 * - Saves checkout selections from WooCommerce Blocks (Store API) to order meta.
 * - Uses ADBSA_Delivery_Normalizer for consistent meta keys/labels.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Delivery_Hooks' ) ) :

final class ADBSA_Delivery_Hooks {

    /**
     * Local debug toggle for this file only.
     */
    const DEBUG_THIS_FILE = false;

    public static function init() {
        add_action( 'woocommerce_thankyou',      [ __CLASS__, 'render_on_thankyou' ], 20 );
        add_action( 'woocommerce_view_order',    [ __CLASS__, 'render_on_view_order' ], 20 );
        add_action( 'woocommerce_email_after_order_table', [ __CLASS__, 'render_in_emails' ], 20, 4 );

        /**
         * ✅ NEW: Save from Woo Blocks (Store API) during order creation.
         * Fires for Blocks checkout; $order is a WC_Order, $request is WP_REST_Request.
         * Run before we add the initial note so the note reflects saved metas.
         */
        add_action(
            'woocommerce_store_api_checkout_update_order_from_request',
            [ __CLASS__, 'save_from_store_api' ],
            20,
            2
        );

        // Add order note once when order is created (Blocks or Classic)
        add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'add_initial_delivery_note' ], 50, 1 );
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ __CLASS__, 'add_initial_delivery_note' ], 50, 2 );

        /**
         * Optional Classic fallback:
         * If a theme/plugin posts classic fields, persist them too.
         * (Safe no-op when fields are absent.)
         */
        add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'maybe_save_from_classic_post' ], 20, 2 );
    }

    /* ---------- Renderers (unchanged) ---------- */

    public static function render_on_thankyou( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order && class_exists( 'ADBSA_Summary_Helper' ) ) {
            echo ADBSA_Summary_Helper::render_from_order( $order );
        }
    }

    public static function render_on_view_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order && class_exists( 'ADBSA_Summary_Helper' ) ) {
            echo ADBSA_Summary_Helper::render_from_order( $order );
        }
    }

    public static function render_in_emails( $order, $sent_to_admin, $plain_text, $email ) {
        if ( $order instanceof WC_Order && class_exists( 'ADBSA_Summary_Helper' ) ) {
            echo ADBSA_Summary_Helper::render_from_order( $order );
        }
    }

    /* ---------- NEW: Save from Store API (Blocks) ---------- */

    /**
     * Save adbsa date/time chosen in Woo Blocks checkout into order meta.
     * Looks for extension data with keys:
     *   - 'adbsa/delivery-date'   (preferred)
     *   - 'adbsa' => ['delivery-date' => '...']
     *   - 'adbsa/delivery-time'
     *   - 'adbsa' => ['delivery-time' => '...']
     *
     * @param WC_Order         $order
     * @param WP_REST_Request  $request
     */
    public static function save_from_store_api( $order, $request = null ) {
        if ( ! $order instanceof WC_Order ) return;

        // Pull raw request body (Blocks Store API)
        $json = ( $request && method_exists( $request, 'get_json_params' ) )
            ? (array) $request->get_json_params()
            : [];

        $ext = isset( $json['extensions'] ) && is_array( $json['extensions'] ) ? $json['extensions'] : [];

        // Support both flat and namespaced shapes
        $dateYmd = '';
        $timeRaw = '';

        // Flat keys like 'adbsa/delivery-date'
        if ( isset( $ext['adbsa/delivery-date'] ) ) $dateYmd = (string) $ext['adbsa/delivery-date'];
        if ( isset( $ext['adbsa/delivery-time'] ) ) $timeRaw = (string) $ext['adbsa/delivery-time'];

        // Namespaced object shape: 'adbsa' => [ 'delivery-date' => '...', 'delivery-time' => '...' ]
        if ( isset( $ext['adbsa'] ) && is_array( $ext['adbsa'] ) ) {
            if ( $dateYmd === '' && isset( $ext['adbsa']['delivery-date'] ) ) {
                $dateYmd = (string) $ext['adbsa']['delivery-date'];
            }
            if ( $timeRaw === '' && isset( $ext['adbsa']['delivery-time'] ) ) {
                $timeRaw = (string) $ext['adbsa']['delivery-time'];
            }
        }

        // Nothing provided → nothing to save.
        if ( $dateYmd === '' && $timeRaw === '' ) {
            if ( self::DEBUG_THIS_FILE ) error_log('[ADBSA][StoreAPI] No delivery fields present in extensions.');
            return;
        }

        // Parse "From 3:00 pm - To 4:00 pm" (or similar)
        $from = '';
        $to   = '';
        if ( $timeRaw !== '' ) {
            // tolerate: From X - To Y  OR  From X to Y
            if ( preg_match( '/from\s+(.+?)\s*(?:-|–|—)?\s*to\s+(.+)/i', $timeRaw, $m ) ) {
                $from = trim( strtolower( $m[1] ) );
                $to   = trim( strtolower( $m[2] ) );
            } else {
                // If a single value slips through, store it as the "from"
                $from = trim( strtolower( $timeRaw ) );
            }
        }

        // Normalize via shared helper (writes canonical formats)
        if ( class_exists( 'ADBSA_Delivery_Normalizer' ) ) {
            $metas = ADBSA_Delivery_Normalizer::normalize( $dateYmd, $from, $to );

            foreach ( $metas as $k => $v ) {
                $order->update_meta_data( $k, $v );
            }
            $order->save();

            if ( self::DEBUG_THIS_FILE ) {
                error_log('[ADBSA][StoreAPI] Saved delivery metas: ' . wp_json_encode( array_keys( $metas ) ));
            }
        }
    }

    /* ---------- Classic fallback (safe no-op when not present) ---------- */

    /**
     * Save if classic POST variables exist (graceful fallback).
     *
     * @param int      $order_id
     * @param array    $data posted
     */
    public static function maybe_save_from_classic_post( $order_id, $data ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $dateYmd = sanitize_text_field( $_POST['order-adbsa-delivery-date'] ?? '' );
        $timeRaw = sanitize_text_field( $_POST['order-adbsa-delivery-time'] ?? '' );

        if ( $dateYmd === '' && $timeRaw === '' ) return;

        $from = $to = '';
        if ( $timeRaw !== '' ) {
            if ( preg_match( '/from\s+(.+?)\s*(?:-|–|—)?\s*to\s+(.+)/i', $timeRaw, $m ) ) {
                $from = trim( strtolower( $m[1] ) );
                $to   = trim( strtolower( $m[2] ) );
            } else {
                $from = trim( strtolower( $timeRaw ) );
            }
        }

        if ( class_exists( 'ADBSA_Delivery_Normalizer' ) ) {
            $metas = ADBSA_Delivery_Normalizer::normalize( $dateYmd, $from, $to );
            foreach ( $metas as $k => $v ) {
                $order->update_meta_data( $k, $v );
            }
            $order->save();
            if ( self::DEBUG_THIS_FILE ) error_log('[ADBSA][Classic] Saved delivery metas.');
        }
    }

    /* ---------- Order note (existing behavior) ---------- */

    /**
     * Add a one-time order note with the delivery details chosen at checkout.
     */
    public static function add_initial_delivery_note( $order, $request = null ) {
        if ( ! $order instanceof WC_Order ) return;
        if ( $order->get_meta( '_adbsa_initial_delivery_note' ) ) return;

        $dateLabel = (string) $order->get_meta( 'delivery_date_locale' );
        if ( $dateLabel === '' ) {
            $dateLabel = (string) $order->get_meta( 'delivery_date_formatted' );
        }

        $range = (string) $order->get_meta( 'delivery_time_range' );
        if ( $range === '' ) {
            $range = (string) $order->get_meta( '_wc_other/adbsa/delivery-time' );
        }

        // Normalize time range label
        if ( $range && stripos( $range, 'to' ) !== false ) {
            $parts = preg_split( '/\s+to\s+/i', $range );
            if ( count( $parts ) === 2 ) {
                $range = sprintf( 'From %s - To %s', trim( $parts[0] ), trim( $parts[1] ) );
            }
        }

        if ( $dateLabel || $range ) {
            $msg = sprintf(
                'Delivery scheduled at checkout: date %s%s%s',
                $dateLabel ?: '(none)',
                $range ? ', time ' : '',
                $range ?: ''
            );

            $order->add_order_note( $msg );
            $order->update_meta_data( '_adbsa_initial_delivery_note', 'yes' );
            $order->save();

            if ( self::DEBUG_THIS_FILE ) {
                error_log('[ADBSA][InitialNote] Added for order #' . $order->get_id() . ' | ' . $msg);
            }
        }
    }
}

ADBSA_Delivery_Hooks::init();

endif;
