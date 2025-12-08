<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/manager/generate_image.php
// Status: MODIFIED

namespace WPAICG\Images\Manager;

use WPAICG\Images\AIPKit_Image_Manager;
use WPAICG\Images\AIPKit_Image_Provider_Strategy_Factory;
use WPAICG\AIPKit_Providers;
use WPAICG\Images\AIPKit_Image_Storage_Helper;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

function generate_image_logic(AIPKit_Image_Manager $managerInstance, string $prompt, array $options = [], ?int $wp_user_id = null): array|WP_Error
{
    $provider_raw = $options['provider'] ?? 'openai';

    $provider_normalized = match(strtolower($provider_raw)) {
        'openai' => 'OpenAI',
        'azure' => 'Azure',
        'google' => 'Google',
        'pexels' => 'Pexels',
        'pixabay' => 'Pixabay',
        'replicate' => 'Replicate',
        default => 'OpenAI',
    };

    if (!in_array($provider_normalized, ['OpenAI', 'Azure', 'Google', 'Pexels', 'Pixabay', 'Replicate'])) {
        /* translators: %s: The provider name that was attempted to be used for image generation. */
        return new WP_Error('unsupported_image_provider', sprintf(__('Image provider "%s" is not currently supported.', 'gpt3-ai-content-generator'), esc_html($provider_raw)), ['status' => 400]);
    }

    $all_settings = $managerInstance->get_image_settings();
    $provider_module_defaults = $all_settings['defaults'][$provider_normalized] ?? ($all_settings['defaults']['OpenAI'] ?? []);
    $final_options = array_merge($provider_module_defaults, $options);
    $final_options['provider'] = $provider_normalized;

    $api_params = AIPKit_Providers::get_provider_data($provider_normalized);
    if (empty($api_params['api_key'])) {
        /* translators: %s: The provider name that was attempted to be used for image generation. */
        return new WP_Error('missing_api_key', sprintf(__('%s API Key is missing.', 'gpt3-ai-content-generator'), $provider_normalized), ['status' => 400]);
    }
    if ($provider_normalized === 'Azure' && empty($api_params['endpoint'])) {
        return new WP_Error('missing_azure_endpoint', __('Azure Endpoint/Region URL is required for image generation.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    $strategy = AIPKit_Image_Provider_Strategy_Factory::get_strategy($provider_normalized);
    if (is_wp_error($strategy)) {
        return $strategy;
    }

    if (empty($final_options['model']) && $provider_normalized === 'OpenAI') {
        $final_options['model'] = 'dall-e-2';
    } elseif (empty($final_options['model']) && $provider_normalized === 'Google') {
        $final_options['model'] = 'gemini-2.0-flash-preview-image-generation';
    }
    if (empty($final_options['size'])) {
        $final_options['size'] = '1024x1024';
    }
    if (empty($final_options['n'])) {
        $final_options['n'] = 1;
    }

    if (($final_options['model'] ?? '') === 'gpt-image-1') {
        if (isset($final_options['response_format']) && !isset($final_options['output_format'])) {
            if ($final_options['response_format'] === 'b64_json') {
                $final_options['output_format'] = 'png';
            }
            unset($final_options['response_format']);
        }
    }

    $result_from_strategy = $strategy->generate_image($prompt, $api_params, $final_options);

    if (is_wp_error($result_from_strategy)) {
        return $result_from_strategy;
    }

    if ($wp_user_id !== null && class_exists(AIPKit_Image_Storage_Helper::class) && isset($result_from_strategy['images'])) {
        $saved_image_data = [];
        foreach ($result_from_strategy['images'] as $image_item) {
            $attachment_id_or_error = AIPKit_Image_Storage_Helper::save_image_to_media_library(
                $image_item,
                $prompt,
                $final_options,
                $wp_user_id
            );
            if (!is_wp_error($attachment_id_or_error) && $attachment_id_or_error) {
                $image_item['attachment_id'] = $attachment_id_or_error;
                $image_item['media_library_url'] = wp_get_attachment_url($attachment_id_or_error);
            }
            $saved_image_data[] = $image_item;
        }
        $result_from_strategy['images'] = $saved_image_data;
    }

    return $result_from_strategy;
}
