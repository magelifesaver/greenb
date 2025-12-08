<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/stt/class-aipkit-stt-manager.php
// MODIFIED FILE - Pass STT model ID from options to strategy.

namespace WPAICG\STT; // Use new STT namespace

use WP_Error;
use WPAICG\AIPKit_Providers; // Needed for API Keys
use WPAICG\Chat\Storage\BotStorage;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_STT_Manager
 * Main class for handling Speech-to-Text (STT) functionality.
 */
class AIPKit_STT_Manager
{
    private $bot_storage;

    public function __construct()
    {
        // Load dependencies or setup initial state if needed
        // Ensure BotStorage exists and instantiate
        if (!class_exists(\WPAICG\Chat\Storage\BotStorage::class)) {
            return;
        }
        $this->bot_storage = new BotStorage();
    }

    /**
     * Register hooks (e.g., for AJAX actions related to STT).
     */
    public function init_hooks()
    {
        add_action('wp_ajax_aipkit_transcribe_audio', [$this, 'ajax_transcribe_audio']);
        add_action('wp_ajax_nopriv_aipkit_transcribe_audio', [$this, 'ajax_transcribe_audio']);
    }

    /**
     * Transcribes audio data to text using the specified provider.
     *
     * @param string $audio_data Base64 encoded audio data OR raw binary data.
     * @param string $audio_format The format of the audio (e.g., 'wav', 'mp3').
     * @param array $options Optional parameters including 'provider', 'bot_id'.
     *                       May also contain 'stt_openai_model_id', 'stt_azure_model_id'.
     * @return string|WP_Error Transcribed text string or WP_Error on failure.
     */
    public function speech_to_text(string $audio_data, string $audio_format, array $options = []): string|WP_Error
    {
        // 1. Determine Provider (from options or bot setting or global setting)
        $stt_provider = null;
        $bot_settings = [];
        if (!empty($options['bot_id'])) {
            $bot_settings = $this->bot_storage->get_chatbot_settings(absint($options['bot_id']));
            $stt_provider = $bot_settings['stt_provider'] ?? null;
            // --- Pass provider-specific model IDs to options ---
            if (($stt_provider === 'OpenAI' || $options['provider'] === 'OpenAI') && isset($bot_settings['stt_openai_model_id'])) {
                $options['stt_model'] = $bot_settings['stt_openai_model_id'];
            }
            if (($stt_provider === 'Azure' || $options['provider'] === 'Azure') && isset($bot_settings['stt_azure_model_id'])) {
                $options['stt_model'] = $bot_settings['stt_azure_model_id']; // Pass Azure model/deployment ID
            }
            // --- END ---
        }
        // If not set in bot settings, fall back to option or global default
        $provider = $stt_provider ?: ($options['provider'] ?? AIPKit_Providers::get_current_provider());

        // Validate we have a provider recognized by STT factory
        $valid_stt_providers = ['OpenAI', 'Azure']; // Add Azure
        if (!in_array($provider, $valid_stt_providers)) {
            $provider = 'OpenAI'; // Fallback to OpenAI if invalid provider selected for STT
        }

        // 2. Get Provider Strategy
        $strategy = AIPKit_STT_Provider_Strategy_Factory::get_strategy($provider);
        if (is_wp_error($strategy)) {
            return $strategy;
        }

        // 3. Get API credentials for the *selected STT provider*
        $provider_data = AIPKit_Providers::get_provider_data($provider);
        $api_params = [
            'api_key' => $provider_data['api_key'] ?? null,
            'base_url' => $provider_data['base_url'] ?? null, // Pass base URL
            // Add other provider-specific params like region, endpoint if needed
            'azure_endpoint' => $provider_data['endpoint'] ?? null,
             // Pass model ID from options (set earlier based on bot settings)
             'stt_model' => $options['stt_model'] ?? null
        ];
        if (empty($api_params['api_key'])) {
            /* translators: %s is the STT provider name */
            return new WP_Error('missing_stt_api_key', sprintf(__('API Key for STT provider %s is missing.', 'gpt3-ai-content-generator'), $provider));
        }
        // Azure specific check
        if ($provider === 'Azure' && empty($api_params['azure_endpoint'])) {
            return new WP_Error('missing_stt_endpoint', __('Azure Endpoint/Region URL is required for STT.', 'gpt3-ai-content-generator'));
        }


        // 4. Validate format support
        $supported_formats = $strategy->get_supported_formats();
        if (!in_array(strtolower($audio_format), $supported_formats)) {
            /* translators: %1$s is the audio format, %2$s is the provider name */
            return new WP_Error('unsupported_stt_format', sprintf(__('Audio format "%1$s" is not supported by %2$s STT.', 'gpt3-ai-content-generator'), $audio_format, $provider));
        }

        // 5. Call strategy's transcribe method, passing $options which now includes the model ID if applicable
        // The strategy implementation will use the relevant keys from $api_params and $options
        $result = $strategy->transcribe_audio($audio_data, $audio_format, $api_params, $options);

        return $result;
    }

