<?php
/**
 * File Path: /aaa-order-workflow/includes/announcements/class-aaa-oc-annc-loader.php
 *
 * Purpose:
 * - Ensures tables exist.
 * - Registers admin settings page.
 * - Enqueues popup assets on the Workflow Board screen.
 * - Registers AJAX.
 * - Uses a custom capability gate ('aaa_oc_view_announcements').
 * - Seeds that capability ONCE to the Shop Manager role (if it exists) so it "exists" in WP.
 *   -> You can then manage it in Admin Menu Editor Pro (add/remove from any role/user).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Announcements_Loader {

    protected $settings_page;

    public function __construct() {
        // Ensure DB tables exist.
        add_action( 'admin_init', [ $this, 'ensure_tables' ] );

        // Seed capability exactly once to Shop Manager so AME can see/manage it.
        add_action( 'init', [ $this, 'ensure_capability_registered' ], 5 );

        // Admin UI (settings page)
        require_once AAA_OC_PLUGIN_DIR . 'includes/announcements/admin/class-aaa-oc-annc-settings-page.php';
        $this->settings_page = new AAA_OC_Announcements_Settings_Page();

        // AJAX
        require_once AAA_OC_PLUGIN_DIR . 'includes/announcements/ajax/class-aaa-oc-annc-ajax.php';
        AAA_OC_Announcements_Ajax::init();

        // Assets for board popup
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_board_assets' ] );

        // Capability gate lives in this feature; removing the plugin removes the gate.
        add_filter( 'aaa_oc_annc_required_cap', function () {
            return 'aaa_oc_view_announcements';
        } );
    }

    public function ensure_tables() {
        if ( class_exists( 'AAA_OC_Announcements_Table_Installer' ) ) {
            AAA_OC_Announcements_Table_Installer::maybe_install();
        }
    }

    /**
     * Create/register the custom capability by attaching it to Shop Manager ONCE.
     * - WordPress has no global cap registry; a cap "exists" when a role/user has it.
     * - If any role already has it (e.g., you added via AME), we do nothing and mark seeded.
     * - If Shop Manager role is missing, we log and try again next load.
     */
    public function ensure_capability_registered() {
        $cap = 'aaa_oc_view_announcements';

        // If we've already seeded (or any role already has it), stop.
    if ( aaa_oc_get_option( 'aaa_oc_annc_cap_seeded', 'announcements' ) ) {
        return;
    }
        if ( function_exists( 'wp_roles' ) ) {
            $roles_obj = wp_roles();
            if ( $roles_obj && ! empty( $roles_obj->roles ) ) {
                foreach ( array_keys( $roles_obj->roles ) as $slug ) {
                    $role = get_role( $slug );
                    if ( $role && isset( $role->capabilities[ $cap ] ) ) {
		aaa_oc_set_option( 'aaa_oc_annc_cap_seeded', 1, 'announcements' );
                        return;
                    }
                }
            }
        }

        // Attach to Shop Manager if it exists.
        $shop_mgr = get_role( 'shop_manager' );
        if ( $shop_mgr ) {
            $shop_mgr->add_cap( $cap );
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( '[ANN] Seeded capability ' . $cap . ' to Shop Manager (one-time)' );
            }
		aaa_oc_set_option( 'aaa_oc_annc_cap_seeded', 1, 'announcements' );
        } else {
            // WooCommerce not ready or role renamed; try again next request.
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( '[ANN] Shop Manager role not found; capability not seeded yet' );
            }
        }
    }

    public function enqueue_board_assets() {
        // Only on the Workflow Board page.
        $screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_board = isset( $_GET['page'] ) && $_GET['page'] === 'aaa-oc-workflow-board';

        if ( ! is_admin() || ! $is_board || ! $screen || strpos( (string) $screen->id, 'aaa-oc' ) === false ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'aaa-oc-annc-css',
            AAA_OC_PLUGIN_URL . 'includes/announcements/assets/css/announcements.css',
            [],
            defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0'
        );

        // JS
        wp_enqueue_script(
            'aaa-oc-annc-js',
            AAA_OC_PLUGIN_URL . 'includes/announcements/assets/js/announcements.js',
            [ 'jquery' ],
            defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0',
            true
        );

        wp_localize_script( 'aaa-oc-annc-js', 'AAA_OC_ANN', [
            'ajax'  => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aaa_oc_annc' ),
            'i18n'  => [
                'ack'     => __( 'I have read this and I am up to date with the new changes.', 'aaa-oc' ),
                'button'  => __( 'Acknowledge & Close', 'aaa-oc' ),
                'next'    => __( 'Next', 'aaa-oc' ),
            ],
        ] );
    }
}
