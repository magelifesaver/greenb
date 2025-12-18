<?php
/**
 * Admin menu and settings registration for DDD Block User IP.
 *
 * This file registers the plugin's admin menu, settings and plugin list link.
 * Splitting these actions into a separate module keeps individual files short
 * and makes it easier to locate related logic. The settings page
 * implementation lives in admin-page.php.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add the Block User IP page under the Tools menu.
 */
function ddd_buip_add_menu() {
    add_management_page(
        __( 'Block User IPs', 'ddd-block-user-ip' ),
        __( 'Block User IPs', 'ddd-block-user-ip' ),
        'manage_options',
        'ddd-block-user-ip',
        'ddd_buip_render_settings_page'
    );
}
add_action( 'admin_menu', 'ddd_buip_add_menu' );

/**
 * Register plugin options for the settings page. These keys correspond
 * directly to the fields on the settings form.
 */
function ddd_buip_register_settings() {
    register_setting( 'ddd_buip_settings', 'ddd_buip_ips' );
    register_setting( 'ddd_buip_settings', 'ddd_buip_safe_ips' );
    register_setting( 'ddd_buip_settings', 'ddd_buip_allowed_country' );
    register_setting( 'ddd_buip_settings', 'ddd_buip_auto_block' );
}
add_action( 'admin_init', 'ddd_buip_register_settings' );

/**
 * Add a settings link to the plugin's action links on the plugins list page.
 *
 * @param string[] $links Action links array.
 * @return string[] Modified links with settings link at the beginning.
 */
function ddd_buip_action_links( $links ) {
    if ( current_user_can( 'manage_options' ) ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=ddd-block-user-ip' ) ) . '">' . esc_html__( 'Settings', 'ddd-block-user-ip' ) . '</a>';
        array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( DDD_BUIP_DIR . 'ddd-block-user-ip.php' ), 'ddd_buip_action_links' );
