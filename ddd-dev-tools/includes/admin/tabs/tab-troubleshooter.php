<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Troubleshooter::settings();
$view = DDD_DT_Troubleshooter::view_data();
$plugins = $view['plugins'] ?? [ 'active' => [], 'inactive' => [] ];
$mu_plugins = $view['mu_plugins'] ?? [];
?>
<h2><?php esc_html_e( 'Troubleshooter', 'ddd-dev-tools' ); ?></h2>
<p><?php esc_html_e( 'Admin-only file/code search for troubleshooting. Recommended for development and staging. If used on production, enable → use → disable.', 'ddd-dev-tools' ); ?></p>
<p><strong><?php esc_html_e( 'Security note:', 'ddd-dev-tools' ); ?></strong> <?php esc_html_e( 'This can read plugin/theme files. Keep it disabled unless you are actively troubleshooting.', 'ddd-dev-tools' ); ?></p>

<form method="post" action="">
    <input type="hidden" name="ddd_dt_action" value="save" />
    <input type="hidden" name="tab" value="troubleshooter" />
    <?php wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Troubleshooter', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable this module', 'ddd-dev-tools' ); ?></label></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Module debug logging', 'ddd-dev-tools' ); ?></th>
            <td><label><input type="checkbox" name="debug_enabled" value="1" <?php checked( ! empty( $s['debug_enabled'] ) ); ?> /> <?php esc_html_e( 'Write troubleshooting actions to the DDD Dev Tools logs', 'ddd-dev-tools' ); ?></label></td>
        </tr>
    </table>

    <?php submit_button( __( 'Save Changes', 'ddd-dev-tools' ) ); ?>
</form>

<div id="dt-ts-disabled" class="notice notice-warning" style="display:none"><p><?php esc_html_e( 'Troubleshooter is disabled. Enable it above to use search and quick actions.', 'ddd-dev-tools' ); ?></p></div>

<?php
if ( ! empty( $s['enabled'] ) ) {
    require __DIR__ . '/partials/troubleshooter-ui.php';
}
