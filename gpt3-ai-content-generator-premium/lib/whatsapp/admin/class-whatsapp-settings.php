<?php

namespace WPAICG\Lib\WhatsApp\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class WhatsApp_Settings
{
    public const OPTION_KEY = 'aipkit_whatsapp_connectors';
    public const NONCE_ACTION = 'aipkit_whatsapp_connectors_nonce_action';
    public const NONCE_FIELD = 'aipkit_whatsapp_connectors_nonce_field';

    public static function register_admin_hooks(): void
    {
        add_action('wp_ajax_aipkit_whatsapp_get_connectors', [__CLASS__, 'ajax_get_connectors']);
        add_action('wp_ajax_aipkit_whatsapp_save_connectors', [__CLASS__, 'ajax_save_connectors']);
        add_action('wp_ajax_aipkit_whatsapp_test_send', [__CLASS__, 'ajax_test_send']);
    }

    public static function ajax_get_connectors(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'gpt3-ai-content-generator')], 403);
        }
        $connectors = get_option(self::OPTION_KEY, []);
        if (!is_array($connectors)) {
            $connectors = [];
        }
        wp_send_json_success(['connectors' => $connectors]);
    }

    public static function ajax_save_connectors(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'gpt3-ai-content-generator')], 403);
        }
        check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');

        $raw = isset($_POST['connectors']) ? wp_unslash($_POST['connectors']) : '';
        $list = json_decode((string)$raw, true);
        if (!is_array($list)) {
            wp_send_json_error(['message' => __('Invalid connectors payload.', 'gpt3-ai-content-generator')], 400);
        }
        $sanitized = [];
        $used_ids = [];
        foreach ($list as $item) {
            if (!is_array($item)) { continue; }
            $label = sanitize_text_field($item['label'] ?? '');
            if ($label === '') { continue; } // Label is required

            // Preserve existing id if provided; otherwise generate one
            $provided_id = isset($item['id']) ? sanitize_key($item['id']) : '';
            $id = $provided_id;
            if ($id === '') {
                $base = sanitize_key(sanitize_title($label));
                if ($base === '') { $base = 'wa'; }
                $candidate = $base;
                $suffix = 1;
                while (in_array($candidate, $used_ids, true)) {
                    $candidate = $base . '_' . $suffix;
                    $suffix++;
                }
                $id = $candidate;
            }
            // Ensure uniqueness within this payload
            if (in_array($id, $used_ids, true)) {
                $base = $id;
                $suffix = 1;
                while (in_array($base . '_' . $suffix, $used_ids, true)) {
                    $suffix++;
                }
                $id = $base . '_' . $suffix;
            }
            $used_ids[] = $id;

            $sanitized[] = [
                'id'              => $id,
                'label'           => $label,
                'phone_number_id' => sanitize_text_field($item['phone_number_id'] ?? ''),
                'access_token'    => trim((string)($item['access_token'] ?? '')),
                'app_secret'      => trim((string)($item['app_secret'] ?? '')),
                'verify_token'    => trim((string)($item['verify_token'] ?? '')),
                'api_version'     => sanitize_text_field($item['api_version'] ?? ''),
            ];
        }
        update_option(self::OPTION_KEY, $sanitized, 'no');
        wp_send_json_success(['message' => __('Saved.', 'gpt3-ai-content-generator'), 'connectors' => $sanitized]);
    }

    public static function ajax_test_send(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'gpt3-ai-content-generator')], 403);
        }
        check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');

        $to = isset($_POST['to']) ? sanitize_text_field(wp_unslash($_POST['to'])) : '';
        $message = isset($_POST['message']) ? wp_kses_post(wp_unslash($_POST['message'])) : '';
        if (empty($to) || empty($message)) {
            wp_send_json_error(['message' => __('Recipient and message are required.', 'gpt3-ai-content-generator')], 400);
        }

        // Connector can be provided as JSON (current form state) or by id to use saved one
        $connector_json = isset($_POST['connector']) ? wp_unslash($_POST['connector']) : '';
        $connector = null;
        if (!empty($connector_json)) {
            $decoded = json_decode($connector_json, true);
            if (is_array($decoded)) { $connector = $decoded; }
        } else {
            $connector_id = isset($_POST['connector_id']) ? sanitize_key(wp_unslash($_POST['connector_id'])) : '';
            if ($connector_id) {
                $connectors = get_option(self::OPTION_KEY, []);
                if (is_array($connectors)) {
                    foreach ($connectors as $c) {
                        if (!empty($c['id']) && (string)$c['id'] === $connector_id) { $connector = $c; break; }
                    }
                }
            }
        }

        if (!is_array($connector)) {
            wp_send_json_error(['message' => __('Connector not found or invalid.', 'gpt3-ai-content-generator')], 400);
        }

        $phone_number_id = sanitize_text_field($connector['phone_number_id'] ?? '');
        $access_token = trim((string)($connector['access_token'] ?? ''));
        $api_version = sanitize_text_field($connector['api_version'] ?? 'v20.0');
        if (empty($phone_number_id) || empty($access_token)) {
            wp_send_json_error(['message' => __('Connector is missing phone number ID or access token.', 'gpt3-ai-content-generator')], 400);
        }

        // Use the client to send a simple text
        if (!class_exists('\\WPAICG\\Lib\\WhatsApp\\Core\\WhatsApp_Client')) {
            $client_path = WPAICG_LIB_DIR . 'whatsapp/core/class-whatsapp-client.php';
            if (file_exists($client_path)) { require_once $client_path; }
        }
        if (!class_exists('\\WPAICG\\Lib\\WhatsApp\\Core\\WhatsApp_Client')) {
            wp_send_json_error(['message' => __('WhatsApp client not available.', 'gpt3-ai-content-generator')], 500);
        }

        $client = new \WPAICG\Lib\WhatsApp\Core\WhatsApp_Client();
        $result = $client->send_text($api_version, $phone_number_id, $access_token, $to, $message);
        if (!empty($result['success'])) {
            wp_send_json_success(['message' => __('Test message sent.', 'gpt3-ai-content-generator'), 'data' => $result['data'] ?? []]);
        }
        $err = isset($result['data']['error']['message']) ? $result['data']['error']['message'] : ($result['error'] ?? __('Unknown error', 'gpt3-ai-content-generator'));
        wp_send_json_error(['message' => sprintf(__('Send failed: %s', 'gpt3-ai-content-generator'), $err)], 500);
    }
}
