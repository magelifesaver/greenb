<?php
/**
 * Plugin Name: LKD First Order Status Updater
 * Plugin URI:  http://yourwebsite.com/lkd-first-order-status-updater
 * Description: Automatically sets the first order of new customers to "Verification Pending" status.
 * Version:     1.0.0
 * Author:      Webmaster LKD
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lkd-first-order-status-updater
 */
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Set the first order status to "pending" for new customers.
 */
function lkd_set_first_order_status_to_pending($order_id) {
    // Ensure the order ID is present
    if (!$order_id) return;

    // Get the order object
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Check if we've already processed this order to prevent duplicate status updates
    if ('yes' === $order->get_meta('_lkd_first_order_status_updated')) {
        return;
    }

    // Get the user who placed the order
    $user = $order->get_user();
    if (!$user || !$user->ID) return; // Check if user exists

    // Count the number of orders by the user, excluding the current one
    $orders = wc_get_orders(array(
        'customer_id' => $user->ID,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'),
        'exclude' => array($order_id),
        'return' => 'ids',
    ));

    // If no previous orders are found, this is the user's first order
    if (count($orders) === 0) {
        // Set the first order's status to 'pending' ('verification pending' in your terms)
        $order->update_status('pending', 'This is the customer\'s first order, setting status to verification pending.');
        // Mark this order as having its first order status updated to prevent future updates
        $order->update_meta_data('_lkd_first_order_status_updated', 'yes');
        $order->save_meta_data();
    }
}

add_action('woocommerce_thankyou', 'lkd_set_first_order_status_to_pending', 10, 1);
/**
 * Plugin activation hook.
 */
function lkd_first_order_status_updater_activate() {
    // Check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }
}
/**
 * Plugin deactivation hook.
 */
function lkd_first_order_status_updater_deactivate() {
    // Deactivation logic can be added here
}
register_activation_hook(__FILE__, 'lkd_first_order_status_updater_activate');
register_deactivation_hook(__FILE__, 'lkd_first_order_status_updater_deactivate');
