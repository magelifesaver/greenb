<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_ATUM_Log_Viewer::settings();

$searched = false;
$product_name = '';
$rows = [];
$error = '';

if ( ! empty( $_POST['ddd_dt_atum_search'] ) ) {
    $searched = true;
    if ( ! current_user_can( 'manage_options' ) ) {
        $error = __( 'Unauthorized', 'ddd-dev-tools' );
    } elseif ( ! isset( $_POST['ddd_dt_atum_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddd_dt_atum_nonce'] ) ), 'ddd_dt_atum_search' ) ) {
        $error = __( 'Invalid nonce.', 'ddd-dev-tools' );
    } elseif ( empty( $s['enabled'] ) ) {
        $error = __( 'ATUM Logs module is disabled.', 'ddd-dev-tools' );
    } else {
        $product_name = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
        $rows = DDD_DT_ATUM_Logs::get_logs_by_product_name( $product_name, (int) ( $s['max_rows'] ?? 500 ) );
        DDD_DT_Logger::write( 'atum_log_viewer', 'search', [ 'term' => $product_name, 'rows' => count( $rows ) ] );
    }
}
?>
<h2><?php esc_html_e( 'ATUM Logs', 'ddd-dev-tools' ); ?></h2>
<p><?php esc_html_e( 'Search ATUM inventory logs by product name. Requires the ATUM logs table to exist.', 'ddd-dev-tools' ); ?></p>

<form method="post" action="">
    <input type="hidden" name="ddd_dt_action" value="save" />
    <input type="hidden" name="tab" value="atum_log_viewer" />
    <?php wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable ATUM Logs', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable this module', 'ddd-dev-tools' ); ?></label></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Max rows', 'ddd-dev-tools' ); ?></th>
            <td><input type="number" name="max_rows" value="<?php echo esc_attr( (int) ( $s['max_rows'] ?? 500 ) ); ?>" min="50" max="5000" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Use DataTables (CDN)', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="use_datatables_cdn" value="1" <?php checked( ! empty( $s['use_datatables_cdn'] ) ); ?> /> <?php esc_html_e( 'Load DataTables from cdn.datatables.net (sorting/paging)', 'ddd-dev-tools' ); ?></label></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Module debug logging', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="debug_enabled" value="1" <?php checked( ! empty( $s['debug_enabled'] ) ); ?> /> <?php esc_html_e( 'Write ATUM log searches to the DDD Dev Tools logs', 'ddd-dev-tools' ); ?></label></td>
        </tr>
    </table>

    <?php submit_button( __( 'Save Changes', 'ddd-dev-tools' ) ); ?>
</form>

<?php if ( empty( $s['enabled'] ) ) : ?>
    <div class="notice notice-warning"><p><?php esc_html_e( 'ATUM Logs is disabled. Enable it above to search logs.', 'ddd-dev-tools' ); ?></p></div>
<?php else : ?>
    <?php if ( ! DDD_DT_ATUM_Logs::table_exists() ) : ?>
        <div class="notice notice-error"><p><?php esc_html_e( 'ATUM logs table not found (wp_atum_logs). If ATUM is not installed or logs are disabled, there is nothing to query.', 'ddd-dev-tools' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'ddd_dt_atum_search', 'ddd_dt_atum_nonce' ); ?>
        <input type="hidden" name="ddd_dt_atum_search" value="1" />
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Product name contains', 'ddd-dev-tools' ); ?></th>
                <td>
                    <input type="text" name="product_name" value="<?php echo esc_attr( $product_name ); ?>" class="regular-text" />
                    <?php submit_button( __( 'Search', 'ddd-dev-tools' ), 'secondary', 'submit', false ); ?>
                </td>
            </tr>
        </table>
    </form>

    <?php if ( $searched && $error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <?php if ( $searched && ! $error ) : ?>
        <h3><?php esc_html_e( 'Results', 'ddd-dev-tools' ); ?></h3>
        <?php if ( empty( $rows ) ) : ?>
            <p><?php esc_html_e( 'No matching logs found.', 'ddd-dev-tools' ); ?></p>
        <?php else : ?>
            <table class="widefat striped" id="ddd-dt-atum-logs-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Time', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Event', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Product', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Order', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Old', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'New', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Qty', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'Move', 'ddd-dev-tools' ); ?></th>
                        <th><?php esc_html_e( 'User', 'ddd-dev-tools' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $r ) : ?>
                        <?php
                        $move = $r->movement;
                        $cls = 'ddd-dt-atum-move-zero';
                        if ( is_numeric( $move ) ) {
                            if ( (int) $move > 0 ) $cls = 'ddd-dt-atum-move-pos';
                            if ( (int) $move < 0 ) $cls = 'ddd-dt-atum-move-neg';
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $r->log_time ); ?></td>
                            <td><?php echo esc_html( $r->event_label ); ?></td>
                            <td><?php echo esc_html( (string) $r->product_id ); ?></td>
                            <td><?php echo esc_html( (string) $r->order_id ); ?></td>
                            <td><?php echo esc_html( (string) $r->old_stock ); ?></td>
                            <td><?php echo esc_html( (string) $r->new_stock ); ?></td>
                            <td><?php echo esc_html( (string) $r->qty ); ?></td>
                            <td class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( is_null( $move ) ? '' : (string) $move ); ?></td>
                            <td><?php echo esc_html( (string) $r->display_name ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
