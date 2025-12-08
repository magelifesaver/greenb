<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/ajax/class-aipkit-semantic-search-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Core\Ajax;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\AIPKit_Providers;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests for the public-facing Semantic Search shortcode.
 */
class AIPKit_Semantic_Search_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private $ai_caller;
    private $vector_store_manager;

    public function __construct()
    {
        if (class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new AIPKit_Vector_Store_Manager();
        }
    }

    /**
     * AJAX handler for performing the semantic search.
     */
    public function ajax_perform_semantic_search()
    {
        if (!check_ajax_referer('aipkit_semantic_search_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'gpt3-ai-content-generator')], 403);
            return;
        }

        if (!$this->ai_caller || !$this->vector_store_manager) {
            $this->send_wp_error(new WP_Error('dependency_missing', __('Search service components are not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
        if (empty($query)) {
            wp_send_json_error(['message' => __('Search query cannot be empty.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        $opts = get_option('aipkit_options', []);
        $settings = $opts['semantic_search'] ?? [];

        $vector_provider = $settings['vector_provider'] ?? '';
        $target_id = $settings['target_id'] ?? '';
        $embedding_provider_key = $settings['embedding_provider'] ?? '';
        $embedding_model = $settings['embedding_model'] ?? '';
        $num_results = $settings['num_results'] ?? 5;
        $no_results_text = $settings['no_results_text'] ?? __('No results found.', 'gpt3-ai-content-generator');

        if (empty($vector_provider) || empty($target_id) || empty($embedding_provider_key) || empty($embedding_model)) {
            $this->send_wp_error(new WP_Error('config_missing', __('Semantic Search is not configured correctly in AI Settings.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // --- FIX: Normalize provider name ---
        $vector_provider_normalized = ucfirst(strtolower($vector_provider));
        // --- END FIX ---

        // Generate embedding for the user's query
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider_norm = $provider_map[$embedding_provider_key] ?? ucfirst($embedding_provider_key);

        $embedding_options = ['model' => $embedding_model];
        $embedding_result = $this->ai_caller->generate_embeddings($embedding_provider_norm, $query, $embedding_options);

        if (is_wp_error($embedding_result)) {
            $this->send_wp_error($embedding_result);
            return;
        }
        if (empty($embedding_result['embeddings'][0])) {
            $this->send_wp_error(new WP_Error('embedding_failed', __('Failed to generate vector for the search query.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }
        $query_vector = $embedding_result['embeddings'][0];

        // Query the vector store
        // --- FIX: Use normalized provider name ---
        $vector_store_config = AIPKit_Providers::get_provider_data($vector_provider_normalized);
        $query_vector_param = ['vector' => $query_vector];
        $search_results = $this->vector_store_manager->query_vectors($vector_provider_normalized, $target_id, $query_vector_param, (int)$num_results, [], $vector_store_config);
        // --- END FIX ---

        if (is_wp_error($search_results)) {
            $this->send_wp_error($search_results);
            return;
        }

        // Format results into HTML
        $html = '';
        if (empty($search_results)) {
            $html = '<p class="aipkit-search-no-results">' . esc_html($no_results_text) . '</p>';
        } else {
            foreach ($search_results as $result) {
                $metadata = $result['metadata'] ?? [];
                $title = $metadata['title'] ?? __('Untitled Result', 'gpt3-ai-content-generator');
                $url = $metadata['url'] ?? '#';
                $snippet = $metadata['original_content'] ?? ($metadata['text_content'] ?? __('No snippet available.', 'gpt3-ai-content-generator'));

                $html .= '<div class="aipkit_semantic_search_item">';
                $html .= '  <h3 class="aipkit_search_result_title"><a href="' . esc_url($url) . '" target="_blank">' . esc_html($title) . '</a></h3>';
                $html .= '  <p class="aipkit_search_result_snippet">' . esc_html(wp_trim_words($snippet, 55, '...')) . '</p>';
                $html .= '  <a href="' . esc_url($url) . '" class="aipkit_search_result_link" target="_blank">' . esc_html($url) . '</a>';
                $html .= '</div>';
            }
        }

        wp_send_json_success(['html' => $html]);
    }
}
