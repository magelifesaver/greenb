<?php
/**
 * Purpose: Workflow Board card (collapsed + expanded-only areas) with blocker banner support.
 * Notes: This file expects variables like $row, $order_id, $order_number, $delivery_date_str, etc. to already be set by the caller.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$delivery_future   = false;
$delivery_today    = false;
$delivery_tomorrow = false;
$delivery_label    = $delivery_date_str; // default

if ( ! empty( $delivery_date_str ) ) {
    // Normalize all dates to Y-m-d in WP local timezone
    $today         = date( 'Y-m-d', strtotime( current_time( 'mysql' ) ) );
    $tomorrow      = date( 'Y-m-d', strtotime( $today . ' +1 day' ) );
    $delivery_date = date( 'Y-m-d', strtotime( $delivery_date_str ) );

    if ( $delivery_date === $today ) {
        $delivery_today = true;
        $delivery_label = 'Today';
    } elseif ( $delivery_date === $tomorrow ) {
        $delivery_tomorrow = true;
        $delivery_label    = 'Tomorrow';
    } elseif ( $delivery_date > $today ) {
        $delivery_future = true;
    }
}

// Resolve WC status name
$status_name = function_exists( 'wc_get_order_status_name' )
    ? wc_get_order_status_name( $row->status ?? '' )
    : ucfirst( $row->status ?? '' );

/**
 * ============================================================
 * Blocker banner (reasons + flags). Must be PHP, not raw text.
 * ============================================================
 */
$aaa_oc_block_reasons = array();
$aaa_oc_flags = array(
    'require_delivery'    => 0,
    'require_fulfillment' => 0,
    'require_driver'      => 0,
    'require_payment'     => 0,
);

$enabled_statuses = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() );
if ( ! in_array( 'wc-completed', $enabled_statuses, true ) ) {
    $enabled_statuses[] = 'wc-completed';
}

$enabled_slugs_no_wc = array_map( function( $s ) {
    return str_replace( 'wc-', '', $s );
}, $enabled_statuses );

$current_slug  = (string) ( $row->status ?? '' );
$current_index = array_search( $current_slug, $enabled_slugs_no_wc, true );
$next_slug     = '';

if ( false !== $current_index && $current_index < count( $enabled_slugs_no_wc ) - 1 ) {
    $next_slug = $enabled_slugs_no_wc[ $current_index + 1 ];
}

if ( $next_slug !== '' ) {

    // F) pending -> processing requires delivery date + time range
    if ( $current_slug === 'pending' && $next_slug === 'processing' ) {
        $req_delivery_date = trim( (string) get_post_meta( $order_id, 'delivery_date_formatted', true ) );
        $req_delivery_time = trim( (string) get_post_meta( $order_id, 'delivery_time_range', true ) );

        if ( $req_delivery_date === '' || $req_delivery_time === '' ) {
            $aaa_oc_flags['require_delivery'] = 1;
            $aaa_oc_block_reasons[] = 'Delivery Required';
        }
    }

    global $wpdb;
    $index_tbl = $wpdb->prefix . 'aaa_oc_order_index';

    // A) processing -> next requires fulfillment fully picked
    $fulfillment_status = $wpdb->get_var(
        $wpdb->prepare( "SELECT fulfillment_status FROM {$index_tbl} WHERE order_id = %d", $order_id )
    );
    $fulfillment_status = strtolower( trim( (string) $fulfillment_status ) );
    $is_fulfillment_complete = ( $fulfillment_status === 'fully_picked' );

    if ( $current_slug === 'processing' && ! $is_fulfillment_complete ) {
        $aaa_oc_flags['require_fulfillment'] = 1;
        $aaa_oc_block_reasons[] = 'Fulfillment Required';
    }

    // B) packed & ready -> out for delivery requires driver
    $driver_id_index = $wpdb->get_var(
        $wpdb->prepare( "SELECT lddfw_driverid FROM {$index_tbl} WHERE order_id = %d", $order_id )
    );
    $driver_id_meta = get_post_meta( $order_id, 'lddfw_driverid', true );
    $driver_id_raw  = ( $driver_id_index !== null && $driver_id_index !== '' ) ? $driver_id_index : $driver_id_meta;
    $has_driver     = is_numeric( $driver_id_raw ) && intval( $driver_id_raw ) > 0;

    $packed_ready_slugs   = array( 'lkd-packed-ready', 'packed-ready', 'packed-and-ready' );
    $delivery_entry_slugs = array( 'out-for-delivery', 'driver-assigned' );

    if ( in_array( $current_slug, $packed_ready_slugs, true ) && in_array( $next_slug, $delivery_entry_slugs, true ) ) {
        if ( ! $has_driver ) {
            $aaa_oc_flags['require_driver'] = 1;
            $aaa_oc_block_reasons[] = 'Driver Required';
        }
    }

    // C/D/E) payment gate (XOR rule)
    $payment_status = strtolower( trim( (string) get_post_meta( $order_id, 'aaa_oc_payment_status', true ) ) );
    $is_paid = ( $payment_status === 'paid' ); // partial never counts
    $envelope_outstanding = intval( get_post_meta( $order_id, 'envelope_outstanding', true ) );
    $is_envelope_ok = ( $envelope_outstanding === 1 );

    $is_payment_gate_pass = ( ( $is_paid && ! $is_envelope_ok ) || ( ! $is_paid && $is_envelope_ok ) );

    $out_for_delivery_slugs = array( 'out-for-delivery' );
    $delivered_slugs        = array( 'lkd-delivered', 'delivered' );

    $needs_payment_gate =
        ( in_array( $current_slug, $out_for_delivery_slugs, true ) && in_array( $next_slug, $delivered_slugs, true ) )
        || ( in_array( $current_slug, $delivered_slugs, true ) && $next_slug === 'completed' )
        || ( in_array( $current_slug, $out_for_delivery_slugs, true ) && $next_slug === 'completed' );

    if ( $needs_payment_gate && ! $is_payment_gate_pass ) {
        $aaa_oc_flags['require_payment'] = 1;
        $aaa_oc_block_reasons[] = 'Payment Required';
    }
}
?>

