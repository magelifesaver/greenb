<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/token-manager/check/CheckAndResetTokensLogic.php
// Status: MODIFIED

namespace WPAICG\Core\TokenManager\Check;

use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\TokenManager\Constants\MetaKeysConstants;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants;
use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler;
use WPAICG\Chat\Storage\BotSettingsManager; // For default constants
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for checking token usage against limits for a given context (chat bot or module).
 * This function is called by the check_and_reset_tokens method in AIPKit_Token_Manager.
 *
 * @param AIPKit_Token_Manager $managerInstance The instance of AIPKit_Token_Manager.
 * @param int|null    $user_id               User ID, or null for guests.
 * @param string|null $session_id            Session ID for guests.
 * @param int|null    $context_id_or_bot_id  Bot ID for 'chat', or IMG_GEN_GUEST_CONTEXT_ID for 'image_generator'. Can be null for other modules.
 * @param string      $module_context        'chat', 'image_generator', or other module slug.
 * @return bool|WP_Error True if allowed, WP_Error if limit exceeded or other error.
 */
function CheckAndResetTokensLogic(
    AIPKit_Token_Manager $managerInstance,
    ?int $user_id,
    ?string $session_id,
    ?int $context_id_or_bot_id,
    string $module_context = 'chat'
): bool|WP_Error {
    global $wpdb;

    // --- NEW: First, check for a persistent token balance for logged-in users ---
    if ($user_id) {
        $token_balance = get_user_meta($user_id, MetaKeysConstants::TOKEN_BALANCE_META_KEY, true);
        if (is_numeric($token_balance) && (int)$token_balance > 0) {
            // User has a positive balance, so they are allowed to proceed.
            // The deduction will happen in RecordTokenUsageLogic.
            return true;
        }
    }
    // --- END NEW ---

    // If no balance, or a guest, proceed with the original periodic usage tracking logic.

    // Validation
    if ($module_context === 'chat' && empty($context_id_or_bot_id)) {
        return new WP_Error('token_check_no_bot_id_logic', __('Bot ID missing for chat token check.', 'gpt3-ai-content-generator'));
    }
    if ($module_context === 'image_generator' && $context_id_or_bot_id === null) { // image_generator uses 0 for guests
        return new WP_Error('token_check_no_img_context_logic', __('Image Generator context ID missing for token check.', 'gpt3-ai-content-generator'));
    }
    if ($module_context === 'ai_forms' && $context_id_or_bot_id === null && !$user_id) { // ai_forms uses 1 for guests, null for users
        // This is a valid state for logged-in users, so only error if guest AND null
        return new WP_Error('token_check_no_aiforms_context_logic', __('AI Forms context ID missing for guest token check.', 'gpt3-ai-content-generator'));
    }

    if (!$user_id && empty($session_id)) {
        return new WP_Error('token_check_no_identifier_logic', __('User/Session ID missing for token check.', 'gpt3-ai-content-generator'));
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
            return new WP_Error('init_error_chat_storage_logic', __('Token manager (bot storage) not initialized.', 'gpt3-ai-content-generator'));
        }
        if ($guest_context_table_id === null) { // Ensure bot_id is numeric for chat
            return new WP_Error('internal_error_chat_context_id_logic', __('Chat context requires a valid Bot ID for token check.', 'gpt3-ai-content-generator'));
        }
        $settings = $bot_storage->get_chatbot_settings($guest_context_table_id);
        $usage_key = MetaKeysConstants::CHAT_USAGE_META_KEY_PREFIX . $guest_context_table_id;
        $reset_key = MetaKeysConstants::CHAT_RESET_META_KEY_PREFIX . $guest_context_table_id;
    } elseif ($module_context === 'image_generator') {
        if (!class_exists(AIPKit_Image_Settings_Ajax_Handler::class)) {
            return new WP_Error('init_error_img_settings_logic', __('Token manager (image settings) not initialized.', 'gpt3-ai-content-generator'));
        }
        $img_settings_all = AIPKit_Image_Settings_Ajax_Handler::get_settings();
        $settings = $img_settings_all['token_management'] ?? [];
        $usage_key = MetaKeysConstants::IMG_USAGE_META_KEY;
        $reset_key = MetaKeysConstants::IMG_RESET_META_KEY;
        // guest_context_table_id for image generator is a constant (e.g., 0)
        $guest_context_table_id = GuestTableConstants::IMG_GEN_GUEST_CONTEXT_ID;
    } elseif ($module_context === 'ai_forms') {
        if (!class_exists(\WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::class)) {
            return new WP_Error('init_error_aiforms_settings_logic', __('Token manager (AI Forms settings) not initialized.', 'gpt3-ai-content-generator'));
        }
        $aiforms_settings_all = \WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
        $settings = $aiforms_settings_all['token_management'] ?? [];
        $usage_key = MetaKeysConstants::AIFORMS_USAGE_META_KEY;
        $reset_key = MetaKeysConstants::AIFORMS_RESET_META_KEY;
        $guest_context_table_id = GuestTableConstants::AI_FORMS_GUEST_CONTEXT_ID;
    } else {
        if ($context_id_or_bot_id === null) {
            return true;
        } // No specific limits for this generic module context if no ID
        return new WP_Error('invalid_module_context_for_tokens_logic', __('Invalid module context or ID for token check.', 'gpt3-ai-content-generator'));
    }

    if ($guest_context_table_id === null && $is_guest) {
        return true; // Or error, based on policy. For now, allow if this unlikely scenario happens.
    }

    // Determine limit and reset period
    $limit = null;
    $reset_period = $settings['token_reset_period'] ?? 'never';
    $default_limit_message = class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_TOKEN_LIMIT_MESSAGE : __('You have reached your token limit for this period.', 'gpt3-ai-content-generator');
    $limit_message_template = $settings['token_limit_message'] ?? '';
    $limit_message = !empty($limit_message_template) ? $limit_message_template : $default_limit_message;

    $current_usage = 0;
    $last_reset_time = 0;
    $guest_table_name = $managerInstance->get_guest_table_name();

    if ($is_guest) {
        $limit = $settings['token_guest_limit'] ?? null;
        if ($limit === 0 || (is_string($limit) && $limit === '0')) { // Check for string '0' too
            return new WP_Error('token_limit_exceeded_guest_logic', __('Access disabled for guests.', 'gpt3-ai-content-generator'));
        }
        if ($limit === null || $limit === '') {
            return true;
        } // Unlimited for this context

        if ($guest_context_table_id !== null) {
            $cache_key = "aipkit_guest_usage_{$session_id}_{$guest_context_table_id}";
            $guest_row = wp_cache_get($cache_key, 'aipkit_token_usage');
            if (false === $guest_row) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table query; unavoidable.
                $guest_row = $wpdb->get_row($wpdb->prepare("SELECT tokens_used, last_reset_timestamp FROM {$guest_table_name} WHERE session_id = %s AND bot_id = %d",
                    $session_id,
                    $guest_context_table_id
                ), ARRAY_A);
                wp_cache_set($cache_key, $guest_row, 'aipkit_token_usage', 300); // Cache for 5 minutes
            }
            if ($guest_row) {
                $current_usage = (int) $guest_row['tokens_used'];
                $last_reset_time = (int) $guest_row['last_reset_timestamp'];
            }
        } else {
            return true; // Should have been caught earlier
        }
    } else { // Logged-in User
        $limit_mode = $settings['token_limit_mode'] ?? 'general';
        if ($limit_mode === 'general') {
            $limit = $settings['token_user_limit'] ?? null;
        } else { // Role-based
            $user_data = get_userdata($user_id);
            $user_roles = $user_data ? (array) $user_data->roles : [];
            $role_limits_raw = $settings['token_role_limits'] ?? [];
            $role_limits = is_string($role_limits_raw) ? json_decode($role_limits_raw, true) : (is_array($role_limits_raw) ? $role_limits_raw : []);
            if (!is_array($role_limits)) {
                $role_limits = [];
            }

            if (empty($user_roles) || empty($role_limits)) {
                $limit = null;
            } else {
                $highest_limit = -1;
                foreach ($user_roles as $role) {
                    if (isset($role_limits[$role])) {
                        $role_limit_value_raw = $role_limits[$role];
                        if ($role_limit_value_raw === null || $role_limit_value_raw === '') {
                            $highest_limit = null; // Explicitly unlimited for this role, overrides others
                            break;
                        }
                        if ($role_limit_value_raw === '0' || $role_limit_value_raw === 0) {
                            $highest_limit = max($highest_limit, 0); // Found a "disabled" (0) limit
                        } elseif (ctype_digit((string)$role_limit_value_raw)) {
                            $highest_limit = max($highest_limit, (int)$role_limit_value_raw);
                        }
                    }
                }
                $limit = ($highest_limit === -1) ? null : $highest_limit;
            }
        }
        if ($limit === 0 || (is_string($limit) && $limit === '0')) {
            return new WP_Error('token_limit_exceeded_user_logic', __('Access disabled for your account/role.', 'gpt3-ai-content-generator'));
        }
        if ($limit === null || $limit === '') {
            return true;
        } // Unlimited

        $current_usage = (int) get_user_meta($user_id, $usage_key, true);
        $last_reset_time = (int) get_user_meta($user_id, $reset_key, true);
    }

    // Fail-safe reset if due (not relying on cron exclusively for active users)
    if ($reset_period !== 'never' && ($limit !== null && $limit !== '')) { // Only reset if there's a limit
        if (\WPAICG\Core\TokenManager\Reset\IsResetDueLogic($last_reset_time, $reset_period)) { // Call the reset due logic
            $log_context_str = $is_guest ? "Guest {$session_id}" : "User {$user_id}";
            $log_module_str = ($module_context === 'chat') ? "Bot {$context_id_or_bot_id}" : "Module {$module_context}";

            $current_usage = 0;
            $last_reset_time_new = time(); // Use a different var name for new reset time
            if ($is_guest && $guest_context_table_id !== null) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching is not applicable for a write operation (REPLACE). Cache is invalidated after.
                $wpdb->replace($guest_table_name, ['session_id' => $session_id, 'bot_id' => $guest_context_table_id, 'tokens_used' => 0, 'last_reset_timestamp' => $last_reset_time_new, 'last_updated_at' => current_time('mysql', 1)], ['%s', '%d', '%d', '%d', '%s']);
                // Invalidate cache after write
                $cache_key = "aipkit_guest_usage_{$session_id}_{$guest_context_table_id}";
                wp_cache_delete($cache_key, 'aipkit_token_usage');
            } elseif (!$is_guest) {
                update_user_meta($user_id, $usage_key, 0);
                update_user_meta($user_id, $reset_key, $last_reset_time_new);
            }
        }
    }

    // Final Limit Check
    if (($limit !== null && $limit !== '') && $current_usage >= (int)$limit) {
        return new WP_Error('token_limit_exceeded_final_logic', $limit_message);
    }
    return true; // Allowed
}