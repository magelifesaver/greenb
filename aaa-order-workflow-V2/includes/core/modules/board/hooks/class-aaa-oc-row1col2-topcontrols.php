<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-row1col2-topcontrols.php
 * Purpose: Top-Right controls — Expanded: "Open Order" + Prev/Next. Collapsed: no output.
 * Hooks:
 *   - aaa_oc_board_collapsed_controls_right  (no-op: hidden per layout)
 *   - aaa_oc_board_top_right                 (expanded header right)
 * Depends: AAA_Render_Next_Prev_Icons::render_next_prev_icons()
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Row1Col2_TopControls_Min {

	public static function init(): void {
		// Collapsed: do nothing (hidden; row1 is full-width pills + Expand only)
		add_action( 'aaa_oc_board_collapsed_controls_right', [ __CLASS__, 'render_collapsed' ], 10, 1 );
		// Expanded: Open Order + Prev/Next
		add_action( 'aaa_oc_board_top_right',                [ __CLASS__, 'render_expanded'  ], 10, 1 );
	}

	public static function render_collapsed( array $ctx ) : void {
		// Intentionally blank per request: no controls in collapsed row-1 right.
	}

	public static function render_expanded( array $ctx ) : void {
		$oi = isset( $ctx['oi'] ) && is_object( $ctx['oi'] ) ? $ctx['oi'] : null;
		if ( ! $oi || empty( $ctx['order_id'] ) ) return;

		$order_id    = (int) $ctx['order_id'];
		$currentSlug = (string) $oi->status;

		// Open Order (admin edit); NOTE: View button removed
		$edit_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );

		echo '<div class="aaa-oc-top-right" style="display:inline-flex;gap:8px;align-items:center;">';
		echo '<a class="button button-modern open-order" href="' . esc_url( $edit_url ) . '" target="_blank" rel="noopener">Open Order</a>';

		// Prev/Next with your helper
		echo AAA_Render_Next_Prev_Icons::render_next_prev_icons( $order_id, $currentSlug, true );
		echo '</div>';
	}
}
AAA_OC_Row1Col2_TopControls_Min::init();
