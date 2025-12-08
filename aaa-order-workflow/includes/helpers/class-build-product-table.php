<?php
/**
 * 
 * FilePath: plugins\aaa-order-workflow\includes\helpers\class-build-product-table.php
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Build_Product_Table {

public static function render( $items, $currency, $picked_json = '', $order_status = 'processing', $fulfillment_status = 'not_picked' ) {
    // Normalize picked_json into a SKU => picked map
    $skuToPicked = [];
    $pickedData  = [];

    if ( is_string( $picked_json ) && $picked_json !== '' ) {
        $decoded = json_decode( $picked_json, true );
        if ( is_array( $decoded ) ) {
            // Case A: array-of-objects [{sku, picked, max}, ...]
            $looksLikeRows = isset($decoded[0]) && is_array($decoded[0]) && array_key_exists('sku', $decoded[0]);
            if ( $looksLikeRows ) {
                foreach ( $decoded as $row ) {
                    $sku    = isset( $row['sku'] ) ? (string) $row['sku'] : '';
                    $picked = isset( $row['picked'] ) ? (int) $row['picked'] : 0;
                    if ( $sku !== '' ) {
                        $skuToPicked[ $sku ] = $picked;
                    }
                }
                $pickedData = $decoded; // keep original for the "Fully Picked" banner condition
            } else {
                // Case B: map/object {"SKU123":1,"SKU456":2}
                foreach ( $decoded as $sku => $qty ) {
                    if ( is_string($sku) ) {
                        $skuToPicked[ $sku ] = (int) $qty;
                    }
                }
                // Build a synthetic array-of-objects so header logic still works
                $pickedData = [];
                foreach ( $skuToPicked as $sku => $qty ) {
                    $pickedData[] = ['sku' => $sku, 'picked' => $qty, 'max' => 0];
                }
            }
        }
    }

    ob_start();
    // If fulfillment is locked, show a header.
    if ( $fulfillment_status === 'fully_picked' && ! empty( $pickedData ) ) {
        echo '<div class="pick-status" style="padding:0.5rem; font-weight:bold;">Pick status: Fully Picked</div>';
    }
    ?>
    <!-- keep the rest of your existing table code as-is -->
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;">Qty</th>
                    <th style="text-align:left;">Product</th>
                    <th style="text-align:right;">Unit Price</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Picked</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ( is_array($items) && ! empty($items) ) {
                foreach ( $items as $itm ) {
                    $qty    = (int) ( $itm['quantity'] ?? 0 );
                    $name   = $itm['name']  ?? '(no name)';
                    $brand  = $itm['brand'] ?? '';
                    $sku    = $itm['sku']   ?? '';
                    
                    // First try to get picked status by SKU, then fall back to lkd_wm_new_sku
                    $picked = 0;
                    if (isset($skuToPicked[$sku])) {
                        $picked = $skuToPicked[$sku];
                    } else {
                        // If not found by SKU, try to find by lkd_wm_new_sku
                        $product_id = $itm['product_id'] ?? 0;
                        if ($product_id) {
                            $new_sku = get_post_meta($product_id, 'lkd_wm_new_sku', true);
                            if ($new_sku && isset($skuToPicked[$new_sku])) {
                                $picked = $skuToPicked[$new_sku];
                            }
                        }
                    }

                    $unit_price = ( $qty > 0 ) ? ((float)$itm['subtotal'] / $qty) : 0;
                    $fmt_unit   = wc_price( $unit_price, [ 'currency' => $currency ] );
                    $sold       = (float) ( $itm['total'] ?? 0 );
                    $fmt_sold   = wc_price( $sold, [ 'currency' => $currency ] );

                    $rowStyle = '';
                    if ( $picked >= $qty && $qty > 0 ) {
                        $rowStyle = 'background-color:#a9fca9;';
                    } elseif ( $picked > 0 ) {
                        $rowStyle = 'background-color:#ffffc7;';
                    }
                    ?>
                    <?php
                    // Get the lkd_wm_new_sku if it exists
                    $new_sku = '';
                    $product_id = $itm['product_id'] ?? 0;
                    
                    if ($product_id) {
                        // Get all meta for the product
                        $all_meta = get_post_meta($product_id, 'lkd_wm_new_sku', false);
                        
                        // If we have multiple values, use the first non-empty one
                        if (is_array($all_meta)) {
                            foreach ($all_meta as $meta_value) {
                                if (!empty($meta_value)) {
                                    $new_sku = $meta_value;
                                    break;
                                }
                            }
                        } else {
                            // Fallback to single value
                            $new_sku = get_post_meta($product_id, 'lkd_wm_new_sku', true);
                        }
                    }
                    ?>
                    <tr class="picked-status"
                        style="border-top:1px solid #ddd; <?php echo esc_attr($rowStyle); ?>"
                        data-sku="<?php echo esc_attr($sku); ?>"
                        data-original-sku="<?php echo esc_attr($sku); ?>"
                        data-new-sku="<?php echo esc_attr($new_sku); ?>"
                        data-max="<?php echo (int)$qty; ?>"
                    >
                        <td style="padding:4px;"><?php echo (int)$qty; ?></td>
                        <td style="padding:4px;">
                            <?php
                            echo esc_html($name);
                            if ( $brand ) {
                                echo '<br><em style="font-size:0.85em;">' . esc_html($brand) . '</em>';
                            }
                            if ( $sku ) {
                                echo '<br><small style="font-size:0.8em;">SKU: ' . esc_html($sku) . '</small>';
                            }
                            // Always show New SKU line, with 'No new SKU' text if empty
                            echo '<br><small style="font-size:0.8em;">New SKU: ' . (!empty($new_sku) ? esc_html($new_sku) : 'No new SKU') . '</small>';
                            ?>
                        </td>
                        <td style="padding:4px; text-align:right;"><?php echo wp_kses_post($fmt_unit); ?></td>
                        <td style="padding:4px; text-align:right;"><strong><?php echo wp_kses_post($fmt_sold); ?></strong></td>
                        <td style="text-align:center;">
                        <?php if ( $order_status === 'processing' && $fulfillment_status === 'not_picked' ) : ?>
                            <button class="decrement-picked" data-sku="<?php echo esc_attr($sku); ?>">-</button>
                        <?php endif; ?>
                            <span class="picked-count"><?php echo (int)$picked; ?></span>
                            <span class="picked-text"> of <?php echo (int)$qty; ?> Picked</span>
                        <?php if ( $order_status === 'processing' && $fulfillment_status === 'not_picked' ) : ?>
                            <button class="increment-picked" data-sku="<?php echo esc_attr($sku); ?>">+</button>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="5"><em>No items</em></td></tr>';
            }
            ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}
