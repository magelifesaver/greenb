<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-row1col1-toppills.php
 * Purpose: Top Pills (LEFT) — function-based hooks (no class guards), render in BOTH states.
 * Hooks:
 *   - aaa_oc_board_collapsed_pills
 *   - aaa_oc_board_top_left
 *
 * This version omits the order number pill entirely. The order ID and daily
 * order number are shown in the left-hand column of the card instead of in
 * the pillbar.  Only payment status, payment method, discount flag and
 * shipping method are displayed by default. Modules can extend the set
 * via the `aaa_oc_row1col1_collect_pills` filter.
 *
 * Version: 1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build default pills from index row (excluding order number).
 *
 * @param stdClass $oi The indexed order object.
 * @return array<int, array<string,string>>
 */
function aaa_oc_row1col1_toppills_default_pills( stdClass $oi ) : array {
    $p = [];

    // New customer indicator.  When the indexed snapshot marks a customer as
    // "new" (first‑time buyer), show a bright pill.  The field
    // `customer_type` is set by the customer indexer; any non‑empty value
    // equal to 'new' or 'first' triggers the pill.  This gives dispatch
    // staff a quick visual cue that extra guidance may be required.  A
    // capitalised label is used for consistency with other pills.
    if ( ! empty( $oi->customer_type ) ) {
        $ctype = strtolower( (string) $oi->customer_type );
        if ( in_array( $ctype, [ 'new', 'first' ], true ) ) {
            $p[] = [
                'text'  => 'NEW',
                'class' => 'pill-new',
                'title' => 'New Customer',
            ];
        }
    }

    // Payment status (UNPAID, PAID, PARTIAL)
    if ( ! empty( $oi->payment_status ) ) {
        $cls = strtolower( preg_replace( '/\s+/', '-', (string) $oi->payment_status ) );
        $p[] = [ 'text' => strtoupper( (string) $oi->payment_status ), 'class' => 'pill-paystat ' . $cls, 'title' => 'Payment Status' ];
    }

    // Payment method title
    if ( ! empty( $oi->_payment_method_title ) ) {
        $cls = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $oi->_payment_method_title ) );
        $p[] = [ 'text' => (string) $oi->_payment_method_title, 'class' => 'pill-paymethod ' . $cls, 'title' => 'Payment Method' ];
    }

    // Coupon or discount total (display 'DISCOUNT' pill)
    if ( isset( $oi->discount_total ) && (float) $oi->discount_total > 0 ) {
        $p[] = [ 'text' => 'DISCOUNT', 'class' => 'pill-discount', 'title' => 'Coupon/Discount applied' ];
    }

    // Shipping/delivery method
    if ( ! empty( $oi->shipping_method ) ) {
        $p[] = [ 'text' => strtoupper( (string) $oi->shipping_method ), 'class' => 'pill-shipmethod', 'title' => 'Shipping/Delivery Method' ];
    }

    return $p;
}

/**
 * Render pills. Runs in BOTH collapsed + expanded (shell calls both hooks).
 *
 * @param array $ctx Card context (contains 'oi').
 * @return void
 */
function aaa_oc_row1col1_toppills_render( array $ctx ) : void {
    $oi = isset( $ctx['oi'] ) && is_object( $ctx['oi'] ) ? $ctx['oi'] : (object) [];

    $pills = aaa_oc_row1col1_toppills_default_pills( $oi );
    // Allow modules to extend/modify, but never let a bad filter blank the entire bar
    $pills = apply_filters( 'aaa_oc_row1col1_collect_pills', $pills, $oi, 'row1col1', $ctx );
    if ( ! is_array( $pills ) || empty( $pills ) ) {
        $pills = aaa_oc_row1col1_toppills_default_pills( $oi );
    }

    echo '<div class="aaa-oc-pillbar">';
    foreach ( $pills as $pill ) {
        $text  = isset( $pill['text'] ) ? (string) $pill['text'] : '';
        if ( $text === '' ) {
            continue;
        }
        $class = isset( $pill['class'] ) ? (string) $pill['class'] : '';
        $title = isset( $pill['title'] ) ? (string) $pill['title'] : '';
        echo '<span class="aaa-oc-pill ' . esc_attr( $class ) . '"' . ( $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>'
            . esc_html( $text )
            . '</span>';
    }
    echo '</div>';
}

// Hook with functions (no class guards)
add_action( 'aaa_oc_board_collapsed_pills', 'aaa_oc_row1col1_toppills_render', 10, 1 );
add_action( 'aaa_oc_board_top_left',        'aaa_oc_row1col1_toppills_render', 10, 1 );