<?php
/**
 * File: /includes/core/options/admin/tabs/aaa-oc-workflow-settings.php
 * Purpose: "Workflow Settings" core tab for board visibility & polling options.
 * Version: 1.5.0 (converted to tab format)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure helpers are loaded
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '../../../options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

// --- Save handler ---
if ( isset( $_POST['aaa_oc_settings_submit'] ) && check_admin_referer( 'aaa_oc_settings_nonce' ) ) {
	$enabled_statuses = isset( $_POST['aaa_oc_enabled_statuses'] ) ? (array) $_POST['aaa_oc_enabled_statuses'] : [];
	$enabled_statuses = array_map( 'sanitize_text_field', $enabled_statuses );

	$show_countdown_bar = ! empty( $_POST['aaa_oc_show_countdown_bar'] ) ? 1 : 0;
	$disable_polling    = ! empty( $_POST['aaa_oc_disable_polling'] ) ? 1 : 0;

	aaa_oc_set_option( 'aaa_oc_enabled_statuses', $enabled_statuses, 'workflow' );
	aaa_oc_set_option( 'aaa_oc_show_countdown_bar', $show_countdown_bar, 'workflow' );
	aaa_oc_set_option( 'aaa_oc_disable_polling', $disable_polling, 'workflow' );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Workflow settings saved.', 'aaa-order-workflow' ) . '</p></div>';
}

// --- Load current values ---
$all_wc_statuses  = wc_get_order_statuses();
$enabled_statuses = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', [] );
$show_bar         = aaa_oc_get_option( 'aaa_oc_show_countdown_bar', 'workflow', 0 );
$disable_polling  = aaa_oc_get_option( 'aaa_oc_disable_polling', 'workflow', 0 );
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Workflow Settings', 'aaa-order-workflow' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_settings_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Board Status Visibility', 'aaa-order-workflow' ); ?></th>
				<td>
					<?php foreach ( $all_wc_statuses as $slug => $label ) : ?>
						<div style="margin-bottom:6px;">
							<label>
								<input type="checkbox"
								       name="aaa_oc_enabled_statuses[]"
								       value="<?php echo esc_attr( $slug ); ?>"
								       <?php checked( in_array( $slug, $enabled_statuses, true ) ); ?> />
								<?php echo esc_html( $label ) . ' (' . esc_html( $slug ) . ')'; ?>
							</label>
						</div>
					<?php endforeach; ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Show Polling Countdown Bar', 'aaa-order-workflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aaa_oc_show_countdown_bar" value="1" <?php checked( $show_bar, 1 ); ?> />
						<?php esc_html_e( 'Display top countdown on the board page', 'aaa-order-workflow' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Polling (Development)', 'aaa-order-workflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aaa_oc_disable_polling" value="1" <?php checked( $disable_polling, 1 ); ?> />
						<?php esc_html_e( 'Stop board auto-refresh / polling', 'aaa-order-workflow' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="aaa_oc_settings_submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'aaa-order-workflow' ); ?>
			</button>
		</p>
	</form>
</div>
