<?php
/**
 * Partial: AI Training - Vector Store Empty State
 * Displays when no knowledge bases (vector stores) exist.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_empty_state_container">
    <div class="aipkit_empty_state_icon">
        <span class="dashicons dashicons-database"></span>
    </div>
    <h4 class="aipkit_empty_state_title"><?php esc_html_e('No Knowledge Bases Found', 'gpt3-ai-content-generator'); ?></h4>
    <p class="aipkit_empty_state_message"><?php esc_html_e('Get started by creating your first knowledge base. Click the "Add Content" button above to populate it with your site content, files, or text.', 'gpt3-ai-content-generator'); ?></p>
</div>