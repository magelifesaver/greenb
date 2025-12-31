<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-collapsed-summary-right.php
 *
 * Purpose: Render the right-hand summary for collapsed order cards.  This
 * section of the card displays the basic delivery information derived
 * solely from the order index: the shipping/delivery method and the
 * assigned driver's name (if any).  It does not read from WooCommerce
 * order meta or any module tables; all values come from the indexed
 * snapshot passed in $ctx['oi'].  Modules can hook before or after
 * this output to extend it, but the core board always prints these
 * basics when available.
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
         * Output the shipping method and driver name for the collapsed card.
         *
         * @param array $ctx The card context array (contains 'oi' and other
         *                  values).  The order index object is expected at
         *                  $ctx['oi'] and must expose `shipping_method` and
         *                  `driver_id` properties.
         */
        public static function render( array $ctx ): void {
            $oi          = $ctx['oi'] ?? null;
            $shipping    = '';
            $driver_name = '';
            $date_line   = '';
            $time_line   = '';

            if ( $oi ) {
                // Shipping/delivery method title (may be empty).
                if ( ! empty( $oi->shipping_method ) ) {
                    $shipping = trim( (string) $oi->shipping_method );
                }

                // Assigned driver ID → driver display name
                if ( ! empty( $oi->driver_id ) && is_numeric( $oi->driver_id ) ) {
                    $driver_id = (int) $oi->driver_id;
                    if ( $driver_id > 0 ) {
                        $user = get_user_by( 'id', $driver_id );
                        $driver_name = $user ? $user->display_name : ( '#' . $driver_id );
                    }
                }

                // Delivery date formatted or fallback meta
                if ( ! empty( $oi->delivery_date_formatted ) ) {
                    $date_line = trim( (string) $oi->delivery_date_formatted );
                } elseif ( ! empty( $oi->lddfw_delivery_date ) ) {
                    $date_line = trim( (string) $oi->lddfw_delivery_date );
                }

                // Delivery time range or single time
                if ( ! empty( $oi->delivery_time_range ) ) {
                    $time_line = trim( (string) $oi->delivery_time_range );
                } elseif ( ! empty( $oi->delivery_time ) ) {
                    $time_line = trim( (string) $oi->delivery_time );
                } elseif ( ! empty( $oi->lddfw_delivery_time ) ) {
                    $time_line = trim( (string) $oi->lddfw_delivery_time );
                }
            }

            // Print the content only if at least one field is present.
            if ( $shipping || $driver_name || $date_line || $time_line ) {
                echo '<div class="aaa-oc-collapsed-delivery-info" style="text-align:right;">';
                if ( $shipping ) {
                    echo '<div class="aaa-oc-shipping-method" style="font-weight:500; font-size:0.9em; color:#444;">' . esc_html( $shipping ) . '</div>';
                }
                if ( $driver_name ) {
                    echo '<div class="aaa-oc-driver-name" style="font-size:0.8em; color:#0068a3;">' . esc_html( $driver_name ) . '</div>';
                }
                if ( $date_line ) {
                    echo '<div class="aaa-oc-delivery-date" style="font-size:0.8em; color:#555;">' . esc_html__( 'Date:', 'aaa-order-workflow' ) . ' ' . esc_html( $date_line ) . '</div>';
                }
                if ( $time_line ) {
                    echo '<div class="aaa-oc-delivery-time" style="font-size:0.8em; color:#555;">' . esc_html__( 'Time:', 'aaa-order-workflow' ) . ' ' . esc_html( $time_line ) . '</div>';
                }
                echo '</div>';
            }
        }
    }

    // Initialise on load
    AAA_OC_Board_Collapsed_Summary_Right::init();
}