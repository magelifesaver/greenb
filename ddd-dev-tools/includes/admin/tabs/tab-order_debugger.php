<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Order_Debugger::settings();
?>
<h2><?php esc_html_e( 'Order Debugger', 'ddd-dev-tools' ); ?></h2>
<p><?php esc_html_e( 'Dump WooCommerce order REST JSON plus workflow tables (aaa_oc_*) for a given Order ID. Output opens in a new tab.', 'ddd-dev-tools' ); ?></p>

<form method="post" action="">
    <input type="hidden" name="ddd_dt_action" value="save" />
    <input type="hidden" name="tab" value="order_debugger" />
    <?php wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Order Debugger', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable this module', 'ddd-dev-tools' ); ?></label></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Module debug logging', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="debug_enabled" value="1" <?php checked( ! empty( $s['debug_enabled'] ) ); ?> /> <?php esc_html_e( 'Write runs to the DDD Dev Tools logs', 'ddd-dev-tools' ); ?></label></td>
        </tr>
    </table>

    <?php submit_button( __( 'Save Changes', 'ddd-dev-tools' ) ); ?>
</form>

<?php if ( empty( $s['enabled'] ) ) : ?>
    <div class="notice notice-warning"><p><?php esc_html_e( 'Order Debugger is disabled. Enable it above to run.', 'ddd-dev-tools' ); ?></p></div>
<?php else : ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank">
        <input type="hidden" name="action" value="ddd_dt_order_debugger" />
        <?php wp_nonce_field( 'ddd_dt_order_debugger' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Order ID', 'ddd-dev-tools' ); ?></th>
                <td>
                    <input type="number" name="order_id" value="" min="1" />
                    <?php submit_button( __( 'Run (new tab)', 'ddd-dev-tools' ), 'secondary', 'submit', false ); ?>
                </td>
            </tr>
        </table>
    </form>
<?php endif; ?>
