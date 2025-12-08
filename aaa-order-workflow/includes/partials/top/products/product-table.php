<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Product Table (Expanded) -->
<div class="expanded-only" style="display:none;">
<div style="border-left:1px solid #ccc; padding:0.5rem; margin-bottom:1rem;background: #e9e9e9;">
    <?php echo AAA_Build_Product_Table::render( $items_arr, $row->currency, $row->picked_items, $row->status ); ?>

    <?php
    // NEW: Fee lines (positive amounts only)
    $fee_total_positive = 0;
    if ( ! empty($row->fees_json) ) {
        $fees = json_decode($row->fees_json, true);
        if ( is_array($fees) ) {
            foreach ( $fees as $fee ) {
                $amount = isset($fee['amount']) ? (float)$fee['amount'] : 0;
                if ( $amount > 0 ) {
                    $fee_total_positive += $amount;
                }
            }
        }
    }
    if ( $fee_total_positive > 0 ): ?>
        <div style="margin-top:0.5rem; font-size:16px; display:flex; justify-content:space-between;">
            <span>Fees:</span>
            <span><?php echo wc_price($fee_total_positive, ['currency'=>$row->currency]); ?></span>
        </div>
    <?php endif; ?>
</div>
</div>
