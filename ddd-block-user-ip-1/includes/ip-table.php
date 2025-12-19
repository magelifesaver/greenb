<?php
/** IP table rendering and bulk actions for DDD Block User IP. Provides a sortable table with checkboxes and bulk actions. */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/** Handle bulk actions submitted from the table. Applies the selected action to checked IPs. */
function ddd_buip_process_bulk_action() {
    if ( ! current_user_can( 'manage_options' ) || empty( $_POST['ddd_buip_bulk_action'] ) || empty( $_POST['ip_bulk'] ) ) return;
    check_admin_referer( 'ddd_buip_bulk_action' );
    $action   = sanitize_text_field( wp_unslash( $_POST['ddd_buip_bulk_action'] ) );
    $selected = array_map( 'sanitize_text_field', (array) $_POST['ip_bulk'] );
    $block_list = ddd_buip_get_ip_list( 'ddd_buip_ips' );
    $safe_list  = ddd_buip_get_ip_list( 'ddd_buip_safe_ips' );
    foreach ( $selected as $ip ) {
        switch ( $action ) {
            case 'block': $block_list[ $ip ] = $ip; unset( $safe_list[ $ip ] ); break;
            case 'unblock': unset( $block_list[ $ip ] ); break;
            case 'safe': $safe_list[ $ip ] = $ip; unset( $block_list[ $ip ] ); break;
            case 'unsafelist': unset( $safe_list[ $ip ] ); break;
        }
    }
    ddd_buip_save_ip_list( 'ddd_buip_ips', $block_list );
    ddd_buip_save_ip_list( 'ddd_buip_safe_ips', $safe_list );
    wp_safe_redirect( remove_query_arg( array( 'ddd_buip_action', 'ip', '_wpnonce' ) ) );
    exit;
}

