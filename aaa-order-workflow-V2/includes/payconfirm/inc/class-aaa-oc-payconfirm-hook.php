<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/class-aaa-oc-payconfirm-hook.php
 * Purpose: Listen to payment-confirmation saves; parse OR respect edited pc_*, retitle, try match, update index; then publish if draft.
 * Version: 1.0.4
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PayConfirm_Hook {
	public static function init(){ add_action('save_post', [__CLASS__,'on_save'], 20, 3); }

	public static function on_save( $post_id, $post, $update ) {
		if ( wp_is_post_revision($post_id) || $post->post_type !== 'payment-confirmation' ) return;
		if ( ! current_user_can('edit_post', $post_id) ) return;

		$subject = (string)$post->post_title;
		$content = (string)$post->post_content;
		$looks_payment = ( stripos($subject.$content,'zelle')!==false ) || ( stripos($subject,'payment')!==false );
		if ( ! $looks_payment ) return;

		// NEW: if the editor posted pc_* fields, trust those; otherwise parse the email.
		$manual = isset($_POST['pc_payment_method']) || isset($_POST['pc_account_name']) ||
		          isset($_POST['pc_amount']) || isset($_POST['pc_sent_on']) ||
		          isset($_POST['pc_txn']) || isset($_POST['pc_memo']);

		if ( $manual ) {
			$f = [
				'payment_method'     => sanitize_text_field( $_POST['pc_payment_method'] ?? '' ),
				'account_name'       => sanitize_text_field( $_POST['pc_account_name']  ?? '' ),
				'amount'             => (float) ( $_POST['pc_amount'] ?? 0 ),
				'sent_on'            => trim( (string) ( $_POST['pc_sent_on'] ?? '' ) ),
				'transaction_number' => trim( (string) ( $_POST['pc_txn'] ?? '' ) ),
				'memo'               => sanitize_text_field( $_POST['pc_memo'] ?? '' ),
			];
		} else {
			$f = AAA_OC_PayConfirm_Parser::parse( $content, $subject );
		}

		// Always normalize sent_on to the original email time (Postie post_date_gmt) if available.
		$pd_gmt = get_post_field( 'post_date_gmt', $post_id );
		if ( $pd_gmt ) { $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) ); }

		// Save metas
		update_post_meta($post_id, '_pc_payment_method', $f['payment_method']);
		update_post_meta($post_id, '_pc_account_name',   $f['account_name']);
		update_post_meta($post_id, '_pc_amount',         $f['amount']);
		update_post_meta($post_id, '_pc_sent_on',        $f['sent_on']);
		update_post_meta($post_id, '_pc_txn',            $f['transaction_number']);
		update_post_meta($post_id, '_pc_memo',           $f['memo']);

		// Retitle
		$new_title = AAA_OC_PayConfirm_Parser::title($f);
		remove_action('save_post', [__CLASS__,'on_save'], 20);
		wp_update_post(['ID'=>$post_id,'post_title'=>$new_title]);
		add_action('save_post', [__CLASS__,'on_save'], 20, 3);

		// Attempt match
		$result = AAA_OC_PayConfirm_Matcher::attempt($post_id, $f);
		update_post_meta($post_id, '_pc_last_match_result', $result);

		// Normalize status/reason/confidence
		$matched    = ! empty($result['matched']);
		$candidates = isset($result['candidates']) && is_array($result['candidates']) && count($result['candidates'])>0;
		$status     = $matched ? 'matched' : ( $candidates ? 'partial' : 'unmatched' );
		$reason     = isset($result['method']) ? (string)$result['method'] : ( $candidates ? 'amount_multi' : 'name_fuzzy' );
		$confidence = isset($result['confidence']) ? (float)$result['confidence'] : ( $matched ? 1.0 : ( $candidates ? 0.6 : 0.4 ) );

		update_post_meta($post_id, '_pc_match_status',     $status);
		update_post_meta($post_id, '_pc_match_reason',     $reason);
		update_post_meta($post_id, '_pc_match_confidence', $confidence);
		if ( $matched && ! empty($result['order_id']) ) {
			update_post_meta($post_id, '_pc_matched_order_id', (int)$result['order_id']);
		}
		update_post_meta($post_id, '_pc_match_method', $reason); // back-compat mirror

		// Auto-publish after processing if currently draft
		if ( $post->post_status === 'draft' ) {
			remove_action('save_post', [__CLASS__,'on_save'], 20);
			wp_update_post(['ID'=>$post_id,'post_status'=>'publish']);
			add_action('save_post', [__CLASS__,'on_save'], 20, 3);
		}

		if ( defined('AAA_OC_PAYCONFIRM_DEBUG') && AAA_OC_PAYCONFIRM_DEBUG ) {
			error_log('[PayConfirm] save status='.$status.' reason='.$reason.' conf='.$confidence);
		}
	}
}
