<?php
/**
 * File: ajax/class-ac-sessions-extended.php
 * Purpose: Extended sessions handler providing last activity, customer and cart indicators
 * along with bulk end-session support.  Adds new AJAX actions for loading sessions and
 * ending sessions via async requests.  Designed to work alongside the existing AC_Ajax_V2
 * class without modifying it.  To activate these handlers the loader must include this
 * file and call AC_Sessions_Extended::init().
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Sessions_Extended {

    /**
     * Hook up the AJAX handlers.  Called during plugins_loaded from the loader.
     */
    public static function init(){
        add_action('wp_ajax_aaa_ac_load_sessions_extended', [__CLASS__, 'load_sessions']);
        add_action('wp_ajax_aaa_ac_end_sessions',          [__CLASS__, 'end_sessions']);
    }

    /**
     * Fetch a list of users across the network with the given role.  This
     * method mirrors the logic used in AC_Ajax_V2::users_by_role_network(),
     * but is reimplemented here because the original method is protected
     * and cannot be called directly.  It gathers users from all sites in
     * a multisite install, merging by user ID to avoid duplicates and
     * sorting by display name.
     *
     * @param string $role Role slug to filter by.
     * @return array<WP_User> Array of user objects with ID and display_name.
     */
    private static function users_by_role_network( $role ){
        $role = sanitize_key( $role );
        if ( ! $role ) return [];
        $users_map = [];
        $sites = function_exists('get_sites') ? get_sites(['number'=>0]) : [];
        if ( empty($sites) ){
            // Single-site fallback: just fetch users with the role.
            $site_users = get_users([
                'role__in' => [ $role ],
                'orderby'  => 'display_name',
                'order'    => 'ASC',
                'number'   => 2000,
            ]);
            foreach( $site_users as $u ){
                $users_map[ $u->ID ] = $u;
            }
            return array_values( $users_map );
        }
        foreach( $sites as $site ){
            switch_to_blog( (int) $site->blog_id );
            $site_users = get_users([
                'role__in' => [ $role ],
                'orderby'  => 'display_name',
                'order'    => 'ASC',
                'fields'   => ['ID','display_name'],
                'number'   => 5000,
            ]);
            foreach( $site_users as $u ){
                if ( empty( $users_map[ $u->ID ] ) ){
                    $users_map[ $u->ID ] = $u;
                }
            }
            restore_current_blog();
        }
        // Sort by display name (case-insensitive)
        usort( $users_map, function( $a, $b ){ return strcasecmp( $a->display_name, $b->display_name ); } );
        return array_values( $users_map );
    }

    /**
     * Fetch and render the session list for a given role.
     * Adds columns for last activity, customer flag and cart contents.
     *
     * Expected $_GET parameters:
     * - role (string)           : role slug to load
     * - nonce (string)          : security token
     * - online_only (optional)  : truthy filter to limit to online users
     */
    public static function load_sessions(){
        if ( ! current_user_can('manage_network_users') ) {
            wp_send_json_error('no_cap');
        }
        check_ajax_referer('aaa_ac_ajax','nonce');

        $role = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
        $online_only = false;
        if ( isset($_GET['online_only']) ) {
            $online_only = filter_var( $_GET['online_only'], FILTER_VALIDATE_BOOLEAN );
        }

        // Determine which roles are enabled on the network.
        $enabled = function_exists('aaa_ac_get_enabled_roles') ? aaa_ac_get_enabled_roles() : [];
        if ( ! $role || ! in_array($role, $enabled, true) ) {
            wp_send_json_success(['rows' => '']);
            return;
        }

        // Fetch users across the network with the given role.  We use our
        // own implementation because AC_Ajax_V2::users_by_role_network() is
        // protected and not publicly callable.
        $users = self::users_by_role_network( $role );

        global $wpdb;
        $log_table = $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'session_logs';
        $wc_session_table = $wpdb->prefix . 'woocommerce_sessions';

        $rows_data = [];
        $now_ts    = current_time( 'timestamp' );
        $index     = 0;
        foreach ( (array) $users as $u ) {
            // Get session log for this user.
            $log = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, session_token, login_time, is_online
                 FROM {$log_table}
                 WHERE user_id=%d AND is_online=1
                 ORDER BY login_time DESC
                 LIMIT 1",
                $u->ID
            ) );

            // Determine online state.
            $online = $log && (int) $log->is_online === 1;
            if ( ! $online && function_exists( 'aaa_ac_is_user_online' ) ) {
                $online = aaa_ac_is_user_online( $u->ID );
            }
            // Skip offline users when filter enabled.
            if ( $online_only && ! $online ) {
                continue;
            }

            // Session start.
            $start = '';
            if ( $log && $log->login_time ) {
                $start = (string) $log->login_time;
            } else {
                // Fallback to latest login from session tokens.
                if ( class_exists( 'WP_Session_Tokens' ) ) {
                    $mgr    = WP_Session_Tokens::get_instance( $u->ID );
                    $tokens = is_object( $mgr ) ? $mgr->get_all() : [];
                    $best   = 0;
                    foreach ( $tokens as $t ) {
                        $login = isset( $t['login'] ) ? (int) $t['login'] : 0;
                        if ( $login > $best ) {
                            $best = $login;
                        }
                    }
                    if ( $best ) {
                        $start = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $best ), 'Y-m-d H:i:s' );
                    }
                }
            }

            // Token display.
            $token   = $log && $log->session_token ? (string) $log->session_token : '';
            $tokCell = '&mdash;';
            if ( is_string( $token ) && preg_match( '/^[a-f0-9]{40,64}$/i', $token ) ) {
                $short   = substr( $token, 0, 12 ) . ( strlen( $token ) > 12 ? '…' : '' );
                $tokCell = '<code class="aaa-ac-code" title="' . esc_attr( $token ) . '">' . esc_html( $short ) . '</code>';
            }

            // Last activity: attempt to get user meta or fallback to session start.
            $last_activity = '';
            $last_meta_keys = [ 'last_activity', 'wc_last_active', 'last_login' ];
            foreach ( $last_meta_keys as $key ) {
                $val = get_user_meta( $u->ID, $key, true );
                if ( ! empty( $val ) ) {
                    $last_activity = (string) $val;
                    break;
                }
            }
            if ( ! $last_activity ) {
                $last_activity = $start;
            }

            // Customer flag: check for meta keyed is_customer or paying_customer.
            $is_customer = false;
            $meta_checks = [ 'is_customer', 'paying_customer', '_is_paying_customer' ];
            foreach ( $meta_checks as $meta_key ) {
                $val = get_user_meta( $u->ID, $meta_key, true );
                if ( ! empty( $val ) && $val !== '0' && $val !== 'false' ) {
                    $is_customer = true;
                    break;
                }
            }

            // Cart indicator: see if WooCommerce session table holds any cart contents for this user.
            $has_cart = false;
            if ( $wc_session_table ) {
                $sess_value = $wpdb->get_var( $wpdb->prepare(
                    "SELECT session_value FROM {$wc_session_table} WHERE session_key = %s LIMIT 1",
                    (string) $u->ID
                ) );
                if ( $sess_value ) {
                    // Attempt to unserialize if needed.
                    $data = maybe_unserialize( $sess_value );
                    if ( is_string( $data ) ) {
                        $decoded = json_decode( $data, true );
                        if ( is_array( $decoded ) ) {
                            $data = $decoded;
                        }
                    }
                    if ( is_array( $data ) && ! empty( $data['cart'] ) && is_array( $data['cart'] ) ) {
                        $has_cart = ! empty( $data['cart'] );
                    }
                }
            }

            // Compute timestamps and duration.
            $start_ts = $start ? strtotime( $start ) : 0;
            $last_ts  = $last_activity ? strtotime( $last_activity ) : 0;
            $diff_sec = $start_ts ? max( 0, $now_ts - $start_ts ) : 0;
            // Build human readable duration string.
            $days    = (int) floor( $diff_sec / DAY_IN_SECONDS );
            $hours   = (int) floor( ( $diff_sec % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
            $minutes = (int) floor( ( $diff_sec % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
            if ( $days > 0 ) {
                $duration_label = sprintf( '%dd %dh %dm', $days, $hours, $minutes );
            } elseif ( $hours > 0 ) {
                $duration_label = sprintf( '%dh %dm', $hours, $minutes );
            } elseif ( $minutes > 0 ) {
                $duration_label = sprintf( '%dm', $minutes );
            } else {
                $duration_label = '0m';
            }

            $rows_data[] = [
                'user'          => $u,
                'start'         => $start,
                'start_ts'      => $start_ts,
                'last_activity' => $last_activity,
                'last_ts'       => $last_ts,
                'online'        => $online,
                'is_customer'   => $is_customer,
                'has_cart'      => $has_cart,
                'token_html'    => $tokCell,
                'duration_sec'  => $diff_sec,
                'duration'      => $duration_label,
            ];
        }

        // Sort rows by session start timestamp descending.
        usort( $rows_data, function ( $a, $b ) {
            return $b['start_ts'] <=> $a['start_ts'];
        } );

        // Build the HTML rows.
        $html = '';
        $index = 0;
        foreach ( $rows_data as $row ) {
            $u = $row['user'];
            $index++;
            $html .= '<tr data-user-id="' . (int) $u->ID . '">';
            // Checkbox.
            $html .= '<td><input type="checkbox" class="aaa-ac-sel" data-user-id="' . (int) $u->ID . '" /></td>';
            // Index.
            $html .= '<td data-sort-value="' . $index . '">' . $index . '</td>';
            // Display name.
            $html .= '<td>' . esc_html( $u->display_name ) . '</td>';
            // User ID with sort value.
            $html .= '<td data-sort-value="' . (int) $u->ID . '"><a href="' . esc_url( network_admin_url( 'user-edit.php?user_id=' . $u->ID ) ) . '">' . (int) $u->ID . '</a></td>';
            // Session start with sort value.
            $html .= '<td data-sort-value="' . ( $row['start_ts'] ?: 0 ) . '">' . ( $row['start'] ? esc_html( $row['start'] ) : '&mdash;' ) . '</td>';
            // Last activity with sort value.
            $html .= '<td data-sort-value="' . ( $row['last_ts'] ?: 0 ) . '">' . ( $row['last_activity'] ? esc_html( $row['last_activity'] ) : '&mdash;' ) . '</td>';
            // Duration with sort value.
            $html .= '<td data-sort-value="' . ( $row['duration_sec'] ?: 0 ) . '">' . esc_html( $row['duration'] ) . '</td>';
            // Status.
            $html .= '<td>' . ( $row['online'] ? '<span class="aaa-ac-online">' . esc_html__( 'Online', 'aaa-ac' ) . '</span>' : '<span class="aaa-ac-offline">' . esc_html__( 'Offline', 'aaa-ac' ) . '</span>' ) . '</td>';
            // Customer flag.
            $html .= '<td>' . ( $row['is_customer'] ? esc_html__( 'Yes', 'aaa-ac' ) : esc_html__( 'No', 'aaa-ac' ) ) . '</td>';
            // Cart indicator.
            $html .= '<td>' . ( $row['has_cart'] ? esc_html__( 'Yes', 'aaa-ac' ) : esc_html__( 'No', 'aaa-ac' ) ) . '</td>';
            // Action button.
            $html .= '<td>';
            if ( $row['online'] ) {
                $html .= '<button type="button" class="button button-secondary aaa-ac-end-one" data-user-id="' . (int) $u->ID . '">' . esc_html__( 'End', 'aaa-ac' ) . '</button>';
            } else {
                $html .= '—';
            }
            $html .= '</td>';
            // Token.
            $html .= '<td>' . $row['token_html'] . '</td>';
            $html .= '</tr>';
        }

        wp_send_json_success( [ 'rows' => $html ] );
    }

    /**
     * End one or more user sessions.  This method accepts either a single user_id
     * or an array of user_ids.  All tokens for each user will be destroyed.
     *
     * Expected parameters:
     * - user_ids (array or scalar) : IDs of users to end sessions for
     * - nonce (string)             : security token
     */
    public static function end_sessions(){
        if ( ! current_user_can('manage_network_users') ) {
            wp_send_json_error('no_cap');
        }
        check_ajax_referer('aaa_ac_ajax','nonce');

        $raw = [];
        if ( isset($_POST['user_ids']) ) {
            $raw = $_POST['user_ids'];
        } elseif ( isset($_GET['user_ids']) ) {
            $raw = $_GET['user_ids'];
        }

        $ids = [];
        if ( is_array($raw) ) {
            foreach ( $raw as $id ) {
                $id = absint( $id );
                if ( $id ) {
                    $ids[] = $id;
                }
            }
        } else {
            $id = absint( $raw );
            if ( $id ) {
                $ids[] = $id;
            }
        }

        if ( empty( $ids ) ) {
            wp_send_json_error( 'no_ids' );
        }

        $ended = 0;
        foreach ( $ids as $uid ) {
            // Mark logs ended.
            if ( class_exists('AC_Logger') ) {
                AC_Logger::mark_ended_all( $uid, 'admin', 1, 'bulk' );
            }
            // Destroy sessions.
            if ( class_exists('WP_Session_Tokens') ) {
                WP_Session_Tokens::get_instance( $uid )->destroy_all();
            }
            $ended++;
        }
        wp_send_json_success( ['ended' => $ended] );
    }
}