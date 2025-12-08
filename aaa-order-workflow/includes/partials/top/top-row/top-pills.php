<?php
/**
 * File Path: /aaa-order-workflow/includes/views/partials/card-top-pills.php
 *
 * Purpose:
 * Renders a row of pills shown at the top of collapsed order cards.
 * Mapping pills are delegated to helpers.
 * Flag pills (NEW, BDAY, EXPIRED, REC, WARN, SPECIAL, TIP, Coupon, Discount, Store Credit) are inline.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 🧾 Common flags
$daily_number   = ! empty($row->daily_order_number) ? $row->daily_order_number : '';
$is_new         = isset($row->customer_completed_orders) && (int) $row->customer_completed_orders === 0;

// 🎂 Birthday
$has_bday = false;
if ( ! empty($row->lkd_birthday) ) {
    $bday_ts   = strtotime($row->lkd_birthday);
    $today_md  = date('m-d', current_time('timestamp'));
    $bday_md   = date('m-d', $bday_ts);
    $has_bday  = ($bday_md === $today_md);
}

// 🪪 License expired
$has_expired = false;
if ( ! empty($row->lkd_dl_exp) ) {
    $today      = strtotime( date('Y-m-d') );
    $exp_ts     = strtotime($row->lkd_dl_exp);
    $has_expired = $exp_ts && $exp_ts < $today;
}

// 🔖 Flags
$has_warn    = ! empty($row->customer_warnings_text);
$has_special = ! empty($row->customer_special_needs_text);
$has_rec     = ! empty($row->lkd_upload_med);

// 🧾 Payment status (helper)
$paymentrec_status = AAA_OC_Payment_Status_Label::get($row->aaa_oc_payment_status ?? '');

// 💳 Payment methods (helper)
$payment_methods = [
    'Zelle'       => $row->aaa_oc_zelle_amount       ?? 0,
    'Venmo'       => $row->aaa_oc_venmo_amount       ?? 0,
    'Apple Pay'   => $row->aaa_oc_applepay_amount    ?? 0,
    'Credit Card' => $row->aaa_oc_creditcard_amount  ?? 0,
    'Cash App'    => $row->aaa_oc_cashapp_amount     ?? 0,
];

// 💵 Cash is a special pill if >0
$cash_amount = floatval($row->aaa_oc_cash_amount ?? 0);

// 🛰️ Order source (helper)
$mapped_source = AAA_OC_Map_Order_Source::map(
    $row->_created_via ?? '',
    $row->_wc_order_attribution_source_type ?? ''
);
$source_code = $mapped_source['source'] ?? '';

// 📦 Fulfillment status (simple inline map)
$fulfillment_raw = isset($row->fulfillment_status) ? strtolower(trim((string)$row->fulfillment_status)) : 'not_picked';
$fulfillment_map = [
    'not_picked'   => ['label' => 'NOT PICKED', 'bg' => '#d9534f'],  // red
    'fully_picked' => ['label' => 'PACKED',     'bg' => '#28a745'],  // green
];
$fs = $fulfillment_map[ $fulfillment_raw ] ?? ['label' => strtoupper($fulfillment_raw ?: 'NOT PICKED'), 'bg' => '#999'];
?>

<!-- 🎯 PILL CONTAINER -->
<div style="display:flex; width: 100%; justify-content:flex-start; gap:1rem; margin-bottom:0.5rem;">
    <div style="display:flex; flex-wrap:wrap; gap:0.2rem; width:100%">

        <!-- 🔢 Daily Order Number -->
        <?php if ( $daily_number ): ?>
            <span style="background:#2d2; color:#fff; padding:5px 10px; border-radius:4px;">
                #<?php echo esc_html($daily_number); ?>
            </span>
        <?php endif; ?>

        <!-- 🆕 New Customer -->
        <?php if ( $is_new ): ?>
            <span style="background:blue; color:#fff; padding:5px 10px; border-radius:4px;">NEW</span>
        <?php endif; ?>

        <!-- 💳 Payment Status -->
        <?php echo $paymentrec_status; ?>

        <!-- 💳 Payment Method Pills -->
        <?php foreach ( $payment_methods as $label => $amount ) :
            if ( floatval($amount) > 0 ) :
                $code = AAA_OC_Map_Payment_Method::to_code($label); ?>
                <span style="background:#ce1c1c; color:#fff; padding:5px 10px; border-radius:4px;">
                    <?php echo esc_html($code); ?>
                </span>
        <?php endif; endforeach; ?>

        <?php if ( $cash_amount > 0 ) : ?>
            <span style="background:#ce1c1c; color:#fff; padding:5px 10px; border-radius:4px;">CASH</span>
        <?php endif; ?>

        <!-- 🛰️ Order Source -->
        <?php if ( $source_code ): ?>
            <span style="background:#444; color:#fff; padding:5px 10px; border-radius:4px;">
                <?php echo esc_html($source_code); ?>
            </span>
        <?php endif; ?>

        <!-- 📄 REC Upload -->
        <?php if ( $has_rec ): ?>
            <span style="background:purple; color:#fff; padding:5px 10px; border-radius:4px;">
                <a href="<?php echo esc_url($row->lkd_upload_med); ?>" style="color:#fff; text-decoration:none;" target="_blank">REC</a>
            </span>
        <?php endif; ?>

        <!-- 🎂 Birthday -->
        <?php if ( $has_bday ): ?>
            <span style="background:pink; color:#fff; padding:5px 10px; border-radius:4px;">BDAY</span>
        <?php endif; ?>

        <!-- ❗ Expired -->
        <?php if ( $has_expired ): ?>
            <span style="background:red; color:#fff; padding:5px 10px; border-radius:4px;">EXPIRED</span>
        <?php endif; ?>

        <!-- ⚠️ Warning -->
        <?php if ( $has_warn ): ?>
            <span style="background:red; color:#fff; padding:5px 10px; border-radius:4px;">WARNING</span>
        <?php endif; ?>

        <!-- 💬 Special -->
        <?php if ( $has_special ): ?>
            <span style="background:blue; color:#fff; padding:5px 10px; border-radius:4px;">SPECIAL</span>
        <?php endif; ?>

        <!-- 💰 Tip Pills -->
        <?php if ( (float)($row->aaa_oc_tip_total ?? 0) > 0 ) : ?>
            <span class="aaa-pill pill-tip-wt">W-TIP</span>
        <?php endif; ?>
        <?php if ( (float)($row->epayment_tip ?? 0) > 0 ) : ?>
            <span class="aaa-pill pill-tip-et">E-TIP</span>
        <?php endif; ?>

        <!-- 🎟 Coupon Codes -->
        <?php if ( ! empty($row->coupons) ) :
            $codes = explode(',', str_replace(['[',']','"'], '', (string)$row->coupons));
            foreach ($codes as $code) :
                $code = trim($code);
                if ( $code ): ?>
                    <span style="background:#008080; color:#fff; padding:5px 10px; border-radius:4px;">
                        <?php echo esc_html(strtoupper($code)); ?>
                    </span>
        <?php endif; endforeach; endif; ?>

        <!-- 💸 Cart Discount -->
        <?php if ( ! empty($row->_cart_discount) && floatval($row->_cart_discount) > 0 ): ?>
            <span style="background:#555; color:#fff; padding:5px 10px; border-radius:4px;">–%</span>
        <?php endif; ?>

        <!-- 🏦 Store Credit -->
        <?php if ( ! empty($row->_funds_used) && floatval($row->_funds_used) > 0 ): ?>
            <span style="background:#8B4513; color:#fff; padding:5px 10px; border-radius:4px;">SC</span>
        <?php endif; ?>

        <!-- 📦 Fulfillment -->
        <span style="background:<?php echo esc_attr($fs['bg']); ?>; color:#fff; padding:5px 10px; border-radius:4px;">
            <?php echo esc_html($fs['label']); ?>
        </span>
<?php
$envelope_flag = 0;
if ( is_array( $row ) && ! empty( $row['envelope_outstanding'] ) ) {
    $envelope_flag = (int) $row['envelope_outstanding'];
} elseif ( is_object( $row ) && ! empty( $row->envelope_outstanding ) ) {
    $envelope_flag = (int) $row->envelope_outstanding;
}
if ( $envelope_flag ) : ?>
<span class="dashicons dashicons-email aaa-oc-pill-envelope" title="Envelope Outstanding"></span>
<?php endif; ?>
    </div>
