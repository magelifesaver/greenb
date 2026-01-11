<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/admin/class-aaa-oc-forcast-bulk.php
 * Purpose: Register a bulk action on the WooCommerce product list to create
 *          draft purchase orders from selected products. Quantities default
 *          to 1 and the result is summarised via admin notices.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forcast_Bulk
 *
 * Hooks into the product admin table to provide a new bulk action for
 * generating draft purchase orders. The action collects selected product
 * IDs, hands them off to the helper class and then reports back via the
 * redirect URL. An admin notice displays the outcome on return.
 */
class AAA_OC_Forcast_Bulk {

    /**
     * Register our filters and actions. Called on plugins_loaded from the loader.
     */
    public static function init() : void {
        // Add the bulk action to the dropdown on the products screen.
        add_filter( 'bulk_actions-edit-product', [ __CLASS__, 'register_action' ] );
        // Handle the submitted bulk action for products.
        add_filter( 'handle_bulk_actions-edit-product', [ __CLASS__, 'handle_action' ], 10, 3 );
        // Show results after redirect.
        add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
    }

    /**
     * Inject our custom action into the bulk actions array.
     *
     * @param array<string,string> $actions Existing bulk actions.
     * @return array<string,string> Modified actions.
     */
    public static function register_action( array $actions ) : array {
        $actions['aaa_oc_forcast_create_po'] = esc_html__( 'Create Draft POs (Qty=1)', 'aaa-order-workflow' );
        return $actions;
    }

    /**
     * Process our custom bulk action.
     *
     * @param string     $redirect_url The redirect URL.
     * @param string     $action       The action being performed.
     * @param array<int> $product_ids  The IDs of selected products.
     * @return string Redirect URL with added query arguments.
     */
    public static function handle_action( string $redirect_url, string $action, array $product_ids ) : string {
        if ( $action !== 'aaa_oc_forcast_create_po' ) {
            return $redirect_url;
        }
        // Permission check: users must be able to manage WooCommerce to create POs.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return add_query_arg( [ 'forcast_po_errors' => count( (array) $product_ids ) ], $redirect_url );
        }
        // Default result counts.
        $result = [ 'created' => 0, 'errors' => 0 ];
        // Delegate to helper if available.
        if ( class_exists( 'AAA_OC_Forcast_Helper' ) && method_exists( 'AAA_OC_Forcast_Helper', 'create_po_from_products' ) ) {
            $result = AAA_OC_Forcast_Helper::create_po_from_products( $product_ids );
        }
        return add_query_arg( [
            'forcast_po_created' => $result['created'],
            'forcast_po_errors'  => $result['errors'],
        ], $redirect_url );
    }

    /**
     * Output an admin notice summarising the PO creation results.
     */
    public static function admin_notice() : void {
        $created = isset( $_GET['forcast_po_created'] ) ? absint( $_GET['forcast_po_created'] ) : 0;
        $errors  = isset( $_GET['forcast_po_errors'] )  ? absint( $_GET['forcast_po_errors'] )  : 0;
        if ( ! $created && ! $errors ) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php
                printf(
                    /* translators: 1: number of purchase orders created, 2: number of errors */
                    esc_html__( 'Forecast: %1$d draft purchase orders created. Errors: %2$d.', 'aaa-order-workflow' ),
                    $created,
                    $errors
                );
            ?></p>
        </div>
        <?php
    }
}