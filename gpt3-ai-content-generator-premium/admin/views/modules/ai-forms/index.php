<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/index.php
// Status: MODIFIED

/**
 * AIPKit AI Forms Module - Admin View
 * Main screen for managing AI Forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- ADDED: Fetch Vector Store and Model Data ---
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;

$openai_vector_stores = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
}

$pinecone_indexes = [];
if (class_exists(AIPKit_Providers::class)) {
    $pinecone_indexes = AIPKit_Providers::get_pinecone_indexes();
}

$qdrant_collections = [];
if (class_exists(AIPKit_Providers::class)) {
    $qdrant_collections = AIPKit_Providers::get_qdrant_collections();
}

$openai_embedding_models = [];
$google_embedding_models = [];
$azure_embedding_models = [];
if (class_exists(AIPKit_Providers::class)) {
    $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
    $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
    $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
}
// --- END ADDED ---

// --- NEW: Get providers for filter ---
$providers = ['OpenAI', 'OpenRouter', 'Google', 'Azure'];
if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('deepseek')) {
    $providers[] = 'DeepSeek';
}
$is_pro = class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan();
if ($is_pro && class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('ollama')) {
    $providers[] = 'Ollama';
}
// --- END NEW ---
?>
<div class="aipkit_container aipkit_ai_forms_container" id="aipkit_ai_forms_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_container_actions">
             <button id="aipkit_create_new_ai_form_btn" class="aipkit_btn aipkit_btn-primary" style="display: inline-flex;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span class="aipkit_btn-text"><?php esc_html_e('Create New Form', 'gpt3-ai-content-generator'); ?></span>
            </button>

            <!-- Actions Dropdown -->
            <div class="aipkit_actions_menu">
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_btn-icon aipkit_actions_menu_toggle" title="<?php esc_attr_e('More actions', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
                <div class="aipkit_actions_dropdown_menu" style="display: none;">
                    <button type="button" id="aipkit_export_all_ai_forms_btn" class="aipkit_dropdown-item-btn">
                        <span class="dashicons dashicons-download"></span>
                        <span><?php esc_html_e('Export All', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                    <button type="button" id="aipkit_import_ai_forms_btn" class="aipkit_dropdown-item-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <span><?php esc_html_e('Import', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                    <button type="button" id="aipkit_delete_all_ai_forms_btn" class="aipkit_dropdown-item-btn aipkit_dropdown-item--danger">
                        <span class="dashicons dashicons-trash"></span>
                        <span><?php esc_html_e('Delete All', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="aipkit_tabs">
        <div class="aipkit_tab aipkit_active" data-tab="forms-main"><?php esc_html_e('Forms', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_tab" data-tab="forms-settings"><?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <div class="aipkit_tab_content_container">
        <div class="aipkit_tab-content aipkit_active" id="forms-main-content">
            <div class="aipkit_container-body">
                <div id="aipkit_ai_forms_messages">
                    <!-- Messages from AJAX operations will appear here -->
                </div>
                <div id="aipkit_ai_forms_import_messages" style="margin-bottom: 15px;">
                    <!-- Messages for import progress will appear here -->
                </div>
                <input type="file" id="aipkit_ai_forms_import_file_input" style="display: none;" accept="application/json">
                <!-- Form Editor (hidden by default) -->
                <div id="aipkit_form_editor_container" style="display:none;">
                    <?php include __DIR__ . '/partials/form-editor.php'; ?>
                </div>
                <!-- List of Forms -->
                <div id="aipkit_ai_forms_list_container">
                    <!-- NEW: Filters & Search UI -->
                    <div class="aipkit_ai_forms_list_filters">
                        <div class="aipkit_filter_group">
                             <input type="text" id="aipkit_ai_forms_search_input" class="aipkit_form-input" placeholder="<?php esc_attr_e('Search forms...', 'gpt3-ai-content-generator'); ?>">
                        </div>
                        <div class="aipkit_filter_group">
                            <label for="aipkit_ai_forms_provider_filter" class="aipkit_form-label"><?php esc_html_e('Provider:', 'gpt3-ai-content-generator'); ?></label>
                            <select id="aipkit_ai_forms_provider_filter" class="aipkit_form-input">
                                <option value="all"><?php esc_html_e('All Providers', 'gpt3-ai-content-generator'); ?></option>
                                <?php foreach ($providers as $provider_name) : ?>
                                    <option value="<?php echo esc_attr($provider_name); ?>"><?php echo esc_html($provider_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- END NEW -->
                     <div class="aipkit_data-table aipkit_ai_forms_list_table">
                        <table>
                            <thead>
                                <tr>
                                    <th class="aipkit-sortable-col" data-sort-key="ID"><span><?php esc_html_e('ID', 'gpt3-ai-content-generator'); ?></span></th>
                                    <th class="aipkit-sortable-col" data-sort-key="title" data-sort-direction="asc"><span><?php esc_html_e('Title', 'gpt3-ai-content-generator'); ?></span></th>
                                    <th class="aipkit-sortable-col" data-sort-key="provider"><span><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></span></th>
                                    <th class="aipkit-sortable-col" data-sort-key="model"><span><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></span></th>
                                    <th><?php esc_html_e('Shortcode', 'gpt3-ai-content-generator'); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="aipkit_ai_forms_list_tbody">
                                <!-- Rows loaded by JS -->
                            </tbody>
                             <tfoot>
                                <tr>
                                    <th colspan="6">
                                        <div id="aipkit_ai_forms_pagination" class="aipkit_pagination">
                                            <!-- Pagination controls loaded by JS -->
                                        </div>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="aipkit_no_ai_forms_message" style="display: none; text-align: center; padding: 20px; color: var(--aipkit_text-secondary);">
                        <?php esc_html_e('No AI Forms have been created yet.', 'gpt3-ai-content-generator'); ?>
                    </div>
                </div>
            </div><!-- /.aipkit_container-body -->
        </div>
        <div class="aipkit_tab-content" id="forms-settings-content">
            <?php include __DIR__ . '/partials/settings-ai-forms.php'; ?>
        </div>
    </div>
</div><!-- /.aipkit_container -->