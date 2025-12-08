<?php

namespace WPAICG\Core;

// Use statements for the new checker components
use WPAICG\Core\Moderation\AIPKit_BannedIP_Checker;
use WPAICG\Core\Moderation\AIPKit_BannedWords_Checker;
use WPAICG\Core\Moderation\AIPKit_OpenAI_Moderation_Checker;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Content_Moderator (Facade)
 *
 * Centralized class for handling content moderation checks.
 * Delegates specific checks to specialized checker classes.
 */
class AIPKit_Content_Moderator {

    const SECURITY_OPTION_NAME = 'aipkit_security'; // Matches option name used by SecurityAjaxHandler

    /**
     * Checks the provided text and context against configured moderation rules.
     *
     * @param string $text The text content to check (e.g., user message).
     * @param array $context Associative array containing context information.
     *                      Expected keys:
     *                      - 'client_ip': (string) The IP address of the user making the request.
     *                      - 'bot_settings': (array) Settings of the current bot (needed for OpenAI provider check).
     * @return WP_Error|null Returns a WP_Error if the content is flagged (with user-facing message),
     *                       or null if the content passes all checks or moderation is not applicable/failed internally.
     */
    public static function check_content(string $text, array $context = []): ?WP_Error {
        // 1. Get Security Settings
        $security_options = get_option(self::SECURITY_OPTION_NAME, []);
        $client_ip = $context['client_ip'] ?? null;
        $bot_settings = $context['bot_settings'] ?? [];

        // 2. Banned IPs Check
        $banned_ips_settings = $security_options['bannedips'] ?? ['ips' => '', 'message' => ''];
        $ip_check_result = AIPKit_BannedIP_Checker::check($client_ip, $banned_ips_settings);
        if (is_wp_error($ip_check_result)) {
            return $ip_check_result;
        }

        // 3. Banned Words Check
        $banned_words_settings = $security_options['bannedwords'] ?? ['words' => '', 'message' => ''];
        $words_check_result = AIPKit_BannedWords_Checker::check($text, $banned_words_settings);
        if (is_wp_error($words_check_result)) {
            return $words_check_result;
        }

        // 4. OpenAI Moderation API Check (delegates to a checker that uses the Pro Addon Helper)
        $openai_mod_check_result = AIPKit_OpenAI_Moderation_Checker::check($text, $bot_settings);
        if (is_wp_error($openai_mod_check_result)) {
            return $openai_mod_check_result;
        }

        // 5. All checks passed
        return null;
    }
}