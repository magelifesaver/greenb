<?php
/**
 * File: includes/geodata/admin/tabs/aaa-oc-adbc-settings.php
 * Purpose: Tab for Delivery Coords / Geodata (ADBC)
 *          Appears under the new Workflow Settings core page.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure Options helper class exists
if ( ! class_exists( 'AAA_OC_Options' ) ) {
	require_once plugin_dir_path( __DIR__ ) . '../../../core/options/class-aaa-oc-options.php';
}

// Load current settings from custom table
$opts = AAA_OC_Options::get( 'delivery_adbc_options', 'adbc', [] ) ?: [];

// Handle form submission
if ( isset( $_POST['save_delivery_adbc'] )
     && check_admin_referer( 'save_delivery_adbc_action', 'delivery_adbc_nonce' ) ) {

	$new = [
		'google_browser_api_key' => sanitize_text_field( $_POST['google_browser_api_key'] ?? '' ),
		'google_geocode_api_key' => sanitize_text_field( $_POST['google_geocode_api_key'] ?? '' ),

		// Field display toggles
		'hide_shipping_coords' => ! empty( $_POST['hide_shipping_coords'] ),
		'hide_shipping_flag'   => ! empty( $_POST['hide_shipping_flag'] ),
		'show_shipping_badge'  => ! empty( $_POST['show_shipping_badge'] ),
		'hide_billing_coords'  => ! empty( $_POST['hide_billing_coords'] ),
		'hide_billing_flag'    => ! empty( $_POST['hide_billing_flag'] ),
		'show_billing_badge'   => ! empty( $_POST['show_billing_badge'] ),
	];

	AAA_OC_Options::set( 'delivery_adbc_options', $new, 'adbc' );

	echo '<div class="updated"><p>ADBC settings saved.</p></div>';

	$opts = $new; // refresh current values
}
?>

<div class="wrap">
	<h2>Delivery Coords / Geodata (ADBC)</h2>

	<form method="post">
		<?php wp_nonce_field( 'save_delivery_adbc_action', 'delivery_adbc_nonce' ); ?>
		<input type="hidden" name="save_delivery_adbc" value="1" />

		<table class="form-table">
			<tr>
				<th><label for="google_browser_api_key"><?php _e( 'Google Browser API Key', 'aaa-oc' ); ?></label></th>
				<td>
					<input type="text" id="google_browser_api_key"
					       name="google_browser_api_key"
					       value="<?php echo esc_attr( $opts['google_browser_api_key'] ?? '' ); ?>"
					       style="width:100%;">
				</td>
			</tr>
			<tr>
				<th><label for="google_geocode_api_key"><?php _e( 'Google Geocode API Key', 'aaa-oc' ); ?></label></th>
				<td>
					<input type="text" id="google_geocode_api_key"
					       name="google_geocode_api_key"
					       value="<?php echo esc_attr( $opts['google_geocode_api_key'] ?? '' ); ?>"
					       style="width:100%;">
				</td>
			</tr>

			<tr><th colspan="2"><h3>Shipping Fields Display</h3></th></tr>

			<tr><th><?php _e( 'Hide Shipping Coords Fields', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="hide_shipping_coords"
					<?php checked( $opts['hide_shipping_coords'] ?? false ); ?>></td></tr>

			<tr><th><?php _e( 'Hide Shipping Verification Flag', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="hide_shipping_flag"
					<?php checked( $opts['hide_shipping_flag'] ?? false ); ?>></td></tr>

			<tr><th><?php _e( 'Show Shipping Badge', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="show_shipping_badge"
					<?php checked( $opts['show_shipping_badge'] ?? false ); ?>></td></tr>

			<tr><th colspan="2"><h3>Billing Fields Display</h3></th></tr>

			<tr><th><?php _e( 'Hide Billing Coords Fields', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="hide_billing_coords"
					<?php checked( $opts['hide_billing_coords'] ?? false ); ?>></td></tr>

			<tr><th><?php _e( 'Hide Billing Verification Flag', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="hide_billing_flag"
					<?php checked( $opts['hide_billing_flag'] ?? false ); ?>></td></tr>

			<tr><th><?php _e( 'Show Billing Badge', 'aaa-oc' ); ?></th>
				<td><input type="checkbox" name="show_billing_badge"
					<?php checked( $opts['show_billing_badge'] ?? false ); ?>></td></tr>
		</table>

		<p class="submit">
			<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'aaa-oc' ); ?>">
		</p>
	</form>
</div>
