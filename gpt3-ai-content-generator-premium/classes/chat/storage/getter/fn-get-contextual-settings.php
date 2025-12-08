<?php

// File: classes/chat/storage/getter/fn-get-contextual-settings.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves contextual settings like content_aware, file_upload, image_upload,
 * image_triggers, and chat_image_model_id.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of contextual settings.
 */
function get_contextual_settings_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['content_aware_enabled'] = in_array($get_meta_fn('_aipkit_content_aware_enabled', BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED), ['0','1'])
        ? $get_meta_fn('_aipkit_content_aware_enabled', BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED)
        : BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED;

    $settings['enable_file_upload'] = in_array($get_meta_fn('_aipkit_enable_file_upload', BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_file_upload', BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD)
        : BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD;

    $settings['enable_image_upload'] = in_array($get_meta_fn('_aipkit_enable_image_upload', BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_image_upload', BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD)
        : BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD;

    $settings['image_triggers'] = $get_meta_fn('_aipkit_image_triggers', BotSettingsManager::DEFAULT_IMAGE_TRIGGERS);
    if (empty($settings['image_triggers'])) {
        $settings['image_triggers'] = BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
    }

    $settings['chat_image_model_id'] = $get_meta_fn('_aipkit_chat_image_model_id', BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID);
    // Build valid image models dynamically
    $valid_image_models = ['gpt-image-1', 'dall-e-3', 'dall-e-2'];
    if (class_exists('\\WPAICG\\AIPKit_Providers')) {
        // Add Google image models
        $google_models = \WPAICG\AIPKit_Providers::get_google_image_models();
        if (!empty($google_models)) {
            $valid_image_models = array_merge($valid_image_models, wp_list_pluck($google_models, 'id'));
        }
    }

    // Add Azure image models to validation list
    if (class_exists('\WPAICG\AIPKit_Providers')) {
        $azure_models = \WPAICG\AIPKit_Providers::get_azure_image_models();
        if (!empty($azure_models)) {
            $azure_model_ids = wp_list_pluck($azure_models, 'id');
            $valid_image_models = array_merge($valid_image_models, $azure_model_ids);
        }
    }

    // Check if replicate addon is active before adding its models to validation list
    $replicate_addon_active = false;
    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $replicate_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('replicate');
    }

    if ($replicate_addon_active && class_exists('\WPAICG\AIPKit_Providers')) {
        $replicate_models = \WPAICG\AIPKit_Providers::get_replicate_models();
        if (!empty($replicate_models)) {
            $replicate_model_ids = wp_list_pluck($replicate_models, 'id');
            $valid_image_models = array_merge($valid_image_models, $replicate_model_ids);
        }
    }

    if (!in_array($settings['chat_image_model_id'], $valid_image_models)) {
        $settings['chat_image_model_id'] = BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID;
    }

    return $settings;
}
