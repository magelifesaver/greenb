<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/vector-config.php
// Status: MODIFIED

/**
 * Partial: AI Form Editor - Vector & Context Configuration
 * Contains settings for enabling and configuring vector store and web search integration.
 */
if (!defined('ABSPATH')) {
    exit;
}
// Variables passed from parent (index.php -> form-editor.php -> _form-editor-main-settings.php -> this):
// $openai_vector_stores, $pinecone_indexes, $qdrant_collections
// $openai_embedding_models, $google_embedding_models
?>
<div class="aipkit_form-row aipkit_checkbox-row">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_ai_form_enable_vector_store">
            <input
                type="checkbox"
                id="aipkit_ai_form_enable_vector_store"
                name="enable_vector_store"
                class="aipkit_toggle_switch aipkit_ai_form_vector_store_toggle"
                value="1"
            >
            <?php esc_html_e('Vector Store', 'gpt3-ai-content-generator'); ?>
        </label>
    </div>
    <div class="aipkit_form-group">
        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_ai_form_openai_web_search_enabled">
            <input
                type="checkbox"
                id="aipkit_ai_form_openai_web_search_enabled"
                name="openai_web_search_enabled"
                class="aipkit_toggle_switch aipkit_ai_form_openai_web_search_toggle"
                value="1"
            >
            <?php esc_html_e('OpenAI Web Search', 'gpt3-ai-content-generator'); ?>
        </label>
    </div>
    <div class="aipkit_form-group">
        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_ai_form_google_search_grounding_enabled">
            <input
                type="checkbox"
                id="aipkit_ai_form_google_search_grounding_enabled"
                name="google_search_grounding_enabled"
                class="aipkit_toggle_switch aipkit_ai_form_google_search_grounding_toggle"
                value="1"
            >
            <?php esc_html_e('Google Search', 'gpt3-ai-content-generator'); ?>
        </label>
    </div>
</div>
<div class="aipkit_form-help">
    <?php esc_html_e('Provide additional context to the AI by enabling a knowledge base or real-time web search. Visibility of these options depends on the selected AI Provider.', 'gpt3-ai-content-generator'); ?>
</div>

