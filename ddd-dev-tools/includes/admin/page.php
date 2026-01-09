<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$tabs = [
    'overview' => __( 'Overview', 'ddd-dev-tools' ),
    'general' => __( 'General', 'ddd-dev-tools' ),
    'url_cleaner' => __( 'URL Cleaner', 'ddd-dev-tools' ),
    'pagination_redirect' => __( 'Pagination Redirect', 'ddd-dev-tools' ),
    'page_click_manager' => __( 'Page Click Manager', 'ddd-dev-tools' ),
    'troubleshooter' => __( 'Troubleshooter', 'ddd-dev-tools' ),
    'atum_log_viewer' => __( 'ATUM Logs', 'ddd-dev-tools' ),
    'debug_log_manager' => __( 'Debug Log', 'ddd-dev-tools' ),
    'product_debugger' => __( 'Product Debugger', 'ddd-dev-tools' ),
    'order_debugger' => __( 'Order Debugger', 'ddd-dev-tools' ),
    'logs' => __( 'Logs', 'ddd-dev-tools' ),
];

if ( ! isset( $tabs[ $tab ] ) ) {
    $tab = 'overview';
}

echo '<div class="wrap ddd-dt-wrap">';
echo '<h1>' . esc_html__( 'DDD Dev Tools', 'ddd-dev-tools' ) . '</h1>';
echo '<p class="description">' . esc_html__( 'Enable only when needed (dev/staging). On production: enable → use → disable.', 'ddd-dev-tools' ) . '</p>';

if ( isset( $_GET['updated'] ) ) {
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'ddd-dev-tools' ) . '</p></div>';
}

if ( ! DDD_DT_Modules::is_custom_options_ready() ) {
    echo '<div class="notice notice-warning"><p>' . esc_html__( 'Custom options table (aaa_oc_options) not found for this site. Settings will fall back to wp_options.', 'ddd-dev-tools' ) . '</p></div>';
}

echo '<h2 class="nav-tab-wrapper">';
foreach ( $tabs as $k => $label ) {
    $url = add_query_arg( [ 'page' => 'ddd-dev-tools', 'tab' => $k ], admin_url( 'tools.php' ) );
    $cls = ( $k === $tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
    echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}
echo '</h2>';

$file = DDD_DT_DIR . 'includes/admin/tabs/tab-' . $tab . '.php';
if ( is_readable( $file ) ) {
    include $file;
} else {
    echo '<p>' . esc_html__( 'Tab not found.', 'ddd-dev-tools' ) . '</p>';
}
echo '</div>';
