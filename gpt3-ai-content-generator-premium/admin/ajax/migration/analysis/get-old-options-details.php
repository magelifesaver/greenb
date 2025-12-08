<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/get-old-options-details.php
// Status: MODIFIED
// I have added old WooCommerce settings options to the list of options to be analyzed and deleted.

namespace WPAICG\Admin\Ajax\Migration\Analysis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gets details about old options and the legacy `wpaicg` table.
 *
 * @return array ['count' => int, 'details' => array]
 */
function get_old_options_details_logic(): array
{
    $count = 0;
    $details = [];
    $old_options = [
        'wpaicg_options', 'wpaicg_provider', 'wpaicg_chat_widget', 'wpaicg_module_settings',
        'wpaicg_version', 'wpaicg_openai_api_key', 'wpaicg_azure_api_key', 'wpaicg_azure_endpoint',
        'wpaicg_azure_deployment', 'wpaicg_google_model_api_key', 'wpaicg_google_default_model',
        'wpaicg_openrouter_api_key', 'wpaicg_openrouter_default_model', 'wpaicg_deepseek_api_key',
        'wpaicg_elevenlabs_api', 'wpaicg_pinecone_api', 'wpaicg_qdrant_api_key', 'wpaicg_qdrant_endpoint',
        'wpaicg_image_setting_provider', 'wpaicg_image_setting_openai_model', 'wpaicg_image_setting_openai_size',
        'wpaicg_image_setting_openai_quality', 'wpaicg_image_setting_openai_style', 'wpaicg_image_setting_openai_n',
        'wpaicg_image_setting_azure_model', 'wpaicg_image_setting_azure_size', 'wpaicg_image_setting_azure_n',
        'wpaicg_image_setting_google_model', 'wpaicg_image_setting_google_size', 'wpaicg_image_setting_google_n',
        'wpaicg_chat_shortcode_options', 'wpaicg_banned_words', 'wpaicg_banned_ips',
        'wpaicg_editor_button_menus', 'wpaicg_editor_change_action',
        'wpaicg_woo_generate_title', 'wpaicg_woo_generate_description', 'wpaicg_woo_generate_short',
        'wpaicg_woo_generate_tags', 'wpaicg_woo_meta_description', '_wpaicg_shorten_woo_url',
        'wpaicg_generate_woo_focus_keyword', 'wpaicg_enforce_woo_keyword_in_url', 'wpaicg_woo_custom_prompt',
        'wpaicg_order_status_token'
    ];
    foreach ($old_options as $option_name) {
        if (get_option($option_name) !== false) {
            $count++;
            /* translators: %s is the option name */
            $details[] = sprintf(__('Found option: %s', 'gpt3-ai-content-generator'), $option_name);
        }
    }

    // Check for the old wpaicg table by passing its name in an array
    $legacy_table_result = table_exists_and_has_rows_logic(['wpaicg']);
    if ($legacy_table_result['count'] > 0) {
        $count += $legacy_table_result['count'];
        $details = array_merge($details, $legacy_table_result['details']);
    }

    return [
        'count' => $count,
        'summary' => sprintf('%d legacy settings found (options and/or database table entries).', $count),
        'details' => $details
    ];
}
