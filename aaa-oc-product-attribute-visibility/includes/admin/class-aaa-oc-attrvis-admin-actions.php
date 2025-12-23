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
}