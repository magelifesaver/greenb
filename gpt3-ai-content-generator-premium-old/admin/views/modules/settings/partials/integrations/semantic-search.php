<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/semantic-search.php
// Status: MODIFIED
// I have replaced the separate embedding provider and model fields with a single, combined dropdown with optgroups.

/**
 * Partial: Semantic Search integration settings.
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Retrieve saved settings for the semantic search feature
$aipkit_options = get_option('aipkit_options', []);
$semantic_search_settings = $aipkit_options['semantic_search'] ?? [];

$vector_provider = $semantic_search_settings['vector_provider'] ?? 'pinecone';
$target_id = $semantic_search_settings['target_id'] ?? '';
$embedding_provider = $semantic_search_settings['embedding_provider'] ?? 'openai';
$embedding_model = $semantic_search_settings['embedding_model'] ?? '';
$num_results = $semantic_search_settings['num_results'] ?? 5;
$no_results_text = $semantic_search_settings['no_results_text'] ?? __('No results found.', 'gpt3-ai-content-generator');

// Get available indexes/collections for initial population
$all_pinecone_indexes = \WPAICG\AIPKit_Providers::get_pinecone_indexes();
$all_qdrant_collections = \WPAICG\AIPKit_Providers::get_qdrant_collections();

// NEW: Get embedding models for the new dropdown
$openai_embedding_models = \WPAICG\AIPKit_Providers::get_openai_embedding_models();
$google_embedding_models = \WPAICG\AIPKit_Providers::get_google_embedding_models();
$all_embedding_models_map = [];
foreach ($openai_embedding_models as $m) { $all_embedding_models_map[$m['id']] = true; }
foreach ($google_embedding_models as $m) { $all_embedding_models_map[$m['id']] = true; }

?>
<!-- Semantic Search Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Semantic Search', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_semantic_search_vector_provider"><?php esc_html_e('Vector DB', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_semantic_search_vector_provider" name="semantic_search_vector_provider" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value="pinecone" <?php selected($vector_provider, 'pinecone'); ?>><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                    <option value="qdrant" <?php selected($vector_provider, 'qdrant'); ?>><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_semantic_search_target_id"><?php esc_html_e('Index', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_semantic_search_target_id" name="semantic_search_target_id" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value=""><?php esc_html_e('-- Select --', 'gpt3-ai-content-generator'); ?></option>
                    <?php
                    // JS will populate this, but we pre-populate for non-JS users and initial correct value.
                    $current_list = [];
                    if ($vector_provider === 'pinecone') {
                        $current_list = $all_pinecone_indexes;
                    }
                    if ($vector_provider === 'qdrant') {
                        $current_list = $all_qdrant_collections;
                    }

                    $found_saved = false;
                    if (!empty($current_list)) {
                        foreach ($current_list as $item) {
                            $item_name = is_array($item) ? ($item['name'] ?? ($item['id'] ?? '')) : $item;
                            if (empty($item_name)) {
                                continue;
                            }
                            $is_selected = selected($target_id, $item_name, false);
                            if ($is_selected) {
                                $found_saved = true;
                            }
                            echo '<option value="' . esc_attr($item_name) . '" ' . $is_selected . '>' . esc_html($item_name) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: $is_selected contains the result of the WP `selected()` function, which is safe for output.
                        }
                    }
                    // If saved value is not in list, show it
                    if (!$found_saved && !empty($target_id)) {
                         echo '<option value="' . esc_attr($target_id) . '" selected>' . esc_html($target_id) . ' (Manual)</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_semantic_search_embedding_model"><?php esc_html_e('Embedding', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_semantic_search_embedding_model" name="semantic_search_embedding_model" class="aipkit_form-input aipkit_autosave_trigger">
                    <optgroup label="OpenAI">
                        <?php foreach ($openai_embedding_models as $model_item): ?>
                            <option value="<?php echo esc_attr($model_item['id']); ?>" <?php selected($embedding_model, $model_item['id']); ?> data-provider="openai">
                                <?php echo esc_html($model_item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Google">
                        <?php foreach ($google_embedding_models as $model_item): ?>
                            <option value="<?php echo esc_attr($model_item['id']); ?>" <?php selected($embedding_model, $model_item['id']); ?> data-provider="google">
                                <?php echo esc_html($model_item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php if (!empty($embedding_model) && !isset($all_embedding_models_map[$embedding_model])): ?>
                        <option value="<?php echo esc_attr($embedding_model); ?>" data-provider="<?php echo esc_attr($embedding_provider); ?>" selected><?php echo esc_html($embedding_model); ?> (Manual)</option>
                    <?php endif; ?>
                </select>
                <input type="hidden" id="aipkit_semantic_search_embedding_provider" name="semantic_search_embedding_provider" value="<?php echo esc_attr($embedding_provider); ?>" class="aipkit_autosave_trigger">
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_semantic_search_num_results"><?php esc_html_e('Number of Results', 'gpt3-ai-content-generator'); ?></label>
                <input type="number" id="aipkit_semantic_search_num_results" name="semantic_search_num_results" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($num_results); ?>" min="1" max="20" />
            </div>
        </div>

        <div class="aipkit_form-row">
             <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_semantic_search_no_results_text"><?php esc_html_e('No Results Text', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_semantic_search_no_results_text" name="semantic_search_no_results_text" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($no_results_text); ?>" />
            </div>
             <div class="aipkit_form-group aipkit_form-col">
                 <?php // This column is now empty after combining embedding fields, can be used for other settings ?>
             </div>
        </div>

        <hr class="aipkit_hr">
        
        <div class="aipkit_form-group">
            <label class="aipkit_form-label"><?php esc_html_e('Shortcode', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <input type="text" class="aipkit_form-input" value="[aipkit_semantic_search]" readonly>
                <button type="button" class="aipkit_btn aipkit_btn-secondary" onclick="window.aipkit_copyShortcode('[aipkit_semantic_search]', this)">
                    <span class="aipkit_btn-text"><?php esc_html_e('Copy', 'gpt3-ai-content-generator'); ?></span>
                </button>
            </div>
            <p class="aipkit_form-help"><?php esc_html_e('Place this shortcode on any page or post to display the search form.', 'gpt3-ai-content-generator'); ?></p>
        </div>

    </div>
</div>