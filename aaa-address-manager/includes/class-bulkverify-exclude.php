<?php
/**
 * Handles the user exclusion action from the mass verification page.
 *
 * Provides an admin_post handler that marks a user as excluded from future
 * listings by setting the _aaa_am_excluded user meta. The link on the
 * mass page includes a nonce and passes the user ID via GET.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_Exclude {
    /**
     * Register the admin_post hook.
     */
    public static function init() : void {
        add_action( 'admin_post_aaa_am_exclude_user', [ __CLASS__, 'handle' ] );
    }

    /**
     * Handle the exclude user request. Requires manage_options capability
     * and a valid nonce. After marking the user, redirects back.
     */
    public static function handle() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }
        $uid = isset( $_GET['uid'] ) ? intval( $_GET['uid'] ) : 0;
        check_admin_referer( 'aaa_am_exclude_' . $uid );
        if ( $uid ) {
            update_user_meta( $uid, '_aaa_am_excluded', 'yes' );
        }
        wp_safe_redirect( admin_url( 'tools.php?page=aaa-am-mass&excluded=1' ) );
        exit;
    }
}