<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/admin/tabs/aaa-oc-board-preferences.php
 * Purpose: Admin tab for Board preferences (lifetime-spend left border tiers).
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Ensure option helpers exist (correct relative path from /core/options/admin/tabs/) */
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __FILE__, 3 ) . '/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}

/** Scope for all options on this tab */
$scope = 'board';

/** Keys (stable) */
$K = [
	'enable' => 'board_left_border_enabled',
	't2min'  => 'board_tier2_min_spend',
	't3min'  => 'board_tier3_min_spend',
	'c1'     => 'board_tier1_color',
	'c2'     => 'board_tier2_color',
	'c3'     => 'board_tier3_color',
];

/** Defaults */
$D = [
	$K['enable'] => 'yes',
	$K['t2min']  => '500',
	$K['t3min']  => '1500',
	$K['c1']     => '#b0b7c3',
	$K['c2']     => '#4a8cff',
	$K['c3']     => '#00c853',
];

/** Load */
$V = [];
foreach ( $D as $key => $def ) {
	$V[ $key ] = (string) aaa_oc_get_option( $key, $scope, $def );
}

/** Save */
if ( isset($_POST['save_board_pref']) && check_admin_referer('aaa_oc_board_pref_save') && current_user_can('manage_options') ) {
	$V[$K['enable']] = ! empty($_POST[$K['enable']]) ? 'yes' : 'no';

	$V[$K['t2min']]  = is_numeric($_POST[$K['t2min']] ?? '') ? (string) $_POST[$K['t2min']] : $D[$K['t2min']];
	$V[$K['t3min']]  = is_numeric($_POST[$K['t3min']] ?? '') ? (string) $_POST[$K['t3min']] : $D[$K['t3min']];

	$V[$K['c1']]     = sanitize_hex_color( $_POST[$K['c1']] ?? $D[$K['c1']] ) ?: $D[$K['c1']];
	$V[$K['c2']]     = sanitize_hex_color( $_POST[$K['c2']] ?? $D[$K['c2']] ) ?: $D[$K['c2']];
	$V[$K['c3']]     = sanitize_hex_color( $_POST[$K['c3']] ?? $D[$K['c3']] ) ?: $D[$K['c3']];

	foreach ( $V as $key => $val ) {
		aaa_oc_set_option( $key, $val, $scope );
	}
	echo '<div class="updated"><p>' . esc_html__( 'Board preferences saved.', 'aaa-oc' ) . '</p></div>';
}
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Board Preferences', 'aaa-oc' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field('aaa_oc_board_pref_save'); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Enable Left Border (Lifetime Spend)', 'aaa-oc'); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr($K['enable']); ?>" <?php checked($V[$K['enable']], 'yes'); ?> /> <?php esc_html_e('Enabled', 'aaa-oc'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Tier 2 Minimum Spend', 'aaa-oc'); ?></th>
				<td><input type="number" step="0.01" min="0" name="<?php echo esc_attr($K['t2min']); ?>" value="<?php echo esc_attr($V[$K['t2min']]); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Tier 3 Minimum Spend', 'aaa-oc'); ?></th>
				<td><input type="number" step="0.01" min="0" name="<?php echo esc_attr($K['t3min']); ?>" value="<?php echo esc_attr($V[$K['t3min']]); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Tier 1 Color (Base)', 'aaa-oc'); ?></th>
				<td><input type="color" name="<?php echo esc_attr($K['c1']); ?>" value="<?php echo esc_attr($V[$K['c1']]); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Tier 2 Color (Loyal)', 'aaa-oc'); ?></th>
				<td><input type="color" name="<?php echo esc_attr($K['c2']); ?>" value="<?php echo esc_attr($V[$K['c2']]); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Tier 3 Color (VIP)', 'aaa-oc'); ?></th>
				<td><input type="color" name="<?php echo esc_attr($K['c3']); ?>" value="<?php echo esc_attr($V[$K['c3']]); ?>" /></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" name="save_board_pref" class="button button-primary"><?php esc_html_e('Save Preferences', 'aaa-oc'); ?></button>
		</p>
	</form>
</div>