<div class="aipkit_ai_form_vector_store_conditional_settings" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
    <div class="aipkit_form-row" style="flex-wrap: nowrap; gap: 10px;">
        <div class="aipkit_form-group aipkit_form-col" style="flex: 0 1 180px;">
            <label class="aipkit_form-label" for="aipkit_ai_form_vector_store_provider"><?php esc_html_e('Vector Store Provider', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_ai_form_vector_store_provider" name="vector_store_provider" class="aipkit_form-input aipkit_ai_form_vector_store_provider_select">
                <option value="openai">OpenAI</option>
                <option value="pinecone">Pinecone</option>
                <option value="qdrant">Qdrant</option>
            </select>
        </div>
        <div class="aipkit_form-group aipkit_form-col" style="flex: 1 1 auto;">
            <!-- OpenAI Selector -->
            <div class="aipkit_form-group aipkit_ai_form_vector_openai_field">
                <label class="aipkit_form-label" for="aipkit_ai_form_openai_vector_store_ids"><?php esc_html_e('Vector Stores (Max 2)', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_ai_form_openai_vector_store_ids" name="openai_vector_store_ids[]" class="aipkit_form-input" multiple size="3">
                     <?php if (!empty($openai_vector_stores)): ?>
                        <?php foreach ($openai_vector_stores as $store): ?>
                            <option value="<?php echo esc_attr($store['id']); ?>"><?php echo esc_html($store['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled><?php esc_html_e('No stores found (Sync in AI Training)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <!-- Pinecone Selector -->
            <div class="aipkit_form-group aipkit_ai_form_vector_pinecone_field" style="display:none;">
                <label class="aipkit_form-label" for="aipkit_ai_form_pinecone_index_name"><?php esc_html_e('Pinecone Index', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_ai_form_pinecone_index_name" name="pinecone_index_name" class="aipkit_form-input">
                    <option value=""><?php esc_html_e('-- Select Index --', 'gpt3-ai-content-generator'); ?></option>
                     <?php if (!empty($pinecone_indexes)): ?>
                        <?php foreach ($pinecone_indexes as $index): ?>
                            <option value="<?php echo esc_attr($index['name']); ?>"><?php echo esc_html($index['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled><?php esc_html_e('No indexes found (Sync in AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <!-- Qdrant Selector -->
            <div class="aipkit_form-group aipkit_ai_form_vector_qdrant_field" style="display:none;">
                <label class="aipkit_form-label" for="aipkit_ai_form_qdrant_collection_name"><?php esc_html_e('Qdrant Collection', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_ai_form_qdrant_collection_name" name="qdrant_collection_name" class="aipkit_form-input">
                    <option value=""><?php esc_html_e('-- Select Collection --', 'gpt3-ai-content-generator'); ?></option>
                     <?php if (!empty($qdrant_collections)): ?>
                        <?php foreach ($qdrant_collections as $collection): ?>
                            <option value="<?php echo esc_attr($collection['name']); ?>"><?php echo esc_html($collection['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled><?php esc_html_e('No collections found (Sync in AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Embedding Config (for Pinecone/Qdrant) -->
    <div class="aipkit_ai_form_embedding_config_row" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--aipkit_container-border);">
        <div class="aipkit_form-row" style="flex-wrap: nowrap; gap: 10px;">
            <div class="aipkit_form-group aipkit_form-col" style="flex: 0 1 180px;">
                <label class="aipkit_form-label" for="aipkit_ai_form_vector_embedding_provider"><?php esc_html_e('Embedding Provider', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_ai_form_vector_embedding_provider" name="vector_embedding_provider" class="aipkit_form-input aipkit_ai_form_vector_embedding_provider_select">
                    <option value="openai">OpenAI</option>
                    <option value="google">Google</option>
                    <option value="azure">Azure</option>
                </select>
            </div>
            <div class="aipkit_form-group aipkit_form-col" style="flex: 1 1 auto;">
                <label class="aipkit_form-label" for="aipkit_ai_form_vector_embedding_model"><?php esc_html_e('Embedding Model', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_ai_form_vector_embedding_model" name="vector_embedding_model" class="aipkit_form-input aipkit_ai_form_vector_embedding_model_select">
                     <option value=""><?php esc_html_e('Select Provider First', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- Top K and Confidence Threshold Sliders -->
    <div class="aipkit_form-row" style="margin-top: 15px; gap: 10px;">
        <div class="aipkit_form-group" style="flex: 1;">
            <label class="aipkit_form-label" for="aipkit_ai_form_vector_store_top_k"><?php esc_html_e('Results Limit', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_slider_wrapper">
                <input type="range" id="aipkit_ai_form_vector_store_top_k" name="vector_store_top_k" class="aipkit_form-input aipkit_range_slider" min="1" max="20" step="1" value="3" />
                <span id="aipkit_ai_form_vector_store_top_k_value" class="aipkit_slider_value">3</span>
            </div>
        </div>

        <div class="aipkit_form-group" style="flex: 1;">
            <label class="aipkit_form-label" for="aipkit_ai_form_vector_store_confidence_threshold"><?php esc_html_e('Score Threshold', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_slider_wrapper">
                <input type="range" id="aipkit_ai_form_vector_store_confidence_threshold" name="vector_store_confidence_threshold" class="aipkit_form-input aipkit_range_slider" min="0" max="100" step="1" value="20" />
                <span id="aipkit_ai_form_vector_store_confidence_threshold_value" class="aipkit_slider_value">20%</span>
            </div>
        </div>
    </div>

    <!-- Help text for both sliders -->
    <div class="aipkit_form-row" style="margin-top: 5px; gap: 10px;">
        <div class="aipkit_form-group" style="flex: 1;">
            <div class="aipkit_form-help">
                <?php esc_html_e('Max relevant results from vector store.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>
        <div class="aipkit_form-group" style="flex: 1;">
            <div class="aipkit_form-help">
                <?php esc_html_e('Only use results with a similarity score above this threshold.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>
    </div>
</div>

<!-- OpenAI Web Search Settings -->
<div class="aipkit_ai_form_openai_web_search_settings" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
    <p class="aipkit_form-help" style="margin-top: 0;"><?php esc_html_e('Configure real-time web search for OpenAI models.', 'gpt3-ai-content-generator'); ?></p>
    
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_context_size"><?php esc_html_e('Search Context Size', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_ai_form_openai_web_search_context_size" name="openai_web_search_context_size" class="aipkit_form-input">
                <option value="low"><?php esc_html_e('Low', 'gpt3-ai-content-generator'); ?></option>
                <option value="medium" selected><?php esc_html_e('Medium (Default)', 'gpt3-ai-content-generator'); ?></option>
                <option value="high"><?php esc_html_e('High', 'gpt3-ai-content-generator'); ?></option>
            </select>
        </div>
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_loc_type"><?php esc_html_e('User Location', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_ai_form_openai_web_search_loc_type" name="openai_web_search_loc_type" class="aipkit_form-input aipkit_ai_form_openai_web_search_loc_type_select">
                <option value="none" selected><?php esc_html_e('None (Default)', 'gpt3-ai-content-generator'); ?></option>
                <option value="approximate"><?php esc_html_e('Approximate', 'gpt3-ai-content-generator'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="aipkit_ai_form_openai_web_search_location_details" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee;">
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_loc_country"><?php esc_html_e('Country (ISO Code)', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_ai_form_openai_web_search_loc_country" name="openai_web_search_loc_country" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., US, GB', 'gpt3-ai-content-generator'); ?>" maxlength="2">
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_loc_city"><?php esc_html_e('City', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_ai_form_openai_web_search_loc_city" name="openai_web_search_loc_city" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., London', 'gpt3-ai-content-generator'); ?>">
            </div>
        </div>
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_loc_region"><?php esc_html_e('Region/State', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_ai_form_openai_web_search_loc_region" name="openai_web_search_loc_region" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., California', 'gpt3-ai-content-generator'); ?>">
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_ai_form_openai_web_search_loc_timezone"><?php esc_html_e('Timezone (IANA)', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_ai_form_openai_web_search_loc_timezone" name="openai_web_search_loc_timezone" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., America/Chicago', 'gpt3-ai-content-generator'); ?>">
            </div>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Leave location fields blank if not applicable. Country code is 2-letter ISO (e.g., US).', 'gpt3-ai-content-generator'); ?></div>
    </div>
</div>

<!-- Google Search Grounding Settings -->
<div class="aipkit_ai_form_google_search_grounding_settings" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
    <p class="aipkit_form-help" style="margin-top: 0;"><?php esc_html_e('Configure Google Search grounding for supported Gemini models.', 'gpt3-ai-content-generator'); ?></p>
    <p class="aipkit_form-help" style="margin-top: 0;"><?php esc_html_e('Supported models: Gemini 2.5 Pro, Gemini 2.5 Flash, Gemini 2.0 Flash, Gemini 1.5 Pro, Gemini 1.5 Flash.', 'gpt3-ai-content-generator'); ?></p>

    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_ai_form_google_grounding_mode"><?php esc_html_e('Grounding Mode', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_ai_form_google_grounding_mode" name="google_grounding_mode" class="aipkit_form-input aipkit_ai_form_google_grounding_mode_select">
                <option value="DEFAULT_MODE" selected><?php esc_html_e('Default (Model Decides/Search as Tool)', 'gpt3-ai-content-generator'); ?></option>
                <option value="MODE_DYNAMIC"><?php esc_html_e('Dynamic Retrieval (Gemini 1.5 Flash only)', 'gpt3-ai-content-generator'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="aipkit_ai_form_google_grounding_dynamic_threshold_container" style="display: none; margin-top: 10px;">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_ai_form_google_grounding_dynamic_threshold"><?php esc_html_e('Dynamic Retrieval Threshold', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_slider_wrapper" style="max-width: 400px;">
                <input type="range" id="aipkit_ai_form_google_grounding_dynamic_threshold" name="google_grounding_dynamic_threshold" class="aipkit_form-input aipkit_range_slider" min="0.0" max="1.0" step="0.01" value="0.30">
                <span id="aipkit_ai_form_google_grounding_dynamic_threshold_value" class="aipkit_slider_value">0.30</span>
            </div>
        </div>
    </div>
</div>