<?php
/**
 * Plugin Name: LKD User Role Change
 * Plugin URI:  https://example.com/plugin-info
 * Description: Disables automatic user role changes based on subscription status in WooCommerce Subscriptions.
 * Version:     1.0.0
 * Author:      Webmaster LKD
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lkd-user-role-change
 * Domain Path: /languages
 */

// Ensure the script is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin initialization.
 */
function lkd_user_role_change_init() {
    add_filter( 'woocommerce_subscriptions_update_users_role', 'lkd_prevent_active_role_change', 10, 3 );
}

/**
 * Prevents changing the user role to active subscriber role.
 *
 * @param bool $allow_change Whether to allow the role change.
 * @param WP_User $user The user object.
 * @param string $role_new The new role being assigned to the user.
 * @return bool Whether to allow the role change.
 */
function lkd_prevent_active_role_change( $allow_change, $user, $role_new ) {
    // Check if the new role is the default active subscriber role.
    if ( 'default_subscriber_role' === $role_new ) {
        // Prevent changing the user's role to active subscriber.
        return false;
    }

    // Allow all other role changes.
    return $allow_change;
}

// Hook the plugin initialization to 'plugins_loaded' to ensure WooCommerce Subscriptions is loaded first.
add_action( 'plugins_loaded', 'lkd_user_role_change_init' );
