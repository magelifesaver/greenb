<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-field-settings.php
 * Purpose: Manage which forecast fields appear on the WooCommerce Products list,
 *          and whether each field is sortable and stored in post meta.
 * Version: 0.2.0
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

$summary_keys = [ 'aip_product_summary', 'aip_inventory_summary', 'aip_sales_summary', 'aip_forecast_summary' ];
$keys = array_diff( array_keys( AAA_OC_Forecast_Meta_Registry::get_keys() ), $summary_keys );
sort( $keys );

$settings = aaa_oc_get_option( $option_key, $scope, [] );
if ( ! is_array( $settings ) ) {
	$settings = [];
}

if ( isset( $_POST['aaa_oc_forecast_field_settings_submit'] ) && check_admin_referer( 'aaa_oc_forecast_field_settings_nonce' ) ) {
	$new = [];

	foreach ( $keys as $key ) {
		$enabled = ! empty( $_POST['enabled'][ $key ] ) ? 1 : 0;
		$mirror  = ! empty( $_POST['mirror'][ $key ] ) ? 1 : 0;
		$groups  = isset( $_POST['summary_groups'][ $key ] ) ? (array) $_POST['summary_groups'][ $key ] : [];
		$groups  = array_values( array_filter( array_map( 'sanitize_key', $groups ) ) );
		$new[ $key ] = [ 'enabled' => $enabled, 'mirror' => $mirror, 'summary_groups' => $groups ];
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

$summary_groups = [
	'product'   => __( 'Product Summary (Customer Care)', 'aaa-oc' ),
	'inventory' => __( 'Inventory Summary (Admin)', 'aaa-oc' ),
	'sales'     => __( 'Sales Summary (Reporting)', 'aaa-oc' ),
	'forecast'  => __( 'Forecast Summary (Planning)', 'aaa-oc' ),
];
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Forecast Field Settings', 'aaa-oc' ); ?></h2>
	<p class="description"><?php esc_html_e( 'These toggles control which forecast fields appear as columns on the WooCommerce Products list, whether the field is mirrored to post meta (for Admin Columns Pro), and which summary arrays should include each field.', 'aaa-oc' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_forecast_field_settings_nonce' ); ?>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field Key', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Show Column', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Mirror to Post Meta', 'aaa-oc' ); ?></th>
					<th><?php esc_html_e( 'Summary Groups', 'aaa-oc' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $keys as $key ) : ?>
					<?php
						$enabled = (int) aaa_oc_forecast_field_setting( $settings, $key, 'enabled', 0 );
						$mirror  = (int) aaa_oc_forecast_field_setting( $settings, $key, 'mirror', 1 );
						$groups  = (array) aaa_oc_forecast_field_setting( $settings, $key, 'summary_groups', [] );
					?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><input type="checkbox" name="enabled[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $enabled ); ?> /></td>
						<td>
							<input type="checkbox" name="mirror[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $mirror ); ?> />
							<p class="description"><?php esc_html_e( 'Use this when Admin Columns Pro needs the field as post meta.', 'aaa-oc' ); ?></p>
						</td>
						<td>
							<?php foreach ( $summary_groups as $group_key => $group_label ) : ?>
								<label style="display:block;">
									<input type="checkbox" name="summary_groups[<?php echo esc_attr( $key ); ?>][]" value="<?php echo esc_attr( $group_key ); ?>" <?php checked( in_array( $group_key, $groups, true ) ); ?> />
									<?php echo esc_html( $group_label ); ?>
								</label>
							<?php endforeach; ?>
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
