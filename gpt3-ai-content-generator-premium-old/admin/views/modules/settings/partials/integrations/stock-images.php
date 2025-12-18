<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/stock-images.php
// Status: NEW FILE

/**
 * Partial: Stock Image API integration settings (Pixabay, Pexels).
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent: $current_pixabay_api_key, $current_pexels_api_key
?>
<!-- Stock Images Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Stock Images', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_pixabay_api_key"><?php esc_html_e('Pixabay API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <div class="aipkit_api-key-wrapper">
                    <input
                        type="password"
                        id="aipkit_pixabay_api_key"
                        name="pixabay_api_key"
                        class="aipkit_form-input aipkit_autosave_trigger"
                        value="<?php echo esc_attr($current_pixabay_api_key); ?>"
                        placeholder="<?php esc_attr_e('Enter your Pixabay API key', 'gpt3-ai-content-generator'); ?>"
                    />
                    <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
                </div>
                <a href="https://pixabay.com/api/docs/" target="_blank" rel="noopener noreferrer" class="aipkit_btn aipkit_btn-secondary aipkit_get_key_btn">
                     <span class="aipkit_btn-text"><?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?></span>
                </a>
            </div>
        </div>

        <hr class="aipkit_hr">

        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_pexels_api_key"><?php esc_html_e('Pexels API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <div class="aipkit_api-key-wrapper">
                    <input
                        type="password"
                        id="aipkit_pexels_api_key"
                        name="pexels_api_key"
                        class="aipkit_form-input aipkit_autosave_trigger"
                        value="<?php echo esc_attr($current_pexels_api_key); ?>"
                        placeholder="<?php esc_attr_e('Enter your Pexels API key', 'gpt3-ai-content-generator'); ?>"
                    />
                    <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
                </div>
                <a href="https://www.pexels.com/api/" target="_blank" rel="noopener noreferrer" class="aipkit_btn aipkit_btn-secondary aipkit_get_key_btn">
                     <span class="aipkit_btn-text"><?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>