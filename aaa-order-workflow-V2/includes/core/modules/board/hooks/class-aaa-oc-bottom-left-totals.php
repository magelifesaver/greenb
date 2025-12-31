<?php
if ( ! defined('ABSPATH') ) exit;

final class AAA_OC_Board_BottomLeft_Totals {
        public static function init() { add_action('aaa_oc_board_bottom_left', [__CLASS__, 'render'], 10, 1); }

        private static function oi($order_id) {
                global $wpdb;
                return $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}aaa_oc_order_index WHERE order_id=%d LIMIT 1", $order_id)
                );
        }

        private static function pi($order_id) {
                global $wpdb;
                return $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id=%d LIMIT 1", $order_id)
                );
        }

        private static function cur($oi) { return $oi && !empty($oi->currency) ? $oi->currency : get_woocommerce_currency(); }
        private static function price($n, $c) { return function_exists('wc_price') ? wc_price((float)$n, ['currency'=>$c]) : number_format((float)$n, 2); }

        public static function render(array $ctx) : void {
                $order_id = (int)($ctx['order_id'] ?? 0);
                if ( $order_id <= 0 ) return;

                $oi = self::oi($order_id);
                if ( ! $oi ) return;
                $pi = self::pi($order_id);
                $cur = self::cur($oi);

                // Base money
                $subtotal  = (float)($oi->subtotal ?? 0);
                $shipping  = (float)($oi->shipping_total ?? 0);
                $tax       = (float)($oi->tax_total ?? 0);
                $total     = (float)($oi->total_amount ?? 0);

                // Discounts
                $cart_disc = (float)($oi->_cart_discount ?? 0);
                $fees_json = isset($oi->fees_json) ? json_decode((string)$oi->fees_json, true) : [];
                $manual_discount = 0.0;
                if ( is_array($fees_json) ) {
                        foreach ($fees_json as $fee) {
                                $a = isset($fee['amount']) ? (float)$fee['amount'] : 0.0;
                                if ( $a < 0 ) $manual_discount += abs($a);
                        }
                }

                // Credits
                $store_credit = (float)($oi->sc_credit_used ?? 0); // mapped from _funds_used
                $acct_funds   = (float)($oi->af_funds_used ?? 0);

                // Tips
                $checkout_tip = (float)($oi->aaa_oc_tip_total ?? 0);        // web/checkout tip
                $epay_tip     = (float)($oi->epayment_tip     ?? 0);        // epayment tip
                $total_tip    = (float)($oi->total_order_tip  ?? 0);

                // Coupons list
                $coupons = [];
                if ( ! empty($oi->coupons) ) {
                        $tmp = json_decode((string)$oi->coupons, true);
                        if ( is_array($tmp) ) $coupons = array_filter(array_map('trim', $tmp));
                }

                // Payment summary
                $real_method = (string)($oi->real_payment_method ?? '');
                $balance     = (float) ($oi->aaa_oc_order_balance ?? 0);
                $pay_status  = (string)($oi->aaa_oc_payment_status ?? $oi->payment_status ?? 'unknown');

                // Buckets from PI (authoritative), fallback to post meta
                $buckets = [
                        'aaa_oc_zelle_amount'      => 0.0,
                        'aaa_oc_cash_amount'       => 0.0,
                        'aaa_oc_venmo_amount'      => 0.0,
                        'aaa_oc_applepay_amount'   => 0.0,
                        'aaa_oc_cashapp_amount'    => 0.0,
                        'aaa_oc_creditcard_amount' => 0.0,
                ];
                if ( $pi ) {
                        foreach ($buckets as $k => $_) {
                                if ( isset($pi->$k) ) $buckets[$k] = (float)$pi->$k;
                        }
                } else {
                        foreach ($buckets as $k => $_) {
                                $v = get_post_meta($order_id, $k, true);
                                if ( is_numeric($v) ) $buckets[$k] = (float)$v;
                        }
                }
                $labels = [
                        'aaa_oc_zelle_amount'      => 'Zelle',
                        'aaa_oc_cash_amount'       => 'Cash',
                        'aaa_oc_venmo_amount'      => 'Venmo',
                        'aaa_oc_applepay_amount'   => 'Apple Pay',
                        'aaa_oc_cashapp_amount'    => 'CashApp',
                        'aaa_oc_creditcard_amount' => 'Credit Card',
                ];
                $paid = [];
                foreach ($buckets as $k => $amt) if ($amt > 0) $paid[] = ['label'=>$labels[$k], 'amount'=>$amt];
                usort($paid, fn($a,$b)=> $b['amount'] <=> $a['amount']);

                // Driver display (optional) - kept for compatibility but not used in output here
                $driver_id = (int)($oi->driver_id ?? 0);
                $driver    = $driver_id ? get_user_by('ID', $driver_id) : null;

                ?>
                <div class="aaa-oc-my-order-totals"
                     style="margin-top:2px;border:1px solid #ccc;border-radius:4px;padding:10px;background:#f9f9f9;min-width:0;line-height:normal;">
                        <div style="font-weight:bold; margin-bottom:0.5rem;">Totals</div>

                        <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><span><?php echo self::price($subtotal, $cur); ?></span></div>

                        <?php if ($cart_disc > 0): ?>
                        <div style="display:flex; justify-content:space-between; color:red;">
                                <span>Coupon Discount:</span><span><?php echo self::price($cart_disc, $cur); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($manual_discount > 0): ?>
                        <div style="display:flex; justify-content:space-between; color:red;">
                                <span>Manual Discount:</span><span><?php echo self::price($manual_discount, $cur); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($shipping > 0): ?>
                        <div style="display:flex; justify-content:space-between;"><span>Shipping:</span><span><?php echo self::price($shipping, $cur); ?></span></div>
                        <?php endif; ?>

                        <?php if ($tax > 0): ?>
                        <div style="display:flex; justify-content:space-between;"><span>Tax:</span><span><?php echo self::price($tax, $cur); ?></span></div>
                        <?php endif; ?>

                        <?php if ($store_credit > 0): ?>
                        <div style="display:flex; justify-content:space-between; color:orange;">
                                <span>Store Credit:</span><span><?php echo self::price($store_credit, $cur); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($acct_funds > 0): ?>
                        <div style="display:flex; justify-content:space-between; color:orange;">
                                <span>Account Funds:</span><span><?php echo self::price($acct_funds, $cur); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($checkout_tip > 0): ?>
                        <div style="display:flex; justify-content:space-between;"><span>Tip:</span><span><?php echo self::price($checkout_tip, $cur); ?></span></div>
                        <?php endif; ?>

                        <div style="display:flex; justify-content:space-between; font-weight:bold; border-top:2px solid #ccc;">
                                <span>Total:</span><span><?php echo self::price($total, $cur); ?></span>
                        </div>

                        <?php if ( ! empty($coupons) ): ?>
                        <div style="margin-top:0.75rem; padding:0.5rem; border:1px solid #ccc; background:#f9f9f9;">
                                <div style="font-weight:bold; margin-bottom:0.25rem;">Coupons Used:</div>
                                <div><?php echo esc_html(implode(', ', $coupons)); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="aaa-oc-section-subheading" style="margin-top:0.75rem; font-weight:bold;">Payments</div>

                        <?php if ($real_method): ?>
                        <div style="display:flex; justify-content:space-between;">
                                <span>Real Method:</span><span><?php echo esc_html($real_method); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($paid as $method): ?>
                        <div style="display:flex; justify-content:space-between;">
                                <span><?php echo esc_html($method['label']); ?>:</span>
                                <span><?php echo self::price($method['amount'], $cur); ?></span>
                        </div>
                        <?php endforeach; ?>

                        <div style="display:flex; justify-content:space-between; font-weight:bold; border-top:2px solid #ccc; margin-top:0.5rem;">
                                <span>Order Balance:</span><span><?php echo self::price($balance, $cur); ?></span>
                        </div>

                        <?php if ($checkout_tip > 0 || $epay_tip > 0): ?>
                        <div class="aaa-oc-tip-section" style="margin-top:0.75rem; border:1px solid #ccc;border-radius:4px;padding:10px;background:#f9f9f9;">
                                <div style="font-weight:bold; margin-bottom:0.5rem;">Tip Information</div>
                                <?php if ($checkout_tip > 0): ?>
                                <div style="display:flex; justify-content:space-between;"><span>Web Tip:</span><span><?php echo self::price($checkout_tip, $cur); ?></span></div>
                                <?php endif; ?>
                                <?php if ($epay_tip > 0): ?>
                                <div style="display:flex; justify-content:space-between;"><span>eTip:</span><span><?php echo self::price($epay_tip, $cur); ?></span></div>
                                <?php endif; ?>
                                <div style="display:flex; justify-content:space-between; font-weight:bold;">
                                        <span>Total Tip:</span><span><?php echo self::price($total_tip, $cur); ?></span>
                                </div>
                        </div>
                        <?php endif; ?>

                        <div style="display:flex; justify-content:space-between; margin-top:0.5rem;">
                                <span>Payment Status:</span><span><?php echo esc_html(ucfirst($pay_status)); ?></span>
                        </div>
                </div>
                <?php
        }
}
AAA_OC_Board_BottomLeft_Totals::init();
