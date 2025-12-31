<?php
/**
 * File: /plugins/aaa-order-workflow/includes/payment/helpers/class-aaa-oc-payment-calc.php
 * Purpose: Centralized calculator for payment totals, balance, status, and real method.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Payment_Calc' ) ) :

class AAA_OC_Payment_Calc {

	private const DEBUG_THIS_FILE = true;

	/**
	 * Compute canonical payment fields from provided inputs.
	 *
	 * @param float $order_total
	 * @param array $amounts {
	 *   cash/zelle/venmo/applepay/cashapp/creditcard + tips (either key is okay):
	 *   'aaa_oc_cash_amount','aaa_oc_zelle_amount','aaa_oc_venmo_amount',
	 *   'aaa_oc_applepay_amount','aaa_oc_cashapp_amount','aaa_oc_creditcard_amount',
	 *   'aaa_oc_tip_total'  (preferred new key)
	 *   '_wpslash_tip'      (legacy fallback)
	 *   'epayment_tip'
	 * }
	 * @return array {
	 *   payrec_total, epayment_total, order_balance, total_order_tip,
	 *   aaa_oc_payment_status, real_payment_method
	 * }
	 */
	public static function compute( float $order_total, array $amounts = [] ): array {

		$getn = static function( string $k ) use ( $amounts ): float {
			return isset( $amounts[ $k ] ) ? (float) $amounts[ $k ] : 0.0;
		};

		$cash       = $getn('aaa_oc_cash_amount');
		$zelle      = $getn('aaa_oc_zelle_amount');
		$venmo      = $getn('aaa_oc_venmo_amount');
		$applepay   = $getn('aaa_oc_applepay_amount');
		$cashapp    = $getn('aaa_oc_cashapp_amount');
		$creditcard = $getn('aaa_oc_creditcard_amount');

		// Tip migration: prefer new key; fall back to legacy
		$new_tip    = $getn('aaa_oc_tip_total');
		$legacy_tip = $getn('_wpslash_tip');
		$front_tip  = $new_tip > 0 ? $new_tip : $legacy_tip;

		$e_tip      = $getn('epayment_tip');

		$payrec_total   = round( $cash + $zelle + $venmo + $applepay + $cashapp + $creditcard, 2 );
		$epayment_total = round( $zelle + $venmo + $applepay + $cashapp + $creditcard, 2 );
		$total_tip      = round( $front_tip + $e_tip, 2 );
		$order_total    = round( $order_total, 2 );
		$balance        = round( $order_total - $payrec_total, 2 );

		$status = 'unpaid';
		if ( $payrec_total <= 0 ) {
			$status = 'unpaid';
		} elseif ( $balance > 0.0001 ) {
			$status = 'partial';
		} else {
			$status = 'paid';
			$balance = max( 0.00, $balance );
		}

		// Dominant rail by priority on ties
		$priority_map = [
			'Zelle'        => $zelle,
			'Cash'         => $cash,
			'Venmo'        => $venmo,
			'ApplePay'     => $applepay,
			'CashApp'      => $cashapp,
			'Credit Card'  => $creditcard,
		];
		$nonzero = array_filter( $priority_map, static fn($v) => $v > 0 );
		$real    = 'unknown';
		if ( ! empty( $nonzero ) ) {
			$max = max( $nonzero );
			foreach ( $priority_map as $label => $val ) {
				if ( $val === $max && $val > 0 ) { $real = $label; break; }
			}
		}

		$out = [
			'payrec_total'          => $payrec_total,
			'epayment_total'        => $epayment_total,
			'order_balance'         => $balance,
			'total_order_tip'       => $total_tip,
			'aaa_oc_payment_status' => $status,
			'real_payment_method'   => $real,
		];

		self::log( '[CALC] in=' . wp_json_encode( [ 'order_total'=>$order_total, 'amounts'=>$amounts ] ) . ' out=' . wp_json_encode( $out ) );
		return $out;
	}

	private static function log( string $msg ): void {
		if ( ! self::DEBUG_THIS_FILE ) return;
		if ( function_exists( 'aaa_oc_log' ) ) { aaa_oc_log( '[PAYMENT_CALC] ' . $msg );
		} else { error_log( '[PAYMENT_CALC] ' . $msg ); }
	}
}

endif;
