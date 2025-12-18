<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-training/partials/vector-store/provider-ui/vector-store-openai.php

/**
 * Partial: AI Training - OpenAI Vector Store MANAGEMENT UI (Revised)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent vector-store.php: $openai_api_key_is_set
?>
<div class="aipkit_settings-section">
    <?php if (!$openai_api_key_is_set): ?>
        <div class="aipkit_notice aipkit_notice-warning">
            <p>
                <?php esc_html_e('OpenAI API Key is not set in global settings. OpenAI Vector Store management will not be available.', 'gpt3-ai-content-generator'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg#providers')); ?>"><?php esc_html_e('Configure API Key', 'gpt3-ai-content-generator'); ?></a>
            </p>
        </div>
    <?php else: ?>
        <!-- Main Content Area for OpenAI Vector Store Management -->
        <div id="aipkit_openai_vs_main_content_area" class="aipkit_openai_vs_main_content_area">
            <!-- Placeholder for when no store is selected or initially -->
            <div class="aipkit_ai_training_section_wrapper aipkit_openai_vs_content_section" id="aipkit_openai_vs_right_col_placeholder" style="display: flex;">
                 <div class="aipkit_ai_training_section_header">
                    <h5><?php esc_html_e('Store Details', 'gpt3-ai-content-generator'); ?></h5>
                </div>
                <p class="aipkit_text-center" style="padding: 20px;"><?php esc_html_e('Select a vector store from the "Target Store" dropdown in the "Add Content" form above to view its details and manage files, or select "Create New" to make a new one.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <!-- File Management Panel (Initially Hidden) -->
            <div id="aipkit_manage_files_panel_openai" class="aipkit_file_management_panel" style="display:none;">
                <div class="aipkit_panel_body">
                    <div id="aipkit_manage_files_list_wrapper_openai" class="aipkit_data-table aipkit_index_logs_table">
                        <table>
                            <thead><tr>
                                <th><?php esc_html_e('Time', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('File ID', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Message', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Source', 'gpt3-ai-content-generator'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                            </tr></thead>
                            <tbody id="aipkit_vector_store_files_table_body_openai">
                                <!-- Log rows populated by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="aipkit_openai_logs_pagination" class="aipkit_logs_pagination_container"></div>
                    <div id="aipkit_manage_files_panel_status_openai" class="aipkit_form-help"></div>
                </div>
            </div>

            <!-- Search Form & Results Area (Initially Hidden) -->
            <div id="aipkit_search_store_form_openai_wrapper_modal_placeholder" class="aipkit_openai_vs_content_section" style="display:none;">
                <div class="aipkit_search_form_container openai">
                    <input type="hidden" id="aipkit_search_vector_store_id_openai" value="">
                    
                    <div class="aipkit_search_form_header">
                        <button type="button" id="aipkit_close_search_panel_btn_openai" class="aipkit_search_form_close_btn" title="<?php esc_attr_e('Close Search', 'gpt3-ai-content-generator'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>

                    <div class="aipkit_search_form_row">
                        <div class="aipkit_search_form_row_main">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_search_query_text_openai"><?php esc_html_e('Query', 'gpt3-ai-content-generator'); ?></label>
                                <input type="text" id="aipkit_search_query_text_openai" class="aipkit_search_form_input aipkit_search_form_input_query" placeholder="<?php esc_attr_e('Enter your search query...', 'gpt3-ai-content-generator'); ?>">
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_search_top_k_openai"><?php esc_html_e('Results', 'gpt3-ai-content-generator'); ?></label>
                                <input type="number" id="aipkit_search_top_k_openai" class="aipkit_search_form_input aipkit_search_form_input_small" value="5" min="1" max="20">
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label">&nbsp;</label>
                                <div class="aipkit_search_form_actions">
                                    <button type="button" id="aipkit_search_vector_store_btn_openai" class="aipkit_search_form_btn aipkit_search_form_btn_primary">
                                        <span class="aipkit_search_form_btn_text"><?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?></span>
                                        <span class="aipkit_search_form_spinner"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="aipkit_search_vector_store_form_status_openai" class="aipkit_search_form_status"></div>
                </div>

                <div id="aipkit_search_results_area_openai" class="aipkit_search_results_container"></div>
            </div>
        </div>
    <?php endif; ?>
</div>