<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-po-processor.php
 * Purpose: Creates ATUM Purchase Orders from queued forecast PO rows. Uses
 *          default settings for supplier, multiple suppliers, expected date,
 *          description and optional meta data.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_PO_Processor {

    /**
     * Build a Purchase Order from queue rows and return the PO ID.
     *
     * @param array[] $rows Queue rows.
     * @return int PO ID or 0 on failure.
     */
    public static function create_purchase_order( array $rows ): int {
        if ( empty( $rows ) || ! function_exists( 'wc_get_product' ) ) {
            return 0;
        }

        $po = self::make_po_instance();
        if ( ! $po ) {
            return 0;
        }

        $settings = self::get_po_settings();
        $supplier_id = absint( $settings['supplier_id'] ?? 0 );
        $multiple_suppliers = ( $settings['multiple_suppliers'] ?? 'yes' ) === 'yes';
        $date_expected = $settings['date_expected'] ?? '';
        $description = $settings['description'] ?? '';
        $meta_data = $settings['meta_data'] ?? [];

        if ( $supplier_id > 0 && method_exists( $po, 'set_supplier' ) ) {
            $po->set_supplier( $supplier_id );
        }
        if ( method_exists( $po, 'set_multiple_suppliers' ) ) {
            if ( $supplier_id > 0 ) {
                $po->set_multiple_suppliers( $multiple_suppliers );
            } else {
                $po->set_multiple_suppliers( true );
            }
        }
        if ( $date_expected && method_exists( $po, 'set_date_expected' ) ) {
            $po->set_date_expected( $date_expected );
        }
        if ( $description && method_exists( $po, 'set_description' ) ) {
            $po->set_description( $description );
        }

        foreach ( $meta_data as $meta ) {
            if ( is_array( $meta ) && isset( $meta['key'] ) ) {
                $po->add_meta_data( $meta['key'], $meta['value'] ?? '' );
            }
        }

        $added = 0;
        foreach ( $rows as $row ) {
            $product_id = absint( $row['product_id'] ?? 0 );
            if ( ! $product_id ) {
                continue;
            }
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }
            $qty = isset( $row['quantity'] ) ? floatval( $row['quantity'] ) : 1;
            if ( $qty <= 0 ) {
                $qty = 1;
            }
            $price = isset( $row['price'] ) && $row['price'] !== null ? floatval( $row['price'] ) : null;

            if ( method_exists( $po, 'add_product' ) ) {
                $po->add_product( $product, $qty, [], $price );
                $added++;
            }
        }

        if ( $added < 1 ) {
            return 0;
        }

        if ( method_exists( $po, 'set_date_created' ) ) {
            $date_created = self::get_wc_now();
            if ( $date_created ) {
                $po->set_date_created( $date_created );
            }
        }

        $po->save();
        if ( method_exists( $po, 'calculate_totals' ) ) {
            $po->calculate_totals();
        }

        return method_exists( $po, 'get_id' ) ? absint( $po->get_id() ) : 0;
    }

    /**
     * Return PO defaults from settings.
     *
     * @return array
     */
    private static function get_po_settings(): array {
        if ( ! function_exists( 'aaa_oc_get_option' ) ) {
            return [];
        }
        $meta_raw = aaa_oc_get_option( 'forecast_po_meta_data', 'forecast', '' );
        $meta_data = [];
        if ( $meta_raw ) {
            $decoded = json_decode( $meta_raw, true );
            if ( is_array( $decoded ) ) {
                if ( array_keys( $decoded ) !== range( 0, count( $decoded ) - 1 ) ) {
                    foreach ( $decoded as $key => $value ) {
                        $meta_data[] = [ 'key' => (string) $key, 'value' => $value ];
                    }
                } else {
                    $meta_data = $decoded;
                }
            }
        }

        return [
            'supplier_id'        => absint( aaa_oc_get_option( 'forecast_po_supplier_id', 'forecast', 0 ) ),
            'multiple_suppliers' => aaa_oc_get_option( 'forecast_po_multiple_suppliers', 'forecast', 'yes' ),
            'date_expected'      => aaa_oc_get_option( 'forecast_po_date_expected', 'forecast', '' ),
            'description'        => aaa_oc_get_option( 'forecast_po_description', 'forecast', '' ),
            'meta_data'          => $meta_data,
        ];
    }

    /**
     * Instantiate an ATUM Purchase Order model.
     *
     * @return object|null
     */
    private static function make_po_instance() {
        if ( class_exists( '\\Atum\\PurchaseOrders\\Models\\POExtended' ) ) {
            return new \Atum\PurchaseOrders\Models\POExtended();
        }
        if ( class_exists( '\\Atum\\PurchaseOrders\\Models\\PurchaseOrder' ) ) {
            return new \Atum\PurchaseOrders\Models\PurchaseOrder();
        }
        return null;
    }

    /**
     * Get a WC_DateTime instance for now if helpers available.
     *
     * @return \WC_DateTime|null
     */
    private static function get_wc_now() {
        if ( class_exists( '\\Atum\\Inc\\Helpers' ) ) {
            return \Atum\Inc\Helpers::get_wc_time( \Atum\Inc\Helpers::get_current_timestamp() );
        }
        if ( class_exists( 'WC_DateTime' ) ) {
            return new \WC_DateTime( 'now', wp_timezone() );
        }
        return null;
    }
}