/** Render the IP activity table with sortable columns and bulk actions. */
function ddd_buip_render_ip_table( $allowed_country, $auto ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'ddd_buip_ip_log';
    // Columns eligible for sorting. Use this array consistently throughout.
    $allowed = array( 'ip', 'country', 'hits', 'last_seen', 'score' );
    $orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed, true ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'last_seen';
    $order   = ( isset( $_GET['order'] ) && 'asc' === strtolower( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
    $order_by_sql = in_array( $orderby, $allowed, true ) ? $orderby : 'last_seen';
    $order_sql    = 'ASC' === $order ? 'ASC' : 'DESC';
    $logs   = $wpdb->get_results( "SELECT ip, country, hits, last_seen, score FROM $table ORDER BY $order_by_sql $order_sql LIMIT 50" );
    ?>
    <form method="post">
    <?php wp_nonce_field( 'ddd_buip_bulk_action' ); ?>
    <p class="tablenav top">
        <select name="ddd_buip_bulk_action">
            <option value=""><?php esc_html_e( 'Bulk actions', 'ddd-block-user-ip' ); ?></option>
            <option value="block"><?php esc_html_e( 'Block', 'ddd-block-user-ip' ); ?></option>
            <option value="unblock"><?php esc_html_e( 'Unblock', 'ddd-block-user-ip' ); ?></option>
            <option value="safe"><?php esc_html_e( 'Safelist', 'ddd-block-user-ip' ); ?></option>
            <option value="unsafelist"><?php esc_html_e( 'Remove safe', 'ddd-block-user-ip' ); ?></option>
        </select>
        <input type="submit" class="button action" value="<?php echo esc_attr( __( 'Apply', 'ddd-block-user-ip' ) ); ?>" />
    </p>
    <table class="widefat striped">
        <thead>
            <tr>
                <td class="manage-column check-column"><input type="checkbox" id="ddd_buip_check_all" /></td>
                <?php
                $columns = array(
                    'ip'       => __( 'IP Address', 'ddd-block-user-ip' ),
                    'country'  => __( 'Country', 'ddd-block-user-ip' ),
                    'hits'     => __( 'Hits', 'ddd-block-user-ip' ),
                    'last_seen'=> __( 'Last Seen', 'ddd-block-user-ip' ),
                    'score'    => __( 'Score', 'ddd-block-user-ip' ),
                    'status'   => __( 'Status', 'ddd-block-user-ip' ),
                    'actions'  => __( 'Actions', 'ddd-block-user-ip' ),
                );
                foreach ( $columns as $col_key => $label ) {
                    if ( in_array( $col_key, $allowed, true ) ) {
                        // Toggle order: if currently ascending on this column, next click will sort descending.
                        $next_order = ( $orderby === $col_key && 'ASC' === $order_sql ) ? 'desc' : 'asc';
                        $query_url  = add_query_arg( array( 'orderby' => $col_key, 'order' => $next_order ) );
                        echo '<th scope="col" class="manage-column"><a href="' . esc_url( $query_url ) . '"><span>' . esc_html( $label ) . '</span><span class="sorting-indicator"></span></a></th>';
                    } else {
                        echo '<th scope="col" class="manage-column">' . esc_html( $label ) . '</th>';
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
        <?php
        if ( $logs ) :
            foreach ( $logs as $log ) :
                $ip        = $log->ip;
                $is_block  = ddd_buip_is_in_manual_block_list( $ip );
                $is_safe   = ddd_buip_is_in_safe_list( $ip );
                $row_country        = $log->country ? strtoupper( $log->country ) : '';
                $would_country_block = ( $auto && $row_country && $row_country !== $allowed_country && ! $is_safe );
                $status    = __( 'Normal', 'ddd-block-user-ip' );
                if ( $is_safe ) {
                    $status = __( 'Safe', 'ddd-block-user-ip' );
                } elseif ( $is_block ) {
                    $status = __( 'Blocked (Manual)', 'ddd-block-user-ip' );
                } elseif ( $would_country_block ) {
                    $status = __( 'Blocked (Country)', 'ddd-block-user-ip' );
                }
                $base_url  = admin_url( 'tools.php?page=ddd-block-user-ip' );
                $block_url = wp_nonce_url( add_query_arg( array( 'ddd_buip_action' => $is_block ? 'unblock' : 'block', 'ip' => rawurlencode( $ip ) ), $base_url ), 'ddd_buip_manage_ip_' . $ip );
                $safe_url  = wp_nonce_url( add_query_arg( array( 'ddd_buip_action' => $is_safe ? 'unsafelist' : 'safe', 'ip' => rawurlencode( $ip ) ), $base_url ), 'ddd_buip_manage_ip_' . $ip );
                ?>
                <tr>
                    <th scope="row" class="check-column"><input type="checkbox" name="ip_bulk[]" value="<?php echo esc_attr( $ip ); ?>" /></th>
                    <td><?php echo esc_html( $ip ); ?></td>
                    <td><?php echo esc_html( $log->country ? $log->country : '-' ); ?></td>
                    <td><?php echo esc_html( $log->hits ); ?></td>
                    <td><?php echo esc_html( $log->last_seen ); ?></td>
                    <td><?php echo esc_html( $log->score ); ?></td>
                    <td><?php echo esc_html( $status ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $block_url ); ?>"><?php echo esc_html( $is_block ? __( 'Unblock', 'ddd-block-user-ip' ) : __( 'Block', 'ddd-block-user-ip' ) ); ?></a>
                        |
                        <a href="<?php echo esc_url( $safe_url ); ?>"><?php echo esc_html( $is_safe ? __( 'Remove safe', 'ddd-block-user-ip' ) : __( 'Safelist', 'ddd-block-user-ip' ) ); ?></a>
                    </td>
                </tr>
                <?php
            endforeach;
        else :
            echo '<tr><td colspan="8">' . esc_html__( 'No log entries yet.', 'ddd-block-user-ip' ) . '</td></tr>';
        endif;
        ?>
        </tbody>
    </table>
    <script type="text/javascript">
    (function() {
        var checkAll = document.getElementById('ddd_buip_check_all');
        if (checkAll) {
            checkAll.addEventListener('click', function(e) {
                var checkboxes = document.querySelectorAll('input[name="ip_bulk[]"]');
                checkboxes.forEach(function(ch) {
                    ch.checked = e.target.checked;
                });
            });
        }
    })();
    </script>
    </form>
    <?php
}