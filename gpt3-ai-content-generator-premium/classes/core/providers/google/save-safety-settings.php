<?php
// File: classes/core/providers/google/save-safety-settings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the save_safety_settings static method of GoogleSettingsHandler.
 *
 * @param array $post_data The unslashed $_POST data containing safety settings.
 * @param array $default_safety_settings The default safety settings array.
 * @return bool True if the option was updated, false otherwise.
 */
function save_safety_settings_logic(array $post_data, array $default_safety_settings): bool {
    $opts = get_option('aipkit_options', array());
    check_and_init_safety_settings_logic($default_safety_settings); 
    $opts = get_option('aipkit_options', array()); 

    $current_settings = $opts['providers']['Google']['safety_settings'] ?? $default_safety_settings;
    $updated_settings = [];
    $valid_thresholds = array('BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE', 'BLOCK_MEDIUM_AND_ABOVE', 'BLOCK_ONLY_HIGH');

    foreach ($default_safety_settings as $default_setting_item) {
         $category = $default_setting_item['category'];
         $short_category = strtolower(str_replace('HARM_CATEGORY_', '', $category));
         $threshold_key = 'safety_' . $short_category;
         $new_threshold = $default_setting_item['threshold'];

         if (isset($post_data[$threshold_key])) {
             $posted_threshold = sanitize_text_field($post_data[$threshold_key]);
             if (in_array($posted_threshold, $valid_thresholds, true)) {
                 $new_threshold = $posted_threshold;
             }
         } else {
             foreach ($current_settings as $current_setting_item) {
                 if (isset($current_setting_item['category']) && $current_setting_item['category'] === $category && isset($current_setting_item['threshold'])) {
                     $new_threshold = $current_setting_item['threshold'];
                     break;
                 }
             }
         }
         $updated_settings[] = ['category' => $category, 'threshold' => $new_threshold];
    }

    if ($opts['providers']['Google']['safety_settings'] !== $updated_settings) {
        $opts['providers']['Google']['safety_settings'] = $updated_settings;
        return update_option('aipkit_options', $opts, 'no');
    }
    return false;
}