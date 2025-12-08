<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/labels-config.php
// Status: NEW FILE

/**
 * Partial: AI Form Editor - Labels & Text Configuration
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_generate_button"><?php esc_html_e('Generate', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_generate_button" name="labels[generate_button]" class="aipkit_form-input" placeholder="<?php esc_attr_e('Generate', 'gpt3-ai-content-generator'); ?>">
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_stop_button"><?php esc_html_e('Stop', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_stop_button" name="labels[stop_button]" class="aipkit_form-input" placeholder="<?php esc_attr_e('Stop', 'gpt3-ai-content-generator'); ?>">
    </div>
</div>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_download_button"><?php esc_html_e('Download', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_download_button" name="labels[download_button]" class="aipkit_form-input" placeholder="<?php esc_attr_e('Download', 'gpt3-ai-content-generator'); ?>">
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_save_button"><?php esc_html_e('Save', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_save_button" name="labels[save_button]" class="aipkit_form-input" placeholder="<?php esc_attr_e('Save', 'gpt3-ai-content-generator'); ?>">
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_copy_button"><?php esc_html_e('Copy', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_copy_button" name="labels[copy_button]" class="aipkit_form-input" placeholder="<?php esc_attr_e('Copy', 'gpt3-ai-content-generator'); ?>">
    </div>
</div>
<hr class="aipkit_hr">
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_provider_label"><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_provider_label" name="labels[provider_label]" class="aipkit_form-input" placeholder="<?php esc_attr_e('AI Provider', 'gpt3-ai-content-generator'); ?>">
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aif_label_model_label"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aif_label_model_label" name="labels[model_label]" class="aipkit_form-input" placeholder="<?php esc_attr_e('AI Model', 'gpt3-ai-content-generator'); ?>">
    </div>
</div>