<?php

namespace WPAICG\AutoGPT\Cron\Init;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checks if the AutoGPT module is active and handles state transitions.
 *
 * @param string $main_cron_hook The name of the main cron hook to clear if necessary.
 * @return bool True if the module is active and initialization should proceed, false otherwise.
 */
function check_module_status_logic(string $main_cron_hook): bool
{
    $option_key_autogpt_active = 'aipkit_autogpt_module_was_active';

    $module_settings = aipkit_dashboard::get_module_settings();
    $is_autogpt_currently_active = !empty($module_settings['autogpt']);
    $was_autogpt_active = (bool) get_option($option_key_autogpt_active, false);

    if (!$is_autogpt_currently_active) {
        // Only clear the cron if it was previously active and is now disabled.
        if ($was_autogpt_active && wp_next_scheduled($main_cron_hook)) {
            wp_clear_scheduled_hook($main_cron_hook);
        }
        update_option($option_key_autogpt_active, false, 'no');
        return false; // Stop further initialization
    }

    if (!$was_autogpt_active) {
        update_option($option_key_autogpt_active, true, 'no');
    }

    return true; // Continue initialization
}
