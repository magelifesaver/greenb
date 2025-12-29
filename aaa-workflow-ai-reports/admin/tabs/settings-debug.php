<?php
/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/admin/tabs/settings-debug.php
 * Description: Tab 4 — Shows WP debug.log and AAA AI logs (/aaa-logs/).
 * Version: 2.2.0
 * Updated: 2025-12-28
 * ============================================================================
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$debug_file    = WP_CONTENT_DIR . '/debug.log';
$aaa_logs_dir  = WP_CONTENT_DIR . '/aaa-logs';
$aaa_logs_files = glob( $aaa_logs_dir . '/ai-report-*.log' );
rsort( $aaa_logs_files );
?>
<h2>Debug Log Viewer</h2>
<p>View the last entries from <code>debug.log</code> and recent AI report logs in <code>/wp-content/aaa-logs/</code>.</p>
<hr>
<h3>WordPress Debug Log</h3>
<?php
if ( file_exists( $debug_file ) ) {
    $lines = file( $debug_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
    $last  = array_slice( $lines, -200 );
    echo '<pre style="background:#1e1e1e;color:#c9d1d9;padding:15px;border-radius:6px;max-height:400px;overflow-y:auto;font-family:monospace;">';
    echo esc_html( implode( "\n", $last ) );
    echo '</pre>';
} else {
    echo '<p>No <code>debug.log</code> file found.</p>';
}
?>
<form method="post">
    <?php wp_nonce_field('aaa_wf_ai_clear_logs','aaa_wf_ai_clear_logs_nonce'); ?>
    <p><button type="submit" name="clear_debug_log" value="1" class="button button-secondary">Clear Debug Log</button></p>
</form>
<?php
if ( isset( $_POST['clear_debug_log'] ) && check_admin_referer('aaa_wf_ai_clear_logs','aaa_wf_ai_clear_logs_nonce') ) {
    if ( current_user_can('manage_woocommerce') && file_exists( $debug_file ) ) {
        file_put_contents( $debug_file, '' );
        echo '<div class="updated notice"><p>✅ Debug log cleared successfully.</p></div>';
    }
}
?>
<hr>
<h3>AI Report Logs</h3>
<?php if ( empty( $aaa_logs_files ) ) : ?>
    <p>No AI report logs found in <code>/aaa-logs/</code>.</p>
<?php else : ?>
    <ul style="list-style:disc;margin-left:20px;">
    <?php foreach ( array_slice( $aaa_logs_files, 0, 10 ) as $log_path ) :
        $filename = basename( $log_path );
        $content  = file_get_contents( $log_path );
        $preview  = substr( $content, 0, 1000 );
    ?>
        <li style="margin-bottom:10px;">
            <strong><?php echo esc_html( $filename ); ?></strong>
            <pre style="background:#1e1e1e;color:#c9d1d9;padding:10px;border-radius:4px;max-height:200px;overflow-y:auto;font-family:monospace;"><?php echo esc_html( $preview ); ?></pre>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php aaa_wf_ai_debug( 'Rendered Debug Viewer tab (using /aaa-logs).', basename( __FILE__ ), 'admin' ); ?>