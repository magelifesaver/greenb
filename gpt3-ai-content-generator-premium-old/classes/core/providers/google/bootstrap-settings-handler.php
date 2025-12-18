<?php
// File: classes/core/providers/google/bootstrap-settings-handler.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google;

use WPAICG\AIPKit_Providers;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/check-and-init-safety-settings.php';
require_once __DIR__ . '/get-safety-settings.php';
require_once __DIR__ . '/save-safety-settings.php';
require_once __DIR__ . '/get-synced-tts-voices.php';
require_once __DIR__ . '/ajax-sync-tts-voices.php';


/**
 * Handles Google-specific settings logic (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class GoogleSettingsHandler {

    const GOOGLE_TTS_VOICES_OPTION = 'aipkit_google_tts_voice_list';
    private static $default_safety_settings = [
        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_CIVIC_INTEGRITY', 'threshold' => 'BLOCK_NONE']
    ];

    public static function check_and_init_safety_settings() {
        \WPAICG\Core\Providers\Google\Methods\check_and_init_safety_settings_logic(self::$default_safety_settings);
    }

    public static function get_safety_settings(): array {
        return \WPAICG\Core\Providers\Google\Methods\get_safety_settings_logic(self::$default_safety_settings);
    }

    public static function save_safety_settings(array $post_data): bool {
        return \WPAICG\Core\Providers\Google\Methods\save_safety_settings_logic($post_data, self::$default_safety_settings);
    }

    public static function get_synced_google_tts_voices(): array {
        return \WPAICG\Core\Providers\Google\Methods\get_synced_google_tts_voices_logic(self::GOOGLE_TTS_VOICES_OPTION);
    }

    public static function ajax_sync_google_tts_voices() {
        \WPAICG\Core\Providers\Google\Methods\ajax_sync_google_tts_voices_logic(self::GOOGLE_TTS_VOICES_OPTION);
    }
}