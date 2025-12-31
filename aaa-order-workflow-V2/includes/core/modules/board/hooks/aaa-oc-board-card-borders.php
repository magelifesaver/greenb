<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/aaa-oc-board-card-borders.php
 * Purpose: Inject inline border styles on board cards using filter-driven values from lifetime spend + customer signals.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'aaa_oc_board_card_border_styles' ) ) {
	/**
	 * Echo inline style="" for border colors based on filter responses.
	 * This is called from the board layout shell via:
	 * do_action( 'aaa_oc_board_card_borders', $ctx );
	 */
	function aaa_oc_board_card_border_styles( array $ctx ) {
		$colors = [
			'left'   => apply_filters( 'aaa_oc_board_border_left',   'transparent', $ctx ),
			'top'    => apply_filters( 'aaa_oc_board_border_top',    'transparent', $ctx ),
			'right'  => apply_filters( 'aaa_oc_board_border_right',  'transparent', $ctx ),
			'bottom' => apply_filters( 'aaa_oc_board_border_bottom', 'transparent', $ctx ),
		];

		// Build inline style string
		$style_parts = [];

		foreach ( $colors as $side => $color ) {
			$color = trim( (string) $color );
			if ( $color && strtolower($color) !== 'transparent' ) {
				$style_parts[] = "border-{$side}-color:{$color};";
			}
		}

		if ( ! empty( $style_parts ) ) {
			echo ' style="' . esc_attr( implode( ' ', $style_parts ) ) . '"';
		}
	}

	// Hook it up
	add_action( 'aaa_oc_board_card_borders', 'aaa_oc_board_card_border_styles', 10, 1 );
}
