<?php
/**
 * File Path: /aaa-order-workflow/includes/announcements/ajax/class-aaa-oc-annc-ajax.php
 *
 * Purpose:
 * AJAX for fetching the next due announcement and recording user acceptance.
 * Permissions:
 * - Uses a filterable capability via 'aaa_oc_annc_required_cap' (default 'read').
 *   You can override to a custom cap like 'aaa_oc_view_announcements'.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Announcements_Ajax {

    private const DEBUG_THIS_FILE = false;
    
    public static function init() {
        add_action( 'wp_ajax_aaa_oc_annc_next',   [ __CLASS__, 'next' ] );
        add_action( 'wp_ajax_aaa_oc_annc_accept', [ __CLASS__, 'accept' ] );
    }

    protected static function tables() {
        global $wpdb;
        return [
            'ann' => $wpdb->prefix . 'aaa_oc_announcements',
            'usr' => $wpdb->prefix . 'aaa_oc_announcement_user',
        ];
    }

    protected static function required_capability() {
        /**
         * Filter: aaa_oc_annc_required_cap
         * Return the capability required to view/acknowledge announcements.
         * Examples: 'read' (default), 'edit_shop_orders', 'manage_woocommerce', or a custom cap like 'aaa_oc_view_announcements'.
         */
        return apply_filters( 'aaa_oc_annc_required_cap', 'read' );
    }

    protected static function check_permission() {
        $cap = self::required_capability();
        return ( is_user_logged_in() && current_user_can( $cap ) );
    }

    public static function next() {
        check_ajax_referer( 'aaa_oc_annc', 'nonce' );
        if ( ! self::check_permission() ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }

        global $wpdb;
        $t       = self::tables();
        $user_id = get_current_user_id();
        $now     = current_time( 'mysql' );

        // Next active announcement in window that the user hasn't accepted.
        $sql = $wpdb->prepare("
            SELECT a.*
            FROM {$t['ann']} a
            WHERE a.is_active = 1
              AND (a.start_at IS NULL OR a.start_at = '' OR a.start_at <= %s)
              AND (a.end_at   IS NULL OR a.end_at   = '' OR a.end_at   >= %s)
              AND NOT EXISTS (
                    SELECT 1 FROM {$t['usr']} u
                    WHERE u.announcement_id = a.id AND u.user_id = %d AND u.accepted = 1
              )
            ORDER BY a.id ASC
            LIMIT 1
        ", $now, $now, $user_id );

        $row = $wpdb->get_row( $sql );
        if ( ! $row ) {
            wp_send_json_success( [ 'has' => false ] );
        }

        // Mark seen (first time).
        $seen_at = current_time( 'mysql' );
        $q = $wpdb->prepare("
            INSERT INTO {$t['usr']} (announcement_id, user_id, seen_at, accepted, accepted_at)
            VALUES (%d, %d, %s, 0, NULL)
            ON DUPLICATE KEY UPDATE seen_at = IFNULL(seen_at, VALUES(seen_at))
        ", (int) $row->id, (int) $user_id, $seen_at );
        $wpdb->query( $q );

        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[ANN][AJAX] Next announcement id=' . (int) $row->id . ' user=' . (int) $user_id );
        }

        wp_send_json_success( [
            'has'     => true,
            'id'      => (int) $row->id,
            'title'   => $row->title,
            'content' => wp_kses_post( $row->content ),
            'footer'  => __( 'By checking this box you confirm you have read this update and are up to date with the new changes.', 'aaa-order-workflow' ),
        ] );
    }

    public static function accept() {
        check_ajax_referer( 'aaa_oc_annc', 'nonce' );
        if ( ! self::check_permission() ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }

        $ann_id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $confirmed = ! empty( $_POST['confirm'] ) && $_POST['confirm'] === '1';

        if ( ! $ann_id || ! $confirmed ) {
            wp_send_json_error( [ 'message' => 'invalid' ], 400 );
        }

        global $wpdb;
        $t         = self::tables();
        $user_id   = get_current_user_id();
        $accepted  = current_time( 'mysql' );

        $q = $wpdb->prepare("
            INSERT INTO {$t['usr']} (announcement_id, user_id, seen_at, accepted, accepted_at)
            VALUES (%d, %d, %s, 1, %s)
            ON DUPLICATE KEY UPDATE accepted = 1, accepted_at = VALUES(accepted_at), seen_at = IFNULL(seen_at, VALUES(seen_at))
        ", (int) $ann_id, (int) $user_id, $accepted, $accepted );
        $wpdb->query( $q );

	if ( self::DEBUG_THIS_FILE && function_exists( 'aaa_oc_log' ) ) {
	    aaa_oc_log( '[ANN][AJAX] Accepted id=' . (int) $ann_id . ' user=' . (int) $user_id );
	}

        wp_send_json_success( [ 'ok' => true ] );
    }
}
