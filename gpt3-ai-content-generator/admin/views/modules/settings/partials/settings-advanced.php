<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/settings-advanced.php
// Status: MODIFIED
// I have added a link to the REST API documentation for the Public API Access section.

/**
 * Partial: Advanced Settings Tab Content
 * Includes accordions for Public API Key, Content Control & Moderation, and Consent settings.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables passed from parent (settings/index.php) are required by the included partials:
// $public_api_key
// $saved_banned_words, $saved_word_notification_message, $placeholder_word_message,
// $saved_banned_ips, $saved_ip_notification_message, $placeholder_ip_message
// $is_pro, $openai_mod_addon_active, $openai_moderation_enabled, $openai_moderation_message, $placeholder_openai_message
// $consent_addon_active, $saved_consent_title, $placeholder_consent_title, $saved_consent_message,
// $placeholder_consent_message, $saved_consent_button, $placeholder_consent_button

?>
<div style="padding-top:20px;"> <?php // Add top padding to tab content?>
    <div class="aipkit_accordion-group">
        <!-- Public API Key Accordion -->
        <div class="aipkit_accordion">
            <div class="aipkit_accordion-header"><span class="dashicons dashicons-arrow-right-alt2"></span><?php echo esc_html__('Public API Access', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_accordion-content">
                <div class="aipkit_form-group">
                     <label class="aipkit_form-label" for="aipkit_public_api_key"><?php esc_html_e('Public API Key', 'gpt3-ai-content-generator'); ?></label>
                     <div class="aipkit_api-key-wrapper">
                         <input
                            type="password"
                            id="aipkit_public_api_key"
                            name="public_api_key"
                            class="aipkit_form-input aipkit_autosave_trigger"
                            value="<?php echo esc_attr($public_api_key); ?>"
                            placeholder="<?php esc_attr_e('Leave blank to disable public API access', 'gpt3-ai-content-generator'); ?>"
                        />
                         <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
                    </div>
                </div>
                <p class="aipkit_form-help">
                    <?php esc_html_e('Set a key to allow external access to certain plugin features via the REST API. Keep this key secure.', 'gpt3-ai-content-generator'); ?>
                     <a href="https://docs.aipower.org/docs/api-reference" target="_blank" rel="noopener noreferrer"><?php esc_html_e('API Documentation', 'gpt3-ai-content-generator'); ?></a>
                </p>
            </div>
        </div>


        <!-- Content Control & Moderation Accordion -->
        <div class="aipkit_accordion">
            <div class="aipkit_accordion-header"><span class="dashicons dashicons-arrow-right-alt2"></span><?php echo esc_html__('Content Control & Moderation', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_accordion-content">
                <?php // This partial now contains Banned Words/IPs?>
                <?php include __DIR__ . '/settings-advanced-access.php'; ?>

                <?php // Conditionally include OpenAI Moderation settings?>
                <?php if ($openai_mod_addon_active): ?>
                    
                    <?php
                    // UPDATED INCLUDE PATH
                    $moderation_partial_path = WPAICG_LIB_DIR . 'views/settings/partials/settings-advanced-moderation.php';
                    if (file_exists($moderation_partial_path)) {
                        include $moderation_partial_path;
                    }
?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Consent Compliance Accordion (only show if addon is active AND user is Pro) -->
        <?php if ($consent_addon_active): ?>
        <div class="aipkit_accordion">
            <div class="aipkit_accordion-header"><span class="dashicons dashicons-arrow-right-alt2"></span><?php echo esc_html__('Consent Compliance', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_accordion-content">
                <?php
                // UPDATED INCLUDE PATH
                $consent_partial_path = WPAICG_LIB_DIR . 'views/settings/partials/settings-advanced-consent.php';
            if (file_exists($consent_partial_path)) {
                include $consent_partial_path;
            }
?>
            </div>
        </div>
        <?php endif; ?>
    </div> <!-- End aipkit_accordion-group -->
</div>