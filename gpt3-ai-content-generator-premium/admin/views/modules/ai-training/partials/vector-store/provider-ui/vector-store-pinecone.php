<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-training/partials/vector-store/provider-ui/vector-store-pinecone.php

/**
 * Partial: AI Training - Pinecone Vector Store MANAGEMENT UI
 * Displays details and actions for a selected Pinecone index.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\AIPKit_Providers;

$pinecone_data = AIPKit_Providers::get_provider_data('Pinecone');
$pinecone_api_key_is_set = !empty($pinecone_data['api_key']);

$pinecone_nonce = wp_create_nonce('aipkit_vector_store_pinecone_nonce');
?>
<!-- Ensure nonce is available for JS -->
<input type="hidden" id="aipkit_vector_store_pinecone_nonce_management" value="<?php echo esc_attr($pinecone_nonce); ?>">

<div class="aipkit_settings-section">
    <?php if (!$pinecone_api_key_is_set): ?>
        <div class="aipkit_notice aipkit_notice-warning">
            <p>
                <?php echo esc_html( __('Pinecone API Key is not set in global settings. Pinecone management will not be available.', 'gpt3-ai-content-generator') ); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg#integrations')); ?>"><?php esc_html_e('Configure API Key', 'gpt3-ai-content-generator'); ?></a>
            </p>
        </div>
    <?php else: ?>
        <!-- Main Content Area for Pinecone Index Management (Populated by JS) -->
        <div id="aipkit_pinecone_vs_main_content_area" class="aipkit_openai_vs_main_content_area"> <?php // Re-use OpenAI styles for now ?>

            <!-- Placeholder for when no index is selected or initially -->
            <div class="aipkit_ai_training_section_wrapper aipkit_openai_vs_content_section" id="aipkit_pinecone_vs_right_col_placeholder" style="display: flex;">
                 <div class="aipkit_ai_training_section_header">
                    <h5><?php esc_html_e('Pinecone Index Details', 'gpt3-ai-content-generator'); ?></h5>
                 </div>
                <p class="aipkit_text-center" style="padding: 20px;"><?php esc_html_e('Select a Pinecone index from the "Target Index" dropdown in the "Add Content" form above to view its details and manage it.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <!-- Index Details & Management Panel (Initially Hidden, shown by JS) -->
            <div id="aipkit_manage_selected_pinecone_index_panel" class="aipkit_file_management_panel" style="display:none;">
                <div class="aipkit_panel_body">
                    <div id="aipkit_pinecone_index_content_area">
                        <?php // Content might include vector count, namespace management etc. later ?>
                    </div>
                     <!-- NEW: Container for Indexing Logs -->
                    <div id="aipkit_pinecone_index_logs_container">
                        <h6 style="margin-bottom: 8px;"><?php esc_html_e('Recent Indexing Activity:', 'gpt3-ai-content-generator'); ?></h6>
                        <!-- Logs will be populated by JS -->
                         <p class="aipkit_text-center" style="padding: 10px;"><em><?php esc_html_e('Loading records...', 'gpt3-ai-content-generator'); ?></em></p>
                    </div>
                    <div id="aipkit_pinecone_logs_pagination" class="aipkit_logs_pagination_container"></div>
                    <!-- END NEW -->
                    <div id="aipkit_manage_pinecone_index_panel_status" class="aipkit_form-help"></div>
                </div>
            </div>

            <!-- Search Form & Results Area for Pinecone (Initially Hidden) -->
            <div id="aipkit_search_pinecone_index_form_wrapper" class="aipkit_openai_vs_content_section" style="display:none;">
                <div class="aipkit_search_form_container pinecone">
                    <input type="hidden" id="aipkit_search_pinecone_index_id" value="">
                    
                    <div class="aipkit_search_form_header">
                        <button type="button" id="aipkit_close_search_pinecone_panel_btn" class="aipkit_search_form_close_btn" title="<?php esc_attr_e('Close Search', 'gpt3-ai-content-generator'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>

                    <div class="aipkit_search_form_row">
                        <div class="aipkit_search_form_row_main">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_search_query_vector_pinecone"><?php esc_html_e('Query', 'gpt3-ai-content-generator'); ?></label>
                                <input type="text" id="aipkit_search_query_vector_pinecone" class="aipkit_search_form_input aipkit_search_form_input_query" placeholder="<?php esc_attr_e('Enter text to search for...', 'gpt3-ai-content-generator'); ?>">
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_pinecone_search_embedding_model_select"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_pinecone_search_embedding_model_select" class="aipkit_search_form_select aipkit_search_form_input_medium">
                                    <!-- Options populated by JS -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_search_top_k_pinecone"><?php esc_html_e('Results', 'gpt3-ai-content-generator'); ?></label>
                                <input type="number" id="aipkit_search_top_k_pinecone" class="aipkit_search_form_input aipkit_search_form_input_small" value="5" min="1" max="100">
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label" for="aipkit_search_pinecone_namespace"><?php esc_html_e('Namespace', 'gpt3-ai-content-generator'); ?></label>
                                <input type="text" id="aipkit_search_pinecone_namespace" class="aipkit_search_form_input aipkit_search_form_input_medium" placeholder="<?php esc_attr_e('Optional', 'gpt3-ai-content-generator'); ?>">
                            </div>
                        </div>
                        
                        <div class="aipkit_search_form_row_side">
                            <div class="aipkit_search_form_group">
                                <label class="aipkit_search_form_label">&nbsp;</label>
                                <div class="aipkit_search_form_actions">
                                    <button type="button" id="aipkit_search_pinecone_index_btn" class="aipkit_search_form_btn aipkit_search_form_btn_primary">
                                        <span class="aipkit_search_form_btn_text"><?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?></span>
                                        <span class="aipkit_search_form_spinner"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="aipkit_search_pinecone_index_form_status" class="aipkit_search_form_status"></div>
                </div>

                <div id="aipkit_search_pinecone_results_area" class="aipkit_search_results_container"></div>
            </div>
        </div>
    <?php endif; ?>
</div>