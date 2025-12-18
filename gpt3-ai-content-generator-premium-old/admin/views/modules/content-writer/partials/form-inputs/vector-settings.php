<?php
/**
 * Partial: Content Writer Form - Vector Settings
 * @since NEXT_VERSION
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from loader-vars.php:
// $openai_vector_stores, $pinecone_indexes, $qdrant_collections
// $openai_embedding_models, $google_embedding_models
?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_cw_enable_vector_store">
                <input type="checkbox" id="aipkit_cw_enable_vector_store" name="enable_vector_store" class="aipkit_toggle_switch aipkit_cw_vector_store_toggle aipkit_autosave_trigger" value="1">
                <?php esc_html_e('Enable Vector Store', 'gpt3-ai-content-generator'); ?>
            </label>
            <p class="aipkit_form-help"><?php esc_html_e('Use a knowledge base to provide context for generation.', 'gpt3-ai-content-generator'); ?></p>
        </div>
        <div class="aipkit_cw_vector_store_settings_container" style="display:none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_vector_store_provider"><?php esc_html_e('Vector Provider', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_vector_store_provider" name="vector_store_provider" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_vector_store_provider_select">
                        <option value="openai" selected>OpenAI</option>
                        <option value="pinecone">Pinecone</option>
                        <option value="qdrant">Qdrant</option>
                    </select>
                </div>
            </div>
            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col aipkit_cw_vector_store_top_k_field">
                    <label class="aipkit_form-label" for="aipkit_cw_vector_store_top_k"><?php esc_html_e('Results Limit', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipkit_cw_vector_store_top_k" name="vector_store_top_k" class="aipkit_form-input aipkit_autosave_trigger aipkit_range_slider" value="3" min="1" max="20" step="1">
                    <span id="aipkit_cw_vector_store_top_k_value" class="aipkit_slider_value">3</span>
                </div>
                <div class="aipkit_form-group aipkit_form-col aipkit_cw_vector_store_confidence_field">
                    <label class="aipkit_form-label" for="aipkit_cw_vector_store_confidence_threshold"><?php esc_html_e('Score Threshold', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipkit_cw_vector_store_confidence_threshold" name="vector_store_confidence_threshold" class="aipkit_form-input aipkit_autosave_trigger aipkit_range_slider" value="20" min="0" max="100" step="1">
                    <span id="aipkit_cw_vector_store_confidence_threshold_value" class="aipkit_slider_value">20%</span>
                    <p class="aipkit_form-help"><?php esc_html_e('Minimum vector similarity score required (0–100).', 'gpt3-ai-content-generator'); ?></p>
                </div>
            </div>
            <!-- Provider Specific Fields -->
            <div class="aipkit_cw_vector_provider_fields_container">
                <div class="aipkit_form-group aipkit_cw_vector_openai_field">
                    <label class="aipkit_form-label" for="aipkit_cw_openai_vector_store_ids"><?php esc_html_e('OpenAI Vector Stores', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_openai_vector_store_ids" name="openai_vector_store_ids[]" class="aipkit_form-input aipkit_autosave_trigger" multiple size="3" style="height: auto;">
                        <?php if (!empty($openai_vector_stores)): ?>
                            <?php foreach ($openai_vector_stores as $store): ?>
                                <option value="<?php echo esc_attr($store['id']); ?>"><?php echo esc_html($store['name']); ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled><?php esc_html_e('No stores found (Sync in AI Training)', 'gpt3-ai-content-generator'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_cw_vector_pinecone_field" style="display:none;">
                    <label class="aipkit_form-label" for="aipkit_cw_pinecone_index_name"><?php esc_html_e('Pinecone Index', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pinecone_index_name" name="pinecone_index_name" class="aipkit_form-input aipkit_autosave_trigger">
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
                <div class="aipkit_form-group aipkit_cw_vector_qdrant_field" style="display:none;">
                     <label class="aipkit_form-label" for="aipkit_cw_qdrant_collection_name"><?php esc_html_e('Qdrant Collection', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_qdrant_collection_name" name="qdrant_collection_name" class="aipkit_form-input aipkit_autosave_trigger">
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
            <!-- Embedding Config for Pinecone/Qdrant -->
            <div class="aipkit_cw_vector_embedding_config_row" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee;">
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_cw_vector_embedding_provider"><?php esc_html_e('Embedding Provider', 'gpt3-ai-content-generator'); ?></label>
                        <select id="aipkit_cw_vector_embedding_provider" name="vector_embedding_provider" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_vector_embedding_provider_select">
                            <option value="openai" selected>OpenAI</option>
                            <option value="google">Google</option>
                            <option value="azure">Azure</option>
                        </select>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_cw_vector_embedding_model"><?php esc_html_e('Embedding Model', 'gpt3-ai-content-generator'); ?></label>
                        <select id="aipkit_cw_vector_embedding_model" name="vector_embedding_model" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_vector_embedding_model_select">
                            <option value=""><?php esc_html_e('Select Provider First', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>