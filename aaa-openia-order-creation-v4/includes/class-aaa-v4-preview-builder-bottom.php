<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-preview-builder-bottom.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Preview_Builder_Bottom {

    public static function render_bottom( $parsed_data ) {
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $settings = get_option( 'aaa_v4_order_creator_settings', [] );
        $show_billing = empty( $settings['hide_billing_display'] );

        // Ensure we know customer_id
        $customer_id = $parsed_data['customer_id'] ?? 0;

        // Hydrate parsed_data with meta values
        if ( $customer_id ) {
            // Shipping
            $parsed_data['shipping_address_1'] = get_user_meta( $customer_id, 'shipping_address_1', true );
            $parsed_data['shipping_address_2'] = get_user_meta( $customer_id, 'shipping_address_2', true );
            $parsed_data['shipping_city']      = get_user_meta( $customer_id, 'shipping_city', true );
            $parsed_data['shipping_state']     = get_user_meta( $customer_id, 'shipping_state', true );
            $parsed_data['shipping_postcode']  = get_user_meta( $customer_id, 'shipping_postcode', true );
            $parsed_data['shipping_country']   = get_user_meta( $customer_id, 'shipping_country', true );

            // Billing
            $parsed_data['billing_address_1']  = get_user_meta( $customer_id, 'billing_address_1', true );
            $parsed_data['billing_address_2']  = get_user_meta( $customer_id, 'billing_address_2', true );
            $parsed_data['billing_city']       = get_user_meta( $customer_id, 'billing_city', true );
            $parsed_data['billing_state']      = get_user_meta( $customer_id, 'billing_state', true );
            $parsed_data['billing_postcode']   = get_user_meta( $customer_id, 'billing_postcode', true );
            $parsed_data['billing_country']    = get_user_meta( $customer_id, 'billing_country', true );
        }

        // Check if this is an existing customer (set via preview-top)
        $is_existing = isset( $parsed_data['customer_status'] ) && stripos( $parsed_data['customer_status'], 'existing' ) !== false;

        if ( $is_existing && $show_billing ) {
            echo '<h3>Billing Address (unchanged)</h3>';
            echo '<p><em>This is shown for reference only. It will not be modified.</em></p>';
            echo '<div style="padding: 10px; border: 1px solid #ccc; background: #f9f9f9; margin-bottom: 20px;">';
            echo esc_html( $parsed_data['billing_address_1'] );
            if ( ! empty( $parsed_data['billing_address_2'] ) ) {
                echo ', ' . esc_html( $parsed_data['billing_address_2'] );
            }
            echo '<br>' . esc_html( $parsed_data['billing_city'] . ', ' . $parsed_data['billing_state'] . ' ' . $parsed_data['billing_postcode'] );
            echo '</div>';
        }

        ?>
<div class="aaa-v4-two-col">

  <!-- LEFT COLUMN -->
  <div class="aaa-v4-two-col-left">

    <!-- Row 1: Billing + Shipping On File -->
    <div class="aaa-v4-address-blocks">

      <!-- Billing Block -->
      <div class="aaa-v4-address-box">
        <strong>Billing Address on File:</strong><br>
        <div id="billing-address-block"
             data-address='<?php echo json_encode([
               "address_1" => get_user_meta($customer_id, 'billing_address_1', true) ?: ($parsed_data['billing_address_1'] ?? ''),
               "address_2" => get_user_meta($customer_id, 'billing_address_2', true) ?: ($parsed_data['billing_address_2'] ?? ''),
               "city"      => get_user_meta($customer_id, 'billing_city', true)      ?: ($parsed_data['billing_city'] ?? ''),
               "state"     => get_user_meta($customer_id, 'billing_state', true)     ?: ($parsed_data['billing_state'] ?? ''),
               "postcode"  => get_user_meta($customer_id, 'billing_postcode', true)  ?: ($parsed_data['billing_postcode'] ?? ''),
               "country"   => get_user_meta($customer_id, 'billing_country', true)   ?: ($parsed_data['billing_country'] ?? 'US'),
               "lat"       => get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/latitude", true) ?: '',
               "lng"       => get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/longitude", true) ?: '',
               "verified"  => get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/coords-verified", true) ?: 'no',
             ]); ?>'>
          <?php
          $lat_b = get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/latitude", true);
          $lng_b = get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/longitude", true);
          $verified_b = get_user_meta($customer_id, "_wc_billing/aaa-delivery-blocks/coords-verified", true) ?: 'no';

          if ( ! empty( $parsed_data['billing_address_1'] ) ) {
              echo esc_html( $parsed_data['billing_address_1'] );
              if ( ! empty( $parsed_data['billing_address_2'] ) ) {
                  echo ', ' . esc_html( $parsed_data['billing_address_2'] );
              }
              echo '<br>' . esc_html( $parsed_data['billing_city'] . ', ' . $parsed_data['billing_state'] . ' ' . $parsed_data['billing_postcode'] );
          } else {
              echo '<em>No billing address on file</em>';
          }
          ?>
          <div style="font-size:0.9em; margin-top:4px; color:#555;">
            Lat: <?php echo esc_html($lat_b ?: '-'); ?> |
            Lng: <?php echo esc_html($lng_b ?: '-'); ?><br>
            Verified: <?php echo esc_html($verified_b); ?>
          </div>
        </div>
        <button type="button" class="button-modern" id="load-billing-address">Load Billing</button>
      </div>

      <!-- Shipping Block -->
      <div class="aaa-v4-address-box">
        <strong>Shipping Address on File:</strong><br>
        <div id="shipping-address-block"
             data-address='<?php echo json_encode([
               "address_1" => get_user_meta($customer_id, 'shipping_address_1', true) ?: ($parsed_data['shipping_address_1'] ?? ''),
               "address_2" => get_user_meta($customer_id, 'shipping_address_2', true) ?: ($parsed_data['shipping_address_2'] ?? ''),
               "city"      => get_user_meta($customer_id, 'shipping_city', true)      ?: ($parsed_data['shipping_city'] ?? ''),
               "state"     => get_user_meta($customer_id, 'shipping_state', true)     ?: ($parsed_data['shipping_state'] ?? ''),
               "postcode"  => get_user_meta($customer_id, 'shipping_postcode', true)  ?: ($parsed_data['shipping_postcode'] ?? ''),
               "country"   => get_user_meta($customer_id, 'shipping_country', true)   ?: ($parsed_data['shipping_country'] ?? 'US'),
               "lat"       => get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/latitude", true) ?: '',
               "lng"       => get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/longitude", true) ?: '',
               "verified"  => get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/coords-verified", true) ?: 'no',
             ]); ?>'>
          <?php
          $lat_s = get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/latitude", true);
          $lng_s = get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/longitude", true);
          $verified_s = get_user_meta($customer_id, "_wc_shipping/aaa-delivery-blocks/coords-verified", true) ?: 'no';

          if ( ! empty( $parsed_data['shipping_address_1'] ) ) {
              echo esc_html( $parsed_data['shipping_address_1'] );
              if ( ! empty( $parsed_data['shipping_address_2'] ) ) {
                  echo ', ' . esc_html( $parsed_data['shipping_address_2'] );
              }
              echo '<br>' . esc_html( $parsed_data['shipping_city'] . ', ' . $parsed_data['shipping_state'] . ' ' . $parsed_data['shipping_postcode'] );
          } else {
              echo '<em>No shipping address on file</em>';
          }
          ?>
          <div style="font-size:0.9em; margin-top:4px; color:#555;">
            Lat: <?php echo esc_html($lat_s ?: '-'); ?> |
            Lng: <?php echo esc_html($lng_s ?: '-'); ?><br>
            Verified: <?php echo esc_html($verified_s); ?>
          </div>
        </div>
        <button type="button" class="button-modern" id="load-shipping-address">Load Shipping</button>
      </div>

    </div><!-- /Row 1 -->

    <!-- Row 2: Editable Fields -->
    <div class="aaa-v4-shipping-fields">

        <p><strong>Address 1:</strong><br>
        <input type="text" 
               name="shipping_address_1" 
               id="shipping_address_1" 
               style="width:70%;" 
               value="<?php echo esc_attr($parsed_data['shipping_address_1'] ?? ''); ?>"></p>

        <p><strong>Address 2:</strong><br>
        <input type="text" 
               name="shipping_address_2" 
               id="shipping_address_2" 
               style="width:70%;" 
               value="<?php echo esc_attr($parsed_data['shipping_address_2'] ?? ''); ?>"></p>

        <p><strong>City:</strong><br>
        <input type="text" 
               name="shipping_city" 
               id="shipping_city" 
               style="width:50%;" 
               value="<?php echo esc_attr($parsed_data['shipping_city'] ?? ''); ?>"></p>

        <p><strong>State:</strong><br>
        <input type="text" 
               name="shipping_state" 
               id="shipping_state" 
               style="width:30%;" 
               value="<?php echo esc_attr($parsed_data['shipping_state'] ?? ''); ?>"></p>

        <p><strong>Postcode:</strong><br>
        <input type="text" 
               name="shipping_postcode" 
               id="shipping_postcode" 
               style="width:30%;" 
               value="<?php echo esc_attr($parsed_data['shipping_postcode'] ?? ''); ?>"></p>

        <p><strong>Country:</strong><br>
        <input type="text" 
               name="shipping_country" 
               id="shipping_country" 
               style="width:30%;" 
               value="<?php echo esc_attr($parsed_data['shipping_country'] ?? 'US'); ?>"></p>

        <!-- Verification hidden fields -->
        <input type="hidden" name="aaa_oc_latitude" id="aaa_oc_latitude" value="<?php echo esc_attr(get_user_meta($customer_id, '_wc_shipping/aaa-delivery-blocks/latitude', true)); ?>">
        <input type="hidden" name="aaa_oc_longitude" id="aaa_oc_longitude" value="<?php echo esc_attr(get_user_meta($customer_id, '_wc_shipping/aaa-delivery-blocks/longitude', true)); ?>">
        <input type="hidden" name="aaa_oc_coords_verified" id="aaa_oc_coords_verified" value="<?php echo esc_attr(get_user_meta($customer_id, '_wc_shipping/aaa-delivery-blocks/coords-verified', true) ?: 'no'); ?>">

        <p><strong>Verified:</strong> 
            <span id="coords-status">
                <?php echo esc_html(get_user_meta($customer_id, '_wc_shipping/aaa-delivery-blocks/coords-verified', true) ?: 'no'); ?>
            </span>
        </p>

    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="aaa-v4-two-col-right">

	<h3>Delivery Method</h3>
	<p><select name="shipping_method" style="width:50%;" required>
	    <option value="">-- Select a Shipping Method --</option>
	    <?php
	    // collect shipping methods from all zones (custom zones + rest of the world)
	    $all_methods = [];

	    // 1) custom zones
	    $zones = WC_Shipping_Zones::get_zones();
	    foreach ( $zones as $zone ) {
	        if ( ! empty( $zone['shipping_methods'] ) && is_array( $zone['shipping_methods'] ) ) {
	            foreach ( $zone['shipping_methods'] as $method ) {
	                if ( isset( $method->enabled ) && $method->enabled === 'yes' ) {
			    $value = $method->id . ':' . $method->instance_id;
	                    $label = $method->get_title();
	                    $all_methods[ $value ] = $label;
	                }
	            }
	        }
	    }

	    // 2) rest of the world (zone 0)
	    $zone0 = new WC_Shipping_Zone( 0 );
	    foreach ( $zone0->get_shipping_methods() as $method ) {
	        if ( isset( $method->enabled ) && $method->enabled === 'yes' ) {
	            $value = $method->id . ':' . $method->instance_id;
	            $label = $method->get_title() . ' (' . $value . ')';
	            $all_methods[ $value ] = $label;
	        }
	    }

	    // render options, default to free_shipping:11 if present
	    if ( empty( $all_methods ) ) {
	        echo '<option value="" disabled>(No shipping methods found)</option>';
	    } else {
	        foreach ( $all_methods as $value => $label ) {
	            $selected = ( $value === 'free_shipping:11' ) ? 'selected' : '';
	            echo '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
	        }
	    }
	    ?>
	</select></p>

	<h3>Payment Method</h3>
	<p><select name="payment_method" style="width:50%;" required>
	    <option value="">-- Select Payment Method --</option>
	    <?php
	    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
	    foreach ( $gateways as $gateway_id => $gateway ) {
	    	echo '<option value="' . esc_attr( $gateway_id ) . '">' . esc_html( $gateway->get_title() ) . '</option>';
	    }
	    ?>
	</select></p>

