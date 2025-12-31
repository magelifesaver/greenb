<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/hooks/class-aaa-oc-payment-card-hooks.php
 * Purpose: Add the Payment button + full modal (self-contained; no includes) into the Board Actions area.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Payment_Card_Hooks' ) ) :

final class AAA_OC_Payment_Card_Hooks {

	public static function init() : void {
		add_action( 'aaa_oc_board_action_buttons', [ __CLASS__, 'render' ], 15, 1 );
	}

	public static function render( array $ctx ) : void {
		$order_id = isset( $ctx['order_id'] ) ? (int) $ctx['order_id'] : 0;
		if ( $order_id <= 0 ) return;

		// Data (use helper if present)
		$pd = [
			'aaa_oc_order_total'=>'0.00','aaa_oc_zelle_amount'=>'0.00','aaa_oc_venmo_amount'=>'0.00',
			'aaa_oc_applepay_amount'=>'0.00','aaa_oc_creditcard_amount'=>'0.00','aaa_oc_cashapp_amount'=>'0.00',
			'aaa_oc_cash_amount'=>'0.00','aaa_oc_epayment_total'=>'0.00','aaa_oc_payrec_total'=>'0.00',
			'aaa_oc_tip_total'=>'0.00','epayment_tip'=>'0.00','total_order_tip'=>'0.00',
			'aaa_oc_order_balance'=>'0.00','aaa_oc_payment_status'=>'unpaid','cleared'=>0,'envelope_outstanding'=>0,
		];
		if ( class_exists( 'AAA_OC_Payment_Fields' ) && method_exists( 'AAA_OC_Payment_Fields', 'get_payment_fields' ) ) {
			$got = (array) AAA_OC_Payment_Fields::get_payment_fields( $order_id );
			$pd  = array_merge( $pd, $got );
		}

		// Trigger button (same style as others)
		echo '<div style="margin-top:6px; display:inline-block;">';
		echo '  <button type="button" class="button-modern open-payment-modal" data-order-id="' . esc_attr($order_id) . '">Add <br>Payment</button>';
		echo '</div>';

		// Modal wrapper — IMPORTANT: matches existing CSS + JS expectations
		$oid = esc_attr($order_id);
		$sel_unpaid  = selected($pd['aaa_oc_payment_status'],'unpaid',false);
		$sel_partial = selected($pd['aaa_oc_payment_status'],'partial',false);
		$sel_paid    = selected($pd['aaa_oc_payment_status'],'paid',false);
		$chk_cleared = checked(!empty($pd['cleared']),true,false);
		$chk_env     = checked(!empty($pd['envelope_outstanding']),true,false);

		// Wrapper has BOTH classes so:
		//  - your CSS (.aaa-payment-modal, .aaa-payment-modal-overlay) applies
		//  - your JS finds it with .closest('.aaa-payment-modal') for close()
		echo '<div id="aaa-payment-modal-' . $oid . '" class="aaa-payment-modal aaa-payment-modal-overlay" aria-hidden="true" style="display:none;">';
		echo ' <div class="aaa-payment-modal-inner" role="dialog" aria-modal="true">';
		echo '  <button class="close-payment-modal" type="button" aria-label="Close">&times;</button>';
		echo '  <div class="aaa-payment-modal-content">';
		echo '   <div class="aaa-payment-fields" data-order-id="' . $oid . '">';
		echo '    <h3 style="display:flex;justify-content:space-between;align-items:center;">';
		echo '      <span>Payment Information</span>';
		echo '      <span style="font-weight:bold;">Order Total: $' . esc_html(number_format((float)$pd['aaa_oc_order_total'],2)) . '</span>';
		echo '    </h3>';
		echo '    <input type="hidden" name="aaa_oc_order_total" value="' . esc_attr($pd['aaa_oc_order_total']) . '">';

		echo self::num_row('Zelle','aaa_oc_zelle_amount',$pd);
		echo self::num_row('Venmo','aaa_oc_venmo_amount',$pd);
		echo self::num_row('Apple Pay','aaa_oc_applepay_amount',$pd);
		echo self::num_row('Credit Card','aaa_oc_creditcard_amount',$pd);
		echo self::num_row('CashApp','aaa_oc_cashapp_amount',$pd);
		echo self::total_row('ePayment Total','aaa_oc_epayment_total',$pd);
		echo self::num_row('Cash','aaa_oc_cash_amount',$pd);
		echo self::total_row('Payment Total','aaa_oc_payrec_total',$pd);
		echo self::num_row('Web Tip','aaa_oc_tip_total',$pd,true);
		echo self::num_row('ePayment Tip','epayment_tip',$pd,true);
		echo self::total_row('Total Driver Tip','total_order_tip',$pd);

		echo '    <div class="payment-row status-row">';
		echo '      <label>Order Balance:</label>';
		echo '      <input type="number" step="0.01" name="aaa_oc_order_balance" value="' . esc_attr($pd['aaa_oc_order_balance']) . '" readonly>';
		echo '      <label>Payment Status:</label>';
		echo '      <select name="aaa_oc_payment_status">';
		echo '        <option value="unpaid" ' . $sel_unpaid . '>Unpaid</option>';
		echo '        <option value="partial" ' . $sel_partial . '>Partial</option>';
		echo '        <option value="paid" ' . $sel_paid . '>Paid</option>';
		echo '      </select>';
		echo '    </div>';

		echo '    <div class="payment-row">';
		echo '      <label><input type="checkbox" name="cleared" value="1" ' . $chk_cleared . '> Payment verified</label>';
		echo '      <label style="margin-left:12px;"><input type="checkbox" name="envelope_outstanding" value="1" ' . $chk_env . '> Envelope Outstanding</label>';
		echo '    </div>';

		echo '    <button class="button button-modern save-payment-button" data-order-id="' . $oid . '">Save Payment</button>';

		echo '   </div>'; // .aaa-payment-fields
		echo '  </div>';  // .aaa-payment-modal-content
		echo ' </div>';   // .aaa-payment-modal-inner
		echo '</div>';    // wrapper
	}

	private static function num_row( $label, $name, $pd, $readonly=false ) : string {
		$val = isset($pd[$name]) ? (string)$pd[$name] : '0.00';
		$ro  = $readonly ? ' readonly' : '';
		return '<div class="payment-row"><label>' . esc_html($label) . ':</label>'
		     . '<input type="number" step="0.01" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '"' . $ro . '></div>';
	}
	private static function total_row( $label, $hidden_name, $pd ) : string {
		$val = isset($pd[$hidden_name]) ? (float)$pd[$hidden_name] : 0.00;
		return '<div class="payment-row total-row">'
		     . '<label>' . esc_html($label) . ':</label>'
		     . '<div class="total-display">' . esc_html(number_format($val,2)) . '</div>'
		     . '<input type="hidden" name="' . esc_attr($hidden_name) . '" value="' . esc_attr(number_format($val,2,'.','')) . '">'
		     . '</div>';
	}
}

AAA_OC_Payment_Card_Hooks::init();

endif;
