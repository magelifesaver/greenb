<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-products-table-hook.php
 * Purpose: Render product table into the card via hook aaa_oc_board_products_table (core/neutral).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Board_Products_Table_Hook' ) ) :

final class AAA_OC_Board_Products_Table_Hook {

	public static function init() : void {
		add_action( 'aaa_oc_board_products_table', [ __CLASS__, 'render' ], 10, 1 );
	}

	public static function render( array $ctx ) : void {
		$oi = isset($ctx['oi']) ? (object)$ctx['oi'] : null;
		if ( ! $oi ) { echo '<em>No items</em>'; return; }

		$key = self::card_key( $ctx, $oi );
		if ( $key !== '' && ! empty($GLOBALS['AAA_OC__CARD_PRODUCTS_HANDLED'][$key]) ) return;

		if ( class_exists('AAA_Build_Product_Table') && method_exists('AAA_Build_Product_Table','render') ) {
			$items_arr = json_decode( (string)($oi->items ?? '[]'), true ) ?: [];
			echo '<div class="expanded-only" style="display:block;border-left:1px solid #ccc; padding:8px; background:#e9e9e9;">';
			echo AAA_Build_Product_Table::render( $items_arr, (string)($oi->currency ?? '') );
			echo '</div>';
		}
	}

	private static function card_key( array $ctx, \stdClass $oi ) : string {
		$oid = 0;
		if ( isset($ctx['order_id']) && (int)$ctx['order_id'] > 0 ) $oid = (int)$ctx['order_id'];
		elseif ( isset($oi->order_id) && (int)$oi->order_id > 0 )   $oid = (int)$oi->order_id;
		elseif ( isset($oi->ID) && (int)$oi->ID > 0 )               $oid = (int)$oi->ID;

		if ( $oid > 0 ) return 'o_' . $oid;

		$items = (string)($oi->items ?? '');
		$stat  = (string)($oi->status ?? '');
		$curr  = (string)($oi->currency ?? '');
		return 'h_' . md5( $items . '|' . $stat . '|' . $curr );
	}
}
AAA_OC_Board_Products_Table_Hook::init();

endif;
