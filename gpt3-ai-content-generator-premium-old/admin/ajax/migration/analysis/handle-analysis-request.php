<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/handle-analysis-request.php
// Status: MODIFIED
// I have updated this file to include the new woocommerce prompts analysis step.

namespace WPAICG\Admin\Ajax\Migration\Analysis;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\WP_AI_Content_Generator_Activator;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

// Load other analysis files
require_once __DIR__ . '/get-old-options-details.php';
require_once __DIR__ . '/count-cpt-posts.php';
require_once __DIR__ . '/table-exists-and-has-rows.php';
require_once __DIR__ . '/get-old-custom-prompts.php';
require_once __DIR__ . '/get-old-integration-data.php';
require_once __DIR__ . '/get-old-woocommerce-prompts.php';

/**
 * Main logic function for handling the data analysis request.
 * Called by AIPKit_Analyze_Old_Data_Action.
 *
 * @param AIPKit_Migration_Base_Ajax_Action $handlerInstance The instance of the action class.
 * @return void Sends JSON response.
 */
function handle_analysis_request_logic(AIPKit_Migration_Base_Ajax_Action $handlerInstance): void
{
    $permission_check = $handlerInstance->check_module_access_permissions('settings', $handlerInstance::MIGRATION_NONCE_ACTION);
    if (is_wp_error($permission_check)) {
        $handlerInstance->send_wp_error($permission_check);
        return;
    }

    $analysis_results = [];

    try {
        // --- 1. Global, Provider & API Settings ---
        $analysis_results['global_settings'] = get_old_options_details_logic();

        // --- 2. AI Forms & Other Data ---
        // This category now covers legacy CPTs and associated tables.
        // Migration will only handle AI Forms; deletion will handle everything else.
        $cpt_data_cpts = ['wpaicg_mtemplate', 'wpaicg_file', 'wpaicg_finetune', 'wpaicg_form'];
        $cpt_data_posts_result = count_cpt_posts_logic($cpt_data_cpts);
        // Includes form tables and old image log tables for cleanup
        $cpt_data_tables = ['wpaicg_form_logs', 'wpaicg_form_feedback', 'wpaicg_formtokens', 'wpaicg_image_logs', 'wpaicg_imagetokens'];
        $cpt_data_tables_result = table_exists_and_has_rows_logic($cpt_data_tables);
        $total_cpt_data_count = $cpt_data_posts_result['count'] + $cpt_data_tables_result['count'];
        $analysis_results['cpt_data'] = [
            'count' => $total_cpt_data_count,
            'summary' => sprintf(
                '%s, %s',
                $cpt_data_posts_result['summary'],
                $cpt_data_tables_result['summary']
            ),
            'details' => array_merge($cpt_data_posts_result['details'], $cpt_data_tables_result['details'])
        ];

        // --- 3. Chatbots ---
        $chatbot_cpt_result = count_cpt_posts_logic(['wpaicg_chatbot']);
        $chatbot_tables_result = table_exists_and_has_rows_logic(['wpaicg_chatlogs', 'wpaicg_chattokens']);
        $total_chatbot_count = $chatbot_cpt_result['count'] + $chatbot_tables_result['count'];
        $analysis_results['chatbot_data'] = [
            'count' => $total_chatbot_count,
            'summary' => sprintf(
                '%s, %s',
                $chatbot_cpt_result['summary'],
                $chatbot_tables_result['summary']
            ),
            'details' => array_merge($chatbot_cpt_result['details'], $chatbot_tables_result['details'])
        ];

        // --- 4. Indexed Data (Knowledge Base) ---
        $indexed_data_cpts = ['wpaicg_embeddings', 'wpaicg_pdfadmin', 'wpaicg_builder'];
        $indexed_data_result = count_cpt_posts_logic($indexed_data_cpts);
        $analysis_results['indexed_data'] = [
            'count' => $indexed_data_result['count'],
            'summary' => $indexed_data_result['summary'],
            'details' => $indexed_data_result['details']
        ];

        // --- 5. Custom Prompts ---
        $analysis_results['custom_prompts'] = get_old_custom_prompts_logic();

        // --- 6. Integration Data ---
        $analysis_results['integration_data'] = get_old_integration_data_logic();

        // --- 7. WooCommerce Custom Prompts ---
        $analysis_results['woocommerce_prompts'] = get_old_woocommerce_prompts_logic();


        // Update status and save results
        update_option(WP_AI_Content_Generator_Activator::MIGRATION_ANALYSIS_RESULTS_OPTION, $analysis_results, 'no');
        update_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION, 'analysis_complete', 'no');

        wp_send_json_success([
            'message' => __('Analysis complete.', 'gpt3-ai-content-generator'),
            'analysis_data' => $analysis_results,
        ]);

    } catch (\Exception $e) {
        $error_message = 'Analysis failed: ' . $e->getMessage();
        update_option(WP_AI_Content_Generator_Activator::MIGRATION_LAST_ERROR_OPTION, $error_message, 'no');
        $handlerInstance->send_wp_error(new WP_Error('analysis_exception', $error_message, ['status' => 500]));
    }
}
