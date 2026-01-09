<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds a "Picked" column to the WooCommerce Edit Order items table.
 * Reads from _aaa_picked_items order meta (array map of SKU/new_sku => picked qty).
 *
 * Hooks used (core template):
 * - woocommerce_admin_order_item_headers   → add <th>               (see WC html-order-items.php)
 * - woocommerce_admin_order_item_values    → add a <td> per row     (same template)
 */
class AAA_OC_Admin_Fulfillment_Column {

    public static function init() {
        add_action( 'woocommerce_admin_order_item_headers', [ __CLASS__, 'add_header' ], 20 );
        add_action( 'woocommerce_admin_order_item_values',  [ __CLASS__, 'add_value'  ], 20, 3 );
        add_action( 'admin_enqueue_scripts',                [ __CLASS__, 'styles'     ] );
    }

    public static function styles() {
        $css = '.aaa-oc-picked-col{ text-align:center; }
                .aaa-oc-picked-badge{ display:inline-block; min-width:60px; padding:2px 6px;
                   border-radius:12px; color:#fff; font-weight:600; }';
        wp_add_inline_style( 'woocommerce_admin_styles', $css );
    }

    public static function add_header( $order ) {
        echo '<th class="aaa-oc-picked-col">' . esc_html__( 'Picked', 'aaa-oc' ) . '</th>';
    }

    /**
     * @param WC_Product|null $product
     * @param WC_Order_Item   $item
     * @param int             $item_id
     */
    public static function add_value( $product, $item, $item_id ) {
        // Keep table structure for non-product rows (shipping/fee/etc).
        if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
            echo '<td class="aaa-oc-picked-col">&nbsp;</td>';
            return;
        }

        // Resolve order + product
        $order_id    = method_exists( $item, 'get_order_id' ) ? (int) $item->get_order_id() : 0;
        $product_obj = $item->get_product();
        $sku         = $product_obj ? (string) $product_obj->get_sku() : '';
        $new_sku     = $product_obj ? get_post_meta( $product_obj->get_id(), 'lkd_wm_new_sku', true ) : '';
        $qty_ordered = (int) $item->get_quantity();

        // Read fulfillment map from order meta (array or JSON string)
        $map = get_post_meta( $order_id, '_aaa_picked_items', true );
        if ( is_string( $map ) ) {
            $maybe = json_decode( $map, true );
            if ( is_array( $maybe ) ) $map = $maybe;
        }
        if ( ! is_array( $map ) ) $map = [];

        // Compute picked with fallback to new_sku
        $picked = 0;
        if ( $sku && isset( $map[ $sku ] ) ) {
            $picked = (int) $map[ $sku ];
        } elseif ( $new_sku && isset( $map[ $new_sku ] ) ) {
            $picked = (int) $map[ $new_sku ];
        }

        // Badge color
        $color = ( $qty_ordered > 0 && $picked >= $qty_ordered ) ? '#5bb468' : ( $picked > 0 ? '#e0c200' : '#9aa0a6' );

        echo '<td class="aaa-oc-picked-col">';
        echo '<span class="aaa-oc-picked-badge" style="font-size: 20px;padding: 5px 20px;border-radius: 10px;font-weight: 700;background:' . esc_attr( $color ) . ';">' .
             esc_html( $picked . '/' . $qty_ordered ) . '</span>';
        if ( $new_sku && $new_sku !== $sku ) {
            echo '<div style="font-size:13px;opacity:.7;margin-top:7px;">New SKU:<br> ' . esc_html( $new_sku ) . '</div>';
        }
        echo '</td>';
    }
}

AAA_OC_Admin_Fulfillment_Column::init();
