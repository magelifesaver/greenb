<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/admin/tabs/aaa-oc-payconfirm-settings.php
 * Purpose: PayConfirm settings tab (global page). Stores only the match source in aaa_oc_options (scope: modules).
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Save
if ( isset( $_POST['aaa_oc_pc_settings_submit'] ) && check_admin_referer( 'aaa_oc_pc_settings_nonce' ) ) {
	$mode = isset( $_POST['payconfirm_match_source'] ) ? sanitize_key( $_POST['payconfirm_match_source'] ) : 'posts';
	$mode = in_array( $mode, [ 'posts', 'order_index' ], true ) ? $mode : 'posts';
	(function_exists('aaa_oc_set_option')
		? aaa_oc_set_option( 'payconfirm_match_source', $mode, 'modules' )
		: update_option( 'payconfirm_match_source', $mode )
	);
	echo '<div class="notice notice-success"><p>PayConfirm settings saved.</p></div>';
}

// Load
$get_opt = function( $key, $def = '' ) {
	if ( function_exists( 'aaa_oc_get_option' ) ) return aaa_oc_get_option( $key, 'modules', $def );
	$v = get_option( $key, $def ); return is_scalar( $v ) ? $v : $def;
};
$mode = (string) $get_opt( 'payconfirm_match_source', 'posts' );
?>
<div class="wrap">
	<h2>PayConfirm Settings</h2>
	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_pc_settings_nonce' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Match Source</th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="radio" name="payconfirm_match_source" value="posts" <?php checked( $mode, 'posts' ); ?> />
						<span>Posts &amp; Postmeta (classic)</span>
					</label>
					<label style="display:block;">
						<input type="radio" name="payconfirm_match_source" value="order_index" <?php checked( $mode, 'order_index' ); ?> />
						<span>Order Index (fast path)</span>
					</label>
					<p class="description">
						In <strong>Order Index</strong> mode, candidate collection &amp; scoring read from
						<code>aaa_oc_order_index</code> (e.g., <code>pc_aliases</code>, <code>pc_alias_snapshot_ts</code>, totals).
						Debug logging is controlled in WFCP.
					</p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary" name="aaa_oc_pc_settings_submit" value="1">Save Settings</button>
		</p>
	</form>
</div>
