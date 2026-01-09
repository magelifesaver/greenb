<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

$dir = DDD_DT_Logger::dir();
$files = DDD_DT_Logger::list_log_files();

echo '<h2>' . esc_html__( 'Logs', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'Logs are written only when Global debug logging is ON and the module Debug logging is ON.', 'ddd-dev-tools' ) . '</p>';
echo '<p><strong>' . esc_html__( 'Directory:', 'ddd-dev-tools' ) . '</strong> <code>' . esc_html( $dir ) . '</code></p>';

echo '<form method="post" style="margin: 12px 0;">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="clear_logs">';
echo '<input type="hidden" name="tab" value="logs">';
submit_button( __( 'Delete all log files', 'ddd-dev-tools' ), 'delete', 'submit', false );
echo '</form>';

if ( ! $files ) {
    echo '<p>' . esc_html__( 'No log files found.', 'ddd-dev-tools' ) . '</p>';
    return;
}

echo '<table class="widefat striped"><thead><tr>';
echo '<th>' . esc_html__( 'File', 'ddd-dev-tools' ) . '</th>';
echo '<th>' . esc_html__( 'Size', 'ddd-dev-tools' ) . '</th>';
echo '<th>' . esc_html__( 'Modified (UTC)', 'ddd-dev-tools' ) . '</th>';
echo '</tr></thead><tbody>';

foreach ( $files as $f ) {
    $bn = basename( $f );
    $url = add_query_arg( [ 'page' => 'ddd-dev-tools', 'tab' => 'logs', 'log' => rawurlencode( $bn ) ], admin_url( 'tools.php' ) );
    $size = size_format( (int) @filesize( $f ) );
    $mod = gmdate( 'Y-m-d H:i:s', (int) @filemtime( $f ) );
    echo '<tr><td><a href="' . esc_url( $url ) . '">' . esc_html( $bn ) . '</a></td><td>' . esc_html( $size ) . '</td><td>' . esc_html( $mod ) . '</td></tr>';
}
echo '</tbody></table>';

$pick = isset( $_GET['log'] ) ? sanitize_file_name( wp_unslash( $_GET['log'] ) ) : '';
if ( $pick && substr( $pick, -4 ) === '.log' ) {
    $abs = $dir . '/' . $pick;
    $tail = DDD_DT_Logger::tail( $abs, 200 );
    echo '<hr><h3>' . esc_html__( 'Last 200 lines:', 'ddd-dev-tools' ) . ' <code>' . esc_html( $pick ) . '</code></h3>';
    echo '<textarea rows="12" class="large-text code" readonly>' . esc_textarea( implode( "\n", $tail ) ) . '</textarea>';
}
