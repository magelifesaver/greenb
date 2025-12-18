<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/class-aipkit-settings-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax;

use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\aipkit_dashboard; // For addon/plan checks
use WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation; // For addon checks
use WPAICG\Lib\Addons\AIPKit_Consent_Compliance; // For addon checks
use WP_Error;
use WPAICG\WP_AI_Content_Generator_Activator; // ADDED for migration constants

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests for saving core AI Settings and related options.
 * Refactored for better modularity and clarity in saving different settings groups.
 */
class SettingsAjaxHandler extends BaseDashboardAjaxHandler
{
    public function ajax_save_settings()
    {
        $permission_check = $this->check_module_access_permissions('settings');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_module_access_permissions().
        $post_data = wp_unslash($_POST);

        // Store initial states to detect if any actual change occurred
        $initial_core_opts_json = wp_json_encode(get_option('aipkit_options', []));
        $initial_security_opts_json = wp_json_encode(AIPKIT_AI_Settings::get_security_settings());

        // --- Perform Save Operations for Different Setting Groups ---
        $this->save_main_provider_selection($post_data);
        $this->save_all_provider_api_details($post_data);
        $this->save_global_ai_parameters($post_data);
        $this->save_public_api_key($post_data);
        $this->save_google_safety_settings_if_applicable($post_data);
        $this->save_all_security_settings($post_data); // Operates on a separate option
        $enhancer_settings_changed = $this->save_enhancer_settings($post_data);
        $this->save_semantic_search_settings($post_data);
        $updated_enhancer_actions = $this->save_enhancer_actions($post_data); // NEW

        // --- Check if any options actually changed ---
        $final_core_opts_json = wp_json_encode(get_option('aipkit_options', []));
        $final_security_opts_json = wp_json_encode(AIPKIT_AI_Settings::get_security_settings());

        $core_changed = ($initial_core_opts_json !== $final_core_opts_json);
        $security_changed = ($initial_security_opts_json !== $final_security_opts_json);

        if ($core_changed || $security_changed || $enhancer_settings_changed || $updated_enhancer_actions !== null) {
            $response = ['message' => __('Settings saved successfully.', 'gpt3-ai-content-generator')];
            if ($updated_enhancer_actions !== null) {
                $response['updated_enhancer_actions'] = $updated_enhancer_actions;
            }
            wp_send_json_success($response);
        } else {
            wp_send_json_success(['message' => __('No changes detected.', 'gpt3-ai-content-generator')]);
        }
    }

    /**
     * Saves the main AI provider selection.
     * Calls AIPKit_Providers::save_current_provider which handles its own update_option.
     */
    private function save_main_provider_selection(array $post_data): void
    {
        $current_main_provider = isset($post_data['provider']) ? sanitize_text_field($post_data['provider']) : null;
        if ($current_main_provider) {
            AIPKit_Providers::save_current_provider($current_main_provider);
        }
    }

    /**
     * Saves API details for ALL providers if their data is present in POST.
     * Calls AIPKit_Providers::save_provider_data for each, which handles its own update_option.
     */
    private function save_all_provider_api_details(array $post_data): void
    {
        $all_provider_defaults = AIPKit_Providers::get_provider_defaults_all();

        foreach (array_keys($all_provider_defaults) as $provider_name) {
            $provider_key_prefix = strtolower($provider_name);
            $provider_data_from_post = [];
            $provider_has_data_in_post = false;

            // Collect data for this provider from $post_data
            foreach (array_keys($all_provider_defaults[$provider_name]) as $key) {
                // Default form field name construction
                $form_field_name = $provider_key_prefix . '_' . $key;

                // Handle special form field names that don't match the $provider_key_prefix . '_' . $key pattern
                if ($provider_name === 'Azure' && $key === 'model') {
                    $form_field_name = 'azure_deployment';
                }


                if (array_key_exists($form_field_name, $post_data)) {
                    $value_from_post = $post_data[$form_field_name];
                    // Sanitize based on key
                    if (in_array($key, ['base_url', 'endpoint', 'url'], true)) {
                        $sanitized_value = esc_url_raw($value_from_post);
                    } elseif ($key === 'store_conversation') {
                        $sanitized_value = ($value_from_post === '1' ? '1' : '0');
                    } elseif ($key === 'expiration_policy') {
                        $sanitized_value = absint($value_from_post);
                    } else {
                        $sanitized_value = sanitize_text_field($value_from_post);
                    }

                    $provider_data_from_post[$key] = $sanitized_value;
                    $provider_has_data_in_post = true;
                }
            }

            if ($provider_has_data_in_post) {
                AIPKit_Providers::save_provider_data($provider_name, $provider_data_from_post);
            }
        }
    }

