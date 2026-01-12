<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-settings.php
 * Purpose: Forecast global settings + queue tools. Stores options in aaa_oc_options scope "forecast".
 * Version: 0.1.2
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
		'global_lead_time_days'         => isset( $_POST['global_lead_time_days'] ) ? absint( $_POST['global_lead_time_days'] ) : 7,
		'global_cost_percent'           => isset( $_POST['global_cost_percent'] ) ? floatval( $_POST['global_cost_percent'] ) : 50,
		'global_sales_window_days'      => isset( $_POST['global_sales_window_days'] ) ? absint( $_POST['global_sales_window_days'] ) : 90,
		'global_minimum_order_qty'      => isset( $_POST['global_minimum_order_qty'] ) ? absint( $_POST['global_minimum_order_qty'] ) : 1,
		'global_minimum_stock'          => isset( $_POST['global_minimum_stock'] ) ? absint( $_POST['global_minimum_stock'] ) : 0,
		'grid_sales_window_days'        => isset( $_POST['grid_sales_window_days'] ) ? absint( $_POST['grid_sales_window_days'] ) : 180,
		'enable_purchase_orders_globally' => ( isset( $_POST['enable_purchase_orders_globally'] ) && $_POST['enable_purchase_orders_globally'] === 'yes' ) ? 'yes' : 'no',
		'not_moving_t1_days'               => isset( $_POST['not_moving_t1_days'] ) ? absint( $_POST['not_moving_t1_days'] ) : 14,
		'not_moving_t2_days'               => isset( $_POST['not_moving_t2_days'] ) ? absint( $_POST['not_moving_t2_days'] ) : 30,
		'not_moving_t3_after_best_sold_by' => isset( $_POST['not_moving_t3_after_best_sold_by'] ) ? absint( $_POST['not_moving_t3_after_best_sold_by'] ) : 15,
		'enable_new_product_threshold'     => ( isset( $_POST['enable_new_product_threshold'] ) && $_POST['enable_new_product_threshold'] === 'yes' ) ? 'yes' : 'no',
		'new_product_days_threshold'       => isset( $_POST['new_product_days_threshold'] ) ? absint( $_POST['new_product_days_threshold'] ) : 30,
		'enable_stock_threshold'           => ( isset( $_POST['enable_stock_threshold'] ) && $_POST['enable_stock_threshold'] === 'yes' ) ? 'yes' : 'no',
		'stock_threshold_qty'              => isset( $_POST['stock_threshold_qty'] ) ? absint( $_POST['stock_threshold_qty'] ) : 0,
	];

	foreach ( [ 'forecast_not_moving_action', 'forecast_stale_action' ] as $k ) {
		if ( ! isset( $actions[ $values[ $k ] ] ) ) {
			$values[ $k ] = 'none';
		}
	}

	foreach ( $values as $k => $v ) {
		aaa_oc_set_option( $k, $v, $scope );
	}

	$brand_slug_input = isset( $_POST['brand_taxonomy_slug'] ) ? sanitize_key( wp_unslash( $_POST['brand_taxonomy_slug'] ) ) : '';
	if ( ! empty( $brand_slug_input ) ) {
		aaa_oc_set_option( 'brand_taxonomy_slug', $brand_slug_input, $scope );
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
$lead_time = absint( aaa_oc_get_option( 'global_lead_time_days', $scope, 7 ) );
$cost_pct  = floatval( aaa_oc_get_option( 'global_cost_percent', $scope, 50 ) );
$sales_window = absint( aaa_oc_get_option( 'global_sales_window_days', $scope, 90 ) );
$min_order_qty = absint( aaa_oc_get_option( 'global_minimum_order_qty', $scope, 1 ) );
$min_stock = absint( aaa_oc_get_option( 'global_minimum_stock', $scope, 0 ) );
$grid_window = absint( aaa_oc_get_option( 'grid_sales_window_days', $scope, 180 ) );
$po_enabled = aaa_oc_get_option( 'enable_purchase_orders_globally', $scope, 'yes' );
$tier1 = absint( aaa_oc_get_option( 'not_moving_t1_days', $scope, 14 ) );
$tier2 = absint( aaa_oc_get_option( 'not_moving_t2_days', $scope, 30 ) );
$tier3 = absint( aaa_oc_get_option( 'not_moving_t3_after_best_sold_by', $scope, 15 ) );
$new_enabled = aaa_oc_get_option( 'enable_new_product_threshold', $scope, 'no' );
$new_days = absint( aaa_oc_get_option( 'new_product_days_threshold', $scope, 30 ) );
$stock_enabled = aaa_oc_get_option( 'enable_stock_threshold', $scope, 'no' );
$stock_threshold = absint( aaa_oc_get_option( 'stock_threshold_qty', $scope, 0 ) );
$brand_slug = aaa_oc_get_option( 'brand_taxonomy_slug', $scope, 'pwb-brand' );
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Forecast Settings', 'aaa-oc' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_forecast_settings_nonce' ); ?>

		<h3><?php esc_html_e( 'Global Defaults', 'aaa-oc' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="global_lead_time_days"><?php esc_html_e( 'Default Lead Time (days)', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="global_lead_time_days" name="global_lead_time_days" value="<?php echo esc_attr( $lead_time ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="global_cost_percent"><?php esc_html_e( 'Fallback Cost %', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="global_cost_percent" name="global_cost_percent" value="<?php echo esc_attr( $cost_pct ); ?>" class="small-text" step="0.1" min="0" />%</td>
			</tr>
			<tr>
				<th scope="row"><label for="global_sales_window_days"><?php esc_html_e( 'Best Sold By (Shelf Life Days)', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="global_sales_window_days" name="global_sales_window_days" value="<?php echo esc_attr( $sales_window ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="global_minimum_order_qty"><?php esc_html_e( 'Default Minimum Order Qty', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="global_minimum_order_qty" name="global_minimum_order_qty" value="<?php echo esc_attr( $min_order_qty ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="global_minimum_stock"><?php esc_html_e( 'Default Minimum Stock Buffer', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="global_minimum_stock" name="global_minimum_stock" value="<?php echo esc_attr( $min_stock ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="grid_sales_window_days"><?php esc_html_e( 'Grid Sales Window (Max Days to Look Back)', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="grid_sales_window_days" name="grid_sales_window_days" value="<?php echo esc_attr( $grid_window ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="enable_purchase_orders_globally"><?php esc_html_e( 'Enable PO Generation', 'aaa-oc' ); ?></label></th>
				<td>
					<select name="enable_purchase_orders_globally" id="enable_purchase_orders_globally">
						<option value="yes" <?php selected( $po_enabled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'aaa-oc' ); ?></option>
						<option value="no" <?php selected( $po_enabled, 'no' ); ?>><?php esc_html_e( 'No', 'aaa-oc' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'New & Stock Thresholds', 'aaa-oc' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="enable_new_product_threshold"><?php esc_html_e( 'Exclude New Products', 'aaa-oc' ); ?></label></th>
				<td>
					<select name="enable_new_product_threshold" id="enable_new_product_threshold">
						<option value="yes" <?php selected( $new_enabled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'aaa-oc' ); ?></option>
						<option value="no" <?php selected( $new_enabled, 'no' ); ?>><?php esc_html_e( 'No', 'aaa-oc' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="new_product_days_threshold"><?php esc_html_e( 'New Product Days Threshold', 'aaa-oc' ); ?></label></th>
				<td>
					<input type="number" id="new_product_days_threshold" name="new_product_days_threshold" value="<?php echo esc_attr( $new_days ); ?>" class="small-text" min="0" />
					<p class="description"><?php esc_html_e( 'Products first sold within this many days are considered new.', 'aaa-oc' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="enable_stock_threshold"><?php esc_html_e( 'Exclude High Stock Products', 'aaa-oc' ); ?></label></th>
				<td>
					<select name="enable_stock_threshold" id="enable_stock_threshold">
						<option value="yes" <?php selected( $stock_enabled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'aaa-oc' ); ?></option>
						<option value="no" <?php selected( $stock_enabled, 'no' ); ?>><?php esc_html_e( 'No', 'aaa-oc' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="stock_threshold_qty"><?php esc_html_e( 'Stock Threshold (Qty)', 'aaa-oc' ); ?></label></th>
				<td>
					<input type="number" id="stock_threshold_qty" name="stock_threshold_qty" value="<?php echo esc_attr( $stock_threshold ); ?>" class="small-text" min="0" />
					<p class="description"><?php esc_html_e( 'Products with stock above this value are excluded from forecasting when enabled.', 'aaa-oc' ); ?></p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Taxonomy Settings', 'aaa-oc' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="brand_taxonomy_slug"><?php esc_html_e( 'Brand Taxonomy Slug', 'aaa-oc' ); ?></label></th>
				<td>
					<input type="text" id="brand_taxonomy_slug" name="brand_taxonomy_slug" value="<?php echo esc_attr( $brand_slug ); ?>" class="regular-text" placeholder="pwb-brand" />
					<p class="description"><?php esc_html_e( 'Controls which taxonomy is used for brand filtering in the forecast grid.', 'aaa-oc' ); ?></p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Sales Status Tier Thresholds', 'aaa-oc' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="not_moving_t1_days"><?php esc_html_e( 'Tier 1 - No Sale After (Days)', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="not_moving_t1_days" name="not_moving_t1_days" value="<?php echo esc_attr( $tier1 ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="not_moving_t2_days"><?php esc_html_e( 'Tier 2 - Delayed After (Days)', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="not_moving_t2_days" name="not_moving_t2_days" value="<?php echo esc_attr( $tier2 ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="not_moving_t3_after_best_sold_by"><?php esc_html_e( 'Tier 3 - Days Past Best Sold By', 'aaa-oc' ); ?></label></th>
				<td><input type="number" id="not_moving_t3_after_best_sold_by" name="not_moving_t3_after_best_sold_by" value="<?php echo esc_attr( $tier3 ); ?>" class="small-text" min="0" /></td>
			</tr>
		</table>

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
	<p class="description"><?php esc_html_e( 'These actions schedule background queue work to avoid blocking the admin.', 'aaa-oc' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
		<input type="hidden" name="action" value="aaa_oc_forecast_queue_all_enabled" />
		<?php wp_nonce_field( 'aaa_oc_forecast_queue_all_enabled' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Queue All Enabled Products (Scheduled)', 'aaa-oc' ); ?></button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
		<input type="hidden" name="action" value="aaa_oc_forecast_process_queue_now" />
		<?php wp_nonce_field( 'aaa_oc_forecast_process_queue_now' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Process Queue (Scheduled)', 'aaa-oc' ); ?></button>
	</form>
</div>
