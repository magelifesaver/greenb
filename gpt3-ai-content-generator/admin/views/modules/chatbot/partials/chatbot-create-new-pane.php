<?php
/**
 * Partial: Chatbot "Create New" Pane Content
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables passed from parent (chatbot/index.php):
// $createTabContentActiveClass
?>
<div class="aipkit_tab-content <?php echo $createTabContentActiveClass ? 'aipkit_active' : ''; ?>" id="chatbot-create-new-content">
    <div class="aipkit_chatbot-settings-area" style="padding:20px;">
        <h4><?php esc_html_e('Create a New Chatbot','gpt3-ai-content-generator'); ?></h4>
        <p><?php esc_html_e('Start building a new AI chatbot assistant.','gpt3-ai-content-generator'); ?></p>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_new_bot_name"><?php esc_html_e('Chatbot Name','gpt3-ai-content-generator'); ?></label>
            <input
                type="text"
                id="aipkit_new_bot_name"
                name="new_bot_name"
                class="aipkit_form-input"
                placeholder="<?php esc_attr_e('e.g., Lead Generation Bot','gpt3-ai-content-generator'); ?>"
            >
            <div
                id="aipkit_create_bot_message"
                class="aipkit_form-help"
                style="margin-top:10px;"
            ></div>
        </div>
        <button
            id="aipkit_create_new_bot_btn"
            class="aipkit_btn aipkit_btn-primary"
        >
            <span class="aipkit_btn-text"><?php esc_html_e('Start Creating','gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>
