<?php
// File: classes/core/providers/google/get-synced-tts-voices.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_synced_google_tts_voices static method of GoogleSettingsHandler.
 *
 * @param string $option_name The name of the WordPress option storing the voices.
 * @return array List of voice data arrays.
 */
function get_synced_google_tts_voices_logic(string $option_name): array {
    return get_option($option_name, []);
}