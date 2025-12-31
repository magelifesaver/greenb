<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-collapsed-summary-right.php
 *
 * Purpose: Render the right-hand summary for collapsed order cards.  This
 * section of the card displays the basic delivery information derived
 * solely from the order index: the shipping/delivery method.  It does
 * not read from WooCommerce order meta or any module tables; all
 * values come from the indexed snapshot passed in $ctx['oi'].
 * Modules can hook before or after this output to extend it.
 *
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AAA_OC_Board_Collapsed_Summary_Right' ) ) {
    /**
     * Core handler for the `aaa_oc_board_collapsed_summary_right` hook.
     */
    class AAA_OC_Board_Collapsed_Summary_Right {
        /**
         * Register the render callback on plugin load.
         */
        public static function init(): void {
            add_action( 'aaa_oc_board_collapsed_summary_right', [ __CLASS__, 'render' ], 10, 1 );
        }

        /**
         * Output the shipping method for the collapsed card.
         *
         * @param array $ctx The card context array (contains 'oi' and other
         *                  values).  The order index object is expected at
         *                  $ctx['oi'] and must expose a `shipping_method`
         *                  property.
         */
        public static function render( array $ctx ): void {
            $oi       = $ctx['oi'] ?? null;
            $shipping = '';

            if ( $oi ) {
                // Shipping/delivery method title (may be empty).
                if ( ! empty( $oi->shipping_method ) ) {
                    $shipping = trim( (string) $oi->shipping_method );
                }

                /**
                 * Driver assignment, delivery date and time are intentionally
                 * omitted in the core collapsed summary.  These values will
                 * be added by the delivery module when it is enabled.
                 */
            }

            // Print only the shipping method if present.
            if ( $shipping ) {
                echo '<div class="aaa-oc-collapsed-delivery-info" style="text-align:right;">';
                echo '<div class="aaa-oc-shipping-method" style="font-weight:500; font-size:0.9em; color:#444;">' . esc_html( $shipping ) . '</div>';
                echo '</div>';
            }
        }
    }

    // Initialise on load
    AAA_OC_Board_Collapsed_Summary_Right::init();
}
