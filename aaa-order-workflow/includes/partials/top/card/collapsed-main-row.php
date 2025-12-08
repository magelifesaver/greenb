<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$delivery_future   = false;
$delivery_today    = false;
$delivery_tomorrow = false;
$delivery_label    = $delivery_date_str; // default

if ( ! empty( $delivery_date_str ) ) {
    // ✅ Normalize all dates to Y-m-d in WP local timezone
    $today        = date( 'Y-m-d', strtotime( current_time( 'mysql' ) ) );
    $tomorrow     = date( 'Y-m-d', strtotime( $today . ' +1 day' ) );
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
    <?php if ( $delivery_date_str ): ?>
<div class="aaa-delivery-date 
    <?php echo $delivery_future ? 'future-delivery' : ''; ?> 
    <?php echo $delivery_today ? 'today-delivery' : ''; ?> 
    <?php echo $delivery_tomorrow ? 'tomorrow-delivery' : ''; ?>" 
    style="margin-top:4px;">
    <?php echo esc_html($delivery_label); ?>
</div>
    <?php endif; ?>
        <?php if ( $delivery_time ): ?>
            <div class="aaa-delivery-time" style="margin-top:4px;">
                <?php echo esc_html( $delivery_time ); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right column: delivery info + expand button -->
    <div style="flex:1; text-align:right; display:flex; flex-direction:column; justify-content:flex-end;">
        <!-- Delivery info (still visible in collapsed) -->
        <div class="aaa-delivery-info">
            <?php if ( $shipping_method ): ?>
                <div style="margin-top:4px;"><?php echo esc_html( $shipping_method ); ?></div>
            <?php endif; ?>
            <?php if ( $driver_name ): ?>
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
  
            <!-- Expand button on first "row" -->
            <div class="collapsed-only">
                <button class="button-modern aaa-oc-view-edit"
                        style="font-size:14px; line-height:1; margin-bottom:6px;"
                        data-order-id="<?php echo esc_attr( $order_id ); ?>">
                    Expand
                </button>

                <!-- Next/Prev icons on second "row" -->
                <div>
                    <?php echo AAA_Render_Next_Prev_Icons::render_next_prev_icons( $order_id, $row->status, true ); ?>
                </div>
            </div>
        </div>

        <!-- Customer Note Section (hidden by default in collapsed) -->
        <div class="expanded-only" style="display:none;">
            <div class="aaa-announcement">
                <p><?php echo esc_html( $status_name ); ?></p>
            </div>
            <div class="aaa-customer-notes"
                 style="flex:1; border-radius: 5px; padding:0.5rem; font-size:16px; display:none; text-align:left; background: #fdff91;">
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