<h3>Delivery Date &amp; Time</h3>

<p><strong>Delivery date:</strong><br>
    <input type="date"
           name="aaa_delivery_date"
           value="<?php echo esc_attr($parsed_data['aaa_delivery_date'] ?? date_i18n('Y-m-d')); ?>"
           style="width:30%;">
</p>

<p><strong>From time:</strong><br>
  <select name="aaa_delivery_time_from" style="width:30%;">
    <?php
      $start = strtotime('10:00');
      $end   = strtotime('22:00');
      for ($t = $start; $t <= $end; $t += 15 * 60) {
          $label = date('g:i A', $t);
          $value = date('H:i', $t);
          echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
      }
    ?>
  </select>
</p>

<p><strong>To time:</strong><br>
  <select name="aaa_delivery_time_to" style="width:30%;">
    <?php
      $start = strtotime('10:00');
      $end   = strtotime('22:00');
      for ($t = $start; $t <= $end; $t += 15 * 60) {
          $label = date('g:i A', $t);
          $value = date('H:i', $t);
          echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
      }
    ?>
  </select>
</p>

        <h3>Order Notes</h3>
        <textarea name="order_notes" rows="3" style="width:100%;"><?php echo esc_textarea($parsed_data['order_notes'] ?? ''); ?></textarea>

  </div><!-- /RIGHT -->

