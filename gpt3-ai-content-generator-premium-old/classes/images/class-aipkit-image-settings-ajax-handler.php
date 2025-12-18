<?php

// UPDATED FILE - Added Google Image Models to defaults and saving logic.

namespace WPAICG\Images;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler; // Use the base dashboard handler for permissions
use WPAICG\Chat\Storage\BotSettingsManager; // Use for default constants
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for saving Image Generator settings.
 * UPDATED: Includes Token Management settings.
 * UPDATED: Includes Google Image Model settings.
 */
class AIPKit_Image_Settings_Ajax_Handler extends BaseDashboardAjaxHandler
{
    public const SETTINGS_OPTION_NAME = 'aipkit_image_generator_settings';

    /**
     * Get the default settings structure.
     */
    public static function get_default_settings(): array
    {
        $default_limit_message = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MESSAGE ?: __('You have reached your token limit for this period.', 'gpt3-ai-content-generator');
        $default_limit_mode = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;
        $default_reset_period = BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD;

        return [
            'common' => [
                'custom_css' => '',
            ],
            'token_management' => [
                'token_limit_mode' => $default_limit_mode,
                'token_guest_limit' => null,
                'token_user_limit' => null,
                'token_role_limits' => [],
                'token_reset_period' => $default_reset_period,
                'token_limit_message' => $default_limit_message,
            ],
            'frontend_display' => [
                'allowed_providers' => '',
                'allowed_models' => '',
            ],
            'replicate' => [
                'disable_safety_checker' => true, // Default to disabled safety check to avoid false positives
            ]
        ];
    }

    /**
     * Retrieves the saved image generator settings, merging with defaults.
     */
    public static function get_settings(): array
    {
        $defaults = self::get_default_settings();
        $saved = get_option(self::SETTINGS_OPTION_NAME, []);

        if (!isset($saved['common']) || !is_array($saved['common'])) {
            $saved['common'] = [];
        }
        $saved['common'] = array_merge($defaults['common'], $saved['common']);
        $saved['common'] = array_intersect_key($saved['common'], $defaults['common']);

        if (!isset($saved['token_management']) || !is_array($saved['token_management'])) {
            $saved['token_management'] = $defaults['token_management'];
        } else {
            $saved['token_management'] = array_merge($defaults['token_management'], $saved['token_management']);
        }
        $saved['token_management'] = array_intersect_key($saved['token_management'], $defaults['token_management']);
        if (isset($saved['token_management']['token_role_limits']) && is_string($saved['token_management']['token_role_limits'])) {
            $decoded_roles = json_decode($saved['token_management']['token_role_limits'], true);
            $saved['token_management']['token_role_limits'] = is_array($decoded_roles) ? $decoded_roles : [];
        } elseif (!isset($saved['token_management']['token_role_limits']) || !is_array($saved['token_management']['token_role_limits'])) {
            $saved['token_management']['token_role_limits'] = [];
        }
        if (!isset($saved['frontend_display']) || !is_array($saved['frontend_display'])) {
            $saved['frontend_display'] = $defaults['frontend_display'];
        } else {
            $saved['frontend_display'] = array_merge($defaults['frontend_display'], $saved['frontend_display']);
        }
        $saved['frontend_display'] = array_intersect_key($saved['frontend_display'], $defaults['frontend_display']);
        
        // Handle Replicate settings
        if (!isset($saved['replicate']) || !is_array($saved['replicate'])) {
            $saved['replicate'] = $defaults['replicate'];
        } else {
            $saved['replicate'] = array_merge($defaults['replicate'], $saved['replicate']);
        }
        $saved['replicate'] = array_intersect_key($saved['replicate'], $defaults['replicate']);
        
        return $saved;
    }

