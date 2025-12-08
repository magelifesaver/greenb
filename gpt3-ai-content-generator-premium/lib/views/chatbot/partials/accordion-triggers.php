<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/chatbot/partials/accordion-triggers.php
// Status: MODIFIED

/**
 * Partial: Chatbot Triggers Accordion Content
 * UPDATED: Now includes the trigger-builder-main.php partial instead of a textarea.
 *          The original textarea is kept but hidden, for JS to populate before form save.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script:
// $bot_id, $bot_settings

$triggers_json = $bot_settings['triggers_json'] ?? '[]'; // Default to an empty JSON array string

?>
<div class="aipkit_accordion" data-section="triggers">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Triggers (Beta)', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_settings_subsection">
          <div class="aipkit_settings_subsection-header">
            <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Trigger Rules', 'gpt3-ai-content-generator'); ?></h5>
          </div>
          <div class="aipkit_settings_subsection-body">
            <?php
            // Include the new UI builder view
            $trigger_builder_view_path = WPAICG_LIB_DIR . 'views/chatbot/partials/triggers/trigger-builder-main.php';
            if (file_exists($trigger_builder_view_path)) {
                include $trigger_builder_view_path;
            } else {
                echo '<p style="color:red;">Error: Trigger builder UI component is missing.</p>';
            }
            ?>
            <?php // This textarea is now primarily for JS to populate before the main form save. ?>
            <?php // It will be hidden by CSS if the JS UI builder loads successfully. ?>
            <textarea
                id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_triggers_json"
                name="triggers_json" <?php // This name is critical for chat-admin-saver.js ?>
                class="aipkit_form-input"
                rows="3"
                style="display:none; width:100%; min-height: 60px; font-family: monospace; font-size: 11px; white-space: pre; overflow-wrap: normal; overflow-x: auto;"
                aria-hidden="true"
                tabindex="-1"
            ><?php echo esc_textarea($triggers_json); ?></textarea>
            <div class="aipkit_form-help" style="margin-top:5px;">
                <?php esc_html_e('Use the UI above to configure triggers.', 'gpt3-ai-content-generator'); ?>
                <a href="https://docs.aipower.org/docs/triggers" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'gpt3-ai-content-generator'); ?></a>
            </div>
          </div>
        </div>
    </div>
</div>
