<?php

namespace WPAICG\Lib\Utils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles sanitizing and processing Google Service Account credentials.
 */
class AIPKit_Google_Credentials_Handler
{
    /**
     * Processes raw Google Sheets credentials from user input into a consistent array format.
     * It handles cases where input is a full JSON string, just the private key, or already an array.
     *
     * @param mixed $creds The raw credentials input from a form.
     * @return array|null The processed credentials as an associative array, or null if input is invalid.
     */
    public static function process_credentials($creds): ?array
    {
        if (empty($creds)) {
            return null;
        }

        if (is_string($creds)) {
            $decoded_creds = json_decode($creds, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_creds)) {
                // It was a valid JSON string, return the decoded array
                return $decoded_creds;
            } elseif (strpos($creds, '-----BEGIN PRIVATE KEY-----') !== false) {
                // This case is unlikely to be valid as it's missing other critical fields
                // like client_email, but we can reconstruct a partial object to preserve the key.
                return [
                    'type' => 'service_account',
                    'project_id' => '',
                    'private_key_id' => '',
                    'private_key' => $creds,
                    'client_email' => '',
                    'client_id' => '',
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => ''
                ];
            } else {
                // Invalid string format
                return null;
            }
        } elseif (is_array($creds)) {
            // Already an array, return it
            return $creds;
        }

        return null;
    }
}
