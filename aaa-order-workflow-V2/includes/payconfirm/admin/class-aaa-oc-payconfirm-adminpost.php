<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/admin/class-aaa-oc-payconfirm-adminpost.php
 * Purpose: Handle "Save & Re-run Match" + "Force" from the post editor; verbose logs.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_PayConfirm_AdminPost {
	public static function init() {
		add_action( 'admin_post_aaa_oc_payconfirm_update_and_match', [ __CLASS__, 'handle' ] );
		add_action( 'admin_post_aaa_oc_payconfirm_force',            [ __CLASS__, 'force' ] );
	}

	/** POST: save edited fields and re-run the matcher */
	public static function handle() {
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) wp_die( 'No permission' );
		if ( ! isset( $_POST['aaa_oc_pc_nonce'] ) || ! wp_verify_nonce( $_POST['aaa_oc_pc_nonce'], 'aaa_oc_pc_save' ) ) wp_die( 'Nonce check failed' );

		$f = [
			'payment_method'     => sanitize_text_field( $_POST['pc_payment_method'] ?? '' ),
			'account_name'       => sanitize_text_field( $_POST['pc_account_name'] ?? '' ),
			'amount'             => (float) ( $_POST['pc_amount'] ?? 0 ),
			'sent_on'            => trim( (string) ( $_POST['pc_sent_on'] ?? '' ) ),
			'transaction_number' => trim( (string) ( $_POST['pc_txn'] ?? '' ) ),
			'memo'               => sanitize_text_field( $_POST['pc_memo'] ?? '' ),
		];

		// persist
		update_post_meta( $post_id, '_pc_payment_method', $f['payment_method'] );
		update_post_meta( $post_id, '_pc_account_name',   $f['account_name']   );
		update_post_meta( $post_id, '_pc_amount',         $f['amount']         );
		update_post_meta( $post_id, '_pc_sent_on',        $f['sent_on']        );
		update_post_meta( $post_id, '_pc_txn',            $f['transaction_number'] );
		update_post_meta( $post_id, '_pc_memo',           $f['memo']           );

		// tidy title
		wp_update_post( [ 'ID' => $post_id, 'post_title' => AAA_OC_PayConfirm_Parser::title( $f ) ] );

		// Use original email time for matching
		$pd_gmt = get_post_field( 'post_date_gmt', $post_id );
		if ( $pd_gmt ) {
			$f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
			update_post_meta( $post_id, '_pc_sent_on', $f['sent_on'] );
		}

		if ( defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) && AAA_OC_PAYCONFIRM_DEBUG ) {
			error_log( '[PayConfirm][ADMIN] Re-run start post=' . $post_id . ' f=' . wp_json_encode( $f ) );
		}

		$result = AAA_OC_PayConfirm_Matcher::attempt( $post_id, $f );
		update_post_meta( $post_id, '_pc_last_match_result', $result );

		$matched    = ! empty( $result['matched'] );
		$candidates = isset( $result['candidates'] ) && is_array( $result['candidates'] ) && count( $result['candidates'] ) > 0;
		$status     = $matched ? 'matched' : ( $candidates ? 'partial' : 'unmatched' );
		$reason     = isset( $result['method'] ) ? (string) $result['method'] : ( $candidates ? 'amount_multi' : 'name_fuzzy' );
		$confidence = isset( $result['confidence'] ) ? (float) $result['confidence'] : ( $matched ? 1.0 : ( $candidates ? 0.6 : 0.4 ) );

		update_post_meta( $post_id, '_pc_match_status',     $status     );
		update_post_meta( $post_id, '_pc_match_reason',     $reason     );
		update_post_meta( $post_id, '_pc_match_confidence', $confidence );
		if ( $matched && ! empty( $result['order_id'] ) ) {
			update_post_meta( $post_id, '_pc_matched_order_id', (int) $result['order_id'] );
		}
		update_post_meta( $post_id, '_pc_match_method', $reason );

		// Upsert inbox row with latest status
		if ( method_exists( 'AAA_OC_PC_Apply', 'upsert_pc_inbox' ) ) {
			AAA_OC_PC_Apply::upsert_pc_inbox( $post_id, $f, $status, $matched ? (int) $result['order_id'] : null, $confidence );
		}

		$p = get_post( $post_id );
		if ( $p && $p->post_status === 'draft' ) {
			wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
		}

		if ( defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) && AAA_OC_PAYCONFIRM_DEBUG ) {
			error_log( '[PayConfirm][ADMIN] Re-run result=' . wp_json_encode( $result ) . ' status=' . $status . ' reason=' . $reason . ' conf=' . $confidence );
		}

		wp_safe_redirect( add_query_arg( [ 'pc_updated' => 1 ], wp_get_referer() ?: admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) );
		exit;
	}

	/** GET: force a specific candidate order */
	public static function force() {
		$post_id  = isset($_GET['post_id'])  ? (int) $_GET['post_id']  : 0;
		$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

		if ( ! $post_id || ! $order_id )                wp_die('Missing parameters');
		if ( ! current_user_can('edit_post', $post_id) ) wp_die('No permission');
		if ( ! wp_verify_nonce($_GET['_wpnonce'] ?? '', 'aaa_oc_pc_force_' . $post_id) ) wp_die('Bad nonce');

		$f = [
			'payment_method'     => (string) get_post_meta($post_id, '_pc_payment_method', true),
			'account_name'       => (string) get_post_meta($post_id, '_pc_account_name',   true),
			'amount'             => (float)  get_post_meta($post_id, '_pc_amount',         true),
			'sent_on'            => (string) get_post_meta($post_id, '_pc_sent_on',        true),
			'transaction_number' => (string) get_post_meta($post_id, '_pc_txn',            true),
			'memo'               => (string) get_post_meta($post_id, '_pc_memo',           true),
		];

		$pd_gmt = get_post_field('post_date_gmt', $post_id);
		if ($pd_gmt) {
			$f['sent_on'] = gmdate('Y-m-d H:i:s', strtotime($pd_gmt));
			update_post_meta($post_id, '_pc_sent_on', $f['sent_on']);
		}

		$res = AAA_OC_PC_Apply::apply($order_id, $post_id, $f, 1.0, 'forced');

		// Metas reflect forced state
		update_post_meta($post_id, '_pc_last_match_result', $res);
		update_post_meta($post_id, '_pc_match_status',      'matched');
		update_post_meta($post_id, '_pc_match_reason',      'forced');
		update_post_meta($post_id, '_pc_match_confidence',  1.0);
		update_post_meta($post_id, '_pc_matched_order_id',  (int) $order_id);
		update_post_meta($post_id, '_pc_match_method',      'forced');

		// Upsert inbox row as matched (apply() also does this, but this keeps it explicit)
		if ( method_exists( 'AAA_OC_PC_Apply', 'upsert_pc_inbox' ) ) {
			AAA_OC_PC_Apply::upsert_pc_inbox( $post_id, $f, 'matched', (int) $order_id, 1.0 );
		}

		$p = get_post($post_id);
		if ($p && $p->post_status === 'draft') {
			wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
		}

		wp_safe_redirect( add_query_arg(['pc_forced' => $order_id], wp_get_referer() ?: admin_url('post.php?post=' . $post_id . '&action=edit')) );
		exit;
	}
}
