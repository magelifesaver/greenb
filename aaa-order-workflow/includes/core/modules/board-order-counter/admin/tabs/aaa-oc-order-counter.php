<?php
/**
 * File: /includes/core/modules/board-order-counter/admin/tabs/aaa-oc-order-counter.php
 * Purpose: Daily Order Counter settings tab under the unified Workflow Settings page (V1).
 * Version: 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Local debug toggle
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
	define( 'DEBUG_THIS_FILE', true );
}

// Ensure option helpers exist
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '../../../../options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

// --- Handle Save ---
if ( isset( $_POST['save_odc'] ) && check_admin_referer( 'aaa_oc_odc_save' ) ) {
	aaa_oc_set_option( 'enable_logging',      ! empty( $_POST['enable_logging'] ),      'odc' );
	aaa_oc_set_option( 'enable_file_logging', ! empty( $_POST['enable_file_logging'] ), 'odc' );
	echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'aaa-order-workflow' ) . '</p></div>';
}

// --- Handle Reset ---
if ( isset( $_POST['reset_odc'] ) && check_admin_referer( 'aaa_oc_odc_reset' ) ) {
	$key = 'daily_order_count_' . current_time( 'Y-m-d' );
	aaa_oc_set_option( $key, 0, 'odc' );
	echo '<div class="updated"><p>' . esc_html__( 'Daily count reset.', 'aaa-order-workflow' ) . '</p></div>';
}

// --- Load current values ---
$log1 = aaa_oc_get_option( 'enable_logging', 'odc', false );
$log2 = aaa_oc_get_option( 'enable_file_logging', 'odc', false );
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Daily Order Counter Settings', 'aaa-order-workflow' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_odc_save' ); ?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable debug.log Logging', 'aaa-order-workflow' ); ?></th>
				<td><input type="checkbox" name="enable_logging" value="1" <?php checked( $log1 ); ?>></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Enable File Logging (odc.log)', 'aaa-order-workflow' ); ?></th>
				<td><input type="checkbox" name="enable_file_logging" value="1" <?php checked( $log2 ); ?>></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="save_odc" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'aaa-order-workflow' ); ?>">
		</p>
	</form>

	<hr>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_odc_reset' ); ?>
		<p><input type="submit" name="reset_odc" class="button" value="<?php esc_attr_e( 'Reset Today’s Counter', 'aaa-order-workflow' ); ?>"></p>
	</form>
</div>
