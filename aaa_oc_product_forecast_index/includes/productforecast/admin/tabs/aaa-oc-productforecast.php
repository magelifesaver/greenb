<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/admin/tabs/aaa-oc-productforecast.php
 * Purpose: Admin UI for ProductForecast Index (manual table install + bulk reindex).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) {
    echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
    return;
}

if ( ! class_exists( 'AAA_OC_ProductForecast_Table_Installer' ) || ! class_exists( 'AAA_OC_ProductForecast_Table_Indexer' ) ) {
    echo '<div class="notice notice-error"><p>ProductForecast module not fully loaded.</p></div>';
    return;
}

$base_url = add_query_arg([
    'page' => 'aaa-oc-productforecast',
], admin_url( 'admin.php' ) );

// PRG handlers.
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'aaa_oc_productforecast_nonce' ) ) {
    $action = sanitize_text_field( $_POST['aaa_oc_pf_action'] ?? '' );

    if ( $action === 'install_tables' ) {
        AAA_OC_ProductForecast_Table_Installer::install();
        wp_safe_redirect( add_query_arg( 'installed', 1, $base_url ) );
        exit;
    }

    if ( $action === 'reindex_all' ) {
        AAA_OC_ProductForecast_Table_Indexer::reindex_all();
        wp_safe_redirect( add_query_arg( 'reindexed', 1, $base_url ) );
        exit;
    }
}

if ( ! empty( $_GET['installed'] ) ) {
    echo '<div class="notice notice-success"><p>Tables installed/updated.</p></div>';
}
if ( ! empty( $_GET['reindexed'] ) ) {
    echo '<div class="notice notice-success"><p>Reindex complete.</p></div>';
}

global $wpdb;
$t_index = $wpdb->prefix . AAA_OC_ProductForecast_Table_Installer::T_INDEX;
$t_log   = $wpdb->prefix . AAA_OC_ProductForecast_Table_Installer::T_LOG;

$rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$t_index}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$enabled = $wpdb->get_var( "SELECT COUNT(*) FROM {$t_index} WHERE forecast_enable_reorder = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

?>
<div class="wrap">
    <h1>Product Forecast Index</h1>

    <p>
        <strong>Index table:</strong> <code><?php echo esc_html( $t_index ); ?></code><br>
        <strong>Log table:</strong> <code><?php echo esc_html( $t_log ); ?></code>
    </p>

    <p>
        <strong>Rows:</strong> <?php echo esc_html( (string) intval( $rows ) ); ?>
        &nbsp; | &nbsp;
        <strong>Enabled (forecast_enable_reorder=1):</strong> <?php echo esc_html( (string) intval( $enabled ) ); ?>
    </p>

    <h2>Actions</h2>

    <form method="post" style="display:inline-block; margin-right:10px;">
        <?php wp_nonce_field( 'aaa_oc_productforecast_nonce' ); ?>
        <input type="hidden" name="aaa_oc_pf_action" value="install_tables" />
        <button class="button">Install / Repair Tables</button>
    </form>

    <form method="post" style="display:inline-block;" onsubmit="return confirm('Reindex ALL products now? This can take time.');">
        <?php wp_nonce_field( 'aaa_oc_productforecast_nonce' ); ?>
        <input type="hidden" name="aaa_oc_pf_action" value="reindex_all" />
        <button class="button button-primary">Reindex All Products</button>
    </form>

    <hr>

    <h2>How syncing works</h2>
    <ul style="list-style:disc; padding-left:18px;">
        <li><strong>Source of truth for editable flags</strong> is product meta (e.g. <code>forecast_enable_reorder</code> is stored as <code>yes</code>/<code>no</code>).</li>
        <li><strong>Source of truth for reporting/grid</strong> is this index table. Flags are stored as <code>1</code>/<code>0</code> for fast filtering/sorting.</li>
        <li>This module auto-updates the table whenever any <code>forecast_*</code> or <code>aip_*</code> postmeta changes.</li>
    </ul>

    <h2>AI summaries</h2>
    <p>
        This module stores two summary blobs when present:
        <code>aip_historical_summary</code> (history-only) and <code>aip_forecast_summary</code> (forecast/projection state).
        Keeping them separate makes AI reporting more precise.
    </p>
</div>