<!-- COLLAPSED CARD MAIN ROW -->
<div style="display:flex; gap:1rem;">
    <!-- Left column: order info -->
    <div style="flex:1;">
        <div style="font-weight:bold; font-size:20px;">
            #<?php echo esc_html( $order_number ); ?>
        </div>

        <div style="margin:10px 0; font-size:18px; font-weight:800;">
            <?php echo esc_html( $row->customer_name ); ?>
        </div>

        <div style="margin-top:4px;">
            <?php echo wp_kses_post( $formatted_amt ); ?>
            <span style="background:#ff9900; color:#fff; padding:3px 6px; border-radius:4px; margin-left:4px;">
                <?php echo (int) $item_count; ?>
            </span>
        </div>

        <div style="margin-top:4px; color:#777; font-size:0.9em;">
            <?php echo esc_html( $published_ago ); ?>
        </div>

        <?php if ( $delivery_date_str ) : ?>
            <div class="aaa-delivery-date
                <?php echo $delivery_future ? 'future-delivery' : ''; ?>
                <?php echo $delivery_today ? 'today-delivery' : ''; ?>
                <?php echo $delivery_tomorrow ? 'tomorrow-delivery' : ''; ?>"
                style="margin-top:4px;">
                <?php echo esc_html( $delivery_label ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $delivery_time ) : ?>
            <div class="aaa-delivery-time" style="margin-top:4px;">
                <?php echo esc_html( $delivery_time ); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right column: delivery info + expand button -->
    <div style="flex:1; text-align:right; display:flex; flex-direction:column; justify-content:flex-end;">

        <!-- Delivery info (still visible in collapsed) -->
        <div class="aaa-delivery-info">
            <?php if ( $shipping_method ) : ?>
                <div style="margin-top:4px;"><?php echo esc_html( $shipping_method ); ?></div>
            <?php endif; ?>

            <?php if ( $driver_name ) : ?>
                <div style="margin-top:4px; color:blue;"><?php echo esc_html( $driver_name ); ?></div>
            <?php endif; ?>
        </div>

        <!-- Expand button (no open-order or next/prev icons in collapsed) -->
        <div class="aaa-nav-buttons"
             style="<?php echo ( $expanded ? 'display:none !important;' : 'display:flex;' ); ?>
                    flex-direction: column;
                    align-items: flex-end;
                    margin-top:12px;
                    text-align:right;">

            <div class="collapsed-only">
                <button class="button-modern aaa-oc-view-edit"
                        style="font-size:14px; line-height:1; margin-bottom:6px;"
                        data-order-id="<?php echo esc_attr( $order_id ); ?>">
                    Expand
                </button>

                <!-- Next/Prev icons -->
                <div>
                    <?php echo AAA_Render_Next_Prev_Icons::render_next_prev_icons( $order_id, $row->status, true ); ?>
                </div>
            </div>
        </div>

        <!-- Expanded-only content -->
        <div class="expanded-only" style="display:none;">
            <div class="aaa-announcement">
                <p><?php echo esc_html( $status_name ); ?></p>
            </div>

            <?php if ( ! empty( $aaa_oc_block_reasons ) ) : ?>
                <div class="aaa-oc-blocker-banner"
                     data-order-id="<?php echo esc_attr( $order_id ); ?>"
                     data-require-delivery="<?php echo (int) $aaa_oc_flags['require_delivery']; ?>"
                     data-require-fulfillment="<?php echo (int) $aaa_oc_flags['require_fulfillment']; ?>"
                     data-require-driver="<?php echo (int) $aaa_oc_flags['require_driver']; ?>"
                     data-require-payment="<?php echo (int) $aaa_oc_flags['require_payment']; ?>">
                    <?php echo esc_html( implode( ' • ', $aaa_oc_block_reasons ) ); ?>
                </div>
            <?php endif; ?>

            <div class="aaa-customer-notes"
                 style="flex:1; border-radius:5px; padding:0.5rem; font-size:16px; display:none; text-align:left; background:#fdff91;">
                <div style="font-weight:bold; margin-bottom:0.5rem;">Notes:</div>
                <?php
                if ( ! empty( $customer_note ) ) {
                    echo nl2br( esc_html( $customer_note ) );
                } else {
                    echo '<em>No Notes</em>';
                }
                ?>
            </div>
        </div>

    </div>
</div>
