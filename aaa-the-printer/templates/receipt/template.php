<?php
/**
 * Template logic for AAA The Printer — DOMPDF-safe, inline logo (data URI).
 * Paste-ready drop-in.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'aaa_lpm_get_order_receipt_html' ) ) {
function aaa_lpm_get_order_receipt_html( $order ) {
    // ===== Basic order data (prefer Shipping; fallback to Billing) =====
    $customer_name    = trim(
        ( $order->get_shipping_first_name() ?: $order->get_billing_first_name() )
        . ' ' .
        ( $order->get_shipping_last_name() ?: $order->get_billing_last_name() )
    );
    $customer_address = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
    $customer_phone   = $order->get_billing_phone(); // unchanged by request
    $order_number     = $order->get_order_number();
    $date_obj         = $order->get_date_created();
    $order_date       = $date_obj ? $date_obj->format('F j, Y') : '';
    $order_time       = $date_obj ? $date_obj->format('g:i A') : '';
    $subtotal         = wc_price( $order->get_subtotal() );
    $tip_raw          = (float) $order->get_meta( '_wpslash_tip' );
    $tip_display      = $tip_raw > 0 ? wc_price( $tip_raw ) : '';
    $payment_method   = $order->get_payment_method_title();
    $total            = wc_price( $order->get_total() );
    $customer_notes   = $order->get_customer_note();
    $shipping_city    = $order->get_shipping_city() ?: $order->get_billing_city();
    $source           = 'Online Order';
    $daily_order_num  = $order->get_meta( '_daily_order_number', true ) ?: $order_number;
    $raw_status       = $order->get_meta( 'aaa_oc_payment_status' );
    if ( empty( $raw_status ) ) { $raw_status = $order->get_status(); }
    $payment_status   = strtoupper( str_replace( '_', ' ', $raw_status ) );

    // ===== Delivery date/time (primary vs secondary) =====
    $delivery_date_meta = $order->get_meta( 'delivery_date' );
    $delivery_time_meta = $order->get_meta( 'delivery_time' );
    $delivery_date_alt  = $order->get_meta( '_lddfw_delivery_date' );
    $delivery_time_alt  = $order->get_meta( '_lddfw_delivery_time' );

    // Helpers
    $parse_date_pretty = function( $raw ) {
        if ( empty( $raw ) ) return '';
        if ( ctype_digit( (string) $raw ) ) return date( 'l F j, Y', (int) $raw );
        $ts = strtotime( $raw ); return $ts ? date( 'l F j, Y', $ts ) : $raw;
    };
    $format_time_12h = function( $raw ) use (&$format_time_12h) {
        if ( empty( $raw ) ) return '';
        if ( strpos( $raw, '-' ) !== false ) {
            list( $a, $b ) = array_map( 'trim', explode( '-', $raw, 2 ) );
            $A = $format_time_12h( $a ); $B = $format_time_12h( $b );
            return ( $A && $B ) ? "$A - $B" : $raw;
        }
        $ts = strtotime( $raw ); return $ts ? date( 'g:i A', $ts ) : $raw;
    };

    $delivery_date = $parse_date_pretty( $delivery_date_meta );
    $delivery_time = $format_time_12h( $delivery_time_meta );
    if ( ! empty( $delivery_date_alt ) ) $delivery_date = $parse_date_pretty( $delivery_date_alt );
    if ( ! empty( $delivery_time_alt ) ) $delivery_time = $format_time_12h( $delivery_time_alt );

    // ===== Discounts & user coupons only =====
    $discount_value = (float) $order->get_discount_total();
    $discount_display = $discount_value > 0 ? '-' . wc_price( $discount_value ) : '';
    $user_coupons = [];
    foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
        if ( $coupon_item->get_meta( '_admin_applied' ) ) continue;
        $user_coupons[] = $coupon_item->get_code();
    }
    $coupon_line = $user_coupons ? implode( ', ', $user_coupons ) : '';

    // ===== Inline logo (data URI) =====
    $inline_logo = function( $url ) {
        $u = wp_upload_dir(); if ( empty( $u['basedir'] ) || empty( $u['baseurl'] ) ) return esc_url( $url );
        $baseurl = rtrim( $u['baseurl'], '/' ); $basedir = rtrim( $u['basedir'], DIRECTORY_SEPARATOR );
        if ( strpos( $url, $baseurl ) !== 0 ) return esc_url( $url );
        $rel = ltrim( substr( $url, strlen( $baseurl ) ), '/' );
        $path = $basedir . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
        if ( ! is_readable( $path ) ) return esc_url( $url );
        $mime = wp_check_filetype( $path )['type'] ?: 'image/png';
        $data = @file_get_contents( $path ); if ( $data === false ) return esc_url( $url );
        return 'data:' . esc_attr( $mime ) . ';base64,' . base64_encode( $data );
    };
    $site_id  = get_current_blog_id();
    $logo_url = ( $site_id == 9 )
        ? 'https://lokeydelivery.com/wp-content/uploads/sites/9/2024/10/Label_Logo-e1729637401233.png'
        : ( $site_id == 13
            ? 'https://35setup.lokey.delivery/wp-content/uploads/sites/13/2025/02/35CAP-logo.png'
            : 'https://example.com/path/to/default-logo.png' );
    $logo_src = $inline_logo( $logo_url );

    // ===== Build HTML =====
    ob_start(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt</title></head>
<body style="margin:0;padding:0;font-family:Arial, sans-serif;width:80mm;">
  <div style="text-align:center;margin-bottom:5px;position:relative;">
    <img src="<?php echo esc_attr( $logo_src ); ?>" alt="Logo" style="width:100px;height:auto;">
    <div style="position:absolute;top:0;right:0;background:#000;color:#fff;font-size:12px;font-weight:bold;padding:2px 6px;border-radius:3px;">
      <?php echo esc_html( $daily_order_num ); ?>
    </div>
  </div>

  <div style="border:4px dotted #000;padding:10px;margin:5px;text-align:center;font-size:14px;line-height:1.2;">
    <div style="white-space:nowrap;"><?php echo esc_html( '#'.$order_number.' - '.$customer_name ); ?></div>
    <div style="white-space:nowrap;"><?php echo esc_html( $customer_address ); ?></div>
    <div style="white-space:nowrap;"><?php echo esc_html( $shipping_city ); ?></div>
    <div style="white-space:nowrap;font-size:16px;font-weight:bold;">
      <?php $d=preg_replace('/\D/','',$customer_phone);
      echo esc_html( strlen($d)===10 ? '('.substr($d,0,3).') '.substr($d,3,3).'-'.substr($d,6) : $customer_phone ); ?>
    </div>
  </div>

  <div style="padding:0 10px;font-size:11px;line-height:1.4;margin-bottom:5px;">
    <strong><?php echo esc_html( $source ); ?></strong><br>
    Order Date: <?php echo esc_html( $order_date ); ?><br>
    Order Time: <?php echo esc_html( $order_time ); ?><br>
    <?php if ( $delivery_date || $delivery_time ): ?>
      Delivery Date: <?php echo esc_html( $delivery_date ); ?><br>
      Delivery Time: <?php echo esc_html( $delivery_time ); ?>
    <?php endif; ?>
  </div>

  <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
    <thead><tr style="border-bottom:1px solid #000;">
      <th style="text-align:left;padding:5px;font-size:10px;">Items</th>
      <th style="text-align:right;padding:5px;font-size:10px;"></th>
      <th style="text-align:right;padding:5px;font-size:10px;"></th>
      <th style="text-align:right;padding:5px;font-size:10px;"></th>
    </tr></thead>
    <tbody>
    <?php foreach ( $order->get_items() as $item ): ?>
      <?php
        $product = $item->get_product();
        $product_name = $item->get_name();
        $brand_name = '';
        if ( $product && $product->get_id() ) {
            $terms = get_the_terms( $product->get_id(), 'berocket_brand' );
            if ( $terms && ! is_wp_error( $terms ) ) $brand_name = implode(', ', wp_list_pluck($terms,'name'));
        }
        $qty  = (int) $item->get_quantity();
        $unit = $qty ? wc_price( $item->get_total() / $qty ) : wc_price( 0 );
        $line = wc_price( $item->get_total() );
      ?>
      <tr><td colspan="4" style="padding:5px;font-size:16px;">
        <?php echo esc_html( $product_name ); ?><br>
        <span style="font-size:12px;color:#555;">Brand: <?php echo esc_html( $brand_name ); ?></span>
      </td></tr>
      <tr style="border-bottom:1px solid #ccc;">
        <td style="padding:5px;font-size:10px;"></td>
        <td style="text-align:right;padding:5px;font-size:10px;">Qty: <?php echo esc_html( $qty ); ?></td>
        <td style="text-align:right;padding:5px;font-size:10px;white-space:nowrap;">@ <?php echo wp_kses_post( $unit ); ?></td>
        <td style="text-align:right;padding:5px;font-size:10px;white-space:nowrap;"><?php echo wp_kses_post( $line ); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div style="padding:10px;font-size:10px;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td>Subtotal</td><td style="text-align:right;"><?php echo wp_kses_post( $subtotal ); ?></td></tr>
      <?php if ( $discount_value > 0 ): ?><tr><td>Discount</td><td style="text-align:right;"><?php echo wp_kses_post( $discount_display ); ?></td></tr><?php endif; ?>
      <?php if ( $tip_raw > 0 ): ?><tr><td>Tip</td><td style="text-align:right;"><?php echo wp_kses_post( $tip_display ); ?></td></tr><?php endif; ?>
      <tr style="font-weight:bold;border-top:1px solid #000;"><td style="padding-top:5px;">Total</td><td style="text-align:right;padding-top:5px;"><?php echo wp_kses_post( $total ); ?></td></tr>
    </table>
  </div>

  <?php
  $coupon_items = $order->get_items('coupon'); $visible = [];
  foreach ( $coupon_items as $ci ) {
      $code = $ci->get_code(); if ( function_exists('aaa_coupon_is_hidden_on_receipt') && aaa_coupon_is_hidden_on_receipt($code) ) continue;
      $amount_display = wc_price( (float) $ci->get_discount() * -1, ['currency'=>$order->get_currency()] );
      $coupon_obj = new WC_Coupon( $code ); $label = get_the_title( $coupon_obj->get_id() );
      $visible[] = sprintf('Coupon: %s – %s (%s)', esc_html(strtoupper($code)), esc_html($label), $amount_display);
  }
  if ( $visible ): ?>
    <div style="border:1px dotted #000;padding:8px;margin:10px 0;font-size:10px;"><strong>Coupon(s) Applied:</strong><br><?php echo implode('<br>',$visible); ?></div>
  <?php endif; ?>

  <hr style="border:0;border-top:3px solid #000;margin:10px 0;">
  <div style="border:1px dotted #000;padding:10px;margin:5px;font-size:14px;">Notes: <?php echo esc_html( $customer_notes ); ?></div>

  <div style="background:#000;color:#fff;font-size:18px;padding:6px 10px;display:flex;justify-content:space-between;font-weight:bold;">
    <div><?php echo esc_html( $payment_method ); ?></div>
    <div><?php echo esc_html( $payment_status ); ?></div>
  </div>

  <?php
  $site_id = get_current_blog_id();
  if ( $site_id == 9 ) { $returns_text = 'Returns are only accepted for defective electronics, vape cartridges, and vape accessories.<br>For full policy or issues, contact <strong>(714) 499-1306</strong> or <strong>lokeydelivery.com/support</strong>'; }
  elseif ( $site_id == 13 ) { $returns_text = 'Returns are only accepted for defective electronics, vape cartridges, and vape accessories.<br>For full policy or issues, contact <strong>(909) 688-3117</strong> or <strong>35cap.com/contact-us/</strong>'; }
  else { $returns_text = ''; }
  if ( $returns_text ): $order_id = $order->get_id(); ?>
    <div style="border-top:2px solid #000;margin-top:10px;padding-top:8px;font-size:11px;">
      <div style="font-weight:bold;margin-bottom:4px;">Returns</div>
      <div style="line-height:1.4;"><?php echo $returns_text; ?></div>
      <div style="display:flex;justify-content:center;align-items:center;gap:20px;margin-top:6px;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($order_id.'|payment'); ?>" alt="Payment QR" style="width:80px;height:80px;">
      </div>
    </div>
  <?php endif; ?>
</body></html>
<?php
    return ob_get_clean();
}}
