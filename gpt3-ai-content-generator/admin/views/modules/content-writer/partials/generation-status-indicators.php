<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/generation-status-indicators.php
// Status: MODIFIED
// I have added a new step for "Tags" to the generation status indicators.

/**
 * Partial: Content Writer - Generation Status Indicators
 * Displays modern visual progress indicators for the content generation workflow.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_cw_generation_status_indicators">
    <div class="aipkit_cw_progress_workflow">
        <!-- Step 1: Title Generation -->
        <div id="aipkit_cw_status_title_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-text-page aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Title', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
        
        <!-- Step 2: Content Generation -->
        <div id="aipkit_cw_status_content_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-admin-post aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Content', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
        
        <!-- Step 3: Excerpt Generation -->
        <div id="aipkit_cw_status_excerpt_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-editor-quote aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Excerpt', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>

        <!-- NEW Step: Tags Generation -->
        <div id="aipkit_cw_status_tags_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-tag aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Tags', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
        
        <!-- Step 4: Image Generation -->
        <div id="aipkit_cw_status_image_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-format-image aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Images', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
        
        <!-- Step 5: Focus Keyword Generation -->
        <div id="aipkit_cw_status_keyword_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-flag aipkit_cw_status_icon"></span>
                </div>
                <span class="aipkit_step_connector"></span>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Focus Keyword', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
        
        <!-- Step 6: Meta Description Generation -->
        <div id="aipkit_cw_status_meta_generation" class="aipkit_cw_status_step">
            <div class="aipkit_step_indicator">
                <div class="aipkit_step_icon">
                    <span class="dashicons dashicons-search aipkit_cw_status_icon"></span>
                </div>
            </div>
            <div class="aipkit_step_content">
                <div class="aipkit_step_label"><?php esc_html_e('Meta Description', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_step_status"><span class="status-text"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></span></div>
            </div>
        </div>
    </div>
</div>