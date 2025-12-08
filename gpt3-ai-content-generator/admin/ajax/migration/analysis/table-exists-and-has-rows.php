<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/table-exists-and-has-rows.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration\Analysis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checks if a list of database tables exist and if they have rows.
 *
 * @param array $table_names An array of table names (without prefix).
 * @return array ['count' => int, 'summary' => string, 'details' => array] The count is the number of tables with rows.
 */
function table_exists_and_has_rows_logic(array $table_names): array
{
    global $wpdb;
    $tables_with_rows_count = 0;
    $details = [];

    foreach ($table_names as $table_name) {
        $full_table_name = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This is a read-only analysis tool.
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)) === $full_table_name) { $row_count = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($full_table_name));
            if ($row_count > 0) {
                $tables_with_rows_count++;
                // translators: %1$d is the number of rows, %2$s is the table name.
                $details[] = sprintf(__('%1$d rows found in table "%2$s".', 'gpt3-ai-content-generator'), $row_count, $full_table_name);
            }
        }
    }

    $summary = sprintf(
        // translators: %d is the number of legacy database tables with data found.
        _n('%d legacy database table with data found.', '%d legacy database tables with data found.', $tables_with_rows_count, 'gpt3-ai-content-generator'),
        $tables_with_rows_count
    );

    return ['count' => $tables_with_rows_count, 'summary' => $summary, 'details' => $details];
}