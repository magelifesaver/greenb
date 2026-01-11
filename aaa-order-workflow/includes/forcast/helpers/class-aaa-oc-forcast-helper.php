<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/helpers/class-aaa-oc-forcast-helper.php
 * Purpose: Helper functions for the Forecast module, including purchase order
 *          creation logic. Provides a high-level API for creating draft POs
 *          from a list of product IDs. Actual integration with ATUM or
 *          WooCommerce purchase order systems can be layered on top of this.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forcast_Helper
 *
 * Contains static helper methods used across the Forecast module. The
 * methods here are intentionally simple and may be expanded in future
 * versions to integrate with external purchase order APIs.
 */
class AAA_OC_Forcast_Helper {

    /**
     * Initialise helper hooks. Currently a no-op but reserved for future use.
     */
    public static function init() : void {
        // Intentionally empty. Future enhancements can hook here.
    }

    /**
     * Create draft purchase orders from a list of products. Quantities
     * default to 1. Returns counts of created POs and errors.
     *
     * @param array<int|string> $product_ids Raw product IDs from the bulk action.
     * @return array{created:int, errors:int} Result counts.
     */
    public static function create_po_from_products( $product_ids ) : array {
        $ids     = array_filter( array_map( 'absint', (array) $product_ids ) );
        $created = 0;
        $errors  = 0;
        foreach ( $ids as $pid ) {
            if ( self::create_single_po( $pid ) ) {
                $created++;
            } else {
                $errors++;
            }
        }
        return [
            'created' => $created,
            'errors'  => $errors,
        ];
    }

    /**
     * Create a single draft purchase order for the given product. In this
     * implementation we simply set a meta flag on the product to mark it as
     * queued for a purchase order. Hook into the `aaa_oc_forcast_product_added_to_po`
     * action to integrate with a real PO system.
     *
     * @param int $product_id Product ID.
     * @return bool True on success.
     */
    protected static function create_single_po( int $product_id ) : bool {
        update_post_meta( $product_id, '_aaa_oc_forcast_added_to_po', 'yes' );

        /**
         * Fires when a product has been marked for addition to a forecast PO.
         *
         * @param int $product_id Product ID.
         */
        do_action( 'aaa_oc_forcast_product_added_to_po', $product_id );
        return true;
    }
}