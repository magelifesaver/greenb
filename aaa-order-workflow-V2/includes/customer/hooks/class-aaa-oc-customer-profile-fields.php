<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/hooks/class-aaa-oc-customer-profile-fields.php
 * Purpose: Add/edit Customer Special Needs (multi) + Warning (reason + optional ban) on WP User profile.
 *          Saves authoritative user meta that downstream indexer snapshots to orders + order index.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Customer_Profile_Fields' ) ) return;

final class AAA_OC_Customer_Profile_Fields {

	const DEBUG_THIS_FILE = true;

	// Authoritative USER meta keys
	const META_NEEDS       = '_aaa_oc_special_needs';         // array of labels (strings)
	const META_WARN_REASON = '_aaa_oc_warning_reason';        // string
	const META_WARN_BAN    = '_aaa_oc_warning_is_ban';        // 'yes'|'no'
	const META_BAN_UNTIL   = '_aaa_oc_warning_ban_until';     // int (unix ts) or 0

	public static function init() : void {
		add_action( 'show_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'save' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save' ] );
	}

	/** Load choices from Customer tab library (scope=customer) with sensible defaults */
	private static function get_needs_library() : array {
		$opt   = function_exists('aaa_oc_get_option') ? 'aaa_oc_get_option' : null;
		$scope = 'customer';
		$defaults = [
			['label'=>'Mobility Assistance'],['label'=>'Hearing Impaired'],['label'=>'Vision Impaired'],
			['label'=>'No Stairs'],['label'=>'Gate Code Required'],['label'=>'Service Animal'],
			['label'=>'Contactless Drop-off'],['label'=>'Call on Arrival'],['label'=>'Large Print Needed'],
			['label'=>'Language Assistance'],
		];
		$list = $opt ? (array) $opt('customer_needs_values', $scope, $defaults) : $defaults;
		$out = [];
		foreach ( $list as $row ) {
			$l = isset($row['label']) ? (string) $row['label'] : '';
			if ( $l !== '' ) $out[] = $l;
		}
		return array_values( array_unique( $out ) );
	}

	private static function ban_presets() : array {
		return [
			'none'      => 'None',
			'1_week'    => '1 Week',
			'1_month'   => '1 Month',
			'3_months'  => '3 Months',
		];
	}

	public static function render( WP_User $user ) : void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;

		$needs     = (array) get_user_meta( $user->ID, self::META_NEEDS, true );
		$warn_txt  = (string) get_user_meta( $user->ID, self::META_WARN_REASON, true );
		$is_ban    = (string) get_user_meta( $user->ID, self::META_WARN_BAN, true );
		$ban_until = (int)    get_user_meta( $user->ID, self::META_BAN_UNTIL, true );

		$choices = self::get_needs_library();
		$presets = self::ban_presets();

		wp_nonce_field( 'aaa_oc_customer_profile_save', 'aaa_oc_customer_profile_nonce' );

		echo '<h2>Customer — Warnings & Special Needs</h2><table class="form-table"><tbody>';

		// Special Needs (multi)
		echo '<tr><th><label>Special Needs</label></th><td>';
		if ( empty($choices) ) {
			echo '<em>No choices configured in the Customer tab.</em>';
		} else {
			foreach ( $choices as $label ) {
				$chk = in_array( $label, $needs, true ) ? 'checked' : '';
				echo '<label style="display:inline-block;margin:4px 14px 4px 0;">';
				echo '<input type="checkbox" name="aaa_oc_needs[]" value="' . esc_attr($label) . '" ' . $chk . '> ' . esc_html($label);
				echo '</label>';
			}
		}
		echo '<p class="description">Shown on the Board and driver printouts.</p></td></tr>';

		// Warning + ban
		echo '<tr><th><label for="aaa_oc_warn_reason">Warning</label></th><td>';
		echo '<textarea name="aaa_oc_warn_reason" id="aaa_oc_warn_reason" rows="3" class="large-text">'. esc_textarea($warn_txt) .'</textarea>';
		echo '<p class="description">Examples: No-show history; Request prepayment; No cash; Address risk…</p>';
		echo '<label style="display:block;margin-top:6px;"><input type="checkbox" name="aaa_oc_warn_is_ban" value="yes" '. checked($is_ban,'yes',false) .'> Mark customer as banned</label>';
		// Length
		$current = 'none';
		if ( $ban_until > time() ) {
			$diff = $ban_until - time();
			$weeks  = round( $diff / WEEK_IN_SECONDS );
			$months = round( $diff / (30 * DAY_IN_SECONDS) );
			if ( $weeks === 1 ) $current = '1_week';
			elseif ( $months === 1 ) $current = '1_month';
			elseif ( $months === 3 ) $current = '3_months';
		}
		echo '<div style="margin-top:6px;">Ban Length: <select name="aaa_oc_ban_length">';
		foreach ( self::ban_presets() as $k=>$lbl ) {
			echo '<option value="'. esc_attr($k) .'" '. selected($current,$k,false) .'>'. esc_html($lbl) .'</option>';
		}
		echo '</select></div>';
		if ( $ban_until > 0 ) {
			echo '<p class="description">Current ban until: '. esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), $ban_until ) ) .'</p>';
		}
		echo '</td></tr>';

		echo '</tbody></table>';
	}

	public static function save( int $user_id ) : void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		if ( empty($_POST['aaa_oc_customer_profile_nonce']) || ! wp_verify_nonce($_POST['aaa_oc_customer_profile_nonce'],'aaa_oc_customer_profile_save') ) return;

		$needs_in = isset($_POST['aaa_oc_needs']) ? (array) $_POST['aaa_oc_needs'] : [];
		$needs = [];
		foreach ( $needs_in as $label ) {
			$label = sanitize_text_field( (string) $label );
			if ( $label !== '' ) $needs[] = $label;
		}
		update_user_meta( $user_id, self::META_NEEDS, $needs );

		$warn  = sanitize_text_field( (string) ($_POST['aaa_oc_warn_reason'] ?? '') );
		update_user_meta( $user_id, self::META_WARN_REASON, $warn );

		$is_ban = ( isset($_POST['aaa_oc_warn_is_ban']) && $_POST['aaa_oc_warn_is_ban'] === 'yes' ) ? 'yes' : 'no';
		update_user_meta( $user_id, self::META_WARN_BAN, $is_ban );

		$len = (string) ($_POST['aaa_oc_ban_length'] ?? 'none');
		$until = 0;
		if ( $is_ban === 'yes' ) {
			switch ( $len ) {
				case '1_week':   $until = time() + WEEK_IN_SECONDS; break;
				case '1_month':  $until = time() + 30 * DAY_IN_SECONDS; break;
				case '3_months': $until = time() + 90 * DAY_IN_SECONDS; break;
			}
		}
		update_user_meta( $user_id, self::META_BAN_UNTIL, (int) $until );

		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][PROFILE] saved uid=' . sanitize_text_field((string)$user_id) . ' needs=' . count($needs) . ' warn=' . ($warn!==''?'yes':'no') . ' ban=' . $is_ban);
		}
	}
}
AAA_OC_Customer_Profile_Fields::init();
