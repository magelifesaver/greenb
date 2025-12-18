<?php
/**
 * Main loader for the DDD Dev Debug Manager plugin.
 *
 * This class sets up core hooks and conditionally loads admin‑specific
 * functionality. It also registers a settings link on the plugins listing
 * screen so administrators can quickly access the debug management page.
 *
 * @package DDD_Dev_Debug_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDD_Dev_Debug_Manager {
    /**
     * Construct the plugin loader.
     */
    public function __construct() {
        // Defer admin code until the back office is loaded.
        if ( is_admin() ) {
            require_once DDD_DEBUG_MANAGER_DIR . 'includes/admin/class-ddd-dev-debug-manager-admin.php';
            new DDD_Dev_Debug_Manager_Admin();
        }

        // Add a Settings link to the plugin row.
        add_filter( 'plugin_action_links_' . plugin_basename( DDD_DEBUG_MANAGER_DIR . 'ddd-dev-debug-manager.php' ), array( $this, 'add_settings_link' ) );
    }

    /**
     * Append a Settings link to the plugin action links.
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_settings_link( $links ) {
        $url      = admin_url( 'tools.php?page=ddd-dev-debug-manager' );
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ddd-dev-debug-manager' ) . '</a>';
        return $links;
    }
}

// Instantiate the loader.
new DDD_Dev_Debug_Manager();