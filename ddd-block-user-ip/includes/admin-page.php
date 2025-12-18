<?php
/** Settings page UI for DDD Block User IP.
 * Generates the admin page HTML, including configuration forms and a
 * recent activity table. Split from menu logic to aid maintainability. */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the plugin settings page: manual/safe lists, automatic block
 * configuration and recent log entries. The status column indicates
 * whether an entry is safe, manually blocked, automatically blocked by
 * country or normal.
 */
function ddd_buip_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ddd_buip_ip_log';
    // Handle row actions from the table (block/unblock/safelist/unsafelist).
    if ( isset( $_GET['ddd_buip_action'], $_GET['ip'] ) ) {
        $action = sanitize_text_field( wp_unslash( $_GET['ddd_buip_action'] ) );
        $ip     = sanitize_text_field( wp_unslash( $_GET['ip'] ) );
        check_admin_referer( 'ddd_buip_manage_ip_' . $ip );
        $block_list = ddd_buip_get_ip_list( 'ddd_buip_ips' );
        $safe_list  = ddd_buip_get_ip_list( 'ddd_buip_safe_ips' );
        switch ( $action ) {
            case 'block':
                $block_list[ $ip ] = $ip;
                unset( $safe_list[ $ip ] );
                break;
            case 'unblock':
                unset( $block_list[ $ip ] );
                break;
            case 'safe':
                $safe_list[ $ip ] = $ip;
                unset( $block_list[ $ip ] );
                break;
            case 'unsafelist':
                unset( $safe_list[ $ip ] );
                break;
        }
        ddd_buip_save_ip_list( 'ddd_buip_ips', $block_list );
        ddd_buip_save_ip_list( 'ddd_buip_safe_ips', $safe_list );
        wp_safe_redirect( remove_query_arg( array( 'ddd_buip_action', 'ip', '_wpnonce' ) ) );
        exit;
    }
    // Fetch up to 50 most recent log entries for display.
    $recent_logs = $wpdb->get_results( "SELECT ip, country, hits, last_seen, score FROM $table ORDER BY last_seen DESC LIMIT 50" );
    $ips      = get_option( 'ddd_buip_ips', '' );
    $safe_ips = get_option( 'ddd_buip_safe_ips', '' );
    $country  = strtoupper( get_option( 'ddd_buip_allowed_country', 'US' ) );
    $auto     = (int) get_option( 'ddd_buip_auto_block', 0 );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Block User IPs', 'ddd-block-user-ip' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'ddd_buip_settings' ); ?>
            <h2><?php esc_html_e( 'Manual IP Block List', 'ddd-block-user-ip' ); ?></h2>
            <p><?php esc_html_e( 'Enter one IP address per line. These IPs are always blocked with a 403 for the entire site.', 'ddd-block-user-ip' ); ?></p>
            <textarea name="ddd_buip_ips" rows="6" class="large-text code"><?php echo esc_textarea( $ips ); ?></textarea>
            <h2><?php esc_html_e( 'Safe IP List', 'ddd-block-user-ip' ); ?></h2>
            <p><?php esc_html_e( 'Enter one IP address per line. These IPs are never blocked, even by country rules.', 'ddd-block-user-ip' ); ?></p>
            <textarea name="ddd_buip_safe_ips" rows="4" class="large-text code"><?php echo esc_textarea( $safe_ips ); ?></textarea>
            <h2><?php esc_html_e( 'Automatic Country Block', 'ddd-block-user-ip' ); ?></h2>
            <p><?php esc_html_e( 'This uses WooCommerce geolocation (MaxMind). If enabled, any IP outside the allowed country is blocked for the entire site.', 'ddd-block-user-ip' ); ?></p>
            <label>
                <input type="checkbox" name="ddd_buip_auto_block" value="1" <?php checked( $auto, 1 ); ?> />
                <?php esc_html_e( 'Automatically block IPs outside the allowed country', 'ddd-block-user-ip' ); ?>
            </label>
            <p>
                <label>
                    <?php esc_html_e( 'Allowed country ISO code (e.g. US):', 'ddd-block-user-ip' ); ?><br />
                    <input type="text" name="ddd_buip_allowed_country" value="<?php echo esc_attr( $country ); ?>" size="4" />
                </label>
            </p>
            <?php submit_button(); ?>
        </form>
        <p><em><?php printf( esc_html__( 'Log table: %s', 'ddd-block-user-ip' ), '<code>' . esc_html( $table ) . '</code>' ); ?></em></p>
        <h2><?php esc_html_e( 'Recent IP Activity', 'ddd-block-user-ip' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'IP Address', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Country', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Hits', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Last Seen', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Score', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'ddd-block-user-ip' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'ddd-block-user-ip' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $recent_logs ) ) : ?>
                <?php foreach ( $recent_logs as $log ) : ?>
                    <?php
                    $ip      = $log->ip;
                    $is_block = ddd_buip_is_in_manual_block_list( $ip );
                    $is_safe  = ddd_buip_is_in_safe_list( $ip );
                    $row_country = $log->country ? strtoupper( $log->country ) : '';
                    $would_country_block = ( $auto && $row_country && $row_country !== $country && ! $is_safe );
                    $status = __( 'Normal', 'ddd-block-user-ip' );
                    if ( $is_safe ) {
                        $status = __( 'Safe', 'ddd-block-user-ip' );
                    } elseif ( $is_block ) {
                        $status = __( 'Blocked (Manual)', 'ddd-block-user-ip' );
                    } elseif ( $would_country_block ) {
                        $status = __( 'Blocked (Country)', 'ddd-block-user-ip' );
                    }
                    $base_url  = admin_url( 'tools.php?page=ddd-block-user-ip' );
                    $block_url = wp_nonce_url(
                        add_query_arg( array( 'ddd_buip_action' => $is_block ? 'unblock' : 'block', 'ip' => rawurlencode( $ip ) ), $base_url ),
                        'ddd_buip_manage_ip_' . $ip
                    );
                    $safe_url  = wp_nonce_url(
                        add_query_arg( array( 'ddd_buip_action' => $is_safe ? 'unsafelist' : 'safe', 'ip' => rawurlencode( $ip ) ), $base_url ),
                        'ddd_buip_manage_ip_' . $ip
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $ip ); ?></td>
                        <td><?php echo esc_html( $log->country ? $log->country : '-' ); ?></td>
                        <td><?php echo esc_html( $log->hits ); ?></td>
                        <td><?php echo esc_html( $log->last_seen ); ?></td>
                        <td><?php echo esc_html( $log->score ); ?></td>
                        <td><?php echo esc_html( $status ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $block_url ); ?>">
                                <?php echo esc_html( $is_block ? __( 'Unblock', 'ddd-block-user-ip' ) : __( 'Block', 'ddd-block-user-ip' ) ); ?>
                            </a>
                            |
                            <a href="<?php echo esc_url( $safe_url ); ?>">
                                <?php echo esc_html( $is_safe ? __( 'Remove safe', 'ddd-block-user-ip' ) : __( 'Safelist', 'ddd-block-user-ip' ) ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'No log entries yet.', 'ddd-block-user-ip' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
