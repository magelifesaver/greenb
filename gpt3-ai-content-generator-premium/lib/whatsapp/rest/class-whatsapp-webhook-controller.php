<?php

namespace WPAICG\Lib\WhatsApp\Rest;

use WP_REST_Request;
use WPAICG\Lib\WhatsApp\Core\WhatsApp_Processor;

if (!defined('ABSPATH')) {
    exit;
}

class WhatsApp_Webhook_Controller
{
    public static function register_routes(): void
    {
        register_rest_route('aipkit/v1', '/webhooks/whatsapp', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [__CLASS__, 'handle_verify'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [__CLASS__, 'handle_incoming'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    private static function get_connectors(): array
    {
        $opts = get_option('aipkit_whatsapp_connectors', []);
        return is_array($opts) ? $opts : [];
    }

    public static function handle_verify(WP_REST_Request $request)
    {
        // Support both underscore and dot forms used by Meta (hub.mode, hub.verify_token, hub.challenge)
        $hub_mode = $request->get_param('hub_mode');
        if ($hub_mode === null) { $hub_mode = $request->get_param('hub.mode'); }
        $verify_token = $request->get_param('hub_verify_token');
        if ($verify_token === null) { $verify_token = $request->get_param('hub.verify_token'); }
        $challenge = $request->get_param('hub_challenge');
        if ($challenge === null) { $challenge = $request->get_param('hub.challenge'); }

        if (strtolower((string)$hub_mode) !== 'subscribe' || empty($verify_token)) {
            return new \WP_REST_Response('Invalid', 400);
        }

        // Accept verification if any connector verify token matches
        $connectors = self::get_connectors();
        foreach ($connectors as $conn) {
            if (!empty($conn['verify_token']) && hash_equals((string)trim($conn['verify_token']), (string)trim($verify_token))) {
                $response = new \WP_REST_Response($challenge, 200);
                // Respond as plain text per Meta's expected verification response
                $response->header('Content-Type', 'text/plain; charset=utf-8');
                return $response;
            }
        }
        return new \WP_REST_Response('Forbidden', 403);
    }

    private static function verify_signature(WP_REST_Request $request, array $connector): bool
    {
        $secret = $connector['app_secret'] ?? '';
        if (empty($secret)) {
            return false;
        }
        $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        if (!is_string($sig) || stripos($sig, 'sha256=') !== 0) {
            return false;
        }
        $provided = substr($sig, 7);
        $raw = $request->get_body();
        $calc = hash_hmac('sha256', $raw, $secret);
        return hash_equals($provided, $calc);
    }

    public static function handle_incoming(WP_REST_Request $request)
    {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return new \WP_REST_Response('Bad Request', 400);
        }

        $connectors = self::get_connectors();
        if (empty($connectors)) {
            return new \WP_REST_Response('No connectors', 200);
        }

        $entries = $body['entry'] ?? [];
        $processor = new WhatsApp_Processor();

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $phone_number_id = $value['metadata']['phone_number_id'] ?? '';
                $messages = $value['messages'] ?? [];
                if (empty($phone_number_id) || empty($messages)) {
                    continue;
                }
                // Find connector by phone_number_id
                $connector = null;
                foreach ($connectors as $conn) {
                    if (!empty($conn['phone_number_id']) && (string)$conn['phone_number_id'] === (string)$phone_number_id) {
                        $connector = $conn;
                        break;
                    }
                }
                if (!$connector) {
                    continue; // Unknown number
                }

                // Verify signature per change (once per request would suffice)
                if (!self::verify_signature($request, $connector)) {
                    continue;
                }

                foreach ($messages as $msg) {
                    $from_wa_id = $msg['from'] ?? '';
                    if (!$from_wa_id) {
                        continue;
                    }
                    $processor->process_incoming($connector, $phone_number_id, $from_wa_id, $msg);
                }
            }
        }
        // Always 200 to acknowledge receipt
        return new \WP_REST_Response('OK', 200);
    }
}
