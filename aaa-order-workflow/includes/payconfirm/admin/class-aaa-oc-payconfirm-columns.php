<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/admin/class-aaa-oc-payconfirm-columns.php
 * Purpose: Add list table columns for Order, Status, Reason, Amount.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_PayConfirm_Columns {
	public static function init() {
		add_filter( 'manage_edit-payment-confirmation_columns', [ __CLASS__, 'cols' ] );
		add_action( 'manage_payment-confirmation_posts_custom_column', [ __CLASS__, 'coldata' ], 10, 2 );
	}

	public static function cols( $cols ) {
		$cols['pc_amount'] = 'Amount';
		$cols['pc_status'] = 'Match Status';
		$cols['pc_reason'] = 'Reason';
		$cols['pc_order']  = 'Order';
		return $cols;
	}

	public static function coldata( $col, $post_id ) {
		if ( $col === 'pc_amount' ) {
			$amt = get_post_meta( $post_id, '_pc_amount', true );
			echo $amt !== '' ? esc_html( number_format( (float) $amt, 2 ) ) : '—';
		}
		if ( $col === 'pc_status' ) {
			echo esc_html( get_post_meta( $post_id, '_pc_match_status', true ) ?: '—' );
		}
		if ( $col === 'pc_reason' ) {
			echo esc_html( get_post_meta( $post_id, '_pc_match_reason', true ) ?: '—' );
		}
		if ( $col === 'pc_order' ) {
			$oid = (int) get_post_meta( $post_id, '_pc_matched_order_id', true );
			if ( $oid ) {
				$url = admin_url( 'post.php?post=' . $oid . '&action=edit' );
				printf( '<a href="%s">#%d</a>', esc_url( $url ), $oid );
			} else {
				echo '—';
			}
		}
	}
}
