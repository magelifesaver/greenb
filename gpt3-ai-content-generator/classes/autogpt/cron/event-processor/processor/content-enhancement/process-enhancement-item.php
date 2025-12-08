<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-enhancement/process-enhancement-item.php
// Status: MODIFIED
// I have added a conditional check to ensure the `reasoning_effort` parameter is only added for compatible OpenAI models (gpt-5, o-series).

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentEnhancement;

use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\SEO\AIPKit_SEO_Helper;
use WP_Error;
// --- ADDED: Dependencies for vector context ---
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\Stream\Vector as VectorContextBuilder;

if (!defined('ABSPATH')) {
    exit;
}

// --- ADDED: Dependency loader for vector context functions ---
$vector_logic_base_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/vector/';
if (file_exists($vector_logic_base_path . 'fn-build-vector-search-context.php')) {
    require_once $vector_logic_base_path . 'fn-build-vector-search-context.php';
}

/**
 * Processes a single "enhance_existing_content" queue item.
 *
 * @param array $item The queue item from the database.
 * @param array $item_config The decoded item_config from the queue item.
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function process_enhancement_item_logic(array $item, array $item_config): array
{

    $post_id = absint($item['target_identifier']);
    $post = get_post($post_id);

    if (!$post || !in_array($post->post_status, $item_config['post_statuses'] ?? ['publish'])) {
        $log_message = "Skipped post #{$post_id}: Post not found or does not match status criteria.";
        return ['status' => 'success', 'message' => $log_message];
    }

    $ai_caller = new AIPKit_AI_Caller();
    $ai_params = ['temperature' => floatval($item_config['ai_temperature'] ?? 1.0)];
    if (($item_config['ai_provider'] ?? '') === 'OpenAI' && isset($item_config['reasoning_effort']) && !empty($item_config['reasoning_effort'])) {
        $model_lower = strtolower($item_config['ai_model'] ?? '');
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
            $ai_params['reasoning'] = ['effort' => sanitize_key($item_config['reasoning_effort'])];
        }
    }
    $user_max_tokens = absint($item_config['content_max_tokens'] ?? 4000);
    $system_instruction = 'You are an expert SEO copywriter and editor. You follow instructions precisely. Your response must contain ONLY the generated text, with no introductory phrases, labels, or quotation marks.';
    $changes_made = [];

    // --- Start: Gather all possible placeholders ---
    $original_meta = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: (get_post_meta($post->ID, '_aioseo_description', true) ?: '');
    if (empty($original_meta)) {
        $original_meta = $post->post_excerpt; // Fallback to excerpt
    }
    $original_focus_keyword = AIPKit_SEO_Helper::get_focus_keyword($post->ID);
    $original_tags = AIPKit_SEO_Helper::get_tags_as_string($post->ID);
    $categories = AIPKit_SEO_Helper::get_categories_as_string($post->ID);
    $placeholders = [
        '{original_title}' => $post->post_title,
        '{original_content}' => wp_strip_all_tags(strip_shortcodes(apply_filters('the_content', $post->post_content))),
        '{original_excerpt}' => $post->post_excerpt,
        '{original_meta_description}' => $original_meta,
        '{original_focus_keyword}' => $original_focus_keyword ?: '',
        '{original_tags}' => $original_tags,
        '{categories}' => $categories,
    ];
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
            $placeholders['{total_sales}'] = $product->get_total_sales();
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
    // --- END: Gather placeholders ---

    // --- Vector Context Logic ---
    $vector_context = '';
    $vector_store_manager = null;
    if (($item_config['enable_vector_store'] ?? '0') === '1') {
        if (class_exists(AIPKit_Vector_Store_Manager::class)) {
            $vector_store_manager = new AIPKit_Vector_Store_Manager();
        }

        if ($vector_store_manager && function_exists('\WPAICG\Core\Stream\Vector\build_vector_search_context_logic')) {
            $vector_context = VectorContextBuilder\build_vector_search_context_logic(
                $ai_caller,
                $vector_store_manager,
                $post->post_title, // Use post title as the query
                $item_config,      // Pass the whole task config
                $item_config['ai_provider'],         // Main AI provider
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
    // --- END: Vector Context Logic ---

    // --- REVISED LOGIC START ---
    $fields_to_enhance = [];
    if (!empty($item_config['update_title']) && $item_config['update_title'] === '1') $fields_to_enhance[] = 'title';
    if (!empty($item_config['update_excerpt']) && $item_config['update_excerpt'] === '1') $fields_to_enhance[] = 'excerpt';
    if (!empty($item_config['update_content']) && $item_config['update_content'] === '1') $fields_to_enhance[] = 'content';
    if (!empty($item_config['update_meta']) && $item_config['update_meta'] === '1') $fields_to_enhance[] = 'meta';

    // Process fields
    foreach ($fields_to_enhance as $field) {
        $prompt_key = $field . '_prompt'; // ** THE FIX IS HERE **
        if (!empty($item_config[$prompt_key])) {

            $prompt = str_replace(array_keys($placeholders), array_values($placeholders), $item_config[$prompt_key]);
            $current_ai_params = $ai_params;

            if ($field === 'title') $current_ai_params['max_completion_tokens'] = 4000;
            if ($field === 'excerpt') $current_ai_params['max_completion_tokens'] = 4000;
            if ($field === 'meta') $current_ai_params['max_completion_tokens'] = 4000;
            if ($field === 'content') $current_ai_params['max_completion_tokens'] = $user_max_tokens;

            $ai_result = $ai_caller->make_standard_call($item_config['ai_provider'], $item_config['ai_model'], [['role' => 'user', 'content' => $prompt]], $current_ai_params, $system_instruction);

            if (is_wp_error($ai_result)) {
                continue; // Skip to the next field on error
            }

            if (!empty($ai_result['content'])) {
                $new_value = trim(str_replace('"', '', $ai_result['content']));
                switch ($field) {
                    case 'title':
                        wp_update_post(['ID' => $post->ID, 'post_title' => sanitize_text_field($new_value)]);
                        $changes_made[] = 'title';
                        break;
                    case 'excerpt':
                        wp_update_post(['ID' => $post->ID, 'post_excerpt' => wp_kses_post($new_value)]);
                        $changes_made[] = 'excerpt';
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
                        $changes_made[] = 'content';
                        break;
                    case 'meta':
                        AIPKit_SEO_Helper::update_meta_description($post->ID, sanitize_text_field($new_value));
                        $changes_made[] = 'meta description';
                        break;
                }
            }
        } 
    }
    // --- REVISED LOGIC END ---

    if (empty($changes_made)) {
        $log_message = "No valid enhancements were processed for post #{$post_id}. This could be due to empty prompts or AI errors.";
        return ['status' => 'success', 'message' => $log_message];
    } else {
        $log_message = "Post #{$post_id} enhanced. Updated: " . implode(', ', $changes_made) . ".";
        return ['status' => 'success', 'message' => $log_message];
    }
}