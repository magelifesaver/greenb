<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/parser/class-aaa-oc-payconfirm-parser.php
 * Purpose: Universal parser router (Zelle + Venmo only). Detect → delegate.
 * Version: 2.0.1
 */
if ( ! defined('ABSPATH') ) { exit; }

require_once __DIR__ . '/helpers/class-aaa-oc-payconfirm-parse-zelle.php';
require_once __DIR__ . '/helpers/class-aaa-oc-payconfirm-parse-venmo.php';

class AAA_OC_PayConfirm_Parser {

	public static function parse( $raw_html, $subject = '' ) {
		$html  = (string) $raw_html;
		$plain = wp_strip_all_tags( $html );

		$method = self::detect_method( $plain, (string)$subject );
		switch ( $method ) {
			case 'Venmo':
				$data = AAA_OC_PayConfirm_Parse_Venmo::parse( $html, $plain, $subject );
				break;
			case 'Zelle':
			default:
				$data = AAA_OC_PayConfirm_Parse_Zelle::parse( $html, $plain, $subject );
		}

		$data += [
			'payment_method'     => $method ?: 'Zelle',
			'account_name'       => '',
			'amount'             => '',
			'sent_on'            => '',
			'transaction_number' => '',
			'memo'               => '',
		];

		if ( defined('AAA_OC_PAYCONFIRM_DEBUG') && AAA_OC_PAYCONFIRM_DEBUG ) {
			error_log('[PayConfirm][PARSE] method=' . $data['payment_method'] . ' fields=' . wp_json_encode( $data ) );
		}
		return $data;
	}

	public static function title( $f ) {
		$pm = $f['payment_method'] ?: 'Payment';
		$amt = is_numeric($f['amount']) ? '$'.number_format((float)$f['amount'],2) : '$0.00';
		$name = $f['account_name'] ?: 'Unknown';
		return sprintf('%s - %s Paid by %s', $pm, $amt, $name);
	}

	private static function detect_method( $txt, $subj = '' ) {
		$hay = strtolower( $txt . ' ' . $subj );
		if ( strpos($hay,'venmo') !== false ) return 'Venmo';
		// default to Zelle if we see it, otherwise Zelle parser works fine for tabular banks
		if ( strpos($hay,'zelle') !== false ) return 'Zelle';
		return 'Zelle';
	}
}
