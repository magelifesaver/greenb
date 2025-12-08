<?php
// File: classes/chat/storage/saver/save-bot-settings-logic.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\SaverMethods;

use WPAICG\Chat\Storage\SiteWideBotManager;
use WP_Error;

// Ensure the new method logic files are loaded
require_once __DIR__ . '/validate-bot-post-logic.php';
require_once __DIR__ . '/sanitize-settings-logic.php';
require_once __DIR__ . '/handle-site-wide-logic.php';
require_once __DIR__ . '/save-meta-fields-logic.php';
require_once __DIR__ . '/handle-openai-specific-settings-logic.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Orchestrates the saving of bot settings.
 * MODIFIED: Now handles WP_Error from save_meta_fields_logic.
 *
 * @param int $botId The chatbot post ID.
 * @param array $raw_settings The raw settings array from the form (e.g., $_POST).
 * @param SiteWideBotManager $site_wide_manager The SiteWideBotManager instance.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function save_bot_settings_logic(int $botId, array $raw_settings, SiteWideBotManager $site_wide_manager): bool|WP_Error {
    // 1. Validate Bot Post
    $validation_result = validate_bot_post_logic($botId);
    if (is_wp_error($validation_result)) {
        return $validation_result;
    }

    // 2. Sanitize Settings
    $sanitized_settings = sanitize_settings_logic($raw_settings, $botId);

    // 3. Handle Site-Wide Logic (before saving meta, as it might affect other bots)
    handle_site_wide_logic($site_wide_manager, $botId, $sanitized_settings['site_wide_enabled']);

    // 4. Save Meta Fields
    // This function can now return WP_Error
    $meta_save_result = save_meta_fields_logic($botId, $sanitized_settings);
    if (is_wp_error($meta_save_result)) {
        return $meta_save_result; // Propagate WP_Error if JSON validation failed for triggers
    }

    // 5. Handle OpenAI Specific Settings (like forcing global store_conversation)
    handle_openai_specific_settings_logic($botId, $sanitized_settings);

    // Action hook after settings are saved
    do_action('aipkit_after_bot_settings_saved', $botId, $sanitized_settings);

    return true;
}