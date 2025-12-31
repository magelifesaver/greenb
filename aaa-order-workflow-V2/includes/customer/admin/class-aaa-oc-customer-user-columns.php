<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/admin/class-aaa-oc-customer-user-columns.php
 * Purpose: Add “Warnings” and “Special Needs” columns to Users list (admin), reading authoritative user meta.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( class_exists( 'AAA_OC_Customer_User_Columns' ) ) { return; }

final class AAA_OC_Customer_User_Columns {

	const DEBUG_THIS_FILE = true;

	// Same keys used by profile fields
	const META_NEEDS       = '_aaa_oc_special_needs';     // array of labels
	const META_WARN_REASON = '_aaa_oc_warning_reason';    // string
	const META_WARN_BAN    = '_aaa_oc_warning_is_ban';    // 'yes'|'no'
	const META_BAN_UNTIL   = '_aaa_oc_warning_ban_until'; // int

	public static function init() : void {
		add_filter( 'manage_users_columns',       [ __CLASS__, 'add_columns' ] );
		add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_column' ], 10, 3 );
	}

	public static function add_columns( array $cols ) : array {
		$out = [];
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( $k === 'email' ) {
				$out['aaa_oc_warn']  = __( 'Warnings', 'aaa-oc' );
				$out['aaa_oc_needs'] = __( 'Special Needs', 'aaa-oc' );
			}
		}
		// If “email” wasn’t present, append to end.
		if ( ! isset( $out['aaa_oc_warn'] ) ) {
			$out['aaa_oc_warn']  = __( 'Warnings', 'aaa-oc' );
			$out['aaa_oc_needs'] = __( 'Special Needs', 'aaa-oc' );
		}
		return $out;
	}

	public static function render_column( $value, string $column_name, int $user_id ) {
		if ( 'aaa_oc_warn' === $column_name ) {
			$reason   = (string) get_user_meta( $user_id, self::META_WARN_REASON, true );
			$is_ban   = (string) get_user_meta( $user_id, self::META_WARN_BAN, true );
			$ban_until= (int)    get_user_meta( $user_id, self::META_BAN_UNTIL, true );

			if ( $reason === '' && $is_ban !== 'yes' ) {
				return '<span style="color:#888;">—</span>';
			}
			$parts = [];
			if ( $reason !== '' ) {
				$parts[] = esc_html( self::shorten( $reason, 52 ) );
			}
			if ( $is_ban === 'yes' ) {
				$label = 'Banned';
				if ( $ban_until > time() ) {
					$label .= ' until ' . esc_html( date_i18n( get_option('date_format'), $ban_until ) );
				}
				$parts[] = '<strong style="color:#cc0000;">' . esc_html( $label ) . '</strong>';
			}
			return implode( ' • ', $parts );
		}

		if ( 'aaa_oc_needs' === $column_name ) {
			$needs = (array) get_user_meta( $user_id, self::META_NEEDS, true );
			if ( empty( $needs ) ) {
				return '<span style="color:#888;">—</span>';
			}
			$total = count( $needs );
			$shown = array_slice( $needs, 0, 2 );
			$html  = esc_html( implode( ', ', $shown ) );
			if ( $total > 2 ) { $html .= ' <span style="color:#555;">+'.(int)($total-2).'</span>'; }
			return $html;
		}

		return $value;
	}

	private static function shorten( string $s, int $max ) : string {
		$s = trim( $s );
		return ( mb_strlen( $s ) <= $max ) ? $s : ( mb_substr( $s, 0, $max - 1 ) . '…' );
	}
}

AAA_OC_Customer_User_Columns::init();
