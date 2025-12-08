<?php
/**
 * File: aaa-order-workflow/includes/partials/bottom/order-info/totals/totals.php
 * Version: 1.0.1 · modified to add Coupons Used section
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="expanded-only" style="display:none;">
  
  <!-- Make a single row with two columns -->
  <div style="display:flex; gap:1rem; margin-bottom:1rem;">
    
    <!-- LEFT COLUMN (Totals + User Info + Print) -->
    <div class="aaa-oc-order-info-col" style="flex:1; border-left:0 solid #ccc; padding:0.5rem; font-size:18px; width:100%;">

	<div style="display:flex; gap:1rem; margin-bottom:1rem;">
			<div style="flex:1; border-left:0 solid #ccc; padding:0.5rem;font-size:18px;">
				<div class="aaa-oc-my-order-totals"
				style="margin-top: 2px; border: 1px solid #ccc;border-radius: 4px;padding: 10px;background: #f9f9f9;min-width: 0;  line-height: normal;">
					<div style="font-weight:bold; margin-bottom:0.5rem;">Totals</div>
					<div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><span><?php echo wc_price((float)$row->subtotal, ['currency'=>$row->currency]); ?></span></div>
					<!-- Coupon Discount -->
					<?php if ((float)($row->_cart_discount ?? 0) > 0): ?>
					    <div style="display:flex; justify-content:space-between; color:red;">
					        <span>Coupon Discount:</span>
					        <span><?php echo wc_price((float)$row->_cart_discount, ['currency'=>$row->currency]); ?></span>
					    </div>
					<?php endif; ?>

					<!-- Manual Discount (from fees_json negative fees) -->
					<?php
					$manual_discount_total = 0;
					if ( ! empty($row->fees_json) ) {
					    $fees = json_decode($row->fees_json, true);
					    if ( is_array($fees) ) {
					        foreach ( $fees as $fee ) {
					            $amount = isset($fee['amount']) ? (float)$fee['amount'] : 0;
					            if ( $amount < 0 ) {
					                $manual_discount_total += abs($amount);
					            }
					        }
					    }
					}
					if ( $manual_discount_total > 0 ): ?>
					    <div style="display:flex; justify-content:space-between; color:red;">
					        <span>Manual Discount:</span>
					        <span><?php echo wc_price($manual_discount_total, ['currency'=>$row->currency]); ?></span>
					    </div>
					<?php endif; ?>
					<?php if ((float)$row->shipping_total > 0): ?>
					<div style="display:flex; justify-content:space-between;"><span>Shipping:</span><span><?php echo wc_price($row->shipping_total, ['currency'=>$row->currency]); ?></span></div>
					<?php endif; ?>
					<?php if ((float)$row->tax_total > 0): ?>
					<div style="display:flex; justify-content:space-between;"><span>Tax:</span><span><?php echo wc_price($row->tax_total, ['currency'=>$row->currency]); ?></span></div>
					<?php endif; ?>
					<?php if ((float)$row->_funds_used > 0): ?>
					<div style="display:flex; justify-content:space-between; color:orange;"><span>Store Credit:</span><span><?php echo wc_price((float)$row->_funds_used, ['currency'=>$row->currency]); ?></span></div>
					<?php endif; ?>
				    <?php if ((float)$row->tip_amount > 0): ?>
				        <div style="display:flex; justify-content:space-between;">
				            <span>Tip:</span>
				            <span><?php echo wc_price($row->tip_amount, ['currency'=>$row->currency]); ?></span>
				        </div>
				    <?php endif; ?>
					<div style="display:flex; justify-content:space-between; font-weight:bold; border-top:2px solid #ccc;">
					<span>Total:</span>
					<span><?php echo wc_price($row->total_amount, ['currency'=>$row->currency]); ?></span>
					</div>


					<!-- Payments -->
					<div class="aaa-oc-section-subheading" style="margin-top:0rem; font-weight:bold;">Payments</div>
					<?php
					$methods = [
					    'aaa_oc_zelle_amount'       => 'Zelle',
					    'aaa_oc_cash_amount'        => 'Cash',
					    'aaa_oc_venmo_amount'       => 'Venmo',
					    'aaa_oc_applepay_amount'    => 'Apple Pay',
					    'aaa_oc_creditcard_amount'  => 'Credit Card',
					    'aaa_oc_cashapp_amount'     => 'CashApp',
					];
					$paid = [];
					foreach ( $methods as $key => $label ) {
					    $val = (float) ($row->$key ?? 0);
					    if ( $val > 0 ) {
					      $paid[] = [ 'label' => $label, 'amount' => $val ];
					    }
					}
					usort($paid, fn($a, $b) => $b['amount'] <=> $a['amount']);
					?>
					<?php foreach ( $paid as $method ) : ?>
					<div style="display:flex; justify-content:space-between;">
					<span><?php echo esc_html($method['label']); ?>:</span>
					<span><?php echo wc_price($method['amount'], ['currency' => $row->currency]); ?></span>
					</div>
					<?php endforeach; ?>

					<!-- Balance -->
					<div style="display:flex; justify-content:space-between; font-weight:bold; border-top:2px solid #ccc; margin-top:0.5rem;">
					<span>Order Balance:</span>
					<span><?php echo wc_price((float)($row->aaa_oc_order_balance ?? 0), ['currency' => $row->currency]); ?></span>
					</div>
					<!-- Coupons Used (new section after totals, before payments) -->
					<?php
					$coupon_codes = [];
					if ( ! empty($row->coupons) ) {
					    $decoded = json_decode($row->coupons, true);
					    if ( is_array($decoded) ) {
					        $coupon_codes = array_filter($decoded);
					    }
					}
					if ( ! empty($coupon_codes) ): ?>
					    <div style="margin-top:0.75rem; padding:0.5rem; border:1px solid #ccc; background:#f9f9f9;">
					        <div style="font-weight:bold; margin-bottom:0.25rem;">Coupons Used:</div>
					        <div><?php echo esc_html(implode(', ', $coupon_codes)); ?></div>
					    </div>
					<?php endif; ?>

				</div>
			</div>
				</div>

					<?php
					// Display: Payment Info block under Totals section on expanded card
					?>
					<div class="aaa-oc-payment-info" style="margin-top:1rem; border-left: 0px solid #ccc; padding:0.5rem;">

          <div class="aaa-oc-tip-section" style="margin-top:1rem; border: 1px solid #ccc;border-radius: 4px;padding: 10px;background: #f9f9f9;min-width: 0; ">
            <?php
            $driver_id = (int)($row->driver_id ?? 0);
            $driver = $driver_id ? get_user_by('ID', $driver_id) : null;
            ?>
            <div style="font-weight:bold; margin-bottom:0.5rem;">Tip Information</div>
            <div style="display:flex; justify-content:space-between;"><span>Web Tip:</span><span><?php echo wc_price((float)($row->aaa_oc_tip_total ?? 0), ['currency'=>$row->currency]); ?></span></div>
            <div style="display:flex; justify-content:space-between;"><span>eTip:</span><span><?php echo wc_price((float)($row->epayment_tip ?? 0), ['currency'=>$row->currency]); ?></span></div>
            <div style="display:flex; justify-content:space-between; font-weight:bold;">
              <span>Total Tip:</span>
              <span><?php echo wc_price((float)($row->total_order_tip ?? 0), ['currency'=>$row->currency]); ?></span>
            </div>
            <div style="display:flex; justify-content:space-between;"><span>Assigned Driver:</span><span><?php echo $driver ? esc_html($driver->display_name) : 'N/A'; ?></span></div>
          </div>

					    <div style="display:flex; justify-content:space-between; margin-top:0.5rem;">
					        <span>Payment Status:</span>
					        <span><?php echo esc_html(ucfirst($row->aaa_oc_payment_status ?? 'unknown')); ?></span>
					    </div>
</div>
