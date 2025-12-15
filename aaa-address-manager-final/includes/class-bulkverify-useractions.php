<?php
/**
 * Bulk actions for user address verification.
 *
 * Registers additional bulk actions on the Users screen that allow
 * administrators to enqueue selected users for verification of billing,
 * shipping or both addresses. Handles insertion into the jobs table and
 * schedules the cron if necessary. Duplicate queued entries for a user are
 * avoided by checking existing queued jobs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_UserActions {
    /**
     * Register hooks for bulk actions.
     */
    public static function init() : void {
        add_filter( 'bulk_actions-users', [ __CLASS__, 'register_actions' ] );
        add_filter( 'handle_bulk_actions-users', [ __CLASS__, 'handle_action' ], 10, 3 );
    }

    /**
     * Add our custom actions to the Users bulk actions dropdown.
     *
     * @param array $actions Default actions.
     * @return array Modified actions.
     */
    public static function register_actions( array $actions ) : array {
        $actions['aaa_am_verify_billing']  = __( 'Verify Billing (AM)', 'aaa-address-manager' );
        $actions['aaa_am_verify_shipping'] = __( 'Verify Shipping (AM)', 'aaa-address-manager' );
        $actions['aaa_am_verify_both']     = __( 'Verify Billing + Shipping (AM)', 'aaa-address-manager' );
        return $actions;
    }

    /**
     * Handle the bulk action and enqueue selected users.
     *
     * @param string $redirect Original redirect URL.
     * @param string $action   Selected action slug.
     * @param array  $user_ids Array of selected user IDs.
     * @return string Modified redirect URL.
     */
    public static function handle_action( string $redirect, string $action, array $user_ids ) : string {
        // Only handle our defined actions.
        $valid = [ 'aaa_am_verify_billing', 'aaa_am_verify_shipping', 'aaa_am_verify_both' ];
        if ( ! in_array( $action, $valid, true ) ) {
            return $redirect;
        }
        $scope  = str_replace( 'aaa_am_verify_', '', $action );
        AAA_BulkVerify_Core::ensure_table();
        global $wpdb;
        $table = $wpdb->prefix . AAA_BulkVerify_Core::TABLE;
        $count = 0;
        foreach ( array_map( 'intval', $user_ids ) as $uid ) {
            // Skip if a queued job already exists for this user.
            $already = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'queued'", $uid ) );
            if ( $already ) {
                continue;
            }
            $ok = $wpdb->insert( $table, [
                'user_id'    => $uid,
                'scope'      => $scope,
                'status'     => 'queued',
                'message'    => '',
                'created_at' => current_time( 'mysql' ),
            ] );
            if ( $ok ) {
                $count++;
            }
        }
        // Schedule the cron if not already scheduled.
        if ( $count > 0 && ! wp_next_scheduled( AAA_BulkVerify_Core::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, AAA_BulkVerify_Core::CRON_HOOK );
        }
        // Append a result parameter to the redirect so we can show a notice.
        return add_query_arg( 'aaa_am_queued', $count, $redirect );
    }
}