    /**
     * AJAX handler to save Image Generator settings.
     */
    public function ajax_save_image_settings()
    {
        $permission_check = $this->check_module_access_permissions('image_generator', 'aipkit_image_generator_settings_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_module_access_permissions() above.
        $post_data = wp_unslash($_POST);
        $current_settings = self::get_settings();
        $new_settings = $current_settings; // Start with current settings as a base
        $defaults = self::get_default_settings();

        if (isset($post_data['custom_css'])) {
            $new_settings['common']['custom_css'] = wp_strip_all_tags($post_data['custom_css']);
        }

        $token_defaults = $defaults['token_management'];
        $new_token_settings = $new_settings['token_management'] ?? $token_defaults;
        if (isset($post_data['image_token_limit_mode']) && in_array($post_data['image_token_limit_mode'], ['general', 'role_based'])) {
            $new_token_settings['token_limit_mode'] = $post_data['image_token_limit_mode'];
        }
        if (isset($post_data['image_token_guest_limit'])) {
            $guest_limit_raw = trim($post_data['image_token_guest_limit']);
            if ($guest_limit_raw === '0') {
                $new_token_settings['token_guest_limit'] = 0;
            } elseif (ctype_digit($guest_limit_raw) && $guest_limit_raw > 0) {
                $new_token_settings['token_guest_limit'] = absint($guest_limit_raw);
            } else {
                $new_token_settings['token_guest_limit'] = null;
            }
        }
        if (isset($post_data['image_token_user_limit'])) {
            $user_limit_raw = trim($post_data['image_token_user_limit']);
            if ($user_limit_raw === '0') {
                $new_token_settings['token_user_limit'] = 0;
            } elseif (ctype_digit($user_limit_raw) && $user_limit_raw > 0) {
                $new_token_settings['token_user_limit'] = absint($user_limit_raw);
            } else {
                $new_token_settings['token_user_limit'] = null;
            }
        }
        if (isset($post_data['image_token_role_limits']) && is_array($post_data['image_token_role_limits'])) {
            $editable_roles = get_editable_roles();
            $sanitized_role_limits = [];
            foreach ($editable_roles as $role_slug => $role_info) {
                if (isset($post_data['image_token_role_limits'][$role_slug])) {
                    $raw_limit = trim($post_data['image_token_role_limits'][$role_slug]);
                    if ($raw_limit === '0') {
                        $sanitized_role_limits[$role_slug] = 0;
                    } elseif (ctype_digit($raw_limit) && $raw_limit > 0) {
                        $sanitized_role_limits[$role_slug] = absint($raw_limit);
                    } else {
                        $sanitized_role_limits[$role_slug] = null;
                    }
                }
            }
            $new_token_settings['token_role_limits'] = wp_json_encode($sanitized_role_limits);
        } else {
            $new_token_settings['token_role_limits'] = '[]';
        }
        if (isset($post_data['image_token_reset_period']) && in_array($post_data['image_token_reset_period'], ['never', 'daily', 'weekly', 'monthly'])) {
            $new_token_settings['token_reset_period'] = $post_data['image_token_reset_period'];
        }
        if (isset($post_data['image_token_limit_message'])) {
            $new_token_settings['token_limit_message'] = sanitize_text_field($post_data['image_token_limit_message']);
        }
        $new_settings['token_management'] = $new_token_settings;

        $frontend_defaults = $defaults['frontend_display'];
        $new_frontend_settings = $new_settings['frontend_display'] ?? $frontend_defaults;
        // Providers now inferred from selected models. If no models selected = allow all (store empty string for both fields)
        if (isset($post_data['frontend_models'])) {
            $models_raw = sanitize_textarea_field(wp_unslash($post_data['frontend_models']));
            $models_arr = array_filter(array_map('trim', explode(',', $models_raw)));
            if (empty($models_arr)) {
                // All providers & models allowed
                $new_frontend_settings['allowed_models'] = '';
                $new_frontend_settings['allowed_providers'] = '';
            } else {
                // Build lookup tables from known provider model lists for accurate detection.
                $openai_ids = ['gpt-image-1','dall-e-3','dall-e-2'];
                // Get Google image and video models from synced lists
                $google_ids = [];
                if (class_exists('\\WPAICG\\AIPKit_Providers')) {
                    $google_image_models = \WPAICG\AIPKit_Providers::get_google_image_models();
                    $google_video_models = \WPAICG\AIPKit_Providers::get_google_video_models();
                    foreach ([$google_image_models, $google_video_models] as $list) {
                        if (is_array($list) && !empty($list)) {
                            foreach ($list as $mdl) {
                                if (is_array($mdl) && isset($mdl['id'])) { $google_ids[] = strtolower($mdl['id']); }
                                elseif (is_string($mdl)) { $google_ids[] = strtolower($mdl); }
                            }
                        }
                    }
                }
                $azure_ids = [];
                if (class_exists('\\WPAICG\\AIPKit_Providers')) {
                    $azure_models_list = \WPAICG\AIPKit_Providers::get_azure_image_models();
                    if (is_array($azure_models_list)) {
                        foreach ($azure_models_list as $mdl) {
                            if (is_array($mdl) && isset($mdl['id'])) { $azure_ids[] = strtolower($mdl['id']); }
                            elseif (is_string($mdl)) { $azure_ids[] = strtolower($mdl); }
                        }
                    }
                    $replicate_models_list = \WPAICG\AIPKit_Providers::get_replicate_models();
                } else {
                    $replicate_models_list = [];
                }
                $replicate_ids = [];
                if (is_array($replicate_models_list)) {
                    foreach ($replicate_models_list as $mdl) {
                        if (is_array($mdl) && isset($mdl['id'])) { $replicate_ids[] = strtolower($mdl['id']); }
                        elseif (is_string($mdl)) { $replicate_ids[] = strtolower($mdl); }
                    }
                }
                $openai_lu = array_flip(array_map('strtolower',$openai_ids));
                $google_lu = array_flip(array_map('strtolower',$google_ids));
                $azure_lu = array_flip($azure_ids);
                $replicate_lu = array_flip($replicate_ids);
                $providers_detected = [];
                foreach ($models_arr as $m) {
                    $ml = strtolower($m);
                    if (isset($openai_lu[$ml])) { $providers_detected['OpenAI'] = true; continue; }
                    if (isset($google_lu[$ml])) { $providers_detected['Google'] = true; continue; }
                    if (isset($azure_lu[$ml])) { $providers_detected['Azure'] = true; continue; }
                    if (isset($replicate_lu[$ml])) { $providers_detected['Replicate'] = true; continue; }
                }
                $new_frontend_settings['allowed_models'] = implode(', ', $models_arr);
                $new_frontend_settings['allowed_providers'] = implode(', ', array_keys($providers_detected));
            }
        }
        $new_settings['frontend_display'] = $new_frontend_settings;

        // Handle Replicate settings
        $replicate_defaults = $defaults['replicate'];
        $new_replicate_settings = $new_settings['replicate'] ?? $replicate_defaults;
        // Handle checkbox: when unchecked, POST data won't contain the field, so treat as false
        $new_replicate_settings['disable_safety_checker'] = isset($post_data['replicate_disable_safety_checker']) && ($post_data['replicate_disable_safety_checker'] === '1');
        $new_settings['replicate'] = $new_replicate_settings;

        $current_json = wp_json_encode($current_settings);
        $new_json     = wp_json_encode($new_settings);
        if ($current_json !== $new_json) {
            $updated = update_option(self::SETTINGS_OPTION_NAME, $new_settings, 'no');
            if ($updated) {
                wp_send_json_success(['message' => __('Image Generator settings saved.', 'gpt3-ai-content-generator')]);
            } else {
                // Re-fetch and compare again; if value already matches desired state, treat as success
                $after = get_option(self::SETTINGS_OPTION_NAME, []);
                if (wp_json_encode($after) === $new_json) {
                    wp_send_json_success(['message' => __('Image Generator settings saved.', 'gpt3-ai-content-generator')]);
                } else {
                    $this->send_wp_error(new WP_Error('save_failed', __('Failed to save settings.', 'gpt3-ai-content-generator'), ['status' => 500]));
                }
            }
        } else {
            wp_send_json_success(['message' => __('No changes detected.', 'gpt3-ai-content-generator')]);
        }
    }
}
