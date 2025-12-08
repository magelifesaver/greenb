<?php
// File: classes/core/providers/google/check-and-init-safety-settings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the check_and_init_safety_settings static method of GoogleSettingsHandler.
 *
 * @param array $default_safety_settings The default safety settings array.
 */
function check_and_init_safety_settings_logic(array $default_safety_settings) {
    $opts = get_option('aipkit_options', array());
    $changed = false;

    if (!isset($opts['providers']) || !is_array($opts['providers'])) {
        $opts['providers'] = array();
        $changed = true;
    }
    if (!isset($opts['providers']['Google']) || !is_array($opts['providers']['Google'])) {
        $opts['providers']['Google'] = array();
        $changed = true;
    }

    if (!isset($opts['providers']['Google']['safety_settings'])
        || !is_array($opts['providers']['Google']['safety_settings'])
        || empty($opts['providers']['Google']['safety_settings'])) {
        $opts['providers']['Google']['safety_settings'] = $default_safety_settings;
        $changed = true;
    } else {
        $current_categories = array_column($opts['providers']['Google']['safety_settings'], 'category');
        foreach ($default_safety_settings as $default_setting_item) { // Renamed loop variable
            if (!in_array($default_setting_item['category'], $current_categories, true)) {
                $opts['providers']['Google']['safety_settings'][] = $default_setting_item;
                $changed = true;
            }
        }
    }

    if ($changed) {
        update_option('aipkit_options', $opts, 'no');
    }
}