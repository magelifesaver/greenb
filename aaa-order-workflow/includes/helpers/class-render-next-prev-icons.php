<?php
/**
 * File Path: /includes/class-aaa-oc-render-next-prev-icons.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Render_Next_Prev_Icons {

    /**
     * Renders “prev” and “next” status buttons using Dashicons for clarity.
     *
     * @param int    $order_id     The order ID.
     * @param string $current_slug The current status slug (without “wc-”).
     * @param bool   $expanded     Whether we’re in an expanded modal (so we should close it).
     * @return string              HTML for the two icon buttons.
     */
    public static function render_next_prev_icons( $order_id, $current_slug, $expanded = false ) {
	$enabled_statuses = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() );

        if ( ! in_array( 'wc-completed', $enabled_statuses, true ) ) {
            $enabled_statuses[] = 'wc-completed';
        }
        $enabled_slugs_no_wc = array_map( function( $s ) {
            return str_replace( 'wc-', '', $s );
        }, $enabled_statuses );
        $current_index = array_search( $current_slug, $enabled_slugs_no_wc, true );
        if ( false === $current_index ) {
            return '';
        }

        // If in a modal, also close it on click
        $close_js = $expanded ? '; aaaOcCloseModal();' : '';

        $output  = '<div class="aaa-oc-status-icons" style="display:inline-flex; gap:6px; align-items:center;">';

        // Previous button
        if ( $current_index > 0 ) {
            $prev_slug = $enabled_slugs_no_wc[ $current_index - 1 ];
            $output  .= '<button type="button" '
                      . 'class="aaa-oc-prev-status-icon button-modern" '
                      . 'title="Move to previous status" '
                      . 'onclick="aaaOcChangeOrderStatus('
                      . esc_attr( $order_id ) . ', \'' . esc_js( $prev_slug ) . '\')'
                      . esc_js( $close_js )
                      . '">'
                      . '<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>'
                      . '</button>';
        }

	// Fulfillment status check — block "Next" in PROCESSING unless fully picked
	global $wpdb;
	$index_tbl = $wpdb->prefix . 'aaa_oc_order_index';
	$fulfillment_status = $wpdb->get_var(
	    $wpdb->prepare("SELECT fulfillment_status FROM {$index_tbl} WHERE order_id = %d", $order_id)
	);
	$fulfillment_status = strtolower( trim( (string) $fulfillment_status ) );
	$is_fulfillment_complete = ( $fulfillment_status === 'fully_picked' );

	// Next button (only if not blocked)
	if ( $current_index < count( $enabled_slugs_no_wc ) - 1 ) {
	    $next_slug = $enabled_slugs_no_wc[ $current_index + 1 ];

	    // Block condition: in processing AND fulfillment is NOT fully picked
	    if ( $current_slug === 'processing' && ! $is_fulfillment_complete ) {
	        // Do not render Next button
	    } else {
	        $output .= '<button type="button" '
	                 . 'class="aaa-oc-next-status-icon button-modern" '
	                 . 'title="Move to next status" '
	                 . 'onclick="aaaOcChangeOrderStatus('
	                 . esc_attr( $order_id ) . ', \'' . esc_js( $next_slug ) . '\')'
	                 . esc_js( $close_js )
	                 . '">'
	                 . '<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>'
	                 . '</button>';
	    }
	}

        $output .= '</div>';
        return $output;
    }
}

add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style( 'dashicons' );
} );
