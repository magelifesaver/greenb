<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/replicate.php
// Status: NEW FILE

/**
 * Partial: Replicate API integration settings.
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent: $current_replicate_api_key
?>
<!-- Replicate API Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Replicate', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_replicate_api_key"><?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <div class="aipkit_api-key-wrapper">
                    <input
                        type="password"
                        id="aipkit_replicate_api_key"
                        name="replicate_api_key"
                        class="aipkit_form-input aipkit_autosave_trigger"
                        value="<?php echo esc_attr($current_replicate_api_key); ?>"
                        placeholder="<?php esc_attr_e('Enter your Replicate API key', 'gpt3-ai-content-generator'); ?>"
                    />
                    <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
                </div>
                <a href="https://replicate.com/account/api-tokens" target="_blank" rel="noopener noreferrer" class="aipkit_btn aipkit_btn-secondary aipkit_get_key_btn">
                     <span class="aipkit_btn-text"><?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?></span>
                </a>
                 <button
                    type="button"
                    id="aipkit_sync_replicate_models_btn"
                    class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn"
                    data-provider="Replicate"
                    title="<?php esc_attr_e('Sync available models from Replicate', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="aipkit_btn-text"><?php esc_html_e('Sync Models', 'gpt3-ai-content-generator'); ?></span>
                    <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>