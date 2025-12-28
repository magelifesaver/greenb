<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/admin/class-aaa-oc-attrvis-admin-actions.php
 * Purpose: Process admin‑post actions triggered from the plugin’s forms.
 *
 * Currently supports scheduling a full fix job via cron.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_ADMIN_ACTIONS' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_ADMIN_ACTIONS', true );
}

class AAA_OC_AttrVis_Admin_Actions {
    /**
     * Register our admin‑post handlers.
     */
    public static function init() {
        add_action( 'admin_post_aaa_oc_attrvis_fix_all', array( __CLASS__, 'handle_fix_all' ) );
        add_action( 'admin_post_aaa_oc_attrvis_fix_selected', array( __CLASS__, 'handle_fix_selected' ) );
    }

    /**
     * Handle the fix‑all action triggered by the settings page.
     */
    public static function handle_fix_all() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc-attrvis' ) );
        }
        check_admin_referer( 'aaa_oc_attrvis_fix_all', 'aaa_oc_attrvis_nonce' );
        $batch    = isset( $_POST['batch'] ) ? max( 1, (int) $_POST['batch'] ) : 50;
        $category = isset( $_POST['category'] ) ? (int) $_POST['category'] : 0;
        // Kick off the background job via cron.
        AAA_OC_AttrVis_Cron::schedule_fix_all( $category, $batch );
        // Redirect back to the report page with a flag indicating scheduling success.
        $redirect = add_query_arg( array(
            'post_type'              => 'product',
            'page'                   => AAA_OC_ATTRVIS_SLUG,
            'aaa_oc_attrvis_scheduled' => 1,
        ), admin_url( 'edit.php' ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Handle the fix selected action from the report page.
     */
    public static function handle_fix_selected() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc-attrvis' ) );
        }
        check_admin_referer( 'aaa_oc_attrvis_fix_selected', 'aaa_oc_attrvis_nonce' );
        $selected = isset( $_POST['selected'] ) ? array_map( 'intval', (array) $_POST['selected'] ) : array();
        $batch    = isset( $_POST['batch'] ) ? max( 1, (int) $_POST['batch'] ) : 50;
        $category = isset( $_POST['category'] ) ? (int) $_POST['category'] : 0;
        $stock_status = isset( $_POST['stock_status'] ) ? sanitize_text_field( wp_unslash( $_POST['stock_status'] ) ) : '';
        $attr_names   = isset( $_POST['attributes'] ) ? array_map( 'sanitize_key', (array) $_POST['attributes'] ) : array();
        $paged    = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;
        // Build redirect base with existing filters so user stays on same report view.
        $redirect_base = add_query_arg( array(
            'post_type'    => 'product',
            'page'         => AAA_OC_ATTRVIS_SLUG,
            'batch'        => $batch,
            'category'     => $category,
            'stock_status' => $stock_status,
            'attributes'   => $attr_names,
            'paged'        => $paged,
            'report'       => 1,
        ), admin_url( 'edit.php' ) );
        if ( empty( $selected ) ) {
            // Nothing selected; just redirect back with no message.
            wp_safe_redirect( $redirect_base );
            exit;
        }
        $count = count( $selected );
        // Use the same threshold as the bulk actions for deciding immediate vs cron.
        $threshold = AAA_OC_AttrVis_Bulk_Actions::THRESHOLD;
        if ( $count > $threshold ) {
            AAA_OC_AttrVis_Cron::schedule_fix_ids( $selected );
            $redirect = add_query_arg( array( 'aaa_oc_attrvis_selected_scheduled' => 1 ), $redirect_base );
            wp_safe_redirect( $redirect );
            exit;
        }
        // Immediate fix.
        $res = AAA_OC_AttrVis_Fixer::fix_by_ids( $selected, false );
        $redirect = add_query_arg( array(
            'aaa_oc_attrvis_selected'  => 1,
            'selected_checked'         => (int) $res['checked'],
            'selected_updated'         => (int) $res['products_updated'],
            'selected_rows'            => (int) $res['rows_changed'],
        ), $redirect_base );
        wp_safe_redirect( $redirect );
        exit;
    }
}