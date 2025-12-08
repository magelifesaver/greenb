<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/ajax/actions/bulk-process-single-field.php
// Status: MODIFIED
// I have added a conditional check to ensure the `reasoning_effort` parameter is only added for compatible OpenAI models (gpt-5, o-series).

namespace WPAICG\PostEnhancer\Ajax\Actions;

use WPAICG\PostEnhancer\Ajax\Base\AIPKit_Post_Enhancer_Base_Ajax_Action;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\SEO\AIPKit_SEO_Helper;
use WP_Error;
// --- Dependencies for vector context ---
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\Stream\Vector as VectorContextBuilder;

use function WPAICG\PostEnhancer\Ajax\Base\get_post_full_content;
use function WPAICG\PostEnhancer\Ajax\Base\log_enhancer_bulk_update_logic;

if (!defined('ABSPATH')) {
    exit;
}

// --- Dependency loader for vector context functions ---
$vector_logic_base_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/vector/';
if (file_exists($vector_logic_base_path . 'fn-build-vector-search-context.php')) {
    require_once $vector_logic_base_path . 'fn-build-vector-search-context.php';
}

/**
 * AJAX handler for processing a single field of a post during bulk enhancement.
 * 
 * DEBUG LOGGING:
 * To enable detailed API request/response logging for single field processing:
 * 1. Add this line to your wp-config.php: define('WP_DEBUG', true);
 * 2. Add this line to your wp-config.php: define('WP_DEBUG_LOG', true);
 * 3. Run the bulk enhancer and check the debug.log file in /wp-content/debug.log
 * 
 * The logs will include:
 * - Field processing start with prompt details
 * - API request details for the specific field
 * - Raw API responses from the AI provider
 * - Success/error messages for field updates
 */
