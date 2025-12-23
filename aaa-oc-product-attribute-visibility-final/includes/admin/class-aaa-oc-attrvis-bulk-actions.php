<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/admin/class-aaa-oc-attrvis-bulk-actions.php
 * Purpose: Adds a bulk action to the Products list table to fix attribute
 * visibility. For small selections (≤10 products) the fix runs immediately;
 * larger selections are scheduled as a background job to prevent timeouts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_BULK' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_BULK', true );
}

class AAA_OC_AttrVis_Bulk_Actions {
    /**
     * Key used for the bulk action.
     */
    const ACTION_KEY = 'aaa_oc_attrvis_make_visible';

    /**
     * Threshold above which the fix will run via cron instead of immediately.
     */
    const THRESHOLD = 10;

    /**
     * Initialize hooks for bulk actions and notices.
     */
    public static function init() {
        add_filter( 'bulk_actions-edit-product', array( __CLASS__, 'add_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( __CLASS__, 'handle' ), 10, 3 );
        add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
    }

    /**
     * Add our custom bulk action to the dropdown.
     *
     * @param array $actions Existing bulk actions.
     * @return array
     */
    public static function add_action( $actions ) {
        $actions[ self::ACTION_KEY ] = __( 'Fix Attribute Visibility (Taxonomy)', 'aaa-oc-attrvis' );
        return $actions;
    }

    /**
     * Handle the selected bulk action.
     *
     * @param string $redirect_url URL to redirect to after processing.
     * @param string $action       The action being taken.
     * @param array  $post_ids     The list of selected post IDs.
     * @return string Modified redirect URL with query arguments for notices.
     */
    public static function handle( $redirect_url, $action, $post_ids ) {
        if ( self::ACTION_KEY !== $action ) {
            return $redirect_url;
        }
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return add_query_arg( array( 'aaa_oc_attrvis_bulk_err' => 1 ), $redirect_url );
        }
        $count = count( (array) $post_ids );
        if ( $count > self::THRESHOLD ) {
            // Schedule via cron for large selections.
            AAA_OC_AttrVis_Cron::schedule_fix_ids( $post_ids );
            return add_query_arg( array( 'aaa_oc_attrvis_bulk_scheduled' => $count ), $redirect_url );
        }
        // Immediate fix for small selections.
        $res = AAA_OC_AttrVis_Fixer::fix_by_ids( $post_ids, false );
        if ( AAA_OC_ATTRVIS_DEBUG_BULK ) {
            error_log( '[AAA_OC_ATTRVIS][bulk] immediate fix. checked=' . $res['checked'] . ' updated=' . $res['products_updated'] . ' rows=' . $res['rows_changed'] );
        }
        return add_query_arg( array(
            'aaa_oc_attrvis_bulk' => 1,
            'checked'             => (int) $res['checked'],
            'updated'             => (int) $res['products_updated'],
            'rows'                => (int) $res['rows_changed'],
        ), $redirect_url );
    }

    /**
     * Display admin notices after bulk actions or scheduling.
     */
    public static function notice() {
        // Immediate error due to permissions.
        if ( ! empty( $_GET['aaa_oc_attrvis_bulk_err'] ) ) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Attribute Visibility:', 'aaa-oc-attrvis' ) . '</strong> ' . esc_html__( 'Permission denied.', 'aaa-oc-attrvis' ) . '</p></div>';
            return;
        }
        // Immediate success results.
        if ( ! empty( $_GET['aaa_oc_attrvis_bulk'] ) ) {
            $checked = isset( $_GET['checked'] ) ? (int) $_GET['checked'] : 0;
            $updated = isset( $_GET['updated'] ) ? (int) $_GET['updated'] : 0;
            $rows    = isset( $_GET['rows'] ) ? (int) $_GET['rows'] : 0;
            echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Attribute Visibility:', 'aaa-oc-attrvis' ) . '</strong> ' . esc_html( sprintf( 'Checked %d products. Updated %d products. Rows flipped: %d.', $checked, $updated, $rows ) ) . '</p></div>';
            return;
        }
        // Scheduling message.
        if ( ! empty( $_GET['aaa_oc_attrvis_bulk_scheduled'] ) ) {
            $count = (int) $_GET['aaa_oc_attrvis_bulk_scheduled'];
            echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Attribute Visibility:', 'aaa-oc-attrvis' ) . '</strong> ' . esc_html( sprintf( 'A background job has been scheduled to fix %d selected products.', $count ) ) . '</p></div>';
            return;
        }
    }
}