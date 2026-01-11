<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-settings.php
 * Purpose: Forecast global settings + queue tools. Stores options in aaa_oc_options scope "forecast".
 * Version: 0.1.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_SETTINGS_TAB_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_SETTINGS_TAB_DEBUG', true );
}

if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '/../../core/options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

$scope = 'forecast';

$actions = [
	'review'    => __( 'Flag for Review', 'aaa-oc' ),
	'clearance' => __( 'Mark for Clearance', 'aaa-oc' ),
	'remove'    => __( 'Mark for Removal', 'aaa-oc' ),
	'none'      => __( 'Do Nothing', 'aaa-oc' ),
];

if ( isset( $_POST['aaa_oc_forecast_settings_submit'] ) && check_admin_referer( 'aaa_oc_forecast_settings_nonce' ) ) {
	$values = [
		'forecast_not_moving_label'    => isset( $_POST['forecast_not_moving_label'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_not_moving_label'] ) ) : 'Not Moving',
		'forecast_not_moving_days'     => isset( $_POST['forecast_not_moving_days'] ) ? absint( $_POST['forecast_not_moving_days'] ) : 30,
		'forecast_not_moving_action'   => isset( $_POST['forecast_not_moving_action'] ) ? sanitize_key( wp_unslash( $_POST['forecast_not_moving_action'] ) ) : 'review',
		'forecast_stale_label'         => isset( $_POST['forecast_stale_label'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_stale_label'] ) ) : 'Stale',
		'forecast_stale_days'          => isset( $_POST['forecast_stale_days'] ) ? absint( $_POST['forecast_stale_days'] ) : 60,
		'forecast_stale_action'        => isset( $_POST['forecast_stale_action'] ) ? sanitize_key( wp_unslash( $_POST['forecast_stale_action'] ) ) : 'clearance',
		'forecast_enable_nightly_rerun' => ! empty( $_POST['forecast_enable_nightly_rerun'] ) ? 1 : 0,
	];

	foreach ( [ 'forecast_not_moving_action', 'forecast_stale_action' ] as $k ) {
		if ( ! isset( $actions[ $values[ $k ] ] ) ) {
			$values[ $k ] = 'none';
		}
	}

	foreach ( $values as $k => $v ) {
		aaa_oc_set_option( $k, $v, $scope );
	}

	if ( AAA_OC_FORECAST_SETTINGS_TAB_DEBUG ) {
		error_log( '[AAA_OC Forecast Settings] Saved global forecast settings.' );
	}

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Forecast settings saved.', 'aaa-oc' ) . '</p></div>';
}

$nm_label  = aaa_oc_get_option( 'forecast_not_moving_label', $scope, 'Not Moving' );
$nm_days   = absint( aaa_oc_get_option( 'forecast_not_moving_days', $scope, 30 ) );
$nm_action = aaa_oc_get_option( 'forecast_not_moving_action', $scope, 'review' );

$st_label  = aaa_oc_get_option( 'forecast_stale_label', $scope, 'Stale' );
$st_days   = absint( aaa_oc_get_option( 'forecast_stale_days', $scope, 60 ) );
$st_action = aaa_oc_get_option( 'forecast_stale_action', $scope, 'clearance' );

$nightly   = (int) aaa_oc_get_option( 'forecast_enable_nightly_rerun', $scope, 0 );
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Forecast Settings', 'aaa-oc' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_forecast_settings_nonce' ); ?>

		<h3><?php esc_html_e( 'Status Thresholds', 'aaa-oc' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="forecast_not_moving_label"><?php esc_html_e( 'Not Moving Label', 'aaa-oc' ); ?></label></th>
				<td><input type="text" id="forecast_not_moving_label" name="forecast_not_moving_label" value="<?php echo esc_attr( $nm_label ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="forecast_not_moving_days"><?php esc_html_e( 'Not Moving Days', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="forecast_not_moving_days" name="forecast_not_moving_days" value="<?php echo esc_attr( $nm_days ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Not Moving Action', 'aaa-oc' ); ?></th>
				<td>
					<select name="forecast_not_moving_action">
						<?php foreach ( $actions as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $nm_action, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="forecast_stale_label"><?php esc_html_e( 'Stale Label', 'aaa-oc' ); ?></label></th>
				<td><input type="text" id="forecast_stale_label" name="forecast_stale_label" value="<?php echo esc_attr( $st_label ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="forecast_stale_days"><?php esc_html_e( 'Stale Days', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="forecast_stale_days" name="forecast_stale_days" value="<?php echo esc_attr( $st_days ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Stale Action', 'aaa-oc' ); ?></th>
				<td>
					<select name="forecast_stale_action">
						<?php foreach ( $actions as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $st_action, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Nightly Rerun', 'aaa-oc' ); ?></h3>
		<p><label><input type="checkbox" name="forecast_enable_nightly_rerun" value="1" <?php checked( 1, $nightly ); ?> /> <?php esc_html_e( 'Queue all enabled products each night (daily cron).', 'aaa-oc' ); ?></label></p>

		<p class="submit">
			<button type="submit" name="aaa_oc_forecast_settings_submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?></button>
		</p>
	</form>

	<hr />

	<h3><?php esc_html_e( 'Queue Tools', 'aaa-oc' ); ?></h3>
	<p class="description"><?php esc_html_e( 'These actions add products to the queue and let the queue processor run in batches.', 'aaa-oc' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
		<input type="hidden" name="action" value="aaa_oc_forecast_queue_all_enabled" />
		<?php wp_nonce_field( 'aaa_oc_forecast_queue_all_enabled' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Queue All Enabled Products', 'aaa-oc' ); ?></button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
		<input type="hidden" name="action" value="aaa_oc_forecast_process_queue_now" />
		<?php wp_nonce_field( 'aaa_oc_forecast_process_queue_now' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Process Queue Now', 'aaa-oc' ); ?></button>
	</form>
</div>
