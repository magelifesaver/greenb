<?php
/**
 * File Path: /includes/class-aaa-oc-render-next-prev-icons.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Render_Next_Prev_Icons {

    /**
     * Renders “prev” and “next” status buttons using Dashicons.
     *
     * @param int    $order_id     The order ID.
     * @param string $current_slug The current status slug (without “wc-”).
     * @param bool   $expanded     Whether we’re in an expanded modal (so we should close it).
     * @return string              HTML for the two icon buttons.
     */
    public static function render_next_prev_icons( $order_id, $current_slug, $expanded = false ) {
        // Enabled statuses (ordered)
        $enabled_statuses = function_exists('aaa_oc_get_option')
            ? aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() )
            : get_option( 'aaa_oc_enabled_statuses', array() );

        if ( ! in_array( 'wc-completed', $enabled_statuses, true ) ) {
            $enabled_statuses[] = 'wc-completed';
        }

        // Normalize to slugs without wc-
        $enabled_slugs_no_wc = array_map( static function( $s ) {
            return str_replace( 'wc-', '', (string) $s );
        }, $enabled_statuses );

        $current_index = array_search( (string) $current_slug, $enabled_slugs_no_wc, true );
        if ( false === $current_index ) {
            return '';
        }

        // If in a modal, also close it on click
        $close_js = $expanded ? '; aaaOcCloseModal();' : '';

        $output  = '<div class="aaa-oc-status-icons" style="display:inline-flex; gap:6px; align-items:center;">';

        // Previous button
        if ( $current_index > 0 ) {
            $prev_slug = (string) $enabled_slugs_no_wc[ $current_index - 1 ];
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

        // ——— Conditional fulfillment gate (settings-driven) ———
        // Respect Fulfillment tab: pack_require_picked (default 1/on)
        $pack_require_picked = 1;
        if ( function_exists('aaa_oc_get_option') ) {
            $pack_require_picked = (int) aaa_oc_get_option( 'pack_require_picked', 'fulfillment', 1 );
        }

        // Fetch fulfillment status from index
        global $wpdb;
        $index_tbl = $wpdb->prefix . 'aaa_oc_order_index';
        $fulfillment_status = $wpdb->get_var(
            $wpdb->prepare( "SELECT fulfillment_status FROM {$index_tbl} WHERE order_id = %d", $order_id )
        );
        $fulfillment_status = strtolower( trim( (string) $fulfillment_status ) );
        $is_fulfillment_complete = ( $fulfillment_status === 'fully_picked' );

        // Next button (subject to conditional block)
        if ( $current_index < ( count( $enabled_slugs_no_wc ) - 1 ) ) {
            $next_slug = (string) $enabled_slugs_no_wc[ $current_index + 1 ];

            // Block only when:
            // 1) Current col is "processing"
            // 2) Setting pack_require_picked is enabled
            // 3) Fulfillment is NOT fully picked
            $block_next = ( $current_slug === 'processing' && $pack_require_picked && ! $is_fulfillment_complete );

            if ( ! $block_next ) {
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
