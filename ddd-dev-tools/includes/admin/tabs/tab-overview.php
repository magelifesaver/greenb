<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$g = DDD_DT_Options::get( 'ddd_dt_general', [], 'global' );
$g = is_array( $g ) ? $g : [];
$uc = DDD_DT_URL_Cleaner::settings();
$pr = DDD_DT_Pagination_Redirect::settings();
$pcm = DDD_DT_Page_Click_Manager::settings();
$ts = DDD_DT_Troubleshooter::settings();
$atum = DDD_DT_ATUM_Log_Viewer::settings();
$dbglog = DDD_DT_Debug_Log_Manager::settings();
$pdbg = DDD_DT_Product_Debugger::settings();
$odb = DDD_DT_Order_Debugger::settings();

echo '<h2>' . esc_html__( 'What this plugin does', 'ddd-dev-tools' ) . '</h2>';
echo '<ul class="ddd-dt-list">';
echo '<li><strong>' . esc_html__( 'URL Cleaner', 'ddd-dev-tools' ) . '</strong> — removes selected query parameters (e.g. add-to-cart / layered-nav filters) and redirects to a cleaner canonical URL.</li>';
echo '<li><strong>' . esc_html__( 'Pagination Redirect', 'ddd-dev-tools' ) . '</strong> — redirects legacy /page/{n}/ URLs to the base archive URL (optionally only when the request is a 404).</li>';
echo '<li><strong>' . esc_html__( 'Page Click Manager', 'ddd-dev-tools' ) . '</strong> — exposes a REST endpoint to log “wrong-site” button clicks from a landing page and optionally email the admin.</li>';
echo '<li><strong>' . esc_html__( 'Troubleshooter', 'ddd-dev-tools' ) . '</strong> — admin-only file/code search with selectable scope (target plugin, active/inactive plugins, MU-plugins, entire plugins folder) using PHP, grep, or ripgrep when available.</li>';
echo '<li><strong>' . esc_html__( 'ATUM Logs', 'ddd-dev-tools' ) . '</strong> — queries ATUM logs by product name and displays results in a table (optional DataTables).</li>';
echo '<li><strong>' . esc_html__( 'Debug Log', 'ddd-dev-tools' ) . '</strong> — view/tail/download wp-content/debug.log (if WP_DEBUG_LOG is enabled) with snapshot tools.</li>';
echo '<li><strong>' . esc_html__( 'Product Debugger', 'ddd-dev-tools' ) . '</strong> — dumps Woo product object + post meta + ATUM tables (if present) for a given Product ID.</li>';
echo '<li><strong>' . esc_html__( 'Order Debugger', 'ddd-dev-tools' ) . '</strong> — dumps Woo order REST JSON plus workflow tables (aaa_oc_*) for a given Order ID.</li>';
echo '</ul>';

echo '<h2>' . esc_html__( 'When to enable', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'These tools can affect redirects and logging. Recommended workflow: keep disabled on live unless actively troubleshooting; enable → test → disable.', 'ddd-dev-tools' ) . '</p>';

echo '<h2>' . esc_html__( 'Status', 'ddd-dev-tools' ) . '</h2>';
echo '<table class="widefat striped"><tbody>';
echo '<tr><td>' . esc_html__( 'Global debug logging', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $g['debug_enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'URL Cleaner enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $uc['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Pagination Redirect enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $pr['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Page Click Manager enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $pcm['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Troubleshooter enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $ts['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'ATUM Logs enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $atum['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Debug Log enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $dbglog['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Product Debugger enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $pdbg['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '<tr><td>' . esc_html__( 'Order Debugger enabled', 'ddd-dev-tools' ) . '</td><td>' . ( ! empty( $odb['enabled'] ) ? '<span class="ddd-dt-on">ON</span>' : '<span class="ddd-dt-off">OFF</span>' ) . '</td></tr>';
echo '</tbody></table>';

echo '<h2>' . esc_html__( 'Logging', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'File logs are written only when Global debug logging is ON and the module Debug logging is ON. Logs are stored in:', 'ddd-dev-tools' ) . '</p>';
echo '<code>' . esc_html( DDD_DT_Logger::dir() ) . '</code>';

echo '<p class="description">' . esc_html__( 'Risk: if debug logging is left on during high traffic, logs can grow quickly. Use the General tab to set retention and max file size.', 'ddd-dev-tools' ) . '</p>';

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$legacy = [
    'ddd-url-cleaner/ddd-url-cleaner.php' => __( 'DDD URL Cleaner (legacy)', 'ddd-dev-tools' ),
    'ddd-pagination-redirect/ddd-pagination-redirect.php' => __( 'DDD Pagination Redirect (legacy)', 'ddd-dev-tools' ),
    'ddd-page-click-manager/ddd-page-click-manager.php' => __( 'DDD Page Click Manager (legacy)', 'ddd-dev-tools' ),
    'ddd-dev-troubleshooter-cli/aaa-dev-troubleshooter-js.php' => __( 'DDD Dev Troubleshooter CLI (legacy)', 'ddd-dev-tools' ),
    'ddd-atum-reader/ddd-atum-reader.php' => __( 'DDD ATUM Log Viewer (legacy)', 'ddd-dev-tools' ),
    'ddd-dev-debug-manager/ddd-dev-debug-manager.php' => __( 'DDD Dev Debug Manager (legacy)', 'ddd-dev-tools' ),
    'aaa-product-debugger/aaa-product-debugger.php' => __( 'ATUM Product Debugger (legacy)', 'ddd-dev-tools' ),
    'aaa-order-debugger/aaa-order-debugger.php' => __( 'AAA Order Debugger (legacy)', 'ddd-dev-tools' ),
];
$active_legacy = [];
foreach ( $legacy as $file => $label ) {
    if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $file ) ) {
        $active_legacy[] = $label;
    }
}
if ( $active_legacy ) {
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__( 'Legacy standalone plugins are still active. Disable them to avoid duplicated redirects/logging:', 'ddd-dev-tools' );
    echo ' ' . esc_html( implode( ', ', $active_legacy ) );
    echo '</p></div>';
}
