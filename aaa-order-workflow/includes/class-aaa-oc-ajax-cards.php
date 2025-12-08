<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Ajax_Cards {

    public function __construct() {}

    public static function build_order_card_html( $row, $col_count = 1, $status_slugs_no_wc = [] ) {
        // --------------------------------------------------
        // Basic Card Data
        // --------------------------------------------------
        $expanded     = false; // Default to collapsed view
        $order_id     = $row->order_id;
        $order_number = $row->order_number;
        $total_amt    = (float) $row->total_amount;
        $formatted_amt= wc_price($total_amt);
        $daily_number = (int) $row->daily_order_number;
        $is_new       = ( isset($row->customer_completed_orders) && (int)$row->customer_completed_orders === 0 );


        // Item count
        $items_arr  = json_decode($row->items, true) ?: [];
        $item_count = 0;
        foreach ($items_arr as $it) {
            $item_count += (int) ($it['quantity'] ?? 0);
        }

        $ts            = strtotime($row->time_published);
        $published_ago = AAA_OC_TimeDiff_Helper::my_granular_time_diff( strtotime( $row->time_published ) );

        $shipping_method = $row->shipping_method ?: '';
        $driver_name     = '';
        if ( ! empty($row->driver_id) ) {
            $drv_user    = get_user_by('id', $row->driver_id);
            $driver_name = $drv_user ? $drv_user->display_name : '#' . $row->driver_id;
        }

        $delivery_date_str = '';
        if ( ! empty($row->delivery_date_formatted) ) {
            $try_ts = strtotime($row->delivery_date_formatted);
            if ($try_ts) {
                $delivery_date_str = date('l', $try_ts) . ' ' . date('n/j', $try_ts);
            }
        }
        $delivery_time = $row->delivery_time ?: '';

        // Lifetime spend color
        $lt_spend   = (float) $row->lifetime_spend;
        $left_color = 'lightblue';
        if ( $lt_spend >= 1000 && $lt_spend < 2000 ) {
            $left_color = 'lightgreen';
        } elseif ( $lt_spend >= 2000 ) {
            $left_color = 'black';
        }

        // Warnings / Specials
        $warnings_text = $row->customer_warnings_text;
        $special_text  = $row->customer_special_needs_text;
        $has_warn      = ! empty($warnings_text);
        $has_special   = ! empty($special_text);

        $right_color = 'transparent';
        if ($has_warn) {
            $right_color = 'red';
        }

        // Birthday / Expired ID checks
        $has_bday    = false;
        if ( ! empty($row->lkd_birthday) ) {
            $bday_ts  = strtotime($row->lkd_birthday);
            $bday_md  = date('m-d', $bday_ts);
            $today_md = date('m-d', current_time('timestamp'));
            if ( $bday_md === $today_md ) {
                $has_bday = true;
            }
        }

        $has_expired = false;
        if ( ! empty($row->lkd_dl_exp) ) {
            $today_ts = strtotime( current_time('Y-m-d') . ' 00:00:00' );
            $exp_ts   = strtotime($row->lkd_dl_exp);
            if ($exp_ts && $exp_ts < $today_ts) {
                $has_expired = true;
            }
        }

        // Medical/ID uploads
        $has_rec    = ! empty($row->lkd_upload_med);
        $has_selfie = ! empty($row->lkd_upload_selfie);
        $has_idfile = ! empty($row->lkd_upload_id);

        // --------------------------------------------------
        // Expanded Layout Data
        // --------------------------------------------------
        $subtotal       = (float) ($row->subtotal         ?? 0);
        $discount_total = (float) ($row->discount_total   ?? 0);
        $tip_amount     = (float) ($row->tip_amount       ?? 0);
        $customer_note  = $row->customer_note            ?? '';
        $billing_data   = json_decode($row->billing_json, true) ?: [];
        $customer_email = $row->customer_email            ?? '';
        $customer_phone = $row->customer_phone            ?? '';

        // Start buffering the HTML
        ob_start();

        // 1. Card start
        include AAA_OC_PLUGIN_DIR . 'includes/partials/card-start.php';

        // 2. "Top" partials
        include AAA_OC_PLUGIN_DIR . 'includes/partials/top/top-row/top-pills.php';
        include AAA_OC_PLUGIN_DIR . 'includes/partials/top/top-row/buttons.php';
        include AAA_OC_PLUGIN_DIR . 'includes/partials/top/card/collapsed-main-row.php';
        include AAA_OC_PLUGIN_DIR . 'includes/partials/top/order-info/warnings.php';
        include AAA_OC_PLUGIN_DIR . 'includes/partials/top/products/product-table.php';

        // 3. "Bottom" partials: totals, user info, printing
        include AAA_OC_PLUGIN_DIR . 'includes/partials/bottom/order-info/totals/totals.php';
        include AAA_OC_PLUGIN_DIR . 'includes/partials/bottom/order-info/account-info/user-info.php';
	

        // 4. Payment Fields (new partial)
	include_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-payment-modal.php';
	AAA_OC_Payment_Modal::render_button_and_modal($order_id);

        // 5. Card end
        include AAA_OC_PLUGIN_DIR . 'includes/partials/card-end.php';

        return ob_get_clean();
    }
}
?>