</div><!-- /TWO COL -->


<h3>Products</h3>
<table id="aaa-product-table" style="width:100%; border-collapse:collapse;" border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Image</th>
            <th>Product Name</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Special Price</th>
            <th>Inventory</th>
            <th>Line Total</th>
            <th>Product ID</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $parsed_data['products'] as $index => $product_info ) :
        $product_name = esc_attr( $product_info['product_name'] );
        $qty          = (int) $product_info['quantity'];
        $product_id   = AAA_V4_Product_Matcher::find_product_id( $product_name );
        $unit_price   = '';
        $stock        = '';
        $note         = [];

        // get image
        $image_url = $product_id ? get_the_post_thumbnail_url( $product_id, 'thumbnail' ) : '';

        // if matched, fetch product data
        if ( $product_id ) {
            $product    = wc_get_product( $product_id );
            if ( $product ) {
                $stock      = $product->get_stock_quantity();
                $unit_price = $product->get_price();
            }
            $note[] = 'Matched';
        } else {
            $note[] = 'No match';
        }

        // determine row background
        $row_style = '';
        if ( $product_id && $stock <= 0 ) {
            // out of stock or negative
            $row_style = 'background-color:#f8d7da;';
        } elseif ( ! $product_id ) {
            // no match
            $row_style = 'background:#fff3cd;';
        }
        ?>
        <tr class="product-line" style="<?php echo esc_attr( $row_style ); ?>">
            <td class="product-image">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:60px; max-height:60px; cursor:pointer;">
                <?php endif; ?>
            </td>
            <td>
                <input
                    type="text"
                    class="product-name-input"
                    name="products[<?php echo $index; ?>][product_name]"
                    value="<?php echo $product_name; ?>"
                    style="width:100%;">
            </td>
            <td>
                <input
                    type="number"
                    class="product-qty-input"
                    name="products[<?php echo $index; ?>][quantity]"
                    value="<?php echo esc_attr( $qty ); ?>"
                    style="width:60px;">
            </td>
            <td>
                <input
                    type="text"
                    class="product-price-input"
                    name="products[<?php echo $index; ?>][unit_price]"
                    value="<?php echo esc_attr( $unit_price ); ?>"
                    style="width:80px;"
                    readonly
                    data-original="<?php echo esc_attr( $unit_price ); ?>">
            </td>
            <td>
                <input
                    type="text"
                    class="product-special-input"
                    name="products[<?php echo $index; ?>][special_price]"
                    value=""
                    style="width:80px;">
            </td>
            <td class="product-stock"><?php echo esc_html( $stock ); ?></td>
            <td class="line-total">-</td>
            <td>
                <input
                    type="text"
                    name="products[<?php echo $index; ?>][product_id]"
                    value="<?php echo esc_attr( $product_id ); ?>"
                    style="width:80px;">
            </td>
            <td class="product-note"><?php echo esc_html( implode( ', ', $note ) ); ?></td>
            <td>
                <button type="button" class="line-discount-percent button-modern">[%]</button>
                <button type="button" class="line-discount-fixed button-modern">[$]</button>
                <button type="button" class="remove-line-discount button-modern">XX</button>
                <button type="button" class="remove-product button-modern">Remove</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
        <p><button type="button" id="add-product-line" class="button-modern">+ Add Product Line</button></p>

        <!-- Discount Panel -->
        <h3>Apply Cart Discount</h3>
        <div id="aaa-v4-discount-panel" style="margin-bottom:10px;">
            <button type="button" class="button-modern discount-btn" data-discount="10">10% Off</button>
            <button type="button" class="button-modern discount-btn" data-discount="20">20% Off</button>
            <button type="button" class="button-modern discount-btn" data-discount="30">30% Off</button>
            <button type="button" class="button-modern discount-btn" data-discount="40">40% Off</button>
            <button type="button" class="button-modern" id="target-total-btn">Target Total</button>
            <button type="button" class="button-modern" id="custom-percent-btn">Custom %</button>
            <button type="button" class="button-modern" id="custom-fixed-btn">Fixed $</button>
            <button type="button" id="remove-cart-discount" class="button-modern">Remove Cart Discount</button>
        </div>

        <!-- Totals Summary -->
        <div id="aaa-cart-summary" style="background:#f7f7f7; border:1px solid #ddd; padding:10px; margin-bottom:20px;">
            <strong>Cart Totals:</strong><br>
            <span>Items: <span id="aaa-total-items">0</span></span><br>
            <span>Subtotal: $<span id="aaa-subtotal">0.00</span></span><br>
            <span>Discount: $<span id="aaa-discount-amount">0.00</span></span><br>
            <span><strong>Total: $<span id="aaa-total">0.00</span></strong></span>
        </div>

        <!-- Hidden fields for cart discounts -->
        <input type="hidden" name="cart_discount_percent" id="aaa-cart-discount" value="">
        <input type="hidden" name="cart_discount_fixed" id="aaa-cart-fixed" value="">
        <input type="hidden" name="coupon_code" value="">


        <h3>Coupon Code (Optional)</h3>
        <p>
            <select id="aaa-v4-coupon-code" style="width:50%;">
                <option value="">-- Select Coupon --</option>
                <?php
                $coupons = get_posts([
                    'post_type'      => 'shop_coupon',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish'
                ]);
                foreach ( $coupons as $coupon_post ) {
                    $coupon = new WC_Coupon( $coupon_post->post_name );
                    echo '<option value="' . esc_attr( $coupon_post->post_name ) . '">'
                        . esc_html( $coupon->get_code() . ' - ' . $coupon->get_description() )
                        . '</option>';
                }
                ?>
            </select>
            <button type="button" id="aaa-v4-apply-coupon" class="button-modern">Apply Promo</button>
        </p>
        <div id="aaa-v4-coupon-message" style="margin-top:5px;"></div>

        <h3>Internal Order Note</h3>
        <p>
            <textarea name="internal_order_note" rows="3" style="width:100%;"></textarea>
            <br><em>This note is for internal use only. Customers will not see it.</em>
        </p>

        <h3>Notifications</h3>
        <p>
            <input type="checkbox" name="send_order_confirmation" value="1"> Send Order Confirmation Email<br>
            <input type="checkbox" name="send_account_email" value="1"> Send New Account Setup Email<br>
            <input type="checkbox" name="send_payment_request" value="1"> Send Payment Request Email
        </p>

        <p><input type="checkbox" name="confirm_backorders" required>
            I acknowledge that I have checked all items in this order and confirmed they match the original order. 
            Any products with low stock have been verified in inventory.
        </p>

        <?php if ( ! $customer_id ) : ?>
            <p style="margin-top: 1rem;">
              <label>
                <input 
                  type="checkbox" 
                  name="verify_ftp_id" 
                  value="1" 
                  id="verify_ftp_id"
                >
                I have verified this account by checking the identification card on file, 
                and I have included the identification card in this order.
              </label>
            </p>
        <?php endif; ?>

        <p><input type="submit" class="button-modern button-primary" value="Create WooCommerce Order"></p>

        </form>
        <?php
    }
}
