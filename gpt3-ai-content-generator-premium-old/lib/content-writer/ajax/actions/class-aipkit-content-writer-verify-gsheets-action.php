<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/content-writer/ajax/actions/class-aipkit-content-writer-verify-gsheets-action.php
// Status: MODIFIED

namespace WPAICG\Lib\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class AIPKit_Content_Writer_Verify_Gsheets_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    public function handle()
    {
        // --- MODIFICATION: Manual permission and flexible nonce check ---
        if (!\WPAICG\AIPKit_Role_Manager::user_can_access_module('content-writer') && !\WPAICG\AIPKit_Role_Manager::user_can_access_module('autogpt')) {
            $this->send_wp_error(new WP_Error('permission_denied', __('You do not have permission to use this feature.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        $nonce = isset($_POST['_ajax_nonce']) ? sanitize_key(wp_unslash($_POST['_ajax_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'aipkit_content_writer_nonce') && !wp_verify_nonce($nonce, 'aipkit_nonce') && !wp_verify_nonce($nonce, 'aipkit_automated_tasks_manage_nonce')) {
            $this->send_wp_error(new WP_Error('nonce_failure', __('Security check failed.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        // --- END MODIFICATION ---

        $sheet_id = isset($_POST['gsheets_sheet_id']) ? sanitize_text_field(wp_unslash($_POST['gsheets_sheet_id'])) : '';

        // --- START FIX for PHPCS warnings ---
        // Sanitize the raw JSON string using wp_kses_post. This is a safe way to handle
        // potentially complex string data that needs to be parsed, as it strips harmful HTML/script tags
        // while preserving the essential JSON structure.
        $credentials_json_raw = isset($_POST['gsheets_credentials']) ? wp_kses_post(wp_unslash($_POST['gsheets_credentials'])) : '';
        if (empty($sheet_id) || empty($credentials_json_raw)) {
            $this->send_wp_error(new WP_Error('missing_gsheets_info', __('Google Sheet ID and Credentials are required.', 'gpt3-ai-content-generator')), 400);
            return;
        }
        $credentials_array = json_decode($credentials_json_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($credentials_array)) {
            $this->send_wp_error(new WP_Error('invalid_json_format', __('The provided credentials are not in a valid JSON format.', 'gpt3-ai-content-generator')), 400);
            return;
        }
        // --- END FIX ---

        if (!class_exists('\WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser')) {
            $this->send_wp_error(new WP_Error('gsheets_parser_missing', __('Google Sheets parser component is missing.', 'gpt3-ai-content-generator')), 500);
            return;
        }

        try {
            // The parser now expects an array. Pass the validated array from above.
            $sheets_parser = new AIPKit_Google_Sheets_Parser($credentials_array);

            // Use the new, efficient verification method.
            $result = $sheets_parser->verify_access($sheet_id);

            if (is_wp_error($result)) {
                $this->send_wp_error($result);
            } else {
                wp_send_json_success(['message' => __('Connection successful.', 'gpt3-ai-content-generator')]);
            }
        } catch (\Exception $e) {
            $this->send_wp_error(new WP_Error('gsheets_verification_failed', $e->getMessage()), 400);
        }
    }
}
