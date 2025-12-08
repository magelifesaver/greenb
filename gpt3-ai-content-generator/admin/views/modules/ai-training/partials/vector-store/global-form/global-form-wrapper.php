<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-training/partials/vector-store/global-form/global-form-wrapper.php
// Status: REDESIGNED - Modern Minimal Card Style

/**
 * Partial: AI Training - Global Add Content Form
 * REDESIGNED: Modern minimal card-style form with compact layout
 */

if (!defined('ABSPATH')) {
    exit;
}
// Variables from parent vector-store.php:
// $all_selectable_post_types, $pinecone_api_key_is_set, $qdrant_url_is_set, $qdrant_api_key_is_set
?>
<div class="aipkit_add_content_card">
    <div class="aipkit_add_content_card_body">
        <!-- Form Controls - Top Row (Existing mode first section) -->
        <div class="aipkit_form_controls_compact">
            <div class="aipkit_form_row_compact aipkit_form_single_row">
                <div class="aipkit_form_group_compact" id="aipkit_vs_global_provider_group">
                    <label class="aipkit_form_label_compact" for="aipkit_vs_global_provider_select">
                        <?php esc_html_e('Vector DB', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipkit_vs_global_provider_select" class="aipkit_form_input_compact">
                        <option value="openai" selected>OpenAI</option>
                        <option value="pinecone" <?php disabled(!$pinecone_api_key_is_set); ?>>Pinecone</option>
                        <option value="qdrant" <?php disabled(!$qdrant_url_is_set || !$qdrant_api_key_is_set); ?>>Qdrant</option>
                    </select>
                </div>
                <div class="aipkit_form_group_compact" id="aipkit_vs_global_embedding_model_inline_group" style="display: none;">
                    <label class="aipkit_form_label_compact" for="aipkit_vs_global_embedding_model_select">
                        <?php esc_html_e('Embedding', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipkit_vs_global_embedding_model_select" class="aipkit_form_input_compact">
                        <option value=""><?php esc_html_e('-- Select Model --', 'gpt3-ai-content-generator'); ?></option>
                        <?php // Optgroups and options populated by JS?>
                    </select>
                </div>
                
                <div class="aipkit_form_group_compact aipkit_target_group" id="aipkit_vs_global_target_group">
                    <label class="aipkit_form_label_compact" id="aipkit_vs_global_target_label" for="aipkit_vs_global_target_select">
                        <span class="dashicons dashicons-admin-site"></span>
                        <?php esc_html_e('Target Store', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <div class="aipkit_input_with_actions">
                        <select id="aipkit_vs_global_target_select" class="aipkit_form_input_compact">
                            <option value=""><?php esc_html_e('-- Select Target --', 'gpt3-ai-content-generator'); ?></option>
                            <?php // Options populated by JS?>
                        </select>
                        <div class="aipkit_input_actions">
                            <button type="button" id="aipkit_vs_global_refresh_data_btn" class="aipkit_btn_icon" title="<?php esc_attr_e('Sync Stores', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-image-rotate"></span>
                                <span class="aipkit_spinner" style="display:none;"></span>
                            </button>
                            <button type="button" id="aipkit_vs_global_add_openai_store_btn" class="aipkit_btn_icon" title="<?php esc_attr_e('Create New Store', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                            </button>
                            <button type="button" id="aipkit_vs_global_add_pinecone_index_btn" class="aipkit_btn_icon" title="<?php esc_attr_e('Create New Index', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                            </button>
                            <button type="button" id="aipkit_vs_global_add_qdrant_collection_btn" class="aipkit_btn_icon" title="<?php esc_attr_e('Create New Collection', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Create via modal: plus button opens a modal to create knowledge base -->

        <!-- Source Selection Cards - Now placed under provider/embedding/target controls -->
        <div class="aipkit_form_controls_compact" id="aipkit_vs_global_source_controls">
            <div class="aipkit_form_group_compact aipkit_source_selection_group" id="aipkit_vs_global_source_group">
                <label class="aipkit_form_label_compact">
                    <?php esc_html_e('Source', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_source_cards">
                    <div class="aipkit_source_card aipkit_source_card_active" data-source="text_entry" role="button" tabindex="0">
                        <span class="aipkit_source_card_icon" aria-hidden="true">
                            <span class="dashicons dashicons-editor-paste-text"></span>
                        </span>
                        <span class="aipkit_source_card_label"><?php esc_html_e('Text', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                    <div class="aipkit_source_card" data-source="file_upload" role="button" tabindex="0">
                        <span class="aipkit_source_card_icon" aria-hidden="true">
                            <span class="dashicons dashicons-upload"></span>
                        </span>
                        <span class="aipkit_source_card_label"><?php esc_html_e('File Upload', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                    <div class="aipkit_source_card" data-source="wordpress_content" role="button" tabindex="0">
                        <span class="aipkit_source_card_icon" aria-hidden="true">
                            <span class="dashicons dashicons-admin-post"></span>
                        </span>
                        <span class="aipkit_source_card_label"><?php esc_html_e('Site Content', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                </div>
                <!-- Hidden select for form compatibility -->
                <select id="aipkit_vs_global_data_source" class="aipkit_form_input_compact" style="display: none;">
                    <option value="text_entry" selected><?php esc_html_e('Text', 'gpt3-ai-content-generator'); ?></option>
                    <option value="file_upload"><?php esc_html_e('Files', 'gpt3-ai-content-generator'); ?></option>
                    <option value="wordpress_content"><?php esc_html_e('Site Content', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
        </div>

        <!-- Content Input Area -->
    <div class="aipkit_content_input_area" id="aipkit_content_input_area">
            <div id="aipkit_vs_global_text_entry" class="aipkit_content_source_wrapper">
                <label class="aipkit_form_label_compact" for="aipkit_vs_global_text_content">
                    <?php esc_html_e('Text Content', 'gpt3-ai-content-generator'); ?>
                </label>
                <textarea id="aipkit_vs_global_text_content" class="aipkit_form_textarea_compact" rows="6" placeholder="<?php esc_attr_e('Paste or type your content here...', 'gpt3-ai-content-generator'); ?>"></textarea>
            </div>
            
            <div id="aipkit_vs_global_file_upload" class="aipkit_content_source_wrapper" style="display:none;">
                <label class="aipkit_form_label_compact" for="aipkit_vs_global_file_to_submit">
                    <?php esc_html_e('Upload Files', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_file_upload_wrapper">
                    <!-- Minimal, clean drag & drop area -->
                    <div id="aipkit_vs_global_dropzone" aria-label="<?php echo esc_attr__('Drag and drop files here or click to browse', 'gpt3-ai-content-generator'); ?>" role="button" tabindex="0"
                         style="border:1px dashed #c3c4c7;border-radius:6px;padding:16px;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;background:#fff;">
                        <span class="dashicons dashicons-upload" aria-hidden="true" style="opacity:.8;"></span>
                        <span style="font-size:13px;color:#444;">
                            <?php esc_html_e('Drag & drop files, or click to browse', 'gpt3-ai-content-generator'); ?>
                        </span>
                    </div>
                        <div class="aipkit_supported_file_types" style="margin-top:6px;font-size:12px;color:#646970;">
                            <?php esc_html_e('Supported: TXT, PDF, HTML, DOCX', 'gpt3-ai-content-generator'); ?>
                        </div>
                    <!-- Keep the actual input for accessibility; visually de-emphasized -->
                    <input type="file" id="aipkit_vs_global_file_to_submit" class="aipkit_form_input_compact" accept=".txt,.pdf,.html,.docx,text/plain,application/pdf,application/x-pdf,text/html,application/xhtml+xml,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple style="position:absolute;left:-9999px;width:1px;height:1px;"/>
                    <div id="aipkit_vs_global_submit_upload_limits_info" class="aipkit_upload_limits_info" style="display:none;margin-top:8px;"></div>
                </div>
                <!-- Minimal queued files list; populated by JS when files are selected -->
                <div id="aipkit_vs_global_file_queue" class="aipkit_file_queue" aria-live="polite" style="margin-top:8px;"></div>
                <!-- Removed overall progress and cancel-all to keep UI minimal -->
                <div id="aipkit_vs_global_file_upload_pro_notice" class="aipkit_notice aipkit_notice-info" style="display:none;">
                    <?php // Content populated by JS ?>
                </div>
            </div>
            
            <div id="aipkit_vs_global_wp_content_selector" class="aipkit_content_source_wrapper" style="display:none;">
                <!-- Mode Switcher: Bulk by Type (default) vs Pick Specific -->
                <div class="aipkit_form_controls_compact" style="margin-bottom:8px;">
                    <div class="aipkit_form_row_compact aipkit_form_single_row">
                        <div class="aipkit_form_group_compact">
                            <label class="aipkit_form_label_compact"><?php esc_html_e('Mode', 'gpt3-ai-content-generator'); ?></label>
                            <div class="aipkit_inline_switch">
                                <label style="margin-right:12px;">
                                    <input type="radio" name="aipkit_wp_content_mode" id="aipkit_wp_content_mode_bulk" value="bulk" checked>
                                    <?php esc_html_e('All by Post Type', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="aipkit_wp_content_mode" id="aipkit_wp_content_mode_specific" value="specific">
                                    <?php esc_html_e('Pick Specific', 'gpt3-ai-content-generator'); ?>
                                </label>
                            </div>
                            <input type="hidden" id="aipkit_vs_wp_content_mode" value="bulk" />
                        </div>
                    </div>
                </div>

                <!-- BULK PANEL: Checkboxes for post types + status, minimal UI -->
                <div id="aipkit_wp_content_bulk_panel">
                    <div class="aipkit_wp_content_filters_compact">
                        <div class="aipkit_form_row_compact">
                            <div class="aipkit_form_group_compact">
                                <label class="aipkit_form_label_compact"><?php esc_html_e('Post Types', 'gpt3-ai-content-generator'); ?></label>
                                <div id="aipkit_vs_wp_types_checkboxes" class="aipkit_checkbox_grid">
                                    <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                        <label class="aipkit_checkbox-label" data-ptype="<?php echo esc_attr($post_type_slug); ?>" style="margin-right:12px;">
                                            <input type="checkbox" class="aipkit_wp_type_cb" value="<?php echo esc_attr($post_type_slug); ?>" <?php checked(in_array($post_type_slug, ['post', 'page'], true)); ?> />
                                            <span class="aipkit_checkbox_text"><?php echo esc_html($post_type_obj->label); ?></span>
                                            <span class="aipkit_count_badge" aria-live="polite" aria-label="<?php esc_attr_e('Count', 'gpt3-ai-content-generator'); ?>" data-count="-1"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Hidden multi-select kept for compatibility with existing JS -->
                                <select id="aipkit_vs_wp_content_post_types" class="aipkit_form_input_compact" multiple size="3" style="display:none;">
                                    <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                        <option value="<?php echo esc_attr($post_type_slug); ?>" <?php selected(in_array($post_type_slug, ['post', 'page'], true)); ?>>
                                            <?php echo esc_html($post_type_obj->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="aipkit_form_group_compact">
                                <label for="aipkit_vs_wp_content_status" class="aipkit_form_label_compact"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_vs_wp_content_status" class="aipkit_form_input_compact">
                                    <option value="publish"><?php esc_html_e('Published', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="draft"><?php esc_html_e('Draft', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="any"><?php esc_html_e('Any', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div id="aipkit_wp_bulk_hint" class="aipkit_text-secondary" style="font-size:12px;margin-top:4px;">
                            <?php esc_html_e('Click Add to start. You can stop anytime.', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                </div>

                <!-- SPECIFIC PANEL: existing filters + list + pagination -->
                <div id="aipkit_wp_content_specific_panel" style="display:none;">
                    <div class="aipkit_wp_content_filters_compact">
                        <div class="aipkit_form_row_compact">
                            <div class="aipkit_form_group_compact">
                                <label for="aipkit_vs_wp_content_post_types_specific" class="aipkit_form_label_compact"><?php esc_html_e('Post Types', 'gpt3-ai-content-generator'); ?></label>
                                <!-- Reuse the same select element but show it only in specific mode -->
                                <select id="aipkit_vs_wp_content_post_types_specific" class="aipkit_form_input_compact" multiple size="3">
                                    <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                        <option value="<?php echo esc_attr($post_type_slug); ?>" <?php selected(in_array($post_type_slug, ['post', 'page'], true)); ?>>
                                            <?php echo esc_html($post_type_obj->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="aipkit_form_group_compact">
                                <label for="aipkit_vs_wp_content_status_specific" class="aipkit_form_label_compact"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_vs_wp_content_status_specific" class="aipkit_form_input_compact">
                                    <option value="publish"><?php esc_html_e('Published', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="draft"><?php esc_html_e('Draft', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="any"><?php esc_html_e('Any', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </div>
                            <div class="aipkit_form_group_compact aipkit_load_content_group">
                                <label class="aipkit_form_label_compact"><?php esc_html_e('Action', 'gpt3-ai-content-generator'); ?></label>
                                <button type="button" id="aipkit_vs_load_wp_content_btn" class="aipkit_btn_compact aipkit_btn_secondary">
                                    <span class="aipkit_btn-text"><?php esc_html_e('Load Content', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_spinner" style="display:none;"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="aipkit_vs_wp_content_list_area" class="aipkit_wp_content_list_area">
                        <p class="aipkit_text_placeholder"><?php esc_html_e('Select criteria and click "Load Content".', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    <div id="aipkit_vs_wp_content_pagination" class="aipkit_wp_content_pagination"></div>
                </div>

                <div id="aipkit_vs_wp_content_messages_area" class="aipkit_wp_content_status" style="margin-top: 10px; min-height:1.5em;"></div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="aipkit_add_content_card_footer">
        <div class="aipkit_action_buttons">
            <button type="button" id="aipkit_vs_global_submit_data_btn" class="aipkit_btn_compact aipkit_btn_primary" disabled>
                <span class="aipkit_btn-text"><?php esc_html_e('Add', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
            <!-- Shown when user selects File Upload on a non‑Pro plan -->
            <button type="button" id="aipkit_vs_global_upgrade_to_pro_btn" class="aipkit_btn_compact aipkit_btn_primary" style="display:none;">
                <?php esc_html_e('Upgrade to Pro', 'gpt3-ai-content-generator'); ?>
            </button>
            <button type="button" id="aipkit_vs_global_stop_indexing_btn" class="aipkit_btn_compact aipkit_btn_danger" style="display:none;">
                <span class="dashicons dashicons-controls-stop"></span>
                <?php esc_html_e('Stop', 'gpt3-ai-content-generator'); ?>
            </button>
            <button type="button" id="aipkit_vs_global_cancel_add_content_btn" class="aipkit_btn_compact aipkit_btn_secondary">
                <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
    </div>
</div>