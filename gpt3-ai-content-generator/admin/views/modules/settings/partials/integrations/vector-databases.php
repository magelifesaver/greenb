<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/vector-databases.php
// Status: NEW FILE

/**
 * Partial: Vector Database integration settings (Pinecone, Qdrant).
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent:
// $current_pinecone_api_key, $pinecone_index_list, $current_pinecone_default_index
// $qdrant_defaults, $current_qdrant_url, $current_qdrant_api_key, $qdrant_collection_list, $current_qdrant_default_collection
?>
<!-- Vector Databases Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Vector Databases', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <!-- Pinecone Settings -->
        <h5><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></h5>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_pinecone_api_key"><?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_api-key-wrapper">
                <input
                    type="password"
                    id="aipkit_pinecone_api_key"
                    name="pinecone_api_key"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    value="<?php echo esc_attr($current_pinecone_api_key); ?>"
                    placeholder="<?php esc_attr_e('Enter your Pinecone API key', 'gpt3-ai-content-generator'); ?>"
                />
                <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
            </div>
        </div>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_pinecone_default_index"><?php esc_html_e('Default Index (Optional)', 'gpt3-ai-content-generator'); ?></label>
             <div class="aipkit_input-with-button">
                <select id="aipkit_pinecone_default_index" name="pinecone_default_index" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value=""><?php esc_html_e('-- Select Default Index --', 'gpt3-ai-content-generator'); ?></option>
                    <?php
                    if (!empty($pinecone_index_list)) {
                        $found_current = false;
                        foreach ($pinecone_index_list as $index) {
                            $index_name = is_array($index) ? ($index['name'] ?? ($index['id'] ?? '')) : $index;
                            if (empty($index_name)) {
                                continue;
                            }
                            $is_selected = selected($current_pinecone_default_index, $index_name, false);
                            if ($is_selected) {
                                $found_current = true;
                            }
                            echo '<option value="' . esc_attr($index_name) . '" ' . $is_selected . '>' . esc_html($index_name) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: $is_selected contains the result of the WP `selected()` function, which is safe for output.
                        }
                        if (!$found_current && !empty($current_pinecone_default_index)) {
                            echo '<option value="' . esc_attr($current_pinecone_default_index) . '" selected>' . esc_html($current_pinecone_default_index) . ' (Manual/Not Synced)</option>';
                        }
                    } elseif (!empty($current_pinecone_default_index)) {
                         echo '<option value="' . esc_attr($current_pinecone_default_index) . '" selected>' . esc_html($current_pinecone_default_index) . ' (Manual)</option>';
                    }
                    ?>
                </select>
                <button id="aipkit_sync_pinecone_indexes_btn" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn" data-provider="PineconeIndexes">
                    <span class="aipkit_btn-text"><?php echo esc_html__('Sync Indexes', 'gpt3-ai-content-generator'); ?></span>
                     <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </div>
             <div class="aipkit_form-help"><?php esc_html_e('Sync and select a default index for modules that use Pinecone. Indexes are managed in AI Training.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <hr class="aipkit_hr">
        
        <!-- Qdrant Settings -->
        <h5><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></h5>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_qdrant_url"><?php esc_html_e('URL', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-icon-wrapper">
                <input
                    type="url"
                    id="aipkit_qdrant_url"
                    name="qdrant_url"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    value="<?php echo esc_attr($current_qdrant_url); ?>"
                    placeholder="<?php esc_attr_e('e.g., http://localhost:6333 or https://your-cloud-id.qdrant.cloud', 'gpt3-ai-content-generator'); ?>"
                />
                <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($qdrant_defaults['url']); ?>" data-target-input="aipkit_qdrant_url">
                    <span class="dashicons dashicons-undo"></span>
                </span>
            </div>
        </div>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_qdrant_api_key"><?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_api-key-wrapper">
                <input
                    type="password"
                    id="aipkit_qdrant_api_key"
                    name="qdrant_api_key"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    value="<?php echo esc_attr($current_qdrant_api_key); ?>"
                    placeholder="<?php esc_attr_e('Enter your Qdrant API key', 'gpt3-ai-content-generator'); ?>"
                />
                <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
            </div>
        </div>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_qdrant_default_collection"><?php esc_html_e('Default Collection Name (Optional)', 'gpt3-ai-content-generator'); ?></label>
             <div class="aipkit_input-with-button">
                <select id="aipkit_qdrant_default_collection" name="qdrant_default_collection" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value=""><?php esc_html_e('-- Select Collection --', 'gpt3-ai-content-generator'); ?></option>
                     <?php
                     if (!empty($qdrant_collection_list)) :
                         foreach ($qdrant_collection_list as $collection) {
                             $collection_name = is_array($collection) ? ($collection['name'] ?? ($collection['id'] ?? '')) : $collection;
                             if (empty($collection_name)) {
                                 continue;
                             }
                             echo '<option value="' . esc_attr($collection_name) . '" ' . selected($current_qdrant_default_collection, $collection_name, false) . '>' . esc_html($collection_name) . '</option>';
                         } elseif (!empty($current_qdrant_default_collection)) :
                             echo '<option value="' . esc_attr($current_qdrant_default_collection) . '" selected>' . esc_html($current_qdrant_default_collection) . ' (Manual/Not Synced)</option>';
                         endif; ?>
                </select>
                <button id="aipkit_sync_qdrant_collections_btn" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn" data-provider="QdrantCollections">
                    <span class="aipkit_btn-text"><?php echo esc_html__('Sync Collections', 'gpt3-ai-content-generator'); ?></span>
                     <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </div>
            <div class="aipkit_form-help"><?php esc_html_e('Sync and select a default Qdrant collection. Collections are managed in AI Training.', 'gpt3-ai-content-generator'); ?></div>
        </div>
    </div>
</div>