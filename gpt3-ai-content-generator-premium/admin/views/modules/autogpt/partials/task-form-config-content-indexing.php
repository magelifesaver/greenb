<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-form-config-content-indexing.php
// Status: MODIFIED

/**
 * Partial: Automated Task Form - Content Indexing Configuration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_task_config_content_indexing" class="aipkit_task_config_section">
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_content_indexing_target_store_provider"><?php esc_html_e('Vector Provider', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_content_indexing_target_store_provider" name="target_store_provider" class="aipkit_form-input">
                <option value="openai" selected>OpenAI</option>
                <option value="pinecone">Pinecone</option>
                <option value="qdrant">Qdrant</option>
            </select>
        </div>
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_content_indexing_target_store_id"><?php esc_html_e('Vector Store', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_content_indexing_target_store_id" name="target_store_id" class="aipkit_form-input">
                <option value=""><?php esc_html_e('-- Select Store/Index --', 'gpt3-ai-content-generator'); ?></option>
                <?php // Options populated by JS?>
            </select>
        </div>
        <!-- REVISED: Combined Embedding Model Dropdown -->
        <div class="aipkit_form-group aipkit_form-col" id="aipkit_task_content_indexing_embedding_model_group" style="display: none;">
             <label class="aipkit_form-label" for="aipkit_task_content_indexing_embedding_model"><?php esc_html_e('Embedding', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_content_indexing_embedding_model" name="embedding_model" class="aipkit_form-input">
                <option value=""><?php esc_html_e('-- Select Model --', 'gpt3-ai-content-generator'); ?></option>
                <?php // Options and optgroups populated by JS?>
            </select>
        </div>
    </div>
</div>