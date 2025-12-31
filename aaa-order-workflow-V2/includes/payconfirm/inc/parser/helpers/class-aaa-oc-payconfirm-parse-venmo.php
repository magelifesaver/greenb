<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/parsers/class-aaa-oc-payconfirm-parse-venmo.php
 * Purpose: Parse Venmo emails (amount, payer, memo, txn, date).
 * Version: 1.0.0
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PayConfirm_Parse_Venmo {
	public static function parse( $html, $plain, $subject = '' ) {
		// Amount
		$amt = '';
		if ( preg_match('/\$\s*([0-9][0-9,]*(?:\.[0-9]{2})?)/', $plain, $m ) ) {
			$amt = $m[1];
		} elseif ( preg_match('/amount-container__amount-text[^>]*>\s*([0-9]+)\s*<\/div>.*?amount-container__text-high[^>]*>\s*0?([0-9]{2})\s*<\/div>/is', $html, $m ) ) {
			$amt = $m[1].'.'.$m[2];
		}

		// Payer name
		$name = '';
		if ( preg_match('/([A-Za-z][A-Za-z\'\-\.\s]+)\s+paid you/i', $plain, $m ) ) $name = trim($m[1]);

		// Memo (transaction note)
		$memo = '';
		if ( preg_match('/class="transaction-note[^"]*">([^<]+)/i', $html, $m ) ) $memo = trim($m[1]);

		// Transaction ID
		$txn = '';
		if ( preg_match('/Transaction\s*ID<\/h3>\s*<p[^>]*>([0-9]+)<\/p>/i', $html, $m ) ) $txn = $m[1];

		// Date
		$sent = '';
		if ( preg_match('/Date<\/h3>\s*<p[^>]*>([^<]+)<\/p>/i', $html, $m ) ) $sent = trim($m[1]);

		return [
			'payment_method'     => 'Venmo',
			'account_name'       => $name,
			'amount'             => self::to_float($amt),
			'sent_on'            => self::date_to_mysql($sent),
			'transaction_number' => $txn,
			'memo'               => $memo,
		];
	}

	private static function date_to_mysql($h){ $h=trim((string)$h); if($h==='') return ''; $ts=strtotime($h); return $ts?gmdate('Y-m-d H:i:s',$ts):''; }
	private static function to_float($v){ $x=str_replace([',','$',' '],'',(string)$v); return is_numeric($x)?(float)$x:''; }
}
