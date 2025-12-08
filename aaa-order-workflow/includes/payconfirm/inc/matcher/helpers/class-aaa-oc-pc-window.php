<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/matcher/helpers/class-aaa-oc-pc-window.php
 * Purpose: Date window utility for sent_on → [sent_on-2d .. eod(sent_on)] clamped to now.
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Window {
	public static function window_dates( $sent_on_mysql ) {
		$ts = strtotime( (string) $sent_on_mysql );
		if ( ! $ts ) { return [ null, null ]; }

		$start = $ts - 2 * DAY_IN_SECONDS;
		$eod   = gmmktime( 23, 59, 59, (int) gmdate('n',$ts), (int) gmdate('j',$ts), (int) gmdate('Y',$ts) );
		$end   = min( $eod, time() );

		return [ gmdate( 'Y-m-d H:i:s', $start ), gmdate( 'Y-m-d H:i:s', $end ) ];
	}
}
