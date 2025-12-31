<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-info-right.php
 *
 * Purpose: Provide the default content for the right-hand column of the
 * expanded order card's “Info” row.  When no customer module has
 * overridden this hook, the board displays a summary of the delivery
 * method, the assigned driver and the full delivery address.  This
 * information comes exclusively from the order index snapshot.  If any
 * field is missing, the corresponding line is omitted.  Modules can
 * extend or replace this output by hooking to `aaa_oc_board_info_right`.
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
         * Render the delivery method, driver and address details.
         *
         * @param array $ctx Card context containing `oi` (order index object).
         */
        public static function render( array $ctx ): void {
            $oi = $ctx['oi'] ?? null;
            if ( ! $oi ) {
                return;
            }

            // Shipping method title
            $shipping = '';
            if ( ! empty( $oi->shipping_method ) ) {
                $shipping = trim( (string) $oi->shipping_method );
            }

            // Driver name
            $driver_name = '';
            if ( ! empty( $oi->driver_id ) && is_numeric( $oi->driver_id ) ) {
                $driver_id = (int) $oi->driver_id;
                if ( $driver_id > 0 ) {
                    $user = get_user_by( 'id', $driver_id );
                    $driver_name = $user ? $user->display_name : ( '#' . $driver_id );
                }
            }

            // Delivery date formatted or fallback meta
            $date_line = '';
            if ( ! empty( $oi->delivery_date_formatted ) ) {
                $date_line = trim( (string) $oi->delivery_date_formatted );
            } elseif ( ! empty( $oi->lddfw_delivery_date ) ) {
                $date_line = trim( (string) $oi->lddfw_delivery_date );
            }

            // Delivery time range or single time
            $time_line = '';
            if ( ! empty( $oi->delivery_time_range ) ) {
                $time_line = trim( (string) $oi->delivery_time_range );
            } elseif ( ! empty( $oi->delivery_time ) ) {
                $time_line = trim( (string) $oi->delivery_time );
            } elseif ( ! empty( $oi->lddfw_delivery_time ) ) {
                $time_line = trim( (string) $oi->lddfw_delivery_time );
            }

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
            // Delivery method and driver on first line
            if ( $shipping || $driver_name ) {
                echo '<div class="aaa-oc-delivery-summary" style="font-size:0.95em; color:#444;">';
                if ( $shipping ) {
                    echo '<span class="aaa-oc-shipping-method" style="font-weight:600;">' . esc_html( $shipping ) . '</span>';
                }
                if ( $driver_name ) {
                    if ( $shipping ) {
                        echo '<span class="aaa-oc-sep" style="margin:0 4px;">|</span>';
                    }
                    echo '<span class="aaa-oc-driver-name" style="color:#0068a3;">' . esc_html( $driver_name ) . '</span>';
                }
                echo '</div>';
            }
            // Date and time on second line
            if ( $date_line || $time_line ) {
                echo '<div class="aaa-oc-delivery-datetime" style="font-size:0.9em; color:#555;">';
                if ( $date_line ) {
                    echo '<span class="aaa-oc-delivery-date" style="margin-right:8px;"><strong>' . esc_html__( 'Date:', 'aaa-order-workflow' ) . '</strong> ' . esc_html( $date_line ) . '</span>';
                }
                if ( $time_line ) {
                    echo '<span class="aaa-oc-delivery-time"><strong>' . esc_html__( 'Time:', 'aaa-order-workflow' ) . '</strong> ' . esc_html( $time_line ) . '</span>';
                }
                echo '</div>';
            }
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