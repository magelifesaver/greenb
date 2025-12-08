<?php
/**
 * class-aaa-wf-pwf-index.php
 *
 * Handles creation and upsert into the custom “aaa_wf_pwf_index” table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_WF_PWF_Index {

    /**
     * Creates (or updates) the `wp_aaa_wf_pwf_index` table
     * with all 23 CSV columns + `was_created` flag.
     */
    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = $wpdb->prefix . 'aaa_wf_pwf_index';
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT,
            wm_id VARCHAR(50),
            wm_external_id VARCHAR(50),
            wm_product_id VARCHAR(50),
            wm_og_name TEXT,
            wm_og_slug TEXT,
            wm_og_body TEXT,
            wm_category_raw TEXT,
            wm_og_brand_id TEXT,
            wm_og_brand_name TEXT,
            wm_strain_id VARCHAR(50),
            wm_genetics VARCHAR(100),
            wm_thc_percentage VARCHAR(20),
            wm_cbd_percentage VARCHAR(20),
            wm_license_type VARCHAR(50),
            wm_price_currency VARCHAR(10),
            wm_unit_price DECIMAL(10,2),
            wm_sale_price DECIMAL(10,2),
            wm_discount_type VARCHAR(20),
            wm_discount_value VARCHAR(20),
            wm_online_orderable BOOLEAN,
            wm_published BOOLEAN,
            wm_created_at DATETIME,
            wm_updated_at DATETIME,
            was_created BOOLEAN DEFAULT 0
        ) $charset;
        ";

        dbDelta( $sql );
    }

    /**
     * Inserts or updates a row in the index table for this product.
     *
     * @param int   $post_id       The WooCommerce product ID.
     * @param array $data          The CSV row (associative array keyed by header).
     * @param bool  $was_created   True if this product was created by the import, false otherwise.
     */
    public static function upsert_index( $post_id, $data, $was_created ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aaa_wf_pwf_index';

        // Sanitize/prepare values
        $wm_id           = isset( $data['id'] ) ? sanitize_text_field( $data['id'] ) : '';
        $wm_external_id  = isset( $data['external_id'] ) ? sanitize_text_field( $data['external_id'] ) : '';
        $wm_product_id   = isset( $data['product_id'] ) ? sanitize_text_field( $data['product_id'] ) : '';
        $wm_og_name      = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $wm_og_slug      = isset( $data['slug'] ) ? sanitize_text_field( $data['slug'] ) : '';
        $wm_og_body      = isset( $data['body'] ) ? sanitize_textarea_field( $data['body'] ) : '';
        $wm_category_raw = isset( $data['categories'] ) ? sanitize_text_field( $data['categories'] ) : '';
        $wm_og_brand_id  = isset( $data['brand_id'] ) ? sanitize_text_field( $data['brand_id'] ) : '';
        $wm_og_brand_name= isset( $data['brand_name'] ) ? sanitize_text_field( $data['brand_name'] ) : '';
        $wm_strain_id    = isset( $data['strain_id'] ) ? sanitize_text_field( $data['strain_id'] ) : '';
        $wm_genetics     = isset( $data['genetics'] ) ? sanitize_text_field( $data['genetics'] ) : '';
        $wm_thc          = isset( $data['thc_percentage'] ) ? sanitize_text_field( $data['thc_percentage'] ) : '';
        $wm_cbd          = isset( $data['cbd_percentage'] ) ? sanitize_text_field( $data['cbd_percentage'] ) : '';
        $wm_license      = isset( $data['license_type'] ) ? sanitize_text_field( $data['license_type'] ) : '';
        $wm_currency     = isset( $data['price_currency'] ) ? sanitize_text_field( $data['price_currency'] ) : '';
        $wm_unit_price   = isset( $data['unit_price'] ) ? floatval( $data['unit_price'] ) : 0.00;
        $wm_discount_type= isset( $data['price_rule_adjustment_type'] ) ? sanitize_text_field( $data['price_rule_adjustment_type'] ) : '';
        $wm_discount_val = isset( $data['price_rule_adjustment_value'] ) ? sanitize_text_field( $data['price_rule_adjustment_value'] ) : '';
        $wm_sale_price   = 0.00;
        if ( $wm_discount_type === 'percentage' && $wm_unit_price > 0 ) {
            $discount_perc = floatval( $wm_discount_val );
            $wm_sale_price = $wm_unit_price - ( $wm_unit_price * $discount_perc / 100 );
        }
        $wm_online_order = ( isset( $data['online_orderable'] ) && strtoupper( $data['online_orderable'] ) === 'TRUE' ) ? 1 : 0;
        $wm_published    = ( isset( $data['published'] ) && strtoupper( $data['published'] ) === 'TRUE' ) ? 1 : 0;
        $wm_created_at   = isset( $data['created_at'] ) && ! empty( $data['created_at'] ) 
                            ? sanitize_text_field( $data['created_at'] ) 
                            : current_time( 'mysql' );
        $wm_updated_at   = isset( $data['updated_at'] ) && ! empty( $data['updated_at'] ) 
                            ? sanitize_text_field( $data['updated_at'] ) 
                            : current_time( 'mysql' );
        $was_created_int = $was_created ? 1 : 0;

        // Perform replace (insert or update)
        $wpdb->replace(
            $table,
            array(
                'post_id'            => $post_id,
                'wm_id'              => $wm_id,
                'wm_external_id'     => $wm_external_id,
                'wm_product_id'      => $wm_product_id,
                'wm_og_name'         => $wm_og_name,
                'wm_og_slug'         => $wm_og_slug,
                'wm_og_body'         => $wm_og_body,
                'wm_category_raw'    => $wm_category_raw,
                'wm_og_brand_id'     => $wm_og_brand_id,
                'wm_og_brand_name'   => $wm_og_brand_name,
                'wm_strain_id'       => $wm_strain_id,
                'wm_genetics'        => $wm_genetics,
                'wm_thc_percentage'  => $wm_thc,
                'wm_cbd_percentage'  => $wm_cbd,
                'wm_license_type'    => $wm_license,
                'wm_price_currency'  => $wm_currency,
                'wm_unit_price'      => $wm_unit_price,
                'wm_sale_price'      => $wm_sale_price,
                'wm_discount_type'   => $wm_discount_type,
                'wm_discount_value'  => $wm_discount_val,
                'wm_online_orderable'=> $wm_online_order,
                'wm_published'       => $wm_published,
                'wm_created_at'      => $wm_created_at,
                'wm_updated_at'      => $wm_updated_at,
                'was_created'        => $was_created_int,
            ),
            array(
                '%d',   // post_id
                '%s',   // wm_id
                '%s',   // wm_external_id
                '%s',   // wm_product_id
                '%s',   // wm_og_name
                '%s',   // wm_og_slug
                '%s',   // wm_og_body
                '%s',   // wm_category_raw
                '%s',   // wm_og_brand_id
                '%s',   // wm_og_brand_name
                '%s',   // wm_strain_id
                '%s',   // wm_genetics
                '%s',   // wm_thc_percentage
                '%s',   // wm_cbd_percentage
                '%s',   // wm_license_type
                '%s',   // wm_price_currency
                '%f',   // wm_unit_price
                '%f',   // wm_sale_price
                '%s',   // wm_discount_type
                '%s',   // wm_discount_value
                '%d',   // wm_online_orderable (boolean as int)
                '%d',   // wm_published (boolean as int)
                '%s',   // wm_created_at
                '%s',   // wm_updated_at
                '%d'    // was_created
            )
        );
    }
}
