<?php

namespace WPAICG\AutoGPT\Cron\Init;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifies that the automated tasks database table exists.
 *
 * @param \wpdb $wpdb The WordPress database object.
 * @param string $tasks_table_name The name of the tasks table.
 * @param string $main_cron_hook The name of the main cron hook to clear if necessary.
 * @return bool True if the table exists, false otherwise.
 */
function ensure_table_exists_logic(\wpdb $wpdb, string $tasks_table_name, string $main_cron_hook): bool
{
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tasks_table_name));

    if ($table_exists !== $tasks_table_name) {
        if (wp_next_scheduled($main_cron_hook)) {
            wp_clear_scheduled_hook($main_cron_hook);
        }
        return false;
    }
    return true;
}
