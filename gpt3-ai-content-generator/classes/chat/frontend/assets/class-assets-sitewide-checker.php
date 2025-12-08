<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/assets/class-assets-sitewide-checker.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Assets;

use WPAICG\Chat\Storage\SiteWideBotManager;
use WPAICG\aipkit_dashboard;
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Chat\Frontend\Assets as AssetsOrchestrator; // Use the main orchestrator

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Checks for a site-wide bot and sets relevant static flags in the AssetsOrchestrator.
 */
class AssetsSiteWideChecker {

    public function __construct() {
        // Constructor can be empty or initialize dependencies if needed by the check method.
    }

    /**
     * Checks for a site-wide bot and updates static flags in AssetsOrchestrator.
     */
    public function check(): void {
        if (is_admin() || wp_doing_ajax()) return;

        // Ensure required classes are available
        if (!class_exists(SiteWideBotManager::class) ||
            !class_exists(aipkit_dashboard::class) ||
            !class_exists(BotStorage::class) ||
            !class_exists(BotSettingsManager::class)) {
            return;
        }

        $manager = new SiteWideBotManager();
        $bot_id = $manager->get_site_wide_bot_id();

        if ($bot_id) {
            AssetsOrchestrator::$site_wide_injection_needed = true;

            $bot_storage = new BotStorage();
            $settings = $bot_storage->get_chatbot_settings($bot_id);
            $enable_download_setting = $settings['enable_download'] ?? '0';
            $enable_copy_button_setting = $settings['enable_copy_button'] ?? BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON;
            $enable_feedback_setting = $settings['enable_feedback'] ?? BotSettingsManager::DEFAULT_ENABLE_FEEDBACK;
            $enable_starters_setting = ($settings['enable_conversation_starters'] ?? BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS) === '1';
            $enable_sidebar_setting = ($settings['enable_conversation_sidebar'] ?? BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR) === '1';
            $popup_enabled_setting = ($settings['popup_enabled'] ?? '0') === '1';
            $enable_tts_setting = ($settings['tts_enabled'] ?? BotSettingsManager::DEFAULT_TTS_ENABLED) === '1';
            $enable_stt_setting = ($settings['enable_voice_input'] ?? BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT) === '1';
            $enable_image_gen_command = true;

            AssetsOrchestrator::$consent_needed = true;
            AssetsOrchestrator::$moderation_needed = true;

            $pdf_addon_active = aipkit_dashboard::is_addon_active('pdf_download');
            $starters_addon_active = aipkit_dashboard::is_addon_active('conversation_starters');
            $voice_playback_addon_active = aipkit_dashboard::is_addon_active('voice_playback');

            if ($enable_download_setting === '1' && aipkit_dashboard::is_pro_plan() && $pdf_addon_active) AssetsOrchestrator::$jspdf_needed = true;
            if ($enable_copy_button_setting === '1') AssetsOrchestrator::$copy_button_needed = true;
            if ($enable_feedback_setting === '1') AssetsOrchestrator::$feedback_needed = true;
            if ($starters_addon_active && $enable_starters_setting === true) AssetsOrchestrator::$starters_needed = true; // Corrected variable name
            if ($enable_sidebar_setting === true && !$popup_enabled_setting) AssetsOrchestrator::$sidebar_needed = true;
            if ($voice_playback_addon_active && $enable_tts_setting === true) AssetsOrchestrator::$tts_needed = true;
            if ($enable_stt_setting === true) AssetsOrchestrator::$stt_needed = true; // Corrected variable name
            if ($enable_image_gen_command) AssetsOrchestrator::$image_gen_needed = true;

            $enable_image_upload_setting = ($settings['enable_image_upload'] ?? '0') === '1';
            if ($enable_image_upload_setting) {
                AssetsOrchestrator::$chat_image_upload_needed = true;
            }
        }
    }
}