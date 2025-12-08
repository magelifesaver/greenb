<?php
// File: classes/core/providers/google/get-safety-settings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_safety_settings static method of GoogleSettingsHandler.
 *
 * @param array $default_safety_settings The default safety settings array.
 * @return array The safety settings.
 */
function get_safety_settings_logic(array $default_safety_settings): array {
    check_and_init_safety_settings_logic($default_safety_settings); 
    $opts = get_option('aipkit_options', array());
    return $opts['providers']['Google']['safety_settings'] ?? $default_safety_settings;
}