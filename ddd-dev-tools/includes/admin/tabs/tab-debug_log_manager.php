<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Debug_Log_Manager::settings();

$path = WP_CONTENT_DIR . '/debug.log';
$exists = file_exists( $path );
$size = $exists ? (int) filesize( $path ) : 0;
$mtime = $exists ? (int) filemtime( $path ) : 0;

$file = DDD_DT_Debug_Log_Manager::file();
$snap = DDD_DT_Debug_Log_Manager::snapshot();

$download_url = '';
$snap_url = '';
if ( ! empty( $s['enabled'] ) ) {
    $download_url = admin_url( 'admin-ajax.php?action=ddd_dt_dbg_download&nonce=' . rawurlencode( wp_create_nonce( 'ddd_dt_dbg_download' ) ) );
    $snap_url = admin_url( 'admin-ajax.php?action=ddd_dt_dbg_download_snapshot&nonce=' . rawurlencode( wp_create_nonce( 'ddd_dt_dbg_download_snapshot' ) ) );
}
?>
<h2><?php esc_html_e( 'Debug Log', 'ddd-dev-tools' ); ?></h2>
<p><?php esc_html_e( 'View/tail/download wp-content/debug.log (requires WP_DEBUG_LOG). Use on dev/staging. Disable when not needed.', 'ddd-dev-tools' ); ?></p>

<form method="post" action="">
    <input type="hidden" name="ddd_dt_action" value="save" />
    <input type="hidden" name="tab" value="debug_log_manager" />
    <?php wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Debug Log tools', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable this module', 'ddd-dev-tools' ); ?></label></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Download chunk size (MB)', 'ddd-dev-tools' ); ?></th>
            <td><input type="number" name="download_chunk_mb" value="<?php echo esc_attr( (int) ( $s['download_chunk_mb'] ?? 2 ) ); ?>" min="1" max="25" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Module debug logging', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="debug_enabled" value="1" <?php checked( ! empty( $s['debug_enabled'] ) ); ?> /> <?php esc_html_e( 'Write debug-log actions to the DDD Dev Tools logs', 'ddd-dev-tools' ); ?></label></td>
        </tr>
    </table>

    <?php submit_button( __( 'Save Changes', 'ddd-dev-tools' ) ); ?>
</form>

<div class="ddd-debug-manager">
    <div class="ddd-debug-status">
        <div><strong><?php esc_html_e( 'Path', 'ddd-dev-tools' ); ?></strong> <code><?php echo esc_html( $path ); ?></code></div>
        <div><strong><?php esc_html_e( 'Exists', 'ddd-dev-tools' ); ?></strong> <?php echo $exists ? esc_html__( 'Yes', 'ddd-dev-tools' ) : esc_html__( 'No', 'ddd-dev-tools' ); ?></div>
        <div><strong><?php esc_html_e( 'Size', 'ddd-dev-tools' ); ?></strong> <?php echo esc_html( size_format( $size ) ); ?></div>
        <div><strong><?php esc_html_e( 'Modified', 'ddd-dev-tools' ); ?></strong> <?php echo $mtime ? esc_html( wp_date( 'Y-m-d H:i:s', $mtime ) ) : ''; ?></div>
        <div class="ddd-debug-note"><?php esc_html_e( 'If debug.log is not updating, confirm WP_DEBUG and WP_DEBUG_LOG are enabled.', 'ddd-dev-tools' ); ?></div>
    </div>

    <?php if ( empty( $s['enabled'] ) ) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e( 'Debug Log tools are disabled. Enable above to tail/download and use snapshots.', 'ddd-dev-tools' ); ?></p></div>
    <?php else : ?>
        <div class="ddd-debug-actions">
            <a class="button" href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( 'Download debug.log (zip)', 'ddd-dev-tools' ); ?></a>
            <button type="button" class="button" id="ddd-dt-debug-log-snapshot"><?php esc_html_e( 'Create Snapshot (dedupe lines)', 'ddd-dev-tools' ); ?></button>
            <a class="button" href="<?php echo esc_url( $snap_url ); ?>"><?php esc_html_e( 'Download Snapshot', 'ddd-dev-tools' ); ?></a>
            <button type="button" class="button" id="ddd-dt-debug-log-clear-snapshot"><?php esc_html_e( 'Clear Snapshot', 'ddd-dev-tools' ); ?></button>
        </div>

        <div class="ddd-debug-actions">
            <button type="button" class="button button-primary" id="ddd-dt-debug-log-start"><?php esc_html_e( 'Start Tail', 'ddd-dev-tools' ); ?></button>
            <button type="button" class="button" id="ddd-dt-debug-log-stop"><?php esc_html_e( 'Stop', 'ddd-dev-tools' ); ?></button>
            <button type="button" class="button" id="ddd-dt-debug-log-clear"><?php esc_html_e( 'Clear Output', 'ddd-dev-tools' ); ?></button>
            <span id="ddd-dt-debug-log-status" style="margin-left:10px;"></span>
        </div>

        <textarea id="ddd-dt-debug-log-output" readonly="readonly" placeholder="<?php esc_attr_e( 'Tail output will appear here...', 'ddd-dev-tools' ); ?>"></textarea>
    <?php endif; ?>
</div>
