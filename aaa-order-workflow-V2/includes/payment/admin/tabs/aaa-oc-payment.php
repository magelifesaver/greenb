<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/admin/tabs/aaa-oc-payment.php
 * Purpose: Payment settings tab (toggles for board payment/driver pills).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Ensure option helpers exist */
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __FILE__, 4 ) . '/core/options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

/** Scope */
$scope = 'payment';

/** Keys (stable) */
$k_show_status_pill   = 'payment_show_status_pill';     // Paid / Partial / Unpaid
$k_show_real_method   = 'payment_show_real_method_pill';
$k_show_envelope_pill = 'payment_show_envelope_pill';
$k_show_tips_pill     = 'payment_show_tips_pill';
$k_show_driver_pill   = 'payment_show_driver_pill';     // Driver assignment indicator

/** Defaults */
$def = [
	$k_show_status_pill   => 1,
	$k_show_real_method   => 1,
	$k_show_envelope_pill => 1,
	$k_show_tips_pill     => 1,
	$k_show_driver_pill   => 1,
];

/** Load */
$val = [];
foreach ( $def as $k => $d ) {
	$val[ $k ] = (int) aaa_oc_get_option( $k, $scope, $d );
}

/** Save */
if ( isset($_POST['save_payment_tab']) && check_admin_referer('aaa_oc_payment_save') && current_user_can('manage_options') ) {
	$val[$k_show_status_pill]   = ! empty($_POST[$k_show_status_pill])   ? 1 : 0;
	$val[$k_show_real_method]   = ! empty($_POST[$k_show_real_method])   ? 1 : 0;
	$val[$k_show_envelope_pill] = ! empty($_POST[$k_show_envelope_pill]) ? 1 : 0;
	$val[$k_show_tips_pill]     = ! empty($_POST[$k_show_tips_pill])     ? 1 : 0;
	$val[$k_show_driver_pill]   = ! empty($_POST[$k_show_driver_pill])   ? 1 : 0;

	foreach ( $val as $k => $v ) {
		aaa_oc_set_option( $k, $v, $scope );
	}
	echo '<div class="updated"><p>' . esc_html__( 'Payment settings saved.', 'aaa-oc' ) . '</p></div>';
}
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Payment & Driver Settings', 'aaa-oc' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_payment_save' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Show Payment Status Pill', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($k_show_status_pill); ?>" value="1" <?php checked($val[$k_show_status_pill], 1); ?> /> <?php esc_html_e('Paid / Partial / Unpaid', 'aaa-oc'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Show Real Payment Method Pill', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($k_show_real_method); ?>" value="1" <?php checked($val[$k_show_real_method], 1); ?> /> <?php esc_html_e('Displays normalized method (e.g., Zelle, Venmo, Cash, Card).', 'aaa-oc'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Show Envelope Outstanding Pill', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($k_show_envelope_pill); ?>" value="1" <?php checked($val[$k_show_envelope_pill], 1); ?> /> <?php esc_html_e('Indicates if envelope reconciliation is pending.', 'aaa-oc'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Show Tips Pill', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($k_show_tips_pill); ?>" value="1" <?php checked($val[$k_show_tips_pill], 1); ?> /> <?php esc_html_e('Displays tip presence/amount.', 'aaa-oc'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Show Driver Assignment Pill', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($k_show_driver_pill); ?>" value="1" <?php checked($val[$k_show_driver_pill], 1); ?> /> <?php esc_html_e('Shows assigned driver / unassigned state.', 'aaa-oc'); ?></label></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="save_payment_tab" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?>
			</button>
		</p>
	</form>
</div>
