<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/admin/class-aaa-oc-customer-profile-fields.php
 * Purpose: Add editable Customer fields (Special Needs + Warnings) to WP User profile and save to user meta.
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Customer_Profile_Fields' ) ) return;

final class AAA_OC_Customer_Profile_Fields {

	const DEBUG_THIS_FILE = true;

	const META_NEEDS       = '_aaa_oc_special_needs';
	const META_WARN_REASON = '_aaa_oc_warning_reason';
	const META_WARN_BAN    = '_aaa_oc_warning_is_ban';
	const META_BAN_UNTIL   = '_aaa_oc_warning_ban_until';

	public static function init() : void {
		add_action( 'show_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'save' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save' ] );
	}

	private static function get_list( string $opt_key, array $fallback ) : array {
		$fn = function_exists('aaa_oc_get_option') ? 'aaa_oc_get_option' : null;
		$rows = $fn ? (array) $fn( $opt_key, 'customer', $fallback ) : $fallback;
		$out = [];
		foreach ( $rows as $r ) { $lbl = isset($r['label']) ? trim((string)$r['label']) : ''; if ($lbl!=='') $out[]=$lbl; }
		return array_values(array_unique($out));
	}

	private static function needs_defaults(): array {
		return [
			['label'=>'Mobility Assistance'],['label'=>'Hearing Impaired'],['label'=>'Vision Impaired'],
			['label'=>'No Stairs'],['label'=>'Gate Code Required'],['label'=>'Service Animal'],
			['label'=>'Contactless Drop-off'],['label'=>'Call on Arrival'],['label'=>'Large Print Needed'],
			['label'=>'Language Assistance'],
		];
	}
	private static function warn_defaults(): array {
		return [
			['label'=>'Do Not Serve'],['label'=>'ID Expired'],['label'=>'Previous Chargeback'],['label'=>'Address Risk'],
			['label'=>'High Fraud Risk'],['label'=>'Must Verify ID'],['label'=>'Do Not Leave at Door'],['label'=>'Cash Only'],
			['label'=>'Manager Approval'],['label'=>'Account Flagged'],
		];
	}

	private static function ban_presets() : array {
		return [ 'none'=>'None', '1_week'=>'1 Week', '1_month'=>'1 Month', '3_months'=>'3 Months' ];
	}

	public static function render( WP_User $user ) : void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;

		$needs      = (array) get_user_meta( $user->ID, self::META_NEEDS, true );
		$warn_text  = (string) get_user_meta( $user->ID, self::META_WARN_REASON, true );
		$is_ban     = (string) get_user_meta( $user->ID, self::META_WARN_BAN, true );
		$ban_until  = (int)    get_user_meta( $user->ID, self::META_BAN_UNTIL, true );

		$needs_lib  = self::get_list('customer_needs_values',   self::needs_defaults());
		$warn_lib   = self::get_list('customer_warning_values', self::warn_defaults());
		$presets    = self::ban_presets();

		wp_nonce_field( 'aaa_oc_customer_profile_save', 'aaa_oc_customer_profile_nonce' );

		echo '<h2>Customer — Warnings & Special Needs</h2><table class="form-table" role="presentation"><tbody>';

		echo '<tr><th><label>Special Needs</label></th><td>';
		if ( empty($needs_lib) ) { echo '<em>No choices configured in Customer tab.</em>'; }
		else {
			foreach ( $needs_lib as $label ) {
				$chk = in_array( $label, $needs, true ) ? 'checked' : '';
				echo '<label style="display:inline-block;margin:4px 14px 4px 0;">';
				echo '<input type="checkbox" name="aaa_oc_needs[]" value="' . esc_attr($label) . '" ' . $chk . '> ' . esc_html($label);
				echo '</label>';
			}
		}
		echo '<p class="description">Shown on the Board and driver printouts.</p></td></tr>';

		echo '<tr><th><label>Warnings</label></th><td>';
		if ( empty($warn_lib) ) { echo '<em>No warning options configured in Customer tab.</em>'; }
		else {
			// Show options as checkboxes + a free note; we compose to a single text on save
			foreach ( $warn_lib as $label ) {
				$checked = ( $warn_text && stripos($warn_text, $label) !== false ) ? 'checked' : '';
				echo '<label style="display:inline-block;margin:4px 14px 4px 0;">';
				echo '<input type="checkbox" name="aaa_oc_warn_opts[]" value="' . esc_attr($label) . '" '.$checked.'> ' . esc_html($label);
				echo '</label>';
			}
		}
		echo '<div style="margin-top:8px;"><label>Warning note</label>';
		echo '<textarea name="aaa_oc_warn_note" rows="2" class="large-text" placeholder="Optional note (e.g., No-show x2; Request prepayment)">'. esc_textarea($warn_text) .'</textarea></div>';
		echo '<label style="display:block;margin-top:6px;"><input type="checkbox" name="aaa_oc_warn_is_ban" value="yes" '. checked($is_ban,'yes',false) .'> Ban customer</label>';

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
		foreach ( $presets as $k=>$lbl ) echo '<option value="'.esc_attr($k).'" '.selected($current,$k,false).'>'.esc_html($lbl).'</option>';
		echo '</select></div>';

		if ( $ban_until > 0 ) {
			echo '<p class="description">Current ban until: ' . esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), $ban_until ) ) . '</p>';
		}
		echo '</td></tr>';

		echo '</tbody></table>';
	}

	public static function save( int $user_id ) : void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		if ( empty($_POST['aaa_oc_customer_profile_nonce']) || ! wp_verify_nonce($_POST['aaa_oc_customer_profile_nonce'],'aaa_oc_customer_profile_save') ) return;

		$needs_in = isset($_POST['aaa_oc_needs']) ? (array) $_POST['aaa_oc_needs'] : [];
		$needs = [];
		foreach ( $needs_in as $label ) { $label = sanitize_text_field( (string) $label ); if ( $label !== '' ) $needs[] = $label; }
		update_user_meta( $user_id, self::META_NEEDS, $needs );

		$warn_labels = isset($_POST['aaa_oc_warn_opts']) ? (array) $_POST['aaa_oc_warn_opts'] : [];
		$warn_labels = array_filter( array_map( 'sanitize_text_field', $warn_labels ) );
		$warn_note   = sanitize_text_field( (string) ( $_POST['aaa_oc_warn_note'] ?? '' ) );
		$warn_text   = '';
		if ( $warn_labels ) $warn_text = implode('; ', $warn_labels);
		if ( $warn_note !== '' ) $warn_text = $warn_text ? ($warn_text . ' — ' . $warn_note) : $warn_note;

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
			$warn_text = trim( $warn_text . ( $warn_text ? ' ' : '' ) . '(Banned' . ( $until ? ' until ' . date_i18n( get_option('date_format'), $until ) : '' ) . ')' );
		}
		update_user_meta( $user_id, self::META_WARN_REASON, $warn_text );
		update_user_meta( $user_id, self::META_BAN_UNTIL, (int) $until );

		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][PROFILE] saved uid=' . sanitize_text_field((string)$user_id) . ' needs=' . count($needs) . ' warn=' . ($warn_text!==''?'1':'0') . ' ban=' . $is_ban);
		}
	}
}

AAA_OC_Customer_Profile_Fields::init();
