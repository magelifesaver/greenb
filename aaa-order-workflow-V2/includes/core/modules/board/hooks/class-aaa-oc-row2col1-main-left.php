<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-row2col1-main-left.php
 *
 * Purpose: Default “Row 2 / Col 1” main block for Board cards (collapsed & expanded).
 *          Prints: Daily Order Number, Order Number, Customer Name, Total – # of items, Time Since Placed.
 *
 * Notes:
 * - Delivery date/time lines are intentionally NOT included here; the Delivery module will add them.
 * - Runs on hook: aaa_oc_board_collapsed_left (filter). Returns true to claim the slot.
 *
 * Version: 1.1.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * Render the Row2/Col1 block and claim the area.
 *
 * @param bool  $handled Whether another hook already rendered content.
 * @param array $ctx      Card context. $ctx['oi'] is the indexed order snapshot (stdClass).
 * @return bool           True when we printed (prevents other defaults).
 */
add_filter( 'aaa_oc_board_collapsed_left', function( $handled, array $ctx ) {
    // Respect previous renders.
    if ( $handled ) {
        return true;
    }

    // Grab the order index object
    $oi = isset( $ctx['oi'] ) && is_object( $ctx['oi'] ) ? $ctx['oi'] : (object) [];

    // 1) Order number (fallback to order_id)
    $order_number = (string) ( $oi->order_number ?? '' );
    if ( $order_number === '' && ! empty( $ctx['order_id'] ) ) {
        $order_number = (string) $ctx['order_id'];
    }

    // 1a) Daily order number (if present and > 0)
    $daily_order = '';
    if ( isset( $oi->daily_order_number ) && is_numeric( $oi->daily_order_number ) ) {
        $dn = (int) $oi->daily_order_number;
        if ( $dn > 0 ) {
            $daily_order = '#' . $dn;
        }
    }

    // 2) Customer name
    $customer_name = (string) ( $oi->customer_name ?? '' );

    // 3) Total amount and currency
    $total_raw = (float) ( $oi->total_amount ?? 0 );
    $currency  = (string) ( $oi->currency ?? '' );
    if ( function_exists( 'wc_price' ) ) {
        $formatted_total = wc_price( $total_raw, [ 'currency' => $currency ] );
    } else {
        $formatted_total = number_format( $total_raw, 2 );
    }

    // 3a) Item count (sum quantities in items JSON)
    $items_json = (string) ( $oi->items ?? '[]' );
    $items_arr  = json_decode( $items_json, true );
    $item_count = 0;
    if ( is_array( $items_arr ) ) {
        foreach ( $items_arr as $it ) {
            $item_count += isset( $it['quantity'] ) ? (int) $it['quantity'] : 0;
        }
    }

    // 4) Time since order placed
    $published = trim( (string) ( $oi->time_published ?? '' ) );
    $ago = '';
    if ( $published !== '' ) {
        $ts = strtotime( $published );
        if ( $ts ) {
            if ( class_exists( 'AAA_OC_TimeDiff_Helper' ) && method_exists( 'AAA_OC_TimeDiff_Helper', 'my_granular_time_diff' ) ) {
                $ago = AAA_OC_TimeDiff_Helper::my_granular_time_diff( $ts, current_time( 'timestamp' ) );
            } else {
                $mins = max( 0, floor( ( current_time( 'timestamp' ) - $ts ) / 60 ) );
                $ago  = ( $mins < 60 ) ? "{$mins} min ago" : floor( $mins / 60 ) . ' hour(s) ago';
            }
        }
    }

    // Output container
    echo '<div class="aaa-oc-left-main">';

    // Order number and optional daily order badge
    if ( $order_number !== '' ) {
        echo '<div class="aaa-oc-order-number-large" style="display:flex;align-items:center;gap:6px;font-weight:700;font-size:20px;">';
        echo '<span>#' . esc_html( $order_number ) . '</span>';
        if ( $daily_order !== '' ) {
            // Daily number as small pill
            echo '<span class="aaa-oc-daily-order-num" style="background:#2d2;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.75em;">' . esc_html( $daily_order ) . '</span>';
        }
        echo '</div>';
    }

    // Customer name
    if ( $customer_name !== '' ) {
        echo '<div class="aaa-oc-customer-name" style="margin:8px 0;font-size:16px;font-weight:700;">' . esc_html( $customer_name ) . '</div>';
    }

    // Total and item badge
    echo '<div class="aaa-oc-order-totals" style="margin-top:4px;">';
    echo '<span class="aaa-oc-total-price">' . wp_kses_post( $formatted_total ) . '</span>';
    echo ' <span class="item-badge" style="background:#ff9900;color:#fff;padding:3px 6px;border-radius:4px;margin-left:6px;">' . (int) $item_count . '</span>';
    echo '</div>';

    // Time since placed
    if ( $ago !== '' ) {
        echo '<div class="aaa-oc-time-ago" style="margin-top:4px;color:#777;font-size:.9em;">' . esc_html( $ago ) . '</div>';
    }

    echo '</div>';

    // Claim the slot
    return true;
}, 10, 2 );