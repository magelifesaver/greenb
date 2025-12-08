<?php
/**
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/inc/admin/class-aaa-pm-tip-admin.php
 * Purpose: Admin UI + handler for removing tips (_wpslash_tip) from orders.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_PM_Tip_Admin {

    public static function init() {
        // Show tip meta + remove button on order edit
        add_action( 'woocommerce_admin_order_data_after_order_details', [ __CLASS__, 'render_tip_controls' ] );
        // AJAX handler for remove action
        add_action( 'wp_ajax_aaa_pm_remove_tip', [ __CLASS__, 'ajax_remove_tip' ] );
    }

    public static function render_tip_controls( $order ) {
        if ( ! $order instanceof WC_Order ) return;

        $tip = $order->get_meta( '_wpslash_tip' );
        if ( $tip === '' || $tip === null ) return;

        echo '<div class="order_tip_meta">';
        echo '<p><strong>Tip:</strong> ' . wc_price( $tip ) . '</p>';

        if ( $order->has_status( 'pending' ) ) {
            ?>
            <button type="button" class="button" id="aaa-remove-tip" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
                <?php esc_html_e( 'Remove Tip', 'aaa-offline-gateways-blocks' ); ?>
            </button>
            <script>
            jQuery(function($){
                $('#aaa-remove-tip').on('click', function(e){
                    e.preventDefault();
                    if (!confirm('<?php echo esc_js( __( 'Remove tip from this order?', 'aaa-offline-gateways-blocks' ) ); ?>')) return;
                    $.post(ajaxurl, {
                        action: 'aaa_pm_remove_tip',
                        order_id: $(this).data('order'),
                        _wpnonce: '<?php echo wp_create_nonce('aaa_pm_remove_tip'); ?>'
                    }, function(){ location.reload(); });
                });
            });
            </script>
            <?php
        }

        echo '</div>';
    }

	public static function ajax_remove_tip() {
	    check_ajax_referer( 'aaa_pm_remove_tip' );
	    if ( ! current_user_can( 'manage_woocommerce' ) ) {
	        wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
	    }

	    $order_id = absint( $_POST['order_id'] ?? 0 );
	    $order    = wc_get_order( $order_id );
	    if ( ! $order ) wp_send_json_error( [ 'message' => 'Order not found' ], 404 );

	    // Guards: pending + unpaid
	    if ( $order->get_status() !== 'pending' ) {
	        wp_send_json_error( [ 'message' => 'Order must be pending' ], 400 );
	    }
	    $pay_status = (string) get_post_meta( $order_id, 'aaa_oc_payment_status', true );
	    if ( strtolower( $pay_status ) === 'paid' ) {
	        wp_send_json_error( [ 'message' => 'Payment already marked paid' ], 400 );
	    }

	    // Remove tip fees
	    $removed = 0;
	    foreach ( $order->get_items( 'fee' ) as $item_id => $fee ) {
	        if ( stripos( (string)$fee->get_name(), 'tip' ) !== false ) {
	            $order->remove_item( $item_id );
	            $removed++;
	        }
	    }

	    // Remove tip meta and recalc totals
	    $order->delete_meta_data( '_wpslash_tip' );
	    if ( $removed ) { $order->calculate_totals( true ); }

	    $order->add_order_note( sprintf(
	        'Tip removed by %s via admin.',
	        wp_get_current_user()->display_name ?: 'system'
	    ) );
	    $order->save();

	    // Reindex / let Workflow react
	    do_action( 'woocommerce_update_order', $order_id );

	    wp_send_json_success( [ 'removed' => $removed ] );
	}
}

AAA_PM_Tip_Admin::init();
