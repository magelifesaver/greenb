<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-info-right.php
 *
 * Purpose: Provide the default content for the right-hand column of the
 * expanded order card's “Info” row.  When no customer module has
 * overridden this hook, the board displays a summary of the delivery
 * method and the full delivery address.  Driver name and delivery
 * date/time are omitted from the core implementation and will be
 * provided by modules when enabled.
 *
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AAA_OC_Board_Info_Right' ) ) {
    class AAA_OC_Board_Info_Right {
        /**
         * Hook registration.
         */
        public static function init(): void {
            add_action( 'aaa_oc_board_info_right', [ __CLASS__, 'render' ], 10, 1 );
        }

        /**
         * Render the delivery method and address details.
         *
         * @param array $ctx Card context containing `oi` (order index object).
         */
        public static function render( array $ctx ): void {
            $oi = $ctx['oi'] ?? null;
            if ( ! $oi ) {
                return;
            }

            // Shipping method title.  Prefer a human‑friendly label; if the
            // shipping total is zero treat this as “Free shipping”.  The order
            // index stores shipping_total as a float and shipping_method as
            // the method title (e.g. Flat rate, Local pickup).  When the
            // shipping_total is zero or missing we display “Free shipping”.
            $shipping = '';
            $method_name   = '';
            if ( ! empty( $oi->shipping_method ) ) {
                $method_name = trim( (string) $oi->shipping_method );
            }
            $shipping_cost = isset( $oi->shipping_total ) ? (float) $oi->shipping_total : 0.0;
            if ( $shipping_cost <= 0 && $method_name !== '' ) {
                $shipping = __( 'Free shipping', 'aaa-order-workflow' );
            } elseif ( $method_name !== '' ) {
                $shipping = $method_name;
            }

            // Driver name and delivery date/time are intentionally not loaded
            // in the core view.  These values will be provided by modules.
            $driver_name = '';
            $date_line   = '';
            $time_line   = '';

            // Build delivery address
            $address_parts = [];
            if ( ! empty( $oi->shipping_address_1 ) ) {
                $address_parts[] = $oi->shipping_address_1;
            }
            if ( ! empty( $oi->shipping_address_2 ) ) {
                $address_parts[] = $oi->shipping_address_2;
            }
            // Combine city and state into one segment
            $city_state = '';
            if ( ! empty( $oi->shipping_city ) ) {
                $city_state = $oi->shipping_city;
            }
            if ( ! empty( $oi->shipping_state ) ) {
                $city_state = $city_state ? $city_state . ', ' . $oi->shipping_state : $oi->shipping_state;
            }
            if ( $city_state ) {
                $address_parts[] = $city_state;
            }
            if ( ! empty( $oi->shipping_postcode ) ) {
                $address_parts[] = $oi->shipping_postcode;
            }

            // Join address segments with commas
            $address = implode( ', ', array_map( 'trim', $address_parts ) );

            echo '<div class="aaa-oc-info-right-content" style="display:flex; flex-direction:column; gap:8px;">';
            // Delivery method on its own line
            if ( $shipping ) {
                echo '<div class="aaa-oc-delivery-summary" style="font-size:0.95em; color:#444;">';
                echo '<span class="aaa-oc-shipping-method" style="font-weight:600;">' . esc_html( $shipping ) . '</span>';
                echo '</div>';
            }
            // Date/time omitted by default; modules can add via hooks.

            // Address on third line
            if ( $address ) {
                echo '<div class="aaa-oc-delivery-address" style="font-size:0.85em; color:#666;">' . esc_html( $address ) . '</div>';
            }
            echo '</div>';
        }
    }

    // Initialize the hook owner
    AAA_OC_Board_Info_Right::init();
}
