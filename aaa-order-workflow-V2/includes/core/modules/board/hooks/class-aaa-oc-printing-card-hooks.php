<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/hooks/class-aaa-oc-printing-card-hooks.php
 * Purpose: Add print buttons to card Actions box.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Printing_Card_Hooks' ) ) :

final class AAA_OC_Printing_Card_Hooks {

	public static function init() : void {
		add_action( 'aaa_oc_board_action_buttons', [ __CLASS__, 'render_print_buttons' ], 10, 1 );
	}

	public static function render_print_buttons( array $ctx ) : void {
		$order_id = isset($ctx['order_id']) ? (int)$ctx['order_id'] : 0;
		if ( $order_id <= 0 ) return;

		if ( class_exists('AAA_OC_Printing') && method_exists('AAA_OC_Printing','render_print_buttons') ) {
			echo '<div style="margin-top:6px;">';
			echo AAA_OC_Printing::render_print_buttons( $order_id );
			echo '</div>';
		} else {
			echo '<em>Printing module not available</em>';
		}
	}
}
AAA_OC_Printing_Card_Hooks::init();

endif;
