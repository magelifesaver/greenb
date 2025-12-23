<?php
/**
 * Mass submission page for verifying unverified billing addresses.
 *
 * This component lists users whose billing address has not yet been geocoded
 * and allows administrators to enqueue them en masse or exclude them from
 * future listings. Filters for failed jobs, sort order and per-page count
 * are provided. Exclusion is stored in user meta under _aaa_am_excluded.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_AdminMass {
    /**
     * Register the Tools submenu page.
     */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
    }

    /**
     * Add the Mass Submission page under Tools.
     */
    public static function register_page() : void {
        add_submenu_page(
            'tools.php',
            __( 'Mass Address Verification', 'aaa-address-manager' ),
            __( 'Mass Address Verification', 'aaa-address-manager' ),
            'manage_options',
            'aaa-am-mass',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render the mass submission page. Handles form posts and lists users.
     */
    public static function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        AAA_BulkVerify_Core::ensure_table();
        global $wpdb;
        $jobs_tbl = $wpdb->prefix . AAA_BulkVerify_Core::TABLE;
        // Handle queue/exclude actions.
        if ( ! empty( $_POST['uids'] ) && check_admin_referer( 'aaa_am_mass_submit' ) ) {
            $uids = array_map( 'intval', (array) $_POST['uids'] );
            if ( isset( $_POST['queue_selected'] ) ) {
                $queued = 0;
                $table  = $jobs_tbl;
                foreach ( $uids as $uid ) {
                    // Skip if already queued.
                    $already = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND status='queued'", $uid ) );
                    if ( $already ) {
                        continue;
                    }
                    $ok = $wpdb->insert( $table, [
                        'user_id'    => $uid,
                        'scope'      => 'billing',
                        'status'     => 'queued',
                        'message'    => '',
                        'created_at' => current_time( 'mysql' ),
                    ] );
                    if ( $ok ) {
                        $queued++;
                    }
                }
                if ( $queued > 0 && ! wp_next_scheduled( AAA_BulkVerify_Core::CRON_HOOK ) ) {
                    wp_schedule_single_event( time() + 5, AAA_BulkVerify_Core::CRON_HOOK );
                }
                echo '<div class="updated"><p>' . sprintf( esc_html__( 'Queued %d users.', 'aaa-address-manager' ), esc_html( $queued ) ) . '</p></div>';
            }
            if ( isset( $_POST['exclude_selected'] ) ) {
                foreach ( $uids as $uid ) {
                    update_user_meta( $uid, '_aaa_am_excluded', 'yes' );
                }
                echo '<div class="updated"><p>' . sprintf( esc_html__( 'Excluded %d users.', 'aaa-address-manager' ), esc_html( count( $uids ) ) ) . '</p></div>';
            }
        }
        // Read filters.
        $include_failed = ! empty( $_GET['show_failed'] );
        $order          = ( isset( $_GET['order'] ) && strtolower( (string) $_GET['order'] ) === 'asc' ) ? 'ASC' : 'DESC';
        $per_page       = isset( $_GET['per_page'] ) ? max( 1, (int) $_GET['per_page'] ) : 200;
        // Build SQL: find users with non-empty billing address_1, not verified, not queued, not excluded, optional failed filter.
        $sql = "SELECT u.ID,u.user_login, MAX(CASE WHEN um.meta_key='billing_address_1' THEN um.meta_value END) AS addr1, MAX(CASE WHEN um.meta_key='billing_city' THEN um.meta_value END) AS city, MAX(CASE WHEN um.meta_key='_aaa_am_verify_failed' THEN um.meta_value END) AS failed, MAX(CASE WHEN um.meta_key='_aaa_am_excluded' THEN um.meta_value END) AS excluded FROM {$wpdb->users} u JOIN {$wpdb->usermeta} um ON um.user_id=u.ID GROUP BY u.ID HAVING addr1 <> '' AND u.ID NOT IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='_wc_billing/aaa-delivery-blocks/coords-verified' AND meta_value='yes') AND u.ID NOT IN (SELECT user_id FROM {$jobs_tbl} WHERE status='queued')";
        if ( ! $include_failed ) {
            $sql .= " AND (failed IS NULL OR failed <> 'yes')";
        }
        $sql .= " AND (excluded IS NULL OR excluded <> 'yes')";
        $sql .= " ORDER BY u.ID {$order} LIMIT {$per_page}";
        $users = $wpdb->get_results( $sql );
        echo '<div class="wrap"><h1>' . esc_html__( 'Mass Address Verification', 'aaa-address-manager' ) . '</h1>';
        // Filters: toggle failed, sort, per_page.
        $base_url   = remove_query_arg( [ 'show_failed', 'order', 'per_page' ] );
        $toggle_url = $include_failed ? $base_url : add_query_arg( 'show_failed', 1, $base_url );
        echo '<p>' . ( $include_failed ? '<a href="' . esc_url( $toggle_url ) . '">' . esc_html__( 'Hide failed users', 'aaa-address-manager' ) . '</a>' : '<a href="' . esc_url( $toggle_url ) . '">' . esc_html__( 'Show failed users', 'aaa-address-manager' ) . '</a>' ) . '</p>';
        $asc_url  = add_query_arg( 'order', 'asc' );
        $desc_url = add_query_arg( 'order', 'desc' );
        echo '<p>' . esc_html__( 'Sort by ID:', 'aaa-address-manager' ) . ' <a href="' . esc_url( $asc_url ) . '">' . esc_html__( 'Ascending', 'aaa-address-manager' ) . '</a> | <a href="' . esc_url( $desc_url ) . '">' . esc_html__( 'Descending', 'aaa-address-manager' ) . '</a></p>';
        echo '<form method="get" style="margin-bottom:10px;">';
        echo '<input type="hidden" name="page" value="aaa-am-mass" />';
        echo '<label>' . esc_html__( 'Users per page:', 'aaa-address-manager' ) . ' <input type="number" name="per_page" value="' . esc_attr( $per_page ) . '" min="1" max="5000" /></label> ';
        echo '<button class="button">' . esc_html__( 'Apply', 'aaa-address-manager' ) . '</button>';
        echo '</form>';
        // Table with mass actions.
        echo '<form method="post">';
        wp_nonce_field( 'aaa_am_mass_submit' );
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th><input type="checkbox" id="aaa-am-checkall" /></th>';
        echo '<th>' . esc_html__( 'ID', 'aaa-address-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'User', 'aaa-address-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Address', 'aaa-address-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'City', 'aaa-address-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Failed', 'aaa-address-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Exclude', 'aaa-address-manager' ) . '</th>';
        echo '</tr></thead><tbody>';
        if ( empty( $users ) ) {
            echo '<tr><td colspan="7">' . esc_html__( 'No users found with unverified billing address (respecting filters).', 'aaa-address-manager' ) . '</td></tr>';
        } else {
            foreach ( $users as $u ) {
                $exclude_url = wp_nonce_url( admin_url( 'admin-post.php?action=aaa_am_exclude_user&uid=' . $u->ID ), 'aaa_am_exclude_' . $u->ID );
                echo '<tr>';
                echo '<td><input type="checkbox" name="uids[]" value="' . esc_attr( $u->ID ) . '" /></td>';
                echo '<td><a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->ID ) . '</a></td>';
                echo '<td>' . esc_html( $u->user_login ) . '</td>';
                echo '<td>' . esc_html( (string) $u->addr1 ) . '</td>';
                echo '<td>' . esc_html( (string) $u->city ) . '</td>';
                echo '<td>' . esc_html( $u->failed ? 'yes' : 'no' ) . '</td>';
                $confirm_msg = esc_js( __( 'Exclude this user from this list?', 'aaa-address-manager' ) );
                echo '<td><a class="button" href="' . esc_url( $exclude_url ) . '" onclick="return confirm(\'' . $confirm_msg . '\')">' . esc_html__( 'Exclude', 'aaa-address-manager' ) . '</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '<p>';
        echo '<button type="submit" name="queue_selected" class="button button-primary">' . esc_html__( 'Queue Selected', 'aaa-address-manager' ) . '</button> ';
        echo '<button type="submit" name="exclude_selected" class="button">' . esc_html__( 'Exclude Selected', 'aaa-address-manager' ) . '</button>';
        echo '</p>';
        echo '</form>';
        // JS to handle select all.
        echo '<script>(function(){var all=document.getElementById("aaa-am-checkall"); if(!all) return; all.addEventListener("change", function(){ document.querySelectorAll("input[name=\"uids[]\"]").forEach(function(cb){ cb.checked = all.checked; }); });})();</script>';
        echo '</div>';
    }
}