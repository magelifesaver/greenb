<?php
/**
 * File: includes/admin/class-delivery-settings.php
 * Purpose: Provides a tabbed settings page for Delivery configuration (ETA removed).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Delivery_Settings_Page {

	public function __construct() {
	    add_action( 'admin_menu', [ $this, 'register_menu' ], 60 );
	}

	/** Register submenu under Workflow */
	public function register_menu() {
	    add_submenu_page(
	        'aaa-oc-workflow-board',                                 // ✅ parent: Workflow menu
            __( 'Workflow Delivery Coords AutoComplete Settings', 'aaa-delivery-blocks-coords' ),
            __( 'WF Coords AutoComplete Settings', 'aaa-delivery-blocks-coords' ),
	        'manage_woocommerce',
	        'delivery-settings',                                      // same slug
	        [ $this, 'render_page' ]                                  // same render function
	    );
	}

    /** Render the tab container + load the active tab */
    public function render_page() {
        $tabs = $this->get_tabs();
        $active = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'global';

        echo '<div class="wrap"><h1>'.esc_html__('Coords AutoComplete Settings','aaa-delivery-blocks-coords').'</h1>';
        echo '<h2 class="nav-tab-wrapper">';

        foreach ( $tabs as $id => $label ) {
            $class = $id === $active ? ' nav-tab-active' : '';
            echo '<a href="?page=delivery-settings&tab='.esc_attr($id).'" class="nav-tab'.$class.'">'.esc_html($label).'</a>';
        }
        echo '</h2>';

        $this->load_tab( $active );
        echo '</div>';
    }

    /** Define available tabs (exclude ETA) */
    protected function get_tabs() {
        $tabs = [];
        foreach ( glob( plugin_dir_path(__FILE__) . 'tabs/tab-*.php' ) as $file ) {
            $id = basename($file, '.php');
            $id = str_replace('tab-', '', $id);

            if ( $id === 'eta' ) {
                continue; // strip ETA tab completely
            }

            $tabs[$id] = ucwords(str_replace('-', ' ', $id));
        }
        return $tabs;
    }

    /** Load the requested tab file */
    protected function load_tab( $id ) {
        if ( $id === 'eta' ) {
            echo '<p>'.esc_html__('Invalid tab.','aaa-delivery-blocks-coords').'</p>';
            return;
        }

        $file = plugin_dir_path(__FILE__) . 'tabs/tab-' . $id . '.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<p>'.esc_html__('Invalid tab.','aaa-delivery-blocks-coords').'</p>';
        }
    }
}

new Delivery_Settings_Page();