class AIPKit_PostEnhancer_Bulk_Process_Single_Field extends AIPKit_Post_Enhancer_Base_Ajax_Action
{
    public function handle(): void
    {
        $permission_check = $this->check_permissions('aipkit_generate_title_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_error_response($permission_check);
            return;
        }

        $post = $this->get_post();
        if (is_wp_error($post)) {
            $this->send_error_response($post);
            return;
        }

    // Optional: a conversation UUID to aggregate all steps into one log record
    // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Nonce is checked in check_permissions.
    $conv_uuid = isset($_POST['conversation_uuid']) ? sanitize_key(wp_unslash($_POST['conversation_uuid'])) : null;

        // Get the specific field to process
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Nonce is checked in check_permissions.
        $field = isset($_POST['field']) ? sanitize_text_field(wp_unslash($_POST['field'])) : '';
        if (empty($field)) {
            $this->send_error_response(new WP_Error('missing_field', __('No field specified for processing.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Nonce is checked in check_permissions.
        $item_config_json = isset($_POST['enhancements']) ? wp_unslash($_POST['enhancements']) : '{}';
        $item_config = json_decode($item_config_json, true);

        if (empty($item_config) || !is_array($item_config) || empty($item_config[$field]['prompt'])) {
            $this->send_error_response(new WP_Error('invalid_field_config', __('Invalid configuration for the specified field.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        // AI setup
        $ai_caller = new AIPKit_AI_Caller();
        $global_config = AIPKit_Providers::get_default_provider_config();
        $global_ai_params = AIPKIT_AI_Settings::get_ai_parameters();

        // Use AI config from the request, with fallback to globals
        $provider_raw = $item_config['ai_provider'] ?? $global_config['provider'];
        $provider = match(strtolower($provider_raw)) {
            'openai' => 'OpenAI', 'openrouter' => 'OpenRouter', 'google' => 'Google', 'azure' => 'Azure', 'deepseek' => 'DeepSeek', 'ollama' => 'Ollama',
            default => ucfirst(strtolower($provider_raw))
        };
        $model = $item_config['ai_model'] ?? $global_config['model'];
        $ai_params = [
            'temperature' => isset($item_config['temperature']) ? floatval($item_config['temperature']) : ($global_ai_params['temperature'] ?? 1.0),
            'max_completion_tokens' => isset($item_config['max_tokens']) ? absint($item_config['max_tokens']) : ($global_ai_params['max_completion_tokens'] ?? 4000),
        ];
        // --- NEW: Add reasoning effort to AI params ---
        if ($provider === 'OpenAI' && isset($item_config['reasoning_effort']) && !empty($item_config['reasoning_effort'])) {
            $model_lower = strtolower($model);
            if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
                $ai_params['reasoning'] = ['effort' => sanitize_key($item_config['reasoning_effort'])];
            }
        }
        // --- END NEW ---

        // Extract Vector Store Settings
        $vector_store_enabled = ($item_config['enable_vector_store'] ?? '0') === '1';
        $vector_store_provider = $item_config['vector_store_provider'] ?? null;
        $vector_store_top_k = isset($item_config['vector_store_top_k']) ? absint($item_config['vector_store_top_k']) : 3;
        $openai_vector_store_ids = $item_config['openai_vector_store_ids'] ?? [];
        $pinecone_index_name = $item_config['pinecone_index_name'] ?? null;
        $qdrant_collection_name = $item_config['qdrant_collection_name'] ?? null;
        $vector_embedding_provider = $item_config['vector_embedding_provider'] ?? null;
        $vector_embedding_model = $item_config['vector_embedding_model'] ?? null;

        // Prepare OpenAI vector tools parameter if needed
        if ($vector_store_enabled && $provider === 'OpenAI' && $vector_store_provider === 'openai' && !empty($openai_vector_store_ids)) {
            $ai_params['vector_store_tool_config'] = [
                'type'             => 'file_search',
                'vector_store_ids' => $openai_vector_store_ids,
                'max_num_results'  => $vector_store_top_k,
            ];
        }

        $system_instruction = 'You are an expert SEO copywriter. You follow instructions precisely. Your response must contain ONLY the generated text, with no introductory phrases, labels, or quotation marks.';

        // Gather placeholders
        $original_meta = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: (get_post_meta($post->ID, '_aioseo_description', true) ?: '');
        $original_focus_keyword = AIPKit_SEO_Helper::get_focus_keyword($post->ID);
        $original_tags = AIPKit_SEO_Helper::get_tags_as_string($post->ID);
        $categories = AIPKit_SEO_Helper::get_categories_as_string($post->ID);
        $placeholders = [
            '{original_title}' => $post->post_title,
            '{original_content}' => get_post_full_content($post),
            '{original_excerpt}' => $post->post_excerpt,
            '{original_meta_description}' => $original_meta,
            '{original_focus_keyword}' => $original_focus_keyword ?: '',
            '{original_tags}' => $original_tags,
            '{categories}' => $categories,
        ];

        // Add WooCommerce placeholders if applicable
        if ($post->post_type === 'product' && class_exists('WooCommerce')) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $placeholders['{price}'] = $product->get_price();
                $placeholders['{regular_price}'] = $product->get_regular_price();
                $placeholders['{sale_price}'] = $product->get_sale_price();
                $placeholders['{sku}'] = $product->get_sku();
                $placeholders['{stock_quantity}'] = $product->get_stock_quantity() ?? 'N/A';
                $placeholders['{stock_status}'] = $product->get_stock_status();
                $placeholders['{weight}'] = $product->get_weight();
                $placeholders['{length}'] = $product->get_length();
                $placeholders['{width}'] = $product->get_width();
                $placeholders['{height}'] = $product->get_height();
                $placeholders['{short_description}'] = wp_strip_all_tags($product->get_short_description());
                $placeholders['{purchase_note}'] = $product->get_purchase_note();
                
                $category_terms = get_the_terms($post->ID, 'product_cat');
                if (!is_wp_error($category_terms) && !empty($category_terms)) {
                    $category_names = wp_list_pluck($category_terms, 'name');
                    $placeholders['{product_categories}'] = implode(', ', $category_names);
                } else {
                    $placeholders['{product_categories}'] = '';
                }

                $attributes = $product->get_attributes();
                $attribute_string = '';
                foreach ($attributes as $attribute) {
                    if ($attribute->is_taxonomy()) {
                        $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                        if (!is_wp_error($terms) && !empty($terms)) {
                            $attribute_string .= wc_attribute_label($attribute->get_name()) . ': ' . implode(', ', $terms) . '; ';
                        }
                    } else {
                        $attribute_string .= wc_attribute_label($attribute->get_name()) . ': ' . implode(', ', $attribute->get_options()) . '; ';
                    }
                }
                $placeholders['{attributes}'] = rtrim($attribute_string, '; ');
            }
        }

        // Vector Context Logic
        $vector_context = '';
        $vector_store_manager = null;
        if ($vector_store_enabled) {
            if (class_exists(AIPKit_Vector_Store_Manager::class)) {
                $vector_store_manager = new AIPKit_Vector_Store_Manager();
            }

            if ($vector_store_manager && function_exists('\WPAICG\Core\Stream\Vector\build_vector_search_context_logic')) {
                $vector_context = VectorContextBuilder\build_vector_search_context_logic(
                    $ai_caller,
                    $vector_store_manager,
                    $post->post_title, // Use post title as the query
                    $item_config,      // Pass the whole config as it contains all vector settings
                    $provider,         // Main AI provider
                    null,
                    null,
                    null,
                    null,
                    null // No frontend context in bulk enhancer
                );
            }
        }
        if (!empty($vector_context)) {
            $system_instruction = "## Relevant information from knowledge base:\n" . trim($vector_context) . "\n##\n\n" . $system_instruction;
        }

        // Process the specific field
        $prompt = str_replace(array_keys($placeholders), array_values($placeholders), $item_config[$field]['prompt']);
        
    $ai_result = $ai_caller->make_standard_call($provider, $model, [['role' => 'user', 'content' => $prompt]], $ai_params, $system_instruction, ['post_id' => $post->ID]);

        if (is_wp_error($ai_result)) {
            // Log error to Admin Logs as well
            $error_message = 'Error: ' . $ai_result->get_error_message();
            $error_data = $ai_result->get_error_data() ?? [];
            $request_payload_log = $error_data['request_payload'] ?? null;
            log_enhancer_bulk_update_logic(
                (int) $post->ID,
                $field,
                $prompt,
                $error_message,
                $provider,
                $model,
                null,
                is_array($request_payload_log) ? $request_payload_log : null,
                $conv_uuid
            );
            $this->send_error_response($ai_result);
            return;
        }

        if (empty($ai_result['content'])) {
            // Log empty response as an error entry
            $request_payload_log = $ai_result['request_payload_log'] ?? null;
            log_enhancer_bulk_update_logic(
                (int) $post->ID,
                $field,
                $prompt,
                'Error: AI returned empty response for this field.',
                $provider,
                $model,
                $ai_result['usage'] ?? null,
                is_array($request_payload_log) ? $request_payload_log : null,
                $conv_uuid
            );
            $this->send_error_response(new WP_Error('empty_response', __('AI returned empty response for this field.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        $new_value = trim(str_replace('"', '', $ai_result['content']));
        $success_message = '';

        // Log success before updating the post based on field type so Admin Logs capture the generated content
        log_enhancer_bulk_update_logic(
            (int) $post->ID,
            $field,
            $prompt,
            $new_value,
            $provider,
            $model,
            $ai_result['usage'] ?? null,
            $ai_result['request_payload_log'] ?? null,
            $conv_uuid
        );

        // Update the post based on field type
        switch ($field) {
            case 'keyword':
                AIPKit_SEO_Helper::update_focus_keyword($post->ID, $new_value);
                $success_message = 'Focus keyword updated successfully';
                break;
            case 'title':
                wp_update_post(['ID' => $post->ID, 'post_title' => sanitize_text_field($new_value)]);
                $success_message = 'Title updated successfully';
                break;
            case 'excerpt':
                wp_update_post(['ID' => $post->ID, 'post_excerpt' => wp_kses_post($new_value)]);
                $success_message = 'Excerpt updated successfully';
                break;
            case 'content':
                $html_content = $new_value;
                $html_content = preg_replace('/^#\s+(.*)$/m', '<h1>$1</h1>', $html_content);
                $html_content = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $html_content);
                $html_content = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $html_content);
                $html_content = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $html_content);
                $html_content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html_content);
                $html_content = preg_replace('/(?<!\*)\*(?!\*|_)(.*?)(?<!\*|_)\*(?!\*)/s', '<em>$1</em>', $html_content);
                $html_content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html_content);
                wp_update_post(['ID' => $post->ID, 'post_content' => wp_kses_post($html_content)]);
                $success_message = 'Content updated successfully';
                break;
            case 'tags':
                if (class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
                    $result = \WPAICG\SEO\AIPKit_SEO_Helper::update_tags($post->ID, sanitize_text_field($new_value));
                    if ($result) {
                        $success_message = 'Tags updated successfully';
                    } else {
                        $this->send_error_response(new WP_Error('tags_update_failed', __('Failed to update tags.', 'gpt3-ai-content-generator'), ['status' => 500]));
                        return;
                    }
                } else {
                    $this->send_error_response(new WP_Error('tags_helper_missing', __('Tags helper class not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
                    return;
                }
                break;
            case 'meta':
                AIPKit_SEO_Helper::update_meta_description($post->ID, sanitize_text_field($new_value));
                $success_message = 'Meta description updated successfully';
                break;
            default:
                $this->send_error_response(new WP_Error('unsupported_field', __('Unsupported field type.', 'gpt3-ai-content-generator'), ['status' => 400]));
                return;
        }

        wp_send_json_success([
            'message' => $success_message,
            'field' => $field,
            'post_id' => $post->ID
        ]);
    }
}