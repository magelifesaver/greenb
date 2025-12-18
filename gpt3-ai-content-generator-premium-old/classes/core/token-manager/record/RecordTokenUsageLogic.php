<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/token-manager/record/RecordTokenUsageLogic.php
// Status: MODIFIED

namespace WPAICG\Core\TokenManager\Record;

use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\TokenManager\Constants\MetaKeysConstants;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants;
use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for recording token usage for a given context (chat bot or module).
 * This function is called by the record_token_usage method in AIPKit_Token_Manager.
 *
 * @param AIPKit_Token_Manager $managerInstance The instance of AIPKit_Token_Manager.
 * @param int|null    $user_id               User ID, or null for guests.
 * @param string|null $session_id            Session ID for guests.
 * @param int|null    $context_id_or_bot_id  Bot ID for 'chat', IMG_GEN_GUEST_CONTEXT_ID for 'image_generator' guest table. Can be null for others.
 * @param int         $tokens_used           Number of tokens to record.
 * @param string      $module_context        'chat', 'image_generator', or other module slug.
 */
function RecordTokenUsageLogic(
    AIPKit_Token_Manager $managerInstance,
    ?int $user_id,
    ?string $session_id,
    ?int $context_id_or_bot_id,
    int $tokens_used,
    string $module_context = 'chat'
): void {
    global $wpdb;

    if ($tokens_used <= 0) {
        return;
    }

    $tokens_left_to_deduct = $tokens_used;

    // --- MODIFIED: Deduct from persistent balance first, then periodic ---
    if ($user_id) {
        $token_balance_raw = get_user_meta($user_id, MetaKeysConstants::TOKEN_BALANCE_META_KEY, true);
        if (is_numeric($token_balance_raw) && (int)$token_balance_raw > 0) {
            $current_balance = (int) $token_balance_raw;
            $deducted_from_balance = min($current_balance, $tokens_left_to_deduct);
            $new_balance = $current_balance - $deducted_from_balance;
            update_user_meta($user_id, MetaKeysConstants::TOKEN_BALANCE_META_KEY, $new_balance);
            $tokens_left_to_deduct -= $deducted_from_balance;
        }
    }

    if ($tokens_left_to_deduct <= 0) {
        return; // All tokens were covered by the persistent balance.
    }
    // --- END MODIFICATION ---

    // If tokens are still left to deduct, proceed with the original periodic usage tracking logic.

    // Validation
    if ($module_context === 'chat' && empty($context_id_or_bot_id)) {
        return;
    }
    if ($module_context === 'image_generator' && $context_id_or_bot_id === null) {
        return;
    }
    if ($module_context === 'ai_forms' && $context_id_or_bot_id === null && !$user_id) {
        return;
    }

    if (!$user_id && empty($session_id)) {
        return;
    }

    $is_guest = !$user_id;
    $settings = [];
    $usage_key = '';
    $reset_key = '';
    $guest_context_table_id = is_numeric($context_id_or_bot_id) ? $context_id_or_bot_id : null;

    // Fetch settings based on module context
    if ($module_context === 'chat') {
        $bot_storage = $managerInstance->get_bot_storage();
        if (!$bot_storage) {
            return;
        }
        if ($guest_context_table_id === null) {
            return;
        }
        $settings = $bot_storage->get_chatbot_settings($guest_context_table_id);
        $usage_key = MetaKeysConstants::CHAT_USAGE_META_KEY_PREFIX . $guest_context_table_id;
        $reset_key = MetaKeysConstants::CHAT_RESET_META_KEY_PREFIX . $guest_context_table_id;
    } elseif ($module_context === 'image_generator') {
        if (!class_exists(AIPKit_Image_Settings_Ajax_Handler::class)) {
            return;
        }
        $img_settings_all = AIPKit_Image_Settings_Ajax_Handler::get_settings();
        $settings = $img_settings_all['token_management'] ?? [];
        $usage_key = MetaKeysConstants::IMG_USAGE_META_KEY;
        $reset_key = MetaKeysConstants::IMG_RESET_META_KEY;
        $guest_context_table_id = GuestTableConstants::IMG_GEN_GUEST_CONTEXT_ID;
    } elseif ($module_context === 'ai_forms') {
        if (!class_exists(AIPKit_AI_Form_Settings_Ajax_Handler::class)) {
            return;
        }
        $aiforms_settings_all = AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
        $settings = $aiforms_settings_all['token_management'] ?? [];
        $usage_key = MetaKeysConstants::AIFORMS_USAGE_META_KEY;
        $reset_key = MetaKeysConstants::AIFORMS_RESET_META_KEY;
        $guest_context_table_id = GuestTableConstants::AI_FORMS_GUEST_CONTEXT_ID;
    } else {
        return;
    }

    if ($guest_context_table_id === null && $is_guest) {
        return;
    }

    $reset_period = $settings['token_reset_period'] ?? 'never';
    $should_record = false;

    // Determine if tokens should be recorded based on limits
    if ($is_guest) {
        $limit = $settings['token_guest_limit'] ?? null;
        if ($limit === null || $limit === '' || (ctype_digit((string)$limit) && (int)$limit > 0)) {
            $should_record = true;
        } // Record if unlimited or limit > 0
    } else { // Logged-in User
        $limit_mode = $settings['token_limit_mode'] ?? 'general';
        $limit_value_source = ($limit_mode === 'general') ? ($settings['token_user_limit'] ?? null) : 'role_based';
        if ($limit_value_source === null || $limit_value_source === '' || $limit_value_source === 'role_based') {
            $should_record = true; // Record if unlimited or role-based (as roles might have limits)
        } elseif (ctype_digit((string)$limit_value_source) && (int)$limit_value_source > 0) {
            $should_record = true; // Record if general limit > 0
        }
    }

    if ($should_record) {
        $guest_table_name = $managerInstance->get_guest_table_name();
        $new_usage = 0;
        if ($is_guest && $guest_context_table_id !== null) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
            $guest_row = $wpdb->get_row($wpdb->prepare("SELECT tokens_used, last_reset_timestamp FROM {$guest_table_name} WHERE session_id = %s AND bot_id = %d", $session_id, $guest_context_table_id), ARRAY_A);
            $current_usage = $guest_row ? (int) $guest_row['tokens_used'] : 0;
            $last_reset = $guest_row ? (int) $guest_row['last_reset_timestamp'] : 0;
            $new_usage = $current_usage + $tokens_left_to_deduct; // Use remaining tokens
            if ($last_reset === 0 && $reset_period !== 'never') {
                $last_reset = time();
            } // Set initial reset time if not set
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $upsert_result = $wpdb->replace($guest_table_name, ['session_id' => $session_id, 'bot_id' => $guest_context_table_id, 'tokens_used' => $new_usage, 'last_reset_timestamp' => $last_reset, 'last_updated_at' => current_time('mysql', 1)], ['%s', '%d', '%d', '%d', '%s']);
        } elseif (!$is_guest) {
            $current_usage = (int) get_user_meta($user_id, $usage_key, true);
            $new_usage = $current_usage + $tokens_left_to_deduct; // Use remaining tokens
            update_user_meta($user_id, $usage_key, $new_usage);
            if ($reset_period !== 'never') {
                if (!get_user_meta($user_id, $reset_key, true)) {
                    update_user_meta($user_id, $reset_key, time());
                } // Set initial reset time
            }
        }
        $log_context_str = $is_guest ? "Guest {$session_id}" : "User {$user_id}";
        $log_module_str = ($module_context === 'chat') ? "Bot {$context_id_or_bot_id}" : "Module {$module_context}";
    } else {
        $log_context_str = $is_guest ? "Guest {$session_id}" : "User {$user_id}";
        $log_module_str = ($module_context === 'chat') ? "Bot {$context_id_or_bot_id}" : "Module {$module_context}";
    }
}
