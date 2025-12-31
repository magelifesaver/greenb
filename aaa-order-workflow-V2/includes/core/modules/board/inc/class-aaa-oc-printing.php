<?php
/**
 * File Path: /aaa-order-workflow/includes/core/modules/board/inc/class-aaa-oc-printing.php
 * Purpose:
 * Renders the print buttons block on the expanded order card (Receipt/Picklist + Add Payment)
 * and adds quick-jump status buttons:
 *  - "Schedule Delivery" when status = lkd-packed-ready  → sets to scheduled
 *  - "Set Packed & Ready" when status = scheduled        → sets to lkd-packed-ready
 *
 * Notes:
 * - We pass bare slugs (no "wc-") to aaaOcChangeOrderStatus(), matching your handler.
 * - To avoid a blank overlay, we close the modal first (aaaOcCloseModal()) before changing status.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Printing {

    /**
     * Render buttons block for an order card.
     *
     * @param int $order_id WooCommerce order ID.
     * @return string HTML
     */
    public static function render_print_buttons( $order_id ) {
        // Current order status (slug without "wc-")
        $current_status = '';
        if ( function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $current_status = $order->get_status(); // e.g. "scheduled", "lkd-packed-ready"
            }
        }

        // Nonce used by your print preview/print handlers
        $nonce = wp_create_nonce( 'aaa_lpm_nonce' );

        ob_start();
        ?>
        <div class="aaa-oc-print-buttons-wrap" style="margin:6px 0;">
            <div class="aaa-lpm-title hidden-button">ORDER RECEIPT</div>

            <div class="aaa-lpm-buttons">
                <!-- Receipt -->
                <button type="button" class="button-modern aaa-lpm-preview-html-btn hidden-button"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="receipt"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    HTML
                </button>

                <button type="button" class="button-modern aaa-lpm-preview-pdf-btn print-button link-button"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="receipt"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    Receipt <br> Preview
                </button>

                <button type="button" class="button-modern aaa-lpm-print-btn print-button button-dispatch"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="receipt"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-printer="dispatch">
                    Receipt <br> (Dis)
                </button>

                <button type="button" class="button-modern aaa-lpm-print-btn print-button button-inventory"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="receipt"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-printer="inventory">
                    Receipt <br> (Inv)
                </button>

                <!-- Picklist -->
                <button type="button" class="button-modern aaa-lpm-preview-html-btn hidden-button"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="picklist"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    HTML
                </button>

                <button type="button" class="button-modern aaa-lpm-preview-pdf-btn print-button link-button"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="picklist"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    Pick List <br> Preview
                </button>

                <button type="button" class="button-modern aaa-lpm-print-btn print-button button-dispatch"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="picklist"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-printer="dispatch">
                    Pick List <br> (Dis)
                </button>

                <button type="button" class="button-modern aaa-lpm-print-btn print-button button-inventory"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    data-template="picklist"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-printer="inventory">
                    Pick List <br> (Inv)
                </button>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