    /**
     * Saves global AI parameters (temperature, max_tokens, etc.) to 'aipkit_options'.
     */
    private function save_global_ai_parameters(array $post_data): void
    {
        // --- FIX: Safely retrieve options ---
        $opts = get_option('aipkit_options');
        if (!is_array($opts)) {
            $opts = [];
        }
        // --- END FIX ---

        $default_ai_params = AIPKIT_AI_Settings::$default_ai_params;
        $existing_params = $opts['ai_parameters'] ?? $default_ai_params;
        $new_params = $existing_params;
        $changed = false;

        foreach ($default_ai_params as $key => $default_value) {
            if (isset($post_data[$key])) {
                $value_from_post = $post_data[$key];
                $value_to_set = null;
                switch ($key) {
                    case 'max_completion_tokens': $val = absint($value_from_post);
                        $val = max(1, min($val, 128000));
                        $value_to_set = $val;
                        break;
                    case 'temperature':
                    case 'top_p':
                    case 'frequency_penalty':
                    case 'presence_penalty':
                        $val = floatval($value_from_post);
                        if ($key === 'temperature' || $key === 'frequency_penalty' || $key === 'presence_penalty') {
                            $val = max(0.0, min($val, 2.0));
                        }
                        if ($key === 'top_p') {
                            $val = max(0.0, min($val, 1.0));
                        }
                        $value_to_set = $val;
                        break;
                    default: $value_to_set = sanitize_text_field($value_from_post);
                        break;
                }
                if (!isset($new_params[$key]) || $new_params[$key] !== $value_to_set) {
                    $new_params[$key] = $value_to_set;
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $opts['ai_parameters'] = $new_params;
            update_option('aipkit_options', $opts, 'no');
        }
    }

    /**
     * Saves the Public API Key to 'aipkit_options'.
     */
    private function save_public_api_key(array $post_data): void
    {
        if (isset($post_data['public_api_key'])) {
            // --- FIX: Safely retrieve options ---
            $opts = get_option('aipkit_options');
            if (!is_array($opts)) {
                $opts = [];
            }
            // --- END FIX ---

            $existing_api_keys = $opts['api_keys'] ?? AIPKIT_AI_Settings::$default_api_keys;
            $new_public_key = sanitize_text_field(trim($post_data['public_api_key']));

            if (($existing_api_keys['public_api_key'] ?? '') !== $new_public_key) {
                if (!isset($opts['api_keys']) || !is_array($opts['api_keys'])) {
                    $opts['api_keys'] = AIPKIT_AI_Settings::$default_api_keys;
                }
                $opts['api_keys']['public_api_key'] = $new_public_key;
                update_option('aipkit_options', $opts, 'no');
            }
        }
    }

    /**
     * Saves Google Safety Settings to 'aipkit_options'.
     * Delegates to GoogleSettingsHandler which handles its own update_option.
     */
    private function save_google_safety_settings_if_applicable(array $post_data): void
    {
        if (class_exists(GoogleSettingsHandler::class) && method_exists(GoogleSettingsHandler::class, 'save_safety_settings')) {
            GoogleSettingsHandler::save_safety_settings($post_data);
        }
    }

    /**
     * Saves all security-related settings (Banned Words/IPs, OpenAI Moderation, Consent)
     * to the 'aipkit_security' option.
     */
    private function save_all_security_settings(array $post_data): void
    {
        // --- FIX: Safely retrieve security options ---
        $security_opts = get_option(AIPKIT_AI_Settings::SECURITY_OPTION_NAME);
        if (!is_array($security_opts)) {
            $security_opts = AIPKIT_AI_Settings::get_security_settings(); // Fallback to getter which has defaults
        }
        // --- END FIX ---

        $new_security_opts = $security_opts;

        if (isset($post_data['banned_words']) || isset($post_data['banned_words_message'])) {
            $banned_words_raw = isset($post_data['banned_words']) ? sanitize_textarea_field($post_data['banned_words']) : ($new_security_opts['bannedwords']['words'] ?? '');
            $word_notification_message = isset($post_data['banned_words_message']) ? sanitize_text_field($post_data['banned_words_message']) : ($new_security_opts['bannedwords']['message'] ?? '');
            $words_array = array_map('trim', explode(',', strtolower($banned_words_raw)));
            $new_security_opts['bannedwords']['words'] = implode(',', array_filter($words_array, fn ($word) => !empty($word)));
            $new_security_opts['bannedwords']['message'] = $word_notification_message;
        }

        if (isset($post_data['banned_ips']) || isset($post_data['banned_ips_message'])) {
            $banned_ips_raw = isset($post_data['banned_ips']) ? sanitize_textarea_field($post_data['banned_ips']) : ($new_security_opts['bannedips']['ips'] ?? '');
            $ip_notification_message = isset($post_data['banned_ips_message']) ? sanitize_text_field($post_data['banned_ips_message']) : ($new_security_opts['bannedips']['message'] ?? '');
            $ips_array = array_map('trim', explode(',', $banned_ips_raw));
            $new_security_opts['bannedips']['ips'] = implode(',', array_filter($ips_array, fn ($ip) => !empty($ip)));
            $new_security_opts['bannedips']['message'] = $ip_notification_message;
        }

        $is_pro = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_pro_plan();
        $openai_mod_addon_active = $is_pro && class_exists('\WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation') && aipkit_dashboard::is_addon_active(AIPKit_OpenAI_Moderation::ADDON_KEY);
        if ($openai_mod_addon_active) {
            if (array_key_exists('openai_moderation_enabled', $post_data)) {
                $new_security_opts['openai_moderation_enabled'] = ($post_data['openai_moderation_enabled'] === '1') ? '1' : '0';
            }
            if (isset($post_data['openai_moderation_message'])) {
                $new_security_opts['openai_moderation_message'] = sanitize_text_field($post_data['openai_moderation_message']);
            }
        } else {
            unset($new_security_opts['openai_moderation_enabled'], $new_security_opts['openai_moderation_message']);
        }

        $consent_addon_active = $is_pro && class_exists('\WPAICG\Lib\Addons\AIPKit_Consent_Compliance') && aipkit_dashboard::is_addon_active(AIPKit_Consent_Compliance::ADDON_KEY);
        if ($consent_addon_active) {
            if (isset($post_data['consent_title'])) {
                $new_security_opts['consent']['title'] = sanitize_text_field($post_data['consent_title']);
            }
            if (isset($post_data['consent_message'])) {
                $new_security_opts['consent']['message'] = wp_kses_post($post_data['consent_message']);
            }
            if (isset($post_data['consent_button'])) {
                $new_security_opts['consent']['button'] = sanitize_text_field($post_data['consent_button']);
            }
        } else {
            unset($new_security_opts['consent']);
        }

        if (wp_json_encode($security_opts) !== wp_json_encode($new_security_opts)) {
            update_option(AIPKIT_AI_Settings::SECURITY_OPTION_NAME, $new_security_opts, 'no');
        }
    }

    /**
     * Saves Content Enhancer settings.
     * @param array $post_data
     * @return bool True if settings were changed, false otherwise.
     */
    private function save_enhancer_settings(array $post_data): bool
    {
        // --- FIX: Safely retrieve options ---
        $opts = get_option('aipkit_options');
        if (!is_array($opts)) {
            $opts = [];
        }
        // --- END FIX ---

        $current_enhancer_settings = $opts['enhancer_settings'] ?? [];
        $new_enhancer_settings = $current_enhancer_settings;
        $changed = false;

        if (array_key_exists('enhancer_editor_integration', $post_data)) {
            $new_value = ($post_data['enhancer_editor_integration'] === '1') ? '1' : '0';
            if (($new_enhancer_settings['editor_integration'] ?? '1') !== $new_value) {
                $new_enhancer_settings['editor_integration'] = $new_value;
                $changed = true;
            }
        }

        if (array_key_exists('enhancer_insert_position_default', $post_data)) {
            $raw = sanitize_key($post_data['enhancer_insert_position_default']);
            $allowed = ['replace','after','before'];
            $pos = in_array($raw, $allowed, true) ? $raw : 'replace';
            if (($new_enhancer_settings['default_insert_position'] ?? 'replace') !== $pos) {
                $new_enhancer_settings['default_insert_position'] = $pos;
                $changed = true;
            }
        }

        if ($changed) {
            $opts['enhancer_settings'] = $new_enhancer_settings;
            update_option('aipkit_options', $opts, 'no');
        }
        return $changed;
    }

    /**
     * Saves Content Enhancer custom actions.
     * @param array $post_data
     * @return array|null The updated list of actions if changes were made, otherwise null.
     */
    private function save_enhancer_actions(array $post_data): ?array
    {
        $submitted_actions = $post_data['enhancer_actions'] ?? null;
        if (!is_array($submitted_actions)) {
            return null;
        }
        $actions_option_name = 'aipkit_enhancer_actions';
        $current_actions = get_option($actions_option_name, []);
        $actions_map = [];
        foreach ($current_actions as $action) {
            if (isset($action['id'])) {
                $actions_map[$action['id']] = $action;
            }
        }
        $changed = false;
        foreach ($submitted_actions as $id => $data) {
            $label = sanitize_text_field($data['label'] ?? '');
            $prompt = sanitize_textarea_field($data['prompt'] ?? '');
            if (empty($label) || empty($prompt)) {
                continue;
            }
            if (strpos($id, 'new-') === 0) {
                // Create new action
                $new_id = 'custom-' . wp_generate_uuid4();
                $actions_map[$new_id] = ['id' => $new_id, 'label' => $label, 'prompt' => $prompt, 'is_default' => false];
                $changed = true;
            } elseif (isset($actions_map[$id]) && !$actions_map[$id]['is_default']) {
                // Update existing custom action
                if ($actions_map[$id]['label'] !== $label || $actions_map[$id]['prompt'] !== $prompt) {
                    $actions_map[$id]['label'] = $label;
                    $actions_map[$id]['prompt'] = $prompt;
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $new_actions_array = array_values($actions_map);
            update_option($actions_option_name, $new_actions_array, 'no');
            return $new_actions_array;
        }
        return null;
    }

    /**
     * NEW: Saves Semantic Search settings to 'aipkit_options'.
     *
     * @param array $post_data The $_POST data array.
     */
    private function save_semantic_search_settings(array $post_data): void
    {
        // Check if any semantic search data was submitted
        $semantic_keys_exist = array_filter(array_keys($post_data), function ($key) {
            return strpos($key, 'semantic_search_') === 0;
        });

        if (empty($semantic_keys_exist)) {
            return; // No settings to save
        }

        // --- FIX: Safely retrieve options ---
        $opts = get_option('aipkit_options');
        if (!is_array($opts)) {
            $opts = [];
        }
        // --- END FIX ---

        $current_settings = $opts['semantic_search'] ?? [];
        $new_settings = [];

        // Sanitize and collect new settings
        $new_settings['vector_provider'] = isset($post_data['semantic_search_vector_provider'])
            ? sanitize_key($post_data['semantic_search_vector_provider'])
            : ($current_settings['vector_provider'] ?? 'pinecone');

        $new_settings['target_id'] = isset($post_data['semantic_search_target_id'])
            ? sanitize_text_field($post_data['semantic_search_target_id'])
            : ($current_settings['target_id'] ?? '');

        $new_settings['embedding_provider'] = isset($post_data['semantic_search_embedding_provider'])
            ? sanitize_key($post_data['semantic_search_embedding_provider'])
            : ($current_settings['embedding_provider'] ?? 'openai');

        $new_settings['embedding_model'] = isset($post_data['semantic_search_embedding_model'])
            ? sanitize_text_field($post_data['semantic_search_embedding_model'])
            : ($current_settings['embedding_model'] ?? '');

        $new_settings['num_results'] = isset($post_data['semantic_search_num_results'])
            ? absint($post_data['semantic_search_num_results'])
            : ($current_settings['num_results'] ?? 5);

        $new_settings['no_results_text'] = isset($post_data['semantic_search_no_results_text'])
            ? sanitize_text_field($post_data['semantic_search_no_results_text'])
            : ($current_settings['no_results_text'] ?? __('No results found.', 'gpt3-ai-content-generator'));

        // Compare and update if changed
        if (wp_json_encode($current_settings) !== wp_json_encode($new_settings)) {
            $opts['semantic_search'] = $new_settings;
            update_option('aipkit_options', $opts, 'no');
        }
    }


    /**
     * AJAX: Handles user's choice from the migration tool.
     * @since 2.1
     */
    public function ajax_handle_migration_choice()
    {
        // Security check for this specific action
        check_ajax_referer('aipkit_migration_tool_action', '_ajax_nonce_migration');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'gpt3-ai-content-generator')], 403);
            return;
        }

        $choice = isset($_POST['choice']) ? sanitize_key($_POST['choice']) : '';

        if ($choice === 'start_fresh') {
            update_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION, 'fresh_install_chosen', 'no');
            wp_send_json_success(['message' => __('"Start Fresh" option processed. Old data will be ignored.', 'gpt3-ai-content-generator')]);
        } elseif ($choice === 'migrate_data') {
            // Placeholder for Phase 2
            // update_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION, 'migration_chosen', 'no');
            // update_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION, 'in_progress_phase_2_step_0', 'no');
            wp_send_json_success(['message' => __('Data migration will be available soon.', 'gpt3-ai-content-generator')]);
        } else {
            wp_send_json_error(['message' => __('Invalid migration choice.', 'gpt3-ai-content-generator')], 400);
        }
    }

    /**
     * AJAX: Handles dismissing the migration notice permanently.
     * @since 2.1
     */
    public function ajax_dismiss_migration_notice()
    {
        $permission_check = $this->check_module_access_permissions('settings');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        update_option('aipkit_migration_notice_dismissed', '1', 'no');
        wp_send_json_success(['message' => 'Migration notice dismissed.']);
    }

    /**
     * AJAX: Handles dismissing the Chatolia notice permanently.
     * @since 2.1
     */
    public function ajax_dismiss_chatolia_notice()
    {
        $permission_check = $this->check_module_access_permissions('settings');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        update_option('aipkit_chatolia_notice_dismissed', '1', 'no');
        wp_send_json_success(['message' => 'Chatolia notice dismissed.']);
    }
}
