<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/assets/class-assets-require-flags.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Assets;

use WPAICG\aipkit_dashboard; // To check addon status
use WPAICG\Chat\Frontend\Assets as AssetsOrchestrator; // Use the main orchestrator

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles setting the static flags in AssetsOrchestrator when assets are required.
 */
class AssetsRequireFlags {

    /**
     * Signals that assets are needed, and potentially specific features, updating static flags.
     *
     * @param bool $needs_pdf
     * @param bool $needs_copy
     * @param bool $needs_starters
     * @param bool $needs_sidebar
     * @param bool $needs_feedback
     * @param bool $needs_tts
     * @param bool $needs_stt
     * @param bool $needs_image_gen
     * @param bool $needs_chat_image_upload
     * @param bool $needs_chat_file_upload 
     * @param bool $needs_realtime_voice
     */
    public static function set_flags(
        bool $needs_pdf = false, bool $needs_copy = false, bool $needs_starters = false,
        bool $needs_sidebar = false, bool $needs_feedback = false, bool $needs_tts = false,
        bool $needs_stt = false, bool $needs_image_gen = false, bool $needs_chat_image_upload = false,
        bool $needs_chat_file_upload = false, bool $needs_realtime_voice = false
    ): void {
        // Ensure aipkit_dashboard is available for addon checks
        if (!class_exists(aipkit_dashboard::class)) {
            // Attempt to load it if not found, to prevent fatal errors
            $dashboard_path = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php' : null;
            if ($dashboard_path && file_exists($dashboard_path)) {
                require_once $dashboard_path;
            } else {
                 // Return early or set defaults if dashboard class is critical and missing
                 // For now, we'll proceed with a warning if class_exists check fails after attempt.
                 if (!class_exists(aipkit_dashboard::class)) {
                     return;
                 }
            }
        }


        AssetsOrchestrator::$shortcode_rendered = true;
        AssetsOrchestrator::$consent_needed = true;
        AssetsOrchestrator::$moderation_needed = true;

        if ($needs_pdf && aipkit_dashboard::is_pro_plan() && aipkit_dashboard::is_addon_active('pdf_download')) {
            AssetsOrchestrator::$jspdf_needed = true;
        }
        if ($needs_copy) AssetsOrchestrator::$copy_button_needed = true;
        if ($needs_feedback) AssetsOrchestrator::$feedback_needed = true;
        if ($needs_starters && aipkit_dashboard::is_addon_active('conversation_starters')) {
            AssetsOrchestrator::$starters_needed = true;
        }
        if ($needs_sidebar) AssetsOrchestrator::$sidebar_needed = true;
        if ($needs_tts && aipkit_dashboard::is_addon_active('voice_playback')) {
            AssetsOrchestrator::$tts_needed = true;
        }
        if ($needs_stt) AssetsOrchestrator::$stt_needed = true;
        if ($needs_image_gen) AssetsOrchestrator::$image_gen_needed = true;
        if ($needs_chat_image_upload) AssetsOrchestrator::$chat_image_upload_needed = true;
        // --- Set file upload flag if Pro and addon active ---
        if ($needs_chat_file_upload && aipkit_dashboard::is_pro_plan() && aipkit_dashboard::is_addon_active('file_upload')) {
             AssetsOrchestrator::$chat_file_upload_needed = true;
        }
        if ($needs_realtime_voice && aipkit_dashboard::is_pro_plan() && aipkit_dashboard::is_addon_active('realtime_voice')) {
            AssetsOrchestrator::$realtime_voice_needed = true;
        }
        // --- END ---
    }
}