<?php
// File: classes/chat/storage/class-aipkit_bot_settings_saver.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage;

use WPAICG\Chat\Storage\SiteWideBotManager;
use WP_Error;

// Ensure the main orchestrator logic file is loaded
require_once __DIR__ . '/saver/save-bot-settings-logic.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles SAVING chatbot settings.
 * This class now primarily delegates to the namespaced save_bot_settings_logic function.
 */
class AIPKit_Bot_Settings_Saver {

    private $site_wide_manager;

    public function __construct(SiteWideBotManager $site_wide_manager) {
        $this->site_wide_manager = $site_wide_manager;
    }

    /**
     * Saves the chatbot settings.
     * Delegates the core logic to the modularized save_bot_settings_logic function.
     *
     * @param int $botId The chatbot post ID.
     * @param array $raw_settings The raw settings array from the form (e.g., $_POST).
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function save(int $botId, array $raw_settings): bool|WP_Error {
        // Check if SiteWideBotManager was successfully initialized
        if (!$this->site_wide_manager) {
            return new WP_Error('dependency_missing_saver', __('Site-wide manager component is missing.', 'gpt3-ai-content-generator'));
        }

        // Call the externalized orchestrator function
        return SaverMethods\save_bot_settings_logic($botId, $raw_settings, $this->site_wide_manager);
    }
}