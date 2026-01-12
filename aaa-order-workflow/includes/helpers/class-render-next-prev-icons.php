<?php
/**
 * File Path: /includes/class-aaa-oc-render-next-prev-icons.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Render_Next_Prev_Icons {

    public static function render_next_prev_icons( $order_id, $current_slug, $expanded = false ) {
        $enabled_statuses = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() );

        if ( ! in_array( 'wc-completed', $enabled_statuses, true ) ) {
            $enabled_statuses[] = 'wc-completed';
        }

        $enabled_slugs_no_wc = array_map( function( $s ) {
            return str_replace( 'wc-', '', $s );
        }, $enabled_statuses );

        $current_index = array_search( $current_slug, $enabled_slugs_no_wc, true );
        if ( false === $current_index ) {
            return '';
        }

        $close_js = $expanded ? '; aaaOcCloseModal();' : '';

        $output  = '<div class="aaa-oc-status-icons" style="display:inline-flex; gap:6px; align-items:center;">';

        // Previous button
        if ( $current_index > 0 ) {
            $prev_slug = $enabled_slugs_no_wc[ $current_index - 1 ];
            $output  .= '<button type="button" class="aaa-oc-prev-status-icon button-modern" '
                      . 'title="Move to previous status" '
                      . 'onclick="aaaOcChangeOrderStatus(' . esc_attr( $order_id ) . ', \'' . esc_js( $prev_slug ) . '\')'
                      . esc_js( $close_js ) . '">'
                      . '<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>'
                      . '</button>';
        }

        /**
         * ============================================================
         * Validation / gating for the NEXT button
         * ============================================================
         */

        global $wpdb;
        $index_tbl = $wpdb->prefix . 'aaa_oc_order_index';

        // Gate #1: Fulfillment check — block "Next" in PROCESSING unless fully picked
        $fulfillment_status = $wpdb->get_var(
            $wpdb->prepare( "SELECT fulfillment_status FROM {$index_tbl} WHERE order_id = %d", $order_id )
        );
        $fulfillment_status      = strtolower( trim( (string) $fulfillment_status ) );
        $is_fulfillment_complete = ( $fulfillment_status === 'fully_picked' );

        // Gate #2: Driver required to move into delivery flow
        $driver_id_index = $wpdb->get_var(
            $wpdb->prepare( "SELECT lddfw_driverid FROM {$index_tbl} WHERE order_id = %d", $order_id )
        );
        $driver_id_meta = get_post_meta( $order_id, 'lddfw_driverid', true );
        $driver_id_raw  = ( $driver_id_index !== null && $driver_id_index !== '' ) ? $driver_id_index : $driver_id_meta;
        $has_driver     = is_numeric( $driver_id_raw ) && intval( $driver_id_raw ) > 0;

        /**
         * Gate #3: Payment rule (NO PARTIALS)
         *
         * Definitions:
         * - Paid means: aaa_oc_payment_status === 'paid'
         * - Envelope outstanding means: envelope_outstanding === 1
         *
         * XOR rule you defined:
         * - If PAID: envelope_outstanding must be OFF
         * - If NOT PAID (unpaid/partial): envelope_outstanding must be ON
         *
         * Allowed only if:
         *   (is_paid && !is_envelope_ok) || (!is_paid && is_envelope_ok)
         */
        $payment_status_raw = (string) get_post_meta( $order_id, 'aaa_oc_payment_status', true );
        $payment_status     = strtolower( trim( $payment_status_raw ) );
        $is_paid            = ( $payment_status === 'paid' ); // partial never counts

        $envelope_outstanding = intval( get_post_meta( $order_id, 'envelope_outstanding', true ) );
        $is_envelope_ok       = ( $envelope_outstanding === 1 );

        $is_payment_gate_pass = ( ( $is_paid && ! $is_envelope_ok ) || ( ! $is_paid && $is_envelope_ok ) );

        // Next button (only if not blocked)
        if ( $current_index < count( $enabled_slugs_no_wc ) - 1 ) {
            $next_slug = $enabled_slugs_no_wc[ $current_index + 1 ];

            $block_next = false;

            // A) processing -> next: require fulfillment complete
            if ( $current_slug === 'processing' && ! $is_fulfillment_complete ) {
                $block_next = true;
            }

            // B) packed & ready -> out for delivery: require driver
            $packed_ready_slugs   = array( 'lkd-packed-ready', 'packed-ready', 'packed-and-ready' );
            $delivery_entry_slugs = array( 'out-for-delivery', 'driver-assigned' );

            if ( in_array( $current_slug, $packed_ready_slugs, true ) && in_array( $next_slug, $delivery_entry_slugs, true ) ) {
                if ( ! $has_driver ) {
                    $block_next = true;
                }
            }

            // Status groups
            $out_for_delivery_slugs = array( 'out-for-delivery' );
            $delivered_slugs        = array( 'lkd-delivered', 'delivered' );

            // C) out for delivery -> delivered: must pass payment gate (paid OR outstanding envelope, per XOR rule)
            if ( in_array( $current_slug, $out_for_delivery_slugs, true ) && in_array( $next_slug, $delivered_slugs, true ) ) {
                if ( ! $is_payment_gate_pass ) {
                    $block_next = true;
                }
            }

            // D) delivered -> completed: must pass the same payment gate (per your definition)
            if ( in_array( $current_slug, $delivered_slugs, true ) && $next_slug === 'completed' ) {
                if ( ! $is_payment_gate_pass ) {
                    $block_next = true;
                }
            }

            // E) safety: if any config allows out-for-delivery -> completed directly, enforce same rule
            if ( in_array( $current_slug, $out_for_delivery_slugs, true ) && $next_slug === 'completed' ) {
                if ( ! $is_payment_gate_pass ) {
                    $block_next = true;
                }
            }

            // F) pending -> processing: require delivery date + time range
            if ( $current_slug === 'pending' && $next_slug === 'processing' ) {
                $delivery_date = trim( (string) get_post_meta( $order_id, 'delivery_date_formatted', true ) );
                $delivery_time = trim( (string) get_post_meta( $order_id, 'delivery_time_range', true ) );

                if ( $delivery_date === '' || $delivery_time === '' ) {
                    $block_next = true;
                }
            }

            if ( ! $block_next ) {
                $output .= '<button type="button" class="aaa-oc-next-status-icon button-modern" '
                         . 'title="Move to next status" '
                         . 'onclick="aaaOcChangeOrderStatus(' . esc_attr( $order_id ) . ', \'' . esc_js( $next_slug ) . '\')'
                         . esc_js( $close_js ) . '">'
                         . '<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>'
                         . '</button>';
            }
        }

        $output .= '</div>';
        return $output;
    }
}

add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style( 'dashicons' );
} );
