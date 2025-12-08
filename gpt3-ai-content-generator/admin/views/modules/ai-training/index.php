<?php
/**
 * AIPKit AI Training Module (Knowledge Base) - Main View
 * REVISED: The main view now loads a single content area which handles switching
 * between the new knowledge base card view and the detail view.
 */

if (!defined('ABSPATH')) {
    exit;
}

// All necessary variables will be defined within the included partials.
?>
<div class="aipkit_container aipkit_ai_training_container" id="aipkit_ai_training_module_container">
    <!-- The header and tab structure remains the same -->
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_container-title"><?php esc_html_e('AI Training', 'gpt3-ai-content-generator'); ?></div>
            <div id="aipkit_knowledge_base_global_status" class="aipkit_global_status_area"></div>
        </div>
        <div class="aipkit_container-actions">
            <button id="aipkit_toggle_add_content_form_btn" class="aipkit_btn aipkit_btn-primary">
                <?php esc_html_e('Add Content', 'gpt3-ai-content-generator'); ?>
            </button>
            <button id="aipkit_resync_all_providers_btn" class="aipkit_btn aipkit_btn-secondary" title="<?php esc_attr_e('Sync and fetch all indexes from OpenAI, Pinecone, and Qdrant providers', 'gpt3-ai-content-generator'); ?>">
                <span class="aipkit_spinner" style="display:none;"></span>
                <?php esc_html_e('Sync', 'gpt3-ai-content-generator'); ?>
            </button>
            <div class="aipkit_kb_view_toggle" title="<?php esc_attr_e('Choose how to display knowledge bases', 'gpt3-ai-content-generator'); ?>" style="display:none;" role="group" aria-label="<?php esc_attr_e('View style', 'gpt3-ai-content-generator'); ?>">
                <button id="aipkit_kb_view_cards_btn" type="button" class="aipkit_btn aipkit_icon_btn aipkit_btn-small" title="<?php esc_attr_e('Cards view', 'gpt3-ai-content-generator'); ?>" aria-pressed="false">
                    <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php esc_html_e('Cards view', 'gpt3-ai-content-generator'); ?></span>
                </button>
                <button id="aipkit_kb_view_list_btn" type="button" class="aipkit_btn aipkit_icon_btn aipkit_btn-small" title="<?php esc_attr_e('List view', 'gpt3-ai-content-generator'); ?>" aria-pressed="false">
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php esc_html_e('List view', 'gpt3-ai-content-generator'); ?></span>
                </button>
                <!-- Hidden fallback select for backward compatibility (not displayed) -->
                <label for="aipkit_kb_view_style" class="screen-reader-text"><?php esc_html_e('View style', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_kb_view_style" style="display:none;">
                    <option value="cards"><?php esc_html_e('Cards', 'gpt3-ai-content-generator'); ?></option>
                    <option value="list"><?php esc_html_e('List', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
        </div>
    </div>
    <div class="aipkit_tabs aipkit_main_tabs">
        <div class="aipkit_tab aipkit_active" data-tab="knowledge-base-tab"><?php esc_html_e('Knowledge Base', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_tab" data-tab="indexing-settings-tab"><?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?></div>
    </div>
    <div class="aipkit_tab_content_container">
        <!-- The "Knowledge Base" tab now loads the main vector-store partial which controls the new UI -->
        <div class="aipkit_tab-content aipkit_active" id="knowledge-base-tab-content">
            <?php include __DIR__ . '/partials/vector-store.php'; ?>
        </div>
        <div class="aipkit_tab-content" id="indexing-settings-tab-content">
            <?php include __DIR__ . '/partials/settings.php'; ?>
        </div>
    </div>
</div>