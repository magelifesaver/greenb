<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/admin/class-aaa-oc-forcast-menu.php
 * Purpose: Register a Forecast admin page within WordPress. This page provides
 *          a simple overview of the module and instructions for using the
 *          bulk action. Additional settings and dashboards can be added in
 *          future versions.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forcast_Menu
 *
 * Adds a top-level menu item labelled "Forecast". The page displays basic
 * information about the Forecast module. The menu appears only for users
 * capable of managing WooCommerce.
 */
class AAA_OC_Forcast_Menu {

    /**
     * Hook into admin_menu to register our page.
     */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'add_page' ] );
    }

    /**
     * Add the Forecast page to the admin menu.
     */
    public static function add_page() : void {
        add_menu_page(
            esc_html__( 'Forecast', 'aaa-order-workflow' ),
            esc_html__( 'Forecast', 'aaa-order-workflow' ),
            'manage_woocommerce',
            'aaa-oc-forcast',
            [ __CLASS__, 'render_page' ],
            'dashicons-chart-line',
            56
        );
    }

    /**
     * Render the Forecast admin page. Keeps the output minimal for now.
     */
    public static function render_page() : void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Forecast Module', 'aaa-order-workflow' ); ?></h1>
            <p><?php esc_html_e( 'The Forecast module allows you to create draft purchase orders from selected products. Use the bulk action in the product list to queue products with a default quantity of 1.', 'aaa-order-workflow' ); ?></p>
            <p><?php esc_html_e( 'Future updates will extend this page with forecasting rules, minimum quantity settings and integration with supplier data.', 'aaa-order-workflow' ); ?></p>
        </div>
        <?php
    }
}