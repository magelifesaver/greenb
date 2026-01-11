<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-field-settings.php
 * Purpose: Manage which forecast fields appear on the WooCommerce Products list,
 *          and whether each field is sortable and stored in post meta.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_FIELD_SETTINGS_TAB_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_FIELD_SETTINGS_TAB_DEBUG', true );
}

if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '/../../core/options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

if ( ! class_exists( 'AAA_OC_Forecast_Meta_Registry' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '/../helpers/class-aaa-oc-forecast-meta-registry.php';
}

$option_key = 'forecast_field_settings';
$scope      = 'forecast';

$keys = array_keys( AAA_OC_Forecast_Meta_Registry::get_keys() );
sort( $keys );

$settings = aaa_oc_get_option( $option_key, $scope, [] );
if ( ! is_array( $settings ) ) {
	$settings = [];
}

if ( isset( $_POST['aaa_oc_forecast_field_settings_submit'] ) && check_admin_referer( 'aaa_oc_forecast_field_settings_nonce' ) ) {
	$new = [];

	foreach ( $keys as $key ) {
		$enabled  = ! empty( $_POST['enabled'][ $key ] ) ? 1 : 0;
		$sortable = ! empty( $_POST['sortable'][ $key ] ) ? 1 : 0;
		$storage  = isset( $_POST['storage'][ $key ] ) ? sanitize_key( wp_unslash( $_POST['storage'][ $key ] ) ) : 'meta';
		if ( ! in_array( $storage, [ 'meta', 'table' ], true ) ) {
			$storage = 'meta';
		}
		$new[ $key ] = [ 'enabled' => $enabled, 'sortable' => $sortable, 'storage' => $storage ];
	}

	aaa_oc_set_option( $option_key, $new, $scope );
	$settings = $new;

	if ( AAA_OC_FORECAST_FIELD_SETTINGS_TAB_DEBUG ) {
		error_log( '[AAA_OC Forecast Fields] Saved field settings (' . count( $new ) . ' keys).' );
	}

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Forecast field settings saved.', 'aaa-oc' ) . '</p></div>';
}

function aaa_oc_forecast_field_setting( array $settings, string $key, string $subkey, $default ) {
	return isset( $settings[ $key ][ $subkey ] ) ? $settings[ $key ][ $subkey ] : $default;
}
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Forecast Field Settings', 'aaa-oc' ); ?></h2>
	<p class="description"><?php esc_html_e( 'These toggles control which forecast fields appear as columns on the WooCommerce Products list and whether each field is sortable. Storage controls whether the Products list reads from product meta or from the forecast index table.', 'aaa-oc' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_forecast_field_settings_nonce' ); ?>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field Key', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Show Column', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Sortable (Meta Only)', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Storage Source', 'aaa-oc' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $keys as $key ) : ?>
					<?php
						$enabled  = (int) aaa_oc_forecast_field_setting( $settings, $key, 'enabled', 0 );
						$sortable = (int) aaa_oc_forecast_field_setting( $settings, $key, 'sortable', 0 );
						$storage  = (string) aaa_oc_forecast_field_setting( $settings, $key, 'storage', 'meta' );
					?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><input type="checkbox" name="enabled[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $enabled ); ?> /></td>
						<td><input type="checkbox" name="sortable[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $sortable ); ?> /></td>
						<td>
							<select name="storage[<?php echo esc_attr( $key ); ?>]">
								<option value="meta" <?php selected( 'meta', $storage ); ?>><?php esc_html_e( 'Product Meta', 'aaa-oc' ); ?></option>
								<option value="table" <?php selected( 'table', $storage ); ?>><?php esc_html_e( 'Forecast Index Table', 'aaa-oc' ); ?></option>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" name="aaa_oc_forecast_field_settings_submit" class="button button-primary">
				<?php esc_html_e( 'Save Field Settings', 'aaa-oc' ); ?>
			</button>
		</p>
	</form>
</div>