    /**
     * AJAX handler for transcription requests from the frontend.
     */
    public function ajax_transcribe_audio()
    {
        // Use frontend nonce check as this is called from chat UI
        if (!check_ajax_referer('aipkit_frontend_chat_nonce', '_ajax_nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed (nonce).', 'gpt3-ai-content-generator')], 403);
            return;
        }
        $bot_id = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0; // Get bot ID to read its STT provider setting
        if (empty($bot_id)) {
            wp_send_json_error(['message' => __('Bot ID is required for transcription.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Prepare options early
        $options = ['bot_id' => $bot_id];
        if (isset($_POST['language'])) {
            $options['language'] = sanitize_text_field($_POST['language']);
        }

        $audio_data_binary = '';
        $audio_format = 'webm'; // default fallback

        // 1. Preferred path: multipart file upload (avoids large base64 payloads that WAFs like WordFence can flag)
        if (!empty($_FILES['audio_file']) && is_uploaded_file($_FILES['audio_file']['tmp_name'])) {
            $tmp_name = $_FILES['audio_file']['tmp_name'];
            $file_size = (int) ($_FILES['audio_file']['size'] ?? 0);
            $original_name = sanitize_file_name($_FILES['audio_file']['name'] ?? 'audio');

            // Allow filtering max size; default 4MB
            $max_size = (int) apply_filters('aipkit_stt_max_audio_bytes', 4 * 1024 * 1024);
            if ($file_size <= 0) {
                wp_send_json_error(['message' => __('Uploaded audio file is empty.', 'gpt3-ai-content-generator')], 400);
                return;
            }
            if ($file_size > $max_size) {
                wp_send_json_error(['message' => sprintf(__('Audio file too large. Max size: %d bytes.', 'gpt3-ai-content-generator'), $max_size)], 413);
                return;
            }

            // MIME detection
            $mime = function_exists('mime_content_type') ? mime_content_type($tmp_name) : ($_FILES['audio_file']['type'] ?? '');
            $allowed_mime_map = [
                'audio/webm' => 'webm',
                'audio/wav' => 'wav',
                'audio/x-wav' => 'wav',
                'audio/mpeg' => 'mp3',
                'audio/mp3' => 'mp3',
                'audio/ogg' => 'ogg',
                'audio/ogg; codecs=opus' => 'ogg',
            ];
            if (isset($allowed_mime_map[$mime])) {
                $audio_format = $allowed_mime_map[$mime];
            } else {
                // Fallback: attempt extension parse
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if (in_array($ext, ['webm', 'wav', 'mp3', 'ogg'])) {
                    $audio_format = $ext;
                } else {
                    wp_send_json_error(['message' => __('Unsupported audio MIME type.', 'gpt3-ai-content-generator')], 400);
                    return;
                }
            }

            $audio_data_binary = file_get_contents($tmp_name);
            if ($audio_data_binary === false || $audio_data_binary === '') {
                wp_send_json_error(['message' => __('Failed to read uploaded audio file.', 'gpt3-ai-content-generator')], 400);
                return;
            }
        }
        // 2. Legacy path: base64 data (kept for backward compatibility with older frontends)
        else {
            $audio_base64 = isset($_POST['audio_data']) ? $_POST['audio_data'] : '';
            if (strpos($audio_base64, 'base64,') !== false) {
                $audio_base64 = substr($audio_base64, strpos($audio_base64, 'base64,') + 7);
            }
            $audio_data_binary = base64_decode($audio_base64, true); // strict mode
            $audio_format = isset($_POST['audio_format']) ? sanitize_text_field($_POST['audio_format']) : 'webm';
            if (empty($audio_data_binary)) {
                wp_send_json_error(['message' => __('Invalid or empty audio data received.', 'gpt3-ai-content-generator')], 400);
                return;
            }
            // Enforce size check on decoded data too
            $max_size = (int) apply_filters('aipkit_stt_max_audio_bytes', 4 * 1024 * 1024);
            if (strlen($audio_data_binary) > $max_size) {
                wp_send_json_error(['message' => sprintf(__('Audio data too large after decoding. Max size: %d bytes.', 'gpt3-ai-content-generator'), $max_size)], 413);
                return;
            }
        }

        // Call the main STT method
        $transcription_result = $this->speech_to_text($audio_data_binary, $audio_format, $options);

        if (is_wp_error($transcription_result)) {
            $error_data = $transcription_result->get_error_data();
            $status_code = isset($error_data['status']) ? (int)$error_data['status'] : 500;
            wp_send_json_error(['message' => $transcription_result->get_error_message()], $status_code);
        } else {
            wp_send_json_success(['transcription' => $transcription_result]);
        }
    }
}