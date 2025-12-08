<?php
/**
 * class-aaa-wf-pwf-loader.php
 *
 * Responsible for bootstrapping the AAA WF PWF importer:
 * - Registers custom post meta (lkd_wm_new_sku)
 * - Adds the admin menu page for uploading CSV
 * - Handles form submission and triggers import
 * - Calls the Index class on activation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_WF_PWF_Loader {

    /**
     * Plugin activation callback.
     * Creates the index table.
     */
    public static function activate() {
        // Ensure WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            // Optionally, you could deactivate the plugin if WooCommerce is missing.
            return;
        }
        // Create (or update) the index table
        AAA_WF_PWF_Index::create_table();
    }

    /**
     * Initializes plugin: registers meta fields, adds admin menu, etc.
     */
    public static function init() {
        // Only proceed if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Register the custom meta field for matching on import
        register_post_meta( 'product', 'lkd_wm_new_sku', array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => true,
        ) );

        // Register other Weedmaps meta fields (in case they aren’t already)
        // You can register them similarly if needed:
        $wm_meta = [
            '_wm_id', '_wm_external_id', '_wm_product_id',
            '_brand_id', '_strain_id',
            'wm_brand_name', 'wm_strain_name',
            'wm_genetics', 'wm_thc_percentage', 'wm_cbd_percentage',
            'wm_license_type', 'wm_price_currency',
            'wm_discount_type', 'wm_discount_value',
            'wm_online_orderable', 'wm_published',
            'wm_created_at', 'wm_updated_at',
            'wm_category_raw', 'wm_tags_raw', 'wm_og_name',
            'wm_og_slug', 'wm_og_body'
        ];
        foreach ( $wm_meta as $meta_key ) {
            register_post_meta( 'product', $meta_key, array(
                'type'              => 'string',
                'single'            => true,
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest'      => false,
            ) );
        }

        // Add admin menu entry
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
    }

    /**
     * Registers the “WF Import” admin page under the main menu.
     */
    public static function register_admin_page() {
        add_menu_page(
            __( 'WF PWF Import', 'aaa-wf-pwf' ),    // Page title
            __( 'WF Import',    'aaa-wf-pwf' ),    // Menu title
            'manage_woocommerce',                  // Capability
            'wf-pwf-import',                       // Menu slug
            [ __CLASS__, 'render_import_page' ],   // Callback to display page
            'dashicons-database-import',           // Icon
            56                                      // Position
        );
    }

    /**
     * Renders the import page: file upload form and handles import on submit.
     */
    public static function render_import_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'aaa-wf-pwf' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Import Weedmaps Products', 'aaa-wf-pwf' ) . '</h1>';

        // Handle form submission
        if ( isset( $_POST['wf_pwf_submit'] ) && check_admin_referer( 'wf_pwf_import', 'wf_pwf_nonce' ) ) {
            if ( ! empty( $_FILES['wf_pwf_csv']['tmp_name'] ) ) {
                $csv_path = sanitize_text_field( $_FILES['wf_pwf_csv']['tmp_name'] );
                $results  = AAA_WF_PWF_Importer::import_from_csv( $csv_path );

                if ( is_wp_error( $results ) ) {
                    echo '<div class="notice notice-error"><p>' . esc_html( $results->get_error_message() ) . '</p></div>';
                } else {
                    $created = intval( $results['created'] );
                    $updated = intval( $results['updated'] );
                    $skipped = intval( $results['skipped'] );
                    echo '<div class="notice notice-success">';
                    echo '<p>' . esc_html__( 'Import complete:', 'aaa-wf-pwf' ) . ' '
                         . sprintf( esc_html__( 'Created: %d, Updated: %d, Skipped: %d', 'aaa-wf-pwf' ), $created, $updated, $skipped )
                         . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please choose a CSV file before importing.', 'aaa-wf-pwf' ) . '</p></div>';
            }
        }

        // Display upload form
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field( 'wf_pwf_import', 'wf_pwf_nonce' );
        echo '<table class="form-table"><tbody>';
        echo '<tr>';
        echo '  <th scope="row"><label for="wf_pwf_csv">' . esc_html__( 'CSV File', 'aaa-wf-pwf' ) . '</label></th>';
        echo '  <td><input type="file" id="wf_pwf_csv" name="wf_pwf_csv" accept=".csv" required /></td>';
        echo '</tr>';
        echo '</tbody></table>';
        echo '<p><button type="submit" name="wf_pwf_submit" class="button button-primary">'
             . esc_html__( 'Import Now', 'aaa-wf-pwf' ) . '</button></p>';
        echo '</form>';

        echo '</div>'; // .wrap
    }
}
