<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Product_Debugger::settings();

$ran = false;
$product_id = 0;
$result = null;
$error = '';

if ( ! empty( $_POST['ddd_dt_pdbg_run'] ) ) {
    $ran = true;
    if ( ! current_user_can( 'manage_options' ) ) {
        $error = __( 'Unauthorized', 'ddd-dev-tools' );
    } elseif ( ! isset( $_POST['ddd_dt_pdbg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddd_dt_pdbg_nonce'] ) ), 'ddd_dt_pdbg_run' ) ) {
        $error = __( 'Invalid nonce.', 'ddd-dev-tools' );
    } elseif ( empty( $s['enabled'] ) ) {
        $error = __( 'Product Debugger is disabled.', 'ddd-dev-tools' );
    } else {
        $product_id = absint( $_POST['product_id'] ?? 0 );
        $result = DDD_DT_Product_Debugger::debug_product( $product_id );
        if ( empty( $result['ok'] ) ) {
            $error = (string) ( $result['error'] ?? 'Failed.' );
        } else {
            DDD_DT_Logger::write( 'product_debugger', 'run', [ 'product_id' => $product_id ] );
        }
    }
}
?>
<h2><?php esc_html_e( 'Product Debugger', 'ddd-dev-tools' ); ?></h2>
<p><?php esc_html_e( 'Dump WooCommerce product object + post meta + ATUM tables (if present) for a Product ID.', 'ddd-dev-tools' ); ?></p>

<form method="post" action="">
    <input type="hidden" name="ddd_dt_action" value="save" />
    <input type="hidden" name="tab" value="product_debugger" />
    <?php wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Product Debugger', 'ddd-dev-tools' ); ?></th>
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
    <div class="notice notice-warning"><p><?php esc_html_e( 'Product Debugger is disabled. Enable it above to run.', 'ddd-dev-tools' ); ?></p></div>
<?php else : ?>
    <form method="post" action="">
        <?php wp_nonce_field( 'ddd_dt_pdbg_run', 'ddd_dt_pdbg_nonce' ); ?>
        <input type="hidden" name="ddd_dt_pdbg_run" value="1" />
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Product ID', 'ddd-dev-tools' ); ?></th>
                <td>
                    <input type="number" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" min="1" />
                    <?php submit_button( __( 'Run', 'ddd-dev-tools' ), 'secondary', 'submit', false ); ?>
                </td>
            </tr>
        </table>
    </form>

    <?php if ( $ran && $error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <?php if ( $ran && ! $error && ! empty( $result['ok'] ) ) : ?>
        <p><button type="button" class="button" id="ddd-dt-pdbg-copy"><?php esc_html_e( 'Copy Output', 'ddd-dev-tools' ); ?></button></p>
        <pre id="ddd-dt-pdbg-output" style="max-height:600px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:12px;"><?php
        echo esc_html( "Product\n" );
        echo esc_html( print_r( $result['product'], true ) );
        echo esc_html( "\n\nPost Meta\n" );
        echo esc_html( print_r( $result['meta'], true ) );
        echo esc_html( "\n\nATUM: atum_product_data\n" );
        echo esc_html( print_r( $result['atum_product_data'], true ) );
        echo esc_html( "\n\nATUM: atum_product_locations\n" );
        echo esc_html( print_r( $result['atum_product_locations'], true ) );
        ?></pre>
    <?php endif; ?>
<?php endif; ?>
