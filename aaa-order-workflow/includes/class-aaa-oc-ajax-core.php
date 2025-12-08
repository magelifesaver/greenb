<?php
/**
 * File Path: /aaa-order-workflow/includes/class-aaa-oc-ajax-core.php
 *
 * Purpose:
 *
 * Note: 
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Ajax_Core {

    private $enabled_status_slugs = array();
    private $status_slugs_no_wc   = array();

    public function __construct() {
        add_action( 'wp_ajax_aaa_oc_get_latest_orders', array( $this, 'get_latest_orders' ) );
        add_action( 'wp_ajax_aaa_oc_update_order_status', array( $this, 'update_order_status' ) );
        add_action( 'wp_ajax_aaa_oc_set_driver', array( $this, 'set_driver' ) );

        $this->enabled_status_slugs = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() );

        $this->status_slugs_no_wc   = array_map( function( $s ) {
            return str_replace( 'wc-', '', $s );
        }, $this->enabled_status_slugs );

    }

    public function get_latest_orders() {
        check_ajax_referer( 'aaa_oc_ajax_nonce', '_ajax_nonce' );

        $enabled = $this->enabled_status_slugs;
        if ( empty( $enabled ) ) {
            wp_send_json_success( array( 'columns_html' => '<p>No statuses enabled.</p>' ) );
        }

        global $wpdb;
        $tbl   = $wpdb->prefix . 'aaa_oc_order_index';
        $sort  = isset( $_POST['sortMode'] ) ? sanitize_text_field( $_POST['sortMode'] ) : 'published';
        $by    = ( $sort === 'status' ) ? 'time_in_status' : 'time_published';

        $html = '';

        // For each enabled status:
        foreach ( $enabled as $slug_full ) {
            $slug_no_wc  = str_replace( 'wc-', '', $slug_full );
            $status_name = wc_get_order_status_name( $slug_no_wc );

            // Grab *all* rows for that status (no pagination)
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $tbl
                 WHERE status = %s
                 ORDER BY $by DESC",
                $slug_no_wc
            ) );

            // Count them
            $count = count( $rows );

	    // Determine 1- or 2-column layout
		if ( $count < 10 ) {
		    $ccount = 1;
		} elseif ( $count < 30 ) {
		    $ccount = 2;
		} else {
		    $ccount = 3;
		}

	    // Extra class if this is the "completed" column
	    $extra_class = ( $slug_no_wc === 'completed' ) ? ' aaa-oc-completed-col' : '';
            $col_html  = '<div class="aaa-oc-column col-count-' . $ccount
	       . ' status-' . esc_attr( $slug_no_wc ) . $extra_class
           . '" data-status="' . esc_attr( $slug_no_wc ) . '">';
            $col_html .= '<div class="aaa-oc-column-title" style="display:flex;justify-content:space-between;text-transform: uppercase;align-items:center;">';
            $col_html .= '<span>' . esc_html( $status_name ) . ' <span class="aaa-oc-order-count">(' . $count . ')</span></span>';
            // Sort toggle button
            $toggle_label = ( $sort === 'status' ) ? 'OT' : 'ST';
            $col_html .= '<button class="button aaa-oc-sort-toggle" data-status-slug="' . esc_attr( $slug_full ) . '">' . esc_html( $toggle_label ) . '</button>';
	    // Hide/Show button for completed column
	    if ( $slug_no_wc === 'completed' ) {
	        $col_html .= '<button class="button aaa-oc-toggle-completed" title="Hide/Show Completed">👁</button>';
	    }
            $col_html .= '</div>';

            if ( $rows ) {
                foreach ( $rows as $r ) {
                    $col_html .= AAA_OC_Ajax_Cards::build_order_card_html( $r, $ccount, $this->status_slugs_no_wc );
                }
            } else {
                $col_html .= '<p>No orders found in this status.</p>';
            }

            $col_html .= '</div>';
            $html     .= $col_html;
        }

        wp_send_json_success( array( 'columns_html' => $html ) );
    }

public function update_order_status() {
    check_ajax_referer( 'aaa_oc_ajax_nonce', '_ajax_nonce' );
    $id  = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $ns  = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';
    if ( ! $id || ! $ns ) {
        wp_send_json_error( 'Invalid data' );
    }

    $old     = get_post_field( 'post_status', $id );
    $desired = 'wc-' . $ns;

    if ( $old === $desired ) {
        $this->update_index_status( $id, $ns );
        wp_send_json_success( "Status already $ns" );
    }

    $order = wc_get_order( $id );
    if ( ! $order ) {
        wp_send_json_error( "Invalid order: $id" );
    }

    try {
        // Let WooCommerce handle everything (inventory, completed/paid dates, emails)
        $order->update_status( $ns, 'Status changed via Workflow Board', true );
    } catch ( Exception $e ) {
        wp_send_json_error( 'Error updating order status: ' . $e->getMessage() );
    }

    // Update the index table (still needed for Workflow board snapshot)
    $this->update_index_status( $id, $ns );

    // Optional: if you want *both* Woo’s note AND your helper note, keep this line:
    // AAA_OC_Update_Order_Notes::record_order_status_change( $id, $old, $ns, $order );

    wp_send_json_success( "Order status changed to: $ns" );
}

    private function update_index_status( $id, $slug ) {
        global $wpdb;
        $tbl = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $tbl,
            array( 'status' => $slug, 'time_in_status' => current_time('mysql') ),
            array( 'order_id' => $id ),
            array( '%s','%s' ),
            array( '%d' )
        );
    }

    public function set_driver() {
        check_ajax_referer( 'aaa_oc_ajax_nonce', '_ajax_nonce' );
        $id  = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $did = isset( $_POST['driver_id'] ) ? intval( $_POST['driver_id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( 'Missing order ID' );
        }
        global $wpdb;
        $tbl = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $tbl,
            array( 'driver_id' => $did ),
            array( 'order_id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
        wp_send_json_success( 'Driver updated.' );
    }
}
