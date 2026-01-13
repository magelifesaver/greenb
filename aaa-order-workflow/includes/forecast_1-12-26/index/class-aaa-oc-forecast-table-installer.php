<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-table-installer.php
 * Purpose: Creates and updates the forecast index table. The index table
 *          stores typed forecast metrics for each product. By storing data
 *          outside of post meta we enable fast sorting and filtering on the
 *          forecast grid. This installer uses the column definitions from
 *          AAA_OC_Forecast_Columns to build appropriate SQL types.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Table_Installer {

    /**
     * Registers a low‑priority action on plugins_loaded to build the table.
     * This runs early so the table exists before any indexing occurs.
     */
    public static function init(): void {
        add_action( 'plugins_loaded', [ __CLASS__, 'maybe_install_table' ], 1 );
    }

    /**
     * Creates or updates the forecast index table via dbDelta().
     * Determines column types based on the helper definitions.
     */
    public static function maybe_install_table(): void {
        global $wpdb;
        $table_name      = AAA_OC_FORECAST_INDEX_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        // Build SQL for each forecast field.
        $definitions = AAA_OC_Forecast_Columns::get_columns();
        $column_sql  = '';
        foreach ( $definitions as $key => $def ) {
            $type = $def['type'];
            switch ( $type ) {
                case 'number':
                    $sql_type = 'BIGINT(20) NULL';
                    break;
                case 'percent':
                case 'currency':
                    // Use DECIMAL for currency/percent values for precision.
                    $sql_type = 'DECIMAL(20,4) NULL';
                    break;
                case 'date':
                    $sql_type = 'DATETIME NULL';
                    break;
                case 'boolean':
                    $sql_type = 'TINYINT(1) NULL';
                    break;
                case 'text':
                default:
                    $sql_type = 'LONGTEXT NULL';
                    break;
            }
            $column_name = esc_sql( $key );
            $column_sql .= "`{$column_name}` {$sql_type},\n";
        }

        /*
         * Compose the final CREATE TABLE statement.  In addition to the
         * forecast meta columns defined above we also store basic product
         * context (title, SKU, category, brand) in dedicated columns.  These
         * fields are required because the row builder includes them when
         * inserting/updating rows via wpdb->replace().  Without these
         * definitions the INSERT would fail with unknown column errors.
         */
        $sql = "CREATE TABLE {$table_name} (\n" .
            "product_id BIGINT(20) UNSIGNED NOT NULL,\n" .
            "product_title LONGTEXT NULL,\n" .
            "product_sku VARCHAR(255) NULL,\n" .
            "product_category LONGTEXT NULL,\n" .
            "product_brand LONGTEXT NULL,\n" .
            $column_sql .
            "updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "PRIMARY KEY  (product_id)\n" .
            ") {$charset_collate};";

        // Execute the query via dbDelta. This will create or modify the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
