<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/migrate/class-aipkit-migrate-chatbot-data-action.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration\Migrate;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\WP_AI_Content_Generator_Activator;
use WPAICG\Chat\Admin\AdminSetup as ChatAdminSetup;
use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Core\TokenManager\Constants\MetaKeysConstants;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants;
use WP_Query;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for migrating Chatbot data.
 * REVISED: Now reads settings from both post_content (JSON) and post_meta for comprehensive migration from older plugin versions.
 * FIXED: Uses wp_unslash() on post_content before json_decode to handle escaped characters.
 * ADDED: Fallback mechanism to repair malformed JSON in post_content if initial decoding fails.
 */
class AIPKit_Migrate_Chatbot_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $this->update_category_status('chatbot_data', 'in_progress');
        global $wpdb;
        $processed_counts = ['custom_bots' => 0, 'chat_tokens' => 0];
        $migrated_id_map = [];

        try {
            if (!class_exists(ChatAdminSetup::class) || !class_exists(BotSettingsManager::class) ||
                !class_exists(MetaKeysConstants::class) || !class_exists(GuestTableConstants::class)) {
                throw new \Exception('Core dependency classes (ChatAdminSetup, BotSettingsManager, TokenConstants) not found for Chatbot migration.');
            }

            $old_bots_query = new WP_Query(['post_type' => 'wpaicg_chatbot', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1]);

            if ($old_bots_query->have_posts()) {
                $settings_manager = new BotSettingsManager();
                while ($old_bots_query->have_posts()) {
                    $old_bots_query->the_post();
                    $old_bot = get_post();

                    $new_bot_id = wp_insert_post(['post_title' => $old_bot->post_title, 'post_type' => ChatAdminSetup::POST_TYPE, 'post_status' => $old_bot->post_status, 'post_author' => $old_bot->post_author], true);
                    if (is_wp_error($new_bot_id)) {
                        continue;
                    }

                    $migrated_id_map[$old_bot->ID] = $new_bot_id;

                    // 1. Gather all old settings from meta and content
                    $all_old_settings = $this->gather_all_old_settings($old_bot);

                    // 2. Map old settings to the new structure
                    $new_bot_settings = $this->map_old_to_new_settings($all_old_settings);

                    // 3. Save the new settings
                    $settings_manager->save_bot_settings($new_bot_id, $new_bot_settings);

                    // 4. Handle default/site-wide flags
                    if (get_post_meta($old_bot->ID, '_wpaicg_default_bot', true) === '1' || $old_bot->post_title === 'Default') {
                        update_post_meta($new_bot_id, '_aipkit_default_bot', '1');
                    }
                    if (get_post_meta($old_bot->ID, '_wpaicg_site_wide_enabled', true) === '1') {
                        update_post_meta($new_bot_id, '_aipkit_site_wide_enabled', '1');
                    }

                    $processed_counts['custom_bots']++;
                }
                wp_reset_postdata();
            }

            if (!empty($migrated_id_map)) {
                $existing_map = get_option('aipkit_bot_id_map', []);
                $final_map = $migrated_id_map + $existing_map;
                update_option('aipkit_bot_id_map', $final_map, 'no');
            }

            // Chat Log migration is explicitly disabled
            $processed_counts['chat_logs'] = 0;

            $old_tokens_table = $wpdb->prefix . 'wpaicg_chattokens';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: This is a custom table created by the plugin, and we are checking its existence
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_tokens_table)) === $old_tokens_table) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading from a legacy table during a one-time migration. Table name is constructed safely.
                $old_tokens_data = $wpdb->get_results("SELECT * FROM " . esc_sql($old_tokens_table), ARRAY_A);
                $aggregated_tokens = [];
                foreach ($old_tokens_data as $token_row) {
                    $key_user_id = $token_row['user_id'] ? (int)$token_row['user_id'] : null;
                    $key_session_id = !$key_user_id && !empty($token_row['session_id']) ? $token_row['session_id'] : null;
                    $key_bot_id = (is_numeric($token_row['source']) && $token_row['source'] > 0) ? (int)$token_row['source'] : 0;
                    $context_key = ($key_user_id ? "user_{$key_user_id}" : "guest_{$key_session_id}") . "_bot_{$key_bot_id}";
                    if (!isset($aggregated_tokens[$context_key])) {
                        $aggregated_tokens[$context_key] = ['tokens' => 0, 'first_ts' => strtotime($token_row['created_at'])];
                    }
                    $aggregated_tokens[$context_key]['tokens'] += (int)$token_row['tokens'];
                }
                foreach ($aggregated_tokens as $context_key => $data) {
                    list($user_part, $bot_part) = explode('_bot_', $context_key);
                    $bot_id_for_meta = (int)$bot_part;
                    if (strpos($user_part, 'user_') === 0) {
                        $uid = (int)str_replace('user_', '', $user_part);
                        update_user_meta($uid, MetaKeysConstants::CHAT_USAGE_META_KEY_PREFIX . $bot_id_for_meta, $data['tokens']);
                        update_user_meta($uid, MetaKeysConstants::CHAT_RESET_META_KEY_PREFIX . $bot_id_for_meta, $data['first_ts']);
                    } elseif (strpos($user_part, 'guest_') === 0 && $bot_id_for_meta !== null) {
                        $sid = str_replace('guest_', '', $user_part);
                        if (!empty($sid)) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration write to a new table. Table name is constructed safely.
                            $wpdb->replace($wpdb->prefix . GuestTableConstants::GUEST_TABLE_NAME_SUFFIX, ['session_id' => $sid, 'bot_id' => $bot_id_for_meta, 'tokens_used' => $data['tokens'], 'last_reset_timestamp' => $data['first_ts'], 'last_updated_at' => current_time('mysql', 1)]);
                        }
                    }
                    $processed_counts['chat_tokens']++;
                }
            }

            $this->update_category_status('chatbot_data', 'migrated');
            /* translators: %1$d is the number of migrated bots, %2$d is the number of migrated token records */
            wp_send_json_success(['message' => sprintf(__('Chatbot data migrated: %1$d bots and %2$d token records migrated. Old logs were not migrated.', 'gpt3-ai-content-generator'), $processed_counts['custom_bots'], $processed_counts['chat_tokens']), 'category_status' => 'migrated']);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'chatbot_migration_failed', 'chatbot_data');
        }
    }

    /**
     * Gathers all old settings from an old bot post, combining post_meta and post_content JSON.
     * @param \WP_Post $old_bot The old chatbot post object.
     * @return array An associative array of all found old settings.
     */
    private function gather_all_old_settings(\WP_Post $old_bot): array
    {
        $meta_settings = get_post_meta($old_bot->ID);
        $flat_meta = [];
        foreach ($meta_settings as $key => $value) {
            $flat_meta[$key] = maybe_unserialize($value[0] ?? '');
        }

        $content_settings = [];
        if (!empty($old_bot->post_content)) {
            $unslashed_content = wp_unslash($old_bot->post_content);
            $decoded = json_decode($unslashed_content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $repaired_json_string = $this->repair_json_string($unslashed_content);
                if ($repaired_json_string) {
                    $decoded = json_decode($repaired_json_string, true);
                }
            }

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $content_settings = $decoded;
            }
        }
        return array_merge($flat_meta, $content_settings);
    }

    /**
     * Attempts to repair a malformed JSON string by escaping unescaped quotes within the 'chat_addition_text' value.
     * @param string $json_string The potentially malformed JSON string.
     * @return string|null The repaired string, or null if repair was not possible.
     */
    private function repair_json_string(string $json_string): ?string
    {
        $key_marker = '"chat_addition_text":"';
        $start_pos = strpos($json_string, $key_marker);
        if ($start_pos === false) {
            return null;
        }
        $value_start_pos = $start_pos + strlen($key_marker);

        // Find the next key to determine the end of the current value.
        // We look for a pattern like `","some_key_name"`
        $end_pos = -1;
        if (preg_match('/",\s*"[a-zA-Z0-9_]+"/', $json_string, $matches, PREG_OFFSET_CAPTURE, $value_start_pos)) {
            $end_pos = $matches[0][1];
        }

        if ($end_pos === -1) {
            return null;
        }

        $prefix = substr($json_string, 0, $value_start_pos);
        $value_to_fix = substr($json_string, $value_start_pos, $end_pos - $value_start_pos);
        $suffix = substr($json_string, $end_pos);

        // Escape double quotes that are NOT already escaped.
        $escaped_value = preg_replace('/(?<!\\\\)"/', '\"', $value_to_fix);
        $repaired_json = $prefix . $escaped_value . $suffix;
        return $repaired_json;
    }


    /**
     * Maps the combined old settings array to the new settings structure expected by BotSettingsManager->save_bot_settings.
     * @param array $old_settings The combined array of old settings.
     * @return array The settings array with new keys.
     */
    private function map_old_to_new_settings(array $old_settings): array
    {
        $new_settings = [];
        $map = $this->get_full_migration_map();

        foreach ($map as $old_key => $mapping_info) {
            if (isset($old_settings[$old_key])) {
                $new_key = $mapping_info['new_key'];
                $old_value = $old_settings[$old_key];
                $value_map = $mapping_info['value_map'] ?? null;
                $new_value = $value_map ? ($value_map[$old_value] ?? $old_value) : $old_value;

                // Handle special value transformations
                if ($new_key === 'conversation_starters' && is_array($new_value)) {
                    $new_value = implode("\n", $new_value);
                }
                if ($new_key === 'image_triggers' && $old_key === 'image_enable' && $new_value === '1') {
                    // If image_enable was 1, use the old image_triggers value, or default if it was empty
                    $new_settings['image_triggers'] = $old_settings['image_triggers'] ?? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
                    // Also set enable_image_upload flag
                    $new_settings['enable_image_upload'] = '1';
                    continue; // Skip the regular assignment for this key
                }

                // Handle nested settings (custom theme)
                if (strpos($new_key, '.') !== false) {
                    list($parent, $child) = explode('.', $new_key, 2);
                    $new_settings[$parent][$child] = $new_value;
                } else {
                    $new_settings[$new_key] = $new_value;
                }
            }
        }

        // Post-processing and cleanup
        if (isset($new_settings['pinecone_index_name']) && empty($new_settings['pinecone_index_name'])) {
            unset($new_settings['pinecone_index_name']);
        }
        if (isset($new_settings['qdrant_collection_name']) && empty($new_settings['qdrant_collection_name'])) {
            unset($new_settings['qdrant_collection_name']);
        }

        // For role limits, the old data might be a JSON string, ensure it's passed as such
        if (isset($new_settings['token_role_limits']) && is_array($new_settings['token_role_limits'])) {
            $new_settings['token_role_limits'] = wp_json_encode($new_settings['token_role_limits']);
        }

        return $new_settings;
    }


    /**
     * Provides a comprehensive map of old setting keys (from meta and content) to new setting keys.
     * @return array The mapping configuration.
     */
    private function get_full_migration_map(): array
    {
        return [
            // General & AI Config
            'provider'                 => ['new_key' => 'provider'],
            'model'                    => ['new_key' => 'model'],
            'chat_addition_text'       => ['new_key' => 'instructions'],
            'openai_stream_nav'        => ['new_key' => 'stream_enabled', 'value_map' => ['1' => '1', '0' => '0']],
            'temperature'              => ['new_key' => 'temperature'],
            '_wpaicg_temperature'      => ['new_key' => 'temperature'],
            'max_tokens'               => ['new_key' => 'max_completion_tokens'],
            '_wpaicg_max_completion_tokens' => ['new_key' => 'max_completion_tokens'],
            'conversation_cut'         => ['new_key' => 'max_messages'],
            '_wpaicg_max_messages'     => ['new_key' => 'max_messages'],

            // Appearance
            'welcome'                  => ['new_key' => 'greeting'],
            '_wpaicg_greeting_message' => ['new_key' => 'greeting'],
            'theme'                    => ['new_key' => 'theme'],
            '_wpaicg_theme'            => ['new_key' => 'theme'],
            'footer_text'              => ['new_key' => 'footer_text'],
            '_wpaicg_footer_text'      => ['new_key' => 'footer_text'],
            'placeholder'              => ['new_key' => 'input_placeholder'],
            '_wpaicg_input_placeholder' => ['new_key' => 'input_placeholder'],
            'fullscreen'               => ['new_key' => 'enable_fullscreen', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_fullscreen' => ['new_key' => 'enable_fullscreen'],
            'download_btn'             => ['new_key' => 'enable_download', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_download'  => ['new_key' => 'enable_download'],
            'copy_btn'                 => ['new_key' => 'enable_copy_button', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_copy_button' => ['new_key' => 'enable_copy_button'],
            'feedback_btn'             => ['new_key' => 'enable_feedback', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_feedback'  => ['new_key' => 'enable_feedback'],
            'sidebar'                  => ['new_key' => 'enable_conversation_sidebar', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_conversation_sidebar' => ['new_key' => 'enable_conversation_sidebar'],
            'conversation_starters'    => ['new_key' => 'conversation_starters'],
            '_wpaicg_conversation_starters' => ['new_key' => 'conversation_starters'],
            '_wpaicg_enable_conversation_starters' => ['new_key' => 'enable_conversation_starters'],

            // Popup
            'popup_enabled'            => ['new_key' => 'popup_enabled'],
            '_wpaicg_popup_enabled'    => ['new_key' => 'popup_enabled'],
            'position'                 => ['new_key' => 'popup_position'],
            '_wpaicg_popup_position'   => ['new_key' => 'popup_position'],
            'delay_time'               => ['new_key' => 'popup_delay'],
            '_wpaicg_popup_delay'      => ['new_key' => 'popup_delay'],
            'icon'                     => ['new_key' => 'popup_icon_type'],
            '_wpaicg_popup_icon_type'  => ['new_key' => 'popup_icon_type'],
            'icon_url'                 => ['new_key' => 'popup_icon_value'],
            '_wpaicg_popup_icon_value' => ['new_key' => 'popup_icon_value'],

            // Context
            'content_aware'            => ['new_key' => 'content_aware_enabled', 'value_map' => ['yes' => '1', 'no' => '0']],
            '_wpaicg_content_aware_enabled' => ['new_key' => 'content_aware_enabled'],
            'remember_conversation'    => ['new_key' => 'openai_conversation_state_enabled', 'value_map' => ['yes' => '1', 'no' => '0']],
            '_wpaicg_openai_conversation_state_enabled' => ['new_key' => 'openai_conversation_state_enabled'],

            // Vector Store
            '_wpaicg_enable_vector_store' => ['new_key' => 'enable_vector_store'],
            'vectordb'                 => ['new_key' => 'vector_store_provider'],
            '_wpaicg_vector_store_provider' => ['new_key' => 'vector_store_provider'],
            'embedding_index'          => ['new_key' => 'pinecone_index_name'],
            '_wpaicg_pinecone_index_name' => ['new_key' => 'pinecone_index_name'],
            '_wpaicg_qdrant_collection_name' => ['new_key' => 'qdrant_collection_name'],
            'embedding_provider'       => ['new_key' => 'vector_embedding_provider'],
            '_wpaicg_vector_embedding_provider' => ['new_key' => 'vector_embedding_provider'],
            'embedding_model'          => ['new_key' => 'vector_embedding_model'],
            '_wpaicg_vector_embedding_model' => ['new_key' => 'vector_embedding_model'],
            'embedding_top'            => ['new_key' => 'vector_store_top_k'],
            '_wpaicg_vector_store_top_k' => ['new_key' => 'vector_store_top_k'],
            '_wpaicg_openai_vector_store_ids' => ['new_key' => 'openai_vector_store_ids'],

            // Voice
            'chat_to_speech'           => ['new_key' => 'tts_enabled', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_tts_enabled'      => ['new_key' => 'tts_enabled'],
            'voice_service'            => ['new_key' => 'tts_provider'],
            '_wpaicg_tts_provider'     => ['new_key' => 'tts_provider'],
            'voice_name'               => ['new_key' => 'tts_google_voice_id'],
            '_wpaicg_tts_google_voice_id' => ['new_key' => 'tts_google_voice_id'],
            'openai_voice'             => ['new_key' => 'tts_openai_voice_id'],
            '_wpaicg_tts_openai_voice_id' => ['new_key' => 'tts_openai_voice_id'],
            'openai_model'             => ['new_key' => 'tts_openai_model_id'],
            '_wpaicg_tts_openai_model_id' => ['new_key' => 'tts_openai_model_id'],
            'elevenlabs_voice'         => ['new_key' => 'tts_elevenlabs_voice_id'],
            '_wpaicg_tts_elevenlabs_voice_id' => ['new_key' => 'tts_elevenlabs_voice_id'],
            'elevenlabs_model'         => ['new_key' => 'tts_elevenlabs_model_id'],
            '_wpaicg_tts_elevenlabs_model_id' => ['new_key' => 'tts_elevenlabs_model_id'],
            'audio_btn'                => ['new_key' => 'tts_auto_play', 'value_map' => ['1' => '0', '0' => '1']],
            '_wpaicg_tts_auto_play'    => ['new_key' => 'tts_auto_play'],
            'audio_enable'             => ['new_key' => 'enable_voice_input', 'value_map' => ['1' => '1', '0' => '0']],
            '_wpaicg_enable_voice_input' => ['new_key' => 'enable_voice_input'],
            '_wpaicg_stt_provider'     => ['new_key' => 'stt_provider'],
            '_wpaicg_stt_openai_model_id' => ['new_key' => 'stt_openai_model_id'],
            '_wpaicg_stt_azure_model_id' => ['new_key' => 'stt_azure_model_id'],

            // Images
            'image_enable'             => ['new_key' => 'image_triggers', 'value_map' => ['1' => BotSettingsManager::DEFAULT_IMAGE_TRIGGERS, '0' => '']],
            'image_triggers'           => ['new_key' => 'image_triggers'],
            '_wpaicg_image_triggers'   => ['new_key' => 'image_triggers'],
            '_wpaicg_chat_image_model_id' => ['new_key' => 'chat_image_model_id'],
            '_wpaicg_enable_file_upload' => ['new_key' => 'enable_file_upload'],
            '_wpaicg_enable_image_upload' => ['new_key' => 'enable_image_upload'],

            // Theme Settings from post_content JSON
            'bgcolor'                  => ['new_key' => 'custom_theme_settings.container_bg_color'],
            'fontcolor'                => ['new_key' => 'custom_theme_settings.container_text_color'],
            'ai_bg_color'              => ['new_key' => 'custom_theme_settings.bot_bubble_bg_color'],
            'user_bg_color'            => ['new_key' => 'custom_theme_settings.user_bubble_bg_color'],
            'bg_text_field'            => ['new_key' => 'custom_theme_settings.input_wrapper_bg_color'],
            'input_font_color'         => ['new_key' => 'custom_theme_settings.input_text_color'],
            'border_text_field'        => ['new_key' => 'custom_theme_settings.input_wrapper_border_color'],
            'send_color'               => ['new_key' => 'custom_theme_settings.send_button_text_color'],
            'footer_color'             => ['new_key' => 'custom_theme_settings.footer_bg_color'],
            'footer_font_color'        => ['new_key' => 'custom_theme_settings.footer_text_color'],
            'bar_color'                => ['new_key' => 'custom_theme_settings.header_bg_color'],
            'chat_rounded'             => ['new_key' => 'custom_theme_settings.bubble_border_radius'],
        ];
    }
}
