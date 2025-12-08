<?php
/**
 * File Path: /wp-content/plugins/aaa-fluentcrm-crm-id-columns/aaa-fluentcrm-crm-id-columns.php
 * Plugin Name: AAA FluentCRM – CRM ID Columns
 * Description: Adds a “CRM ID” column to WP Users and WooCommerce Orders lists, linking to the FluentCRM subscriber profile.
 * Version: 1.1.0
 * Author: Webmaster Workflow
 * Text Domain: aaa-fluentcrm-crm-id-columns
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Debug toggle (logs to error_log when true) */
if ( ! defined( 'AAA_FCRM_CRM_ID_COLUMNS_DEBUG' ) ) {
    define( 'AAA_FCRM_CRM_ID_COLUMNS_DEBUG', true );
}
function aaa_fcrm_crmid_log( ...$m ) {
    if ( AAA_FCRM_CRM_ID_COLUMNS_DEBUG ) error_log('[AAA-FCRM:CRMID] '. wp_json_encode($m));
}

/** ---- Helpers ---- */
function aaa_fcrm_crmid_get_subscriber_by_user_id( $user_id ) {
    if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) return null;
    return \FluentCrm\App\Models\Subscriber::where('user_id', (int)$user_id)->first();
}
function aaa_fcrm_crmid_get_subscriber_by_email( $email ) {
    if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) return null;
    if ( ! is_email( $email ) ) return null;
    return \FluentCrm\App\Models\Subscriber::where('email', sanitize_email($email))->first();
}
function aaa_fcrm_crmid_link_html( $subscriber_id ) {
    $url = admin_url( 'admin.php?page=fluentcrm-admin' ) . '#/subscribers/' . intval( $subscriber_id );
    return sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%d</a>',
        esc_url( $url ),
        intval( $subscriber_id )
    );
}

/** ---- Users list: add "CRM ID" column (default visible) + sortable ---- */
add_filter( 'manage_users_columns', function( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'username' === $key ) {
            $new['crm_id'] = __( 'CRM ID', 'aaa-fluentcrm-crm-id-columns' );
        }
    }
    return $new;
}, 10 );

add_action( 'manage_users_custom_column', function( $value, $column_name, $user_id ) {
    if ( 'crm_id' !== $column_name ) return $value;
    $sub = aaa_fcrm_crmid_get_subscriber_by_user_id( $user_id );
    return $sub ? aaa_fcrm_crmid_link_html( $sub->id ) : '—';
}, 10, 3 );

add_filter( 'manage_users_sortable_columns', function( $sortable ) {
    $sortable['crm_id'] = 'crm_id';
    return $sortable;
} );

add_action( 'pre_get_users', function( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( 'crm_id' !== $query->get( 'orderby' ) ) return;
    global $wpdb;
    // LEFT JOIN fc_subscribers to sort by crm id
    $query->query_from .= " LEFT JOIN {$wpdb->prefix}fc_subscribers AS fcsub ON fcsub.user_id = {$wpdb->users}.ID ";
    $query->set( 'orderby', 'fcsub.id ' . ( 'DESC' === strtoupper( $query->get('order') ) ? 'DESC' : 'ASC' ) );
} );

/** ---- Woo Orders list: add "CRM ID" column (default visible) ---- */
add_filter( 'manage_edit-shop_order_columns', function( $columns ) {
    // Insert after order_number if present, else append
    $new = [];
    $inserted = false;
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'order_number' === $key ) {
            $new['crm_id'] = __( 'CRM ID', 'aaa-fluentcrm-crm-id-columns' );
            $inserted = true;
        }
    }
    if ( ! $inserted ) $new['crm_id'] = __( 'CRM ID', 'aaa-fluentcrm-crm-id-columns' );
    return $new;
}, 20 );

add_action( 'manage_shop_order_posts_custom_column', function( $column, $post_id ) {
    if ( 'crm_id' !== $column ) return;
    if ( ! function_exists( 'wc_get_order' ) ) { echo '—'; return; }

    $order = wc_get_order( $post_id );
    if ( ! $order ) { echo '—'; return; }

    // Prefer user_id; fallback to billing email lookup in FluentCRM
    $user_id = $order->get_user_id();
    $sub = $user_id ? aaa_fcrm_crmid_get_subscriber_by_user_id( $user_id ) : null;
    if ( ! $sub ) {
        $sub = aaa_fcrm_crmid_get_subscriber_by_email( $order->get_billing_email() );
    }

    echo $sub ? aaa_fcrm_crmid_link_html( $sub->id ) : '—';
}, 10, 2 );

/** ---- Minimal settings stub under FluentCRM (bottom) ---- */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'fluentcrm-admin',
        'CRM ID Columns',
        'CRM ID Columns',
        'manage_options',
        'aaa-fluentcrm-crm-id-columns',
        function() {
            if ( ! current_user_can( 'manage_options' ) ) return;
            echo '<div class="wrap"><h1>CRM ID Columns</h1>';
            echo '<p>This add-on adds a <strong>CRM ID</strong> column to Users and Woo Orders. No settings required.</p>';
            if ( AAA_FCRM_CRM_ID_COLUMNS_DEBUG ) {
                echo '<p><em>Debug logging is enabled.</em></p>';
            }
            echo '</div>';
        },
        999
    );
}, 20 );

/** ---- Activation ping ---- */
register_activation_hook( __FILE__, function() {
    aaa_fcrm_crmid_log('activated', [ 'time' => current_time('mysql') ]);
});
