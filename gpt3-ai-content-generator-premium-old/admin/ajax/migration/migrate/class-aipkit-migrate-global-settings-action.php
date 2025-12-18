<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/migrate/class-aipkit-migrate-global-settings-action.php
// Status: MODIFIED
// I have added logic to migrate the old AI Assistant custom prompts ('wpaicg_editor_button_menus') to the new Content Enhancer actions ('aipkit_enhancer_actions').

namespace WPAICG\Admin\Ajax\Migration\Migrate;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\WP_AI_Content_Generator_Activator;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\aipkit_dashboard;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\PostEnhancer\Ajax\AIPKit_Enhancer_Actions_Ajax_Handler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for migrating global settings.
 */
class AIPKit_Migrate_Global_Settings_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $this->update_category_status('global_settings', 'in_progress');
        global $wpdb;
        $processed_counts = ['custom_bots' => 0, 'chat_tokens' => 0];
        $migrated_id_map = [];

        try {
            // --- 1. Fetch Old Data ---
            $old_table_data = [];
            $old_wpaicg_table_name = $wpdb->prefix . 'wpaicg';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Reason: Direct query to check if the table exists.
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_wpaicg_table_name)) === $old_wpaicg_table_name) {$old_table_row = $wpdb->get_row("SELECT * FROM " . esc_sql($old_wpaicg_table_name) . " WHERE name = 'wpaicg_settings'", ARRAY_A);
                if ($old_table_row) {
                    $old_table_data = $old_table_row;
                }
            }

            // --- 2. Ensure New Classes are Loaded & Initialize New Settings ---
            if (!class_exists(\WPAICG\AIPKit_Providers::class) || !class_exists(\WPAICG\AIPKIT_AI_Settings::class) || !class_exists(\WPAICG\aipkit_dashboard::class) || !class_exists(\WPAICG\Core\Providers\Google\GoogleSettingsHandler::class)) {
                throw new \Exception('Core dependency classes (Providers, AI_Settings, Dashboard, GoogleSettingsHandler) not found.');
            }
            $new_opts = get_option('aipkit_options', []);
            if (!is_array($new_opts)) {
                $new_opts = [];
            }

            $new_opts['providers'] = AIPKit_Providers::get_provider_defaults_all();
            $new_opts['ai_parameters'] = AIPKIT_AI_Settings::$default_ai_params;
            $new_opts['module_settings'] = aipkit_dashboard::$default_module_settings;
            $new_opts['addons_status'] = aipkit_dashboard::$default_addon_status;
            $new_opts['api_keys'] = AIPKIT_AI_Settings::$default_api_keys;
            $new_security_opts = AIPKIT_AI_Settings::$default_security_settings;

            // --- 3. Migrate Settings ---
            $old_provider = get_option('wpaicg_provider', 'OpenAI');
            $new_opts['provider'] = match (ucfirst(strtolower($old_provider))) {
                'OpenRouter' => 'OpenRouter', 'Google' => 'Google', 'Azure' => 'Azure', 'DeepSeek' => 'DeepSeek',
                default => 'OpenAI',
            };

            $new_opts['providers']['OpenAI']['api_key'] = $old_table_data['api_key'] ?? '';
            $new_opts['providers']['Azure']['api_key'] = get_option('wpaicg_azure_api_key', '');
            $new_opts['providers']['Azure']['endpoint'] = get_option('wpaicg_azure_endpoint', '');
            $new_opts['providers']['Google']['api_key'] = get_option('wpaicg_google_model_api_key', '');
            $new_opts['providers']['OpenRouter']['api_key'] = get_option('wpaicg_openrouter_api_key', '');
            $new_opts['providers']['DeepSeek']['api_key'] = get_option('wpaicg_deepseek_api_key', '');
            $new_opts['providers']['ElevenLabs']['api_key'] = get_option('wpaicg_elevenlabs_api', '');
            $new_opts['providers']['Pinecone']['api_key'] = get_option('wpaicg_pinecone_api', '');
            $new_opts['providers']['Qdrant']['api_key'] = get_option('wpaicg_qdrant_api_key', '');
            $new_opts['providers']['Qdrant']['url'] = get_option('wpaicg_qdrant_endpoint', '');

            $new_opts['providers']['OpenAI']['model'] = get_option('wpaicg_ai_model', 'gpt-4o-mini');
            $new_opts['providers']['OpenRouter']['model'] = get_option('wpaicg_openrouter_default_model', '');
            $new_opts['providers']['Google']['model'] = get_option('wpaicg_google_default_model', '');
            $new_opts['providers']['Azure']['model'] = get_option('wpaicg_azure_deployment', '');

            $old_module_settings = get_option('wpaicg_module_settings', []);
            if (is_array($old_module_settings)) {
                $new_module_map = ['chatgpt' => 'chat_bot', 'promptbase' => 'content_writer', 'content_writer' => 'content_writer', 'imgcreator' => 'image_generator', 'embeddings' => 'training', 'finetune' => 'training', 'auto_content' => 'autogpt', 'auto_article' => 'autogpt', 'rss_feed' => 'autogpt', 'chatbot_widget' => 'chat_bot', 'aipower_dashboard' => null, 'audio_converter' => 'audio_converter', 'account' => 'ai_account', 'chat_logs' => 'logs_viewer', 'forms' => 'ai_forms'];
                foreach ($old_module_settings as $old_key => $is_enabled) {
                    $new_key = $new_module_map[$old_key] ?? null;
                    if ($new_key && isset($new_opts['module_settings'][$new_key])) {
                        $new_opts['module_settings'][$new_key] = (bool)$is_enabled;
                    }
                }
            }

            $old_google_safety_settings = get_option('wpaicg_google_safety_settings');
            if (is_array($old_google_safety_settings)) {
                $new_opts['providers']['Google']['safety_settings'] = $old_google_safety_settings;
            }

            $new_security_opts['bannedwords']['words'] = get_option('wpaicg_banned_words', '');
            $new_security_opts['bannedips']['ips'] = get_option('wpaicg_banned_ips', '');

            // --- Migrate AI Forms Token Settings ---
            if (class_exists(\WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::class)) {
                $old_forms_token_settings = get_option('wpaicg_limit_tokens_form', []);
                if (!empty($old_forms_token_settings) && is_array($old_forms_token_settings)) {
                    $new_forms_settings = \WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::get_default_settings();
                    $new_token_settings = &$new_forms_settings['token_management'];
                    if (isset($old_forms_token_settings['limit']) && is_numeric($old_forms_token_settings['limit'])) {
                        $new_token_settings['token_user_limit'] = absint($old_forms_token_settings['limit']);
                    }
                    if (isset($old_forms_token_settings['guest_limit']) && is_numeric($old_forms_token_settings['guest_limit'])) {
                        $new_token_settings['token_guest_limit'] = absint($old_forms_token_settings['guest_limit']);
                    }
                    if (!empty($old_forms_token_settings['message'])) {
                        $new_token_settings['token_limit_message'] = sanitize_text_field($old_forms_token_settings['message']);
                    }
                    if (isset($old_forms_token_settings['reset_limit']) && is_numeric($old_forms_token_settings['reset_limit'])) {
                        $reset_days = absint($old_forms_token_settings['reset_limit']);
                        if ($reset_days === 1) {
                            $new_token_settings['token_reset_period'] = 'daily';
                        } elseif ($reset_days === 7) {
                            $new_token_settings['token_reset_period'] = 'weekly';
                        } elseif ($reset_days >= 28 && $reset_days <= 31) {
                            $new_token_settings['token_reset_period'] = 'monthly';
                        } else {
                            $new_token_settings['token_reset_period'] = 'never';
                        }
                    }
                    $new_token_settings['token_limit_mode'] = 'general';
                    $new_token_settings['token_role_limits'] = '[]';
                    update_option(\WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::SETTINGS_OPTION_NAME, $new_forms_settings, 'no');
                }
            }
            // --- END ---

            // --- START: Migrate AI Assistant (Content Enhancer) Custom Actions ---
            if (class_exists(AIPKit_Enhancer_Actions_Ajax_Handler::class)) {
                $old_custom_actions = get_option('wpaicg_editor_button_menus', []);
                if (!empty($old_custom_actions) && is_array($old_custom_actions)) {
                    $actions_handler = new AIPKit_Enhancer_Actions_Ajax_Handler();
                    $new_default_actions = $actions_handler->get_default_actions_public();
                    $migrated_actions = [];
                    foreach ($old_custom_actions as $old_action) {
                        if (empty($old_action['name']) || empty($old_action['prompt'])) {
                            continue;
                        }
                        // Transform to new format
                        $migrated_actions[] = [
                            'id' => 'custom-' . wp_generate_uuid4(),
                            'label' => $old_action['name'],
                            'prompt' => str_replace('[text]', '%s', $old_action['prompt']), // Replace placeholder
                            'is_default' => false
                        ];
                    }
                    // Merge new defaults with migrated custom actions
                    $final_actions = array_merge($new_default_actions, $migrated_actions);
                    update_option('aipkit_enhancer_actions', $final_actions, 'no');
                }
            }
            // --- END: Migrate AI Assistant ---


            // --- 4. Save and Finalize ---
            update_option('aipkit_options', $new_opts, 'no');
            update_option(AIPKIT_AI_Settings::SECURITY_OPTION_NAME, $new_security_opts, 'no');
            AIPKit_Providers::get_all_providers();
            AIPKit_Providers::clear_model_caches();
            AIPKIT_AI_Settings::init();

            $this->update_category_status('global_settings', 'migrated');
            wp_send_json_success([
                'message' => __('Global settings migrated successfully.', 'gpt3-ai-content-generator'),
                'category_status' => 'migrated'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'global_settings_migration_failed', 'global_settings');
        }
    }
}