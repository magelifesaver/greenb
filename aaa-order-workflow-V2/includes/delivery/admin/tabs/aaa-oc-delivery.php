<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/admin/tabs/aaa-oc-delivery.php
 * Purpose: delivery settings tab for WFCP
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __FILE__, 4 ) . '/core/options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}


?>
<div class="wrap">
	<h2><?php esc_html_e( 'Delivery Settings', 'aaa-oc' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_delivery_save' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"></th>
				<td>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="save_delivery_tab" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?>
			</button>
		</p>
	</form>
</div>
