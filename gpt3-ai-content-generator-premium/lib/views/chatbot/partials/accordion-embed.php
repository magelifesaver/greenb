<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/chatbot/partials/accordion-embed.php
// Status: NEW FILE (Moved & Modified)

/**
 * Partial: Chatbot Embed Anywhere Accordion Content
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script (chatbot-settings-pane.php):
// $bot_id, $bot_settings

$embed_script_url = WPAICG_PLUGIN_URL . 'dist/js/embed-bootstrap.bundle.js';
$target_div_id = 'aipkit-chatbot-container-' . esc_attr($bot_id);

// This self-executing JavaScript function creates the target div and then loads the main bootstrap script.
$embed_code = sprintf(
    '(function() { var d = document; var c = d.createElement("div"); c.id = "%s"; var s = d.createElement("script"); s.src = "%s"; s.setAttribute("data-bot-id", "%d"); s.setAttribute("data-wp-site", "%s"); s.async = true; var t = d.currentScript || d.getElementsByTagName("script")[0]; t.parentNode.insertBefore(c, t); t.parentNode.insertBefore(s, t); })();',
    esc_js($target_div_id),
    esc_js($embed_script_url),
    esc_js($bot_id),
    esc_js(home_url())
);


$embed_code = '<script type="text/javascript">' . $embed_code . '</script>';

// Get allowed domains setting
$allowed_domains = $bot_settings['embed_allowed_domains'] ?? '';

?>
<div class="aipkit_accordion" data-section="embed">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Embed Anywhere', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <!-- Subsection: Embed Code -->
        <div class="aipkit_settings_subsection">
          <div class="aipkit_settings_subsection-header">
            <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Embed Code', 'gpt3-ai-content-generator'); ?></h5>
          </div>
          <div class="aipkit_settings_subsection-body">
            <p class="aipkit_form-help" style="margin-top:0;">
              <?php esc_html_e('Copy and paste this code snippet into any website\'s HTML where you want the chatbot to appear (e.g., before the closing </body> tag).', 'gpt3-ai-content-generator'); ?>
            </p>
            <div class="aipkit_form-group">
              <label class="aipkit_form-label" for="aipkit_embed_code_full_<?php echo esc_attr($bot_id); ?>">
                <?php esc_html_e('Code', 'gpt3-ai-content-generator'); ?>
              </label>
              <textarea
                id="aipkit_embed_code_full_<?php echo esc_attr($bot_id); ?>"
                class="aipkit_form-input"
                rows="5"
                readonly
                onclick="this.select();"
              ><?php echo esc_textarea($embed_code); ?></textarea>
              <button
                type="button"
                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_copy_embed_code_btn"
                data-target="aipkit_embed_code_full_<?php echo esc_attr($bot_id); ?>"
              >
                <span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Copy Code', 'gpt3-ai-content-generator'); ?>
              </button>
            </div>
          </div>
        </div>

        <!-- Subsection: Allowed Domains -->
        <div class="aipkit_settings_subsection" style="margin-top:12px;">
          <div class="aipkit_settings_subsection-header">
            <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Allowed Domains', 'gpt3-ai-content-generator'); ?></h5>
          </div>
          <div class="aipkit_settings_subsection-body">
            <div class="aipkit_form-group" style="margin-bottom:0;">
              <label class="aipkit_form-label" for="aipkit_embed_allowed_domains_<?php echo esc_attr($bot_id); ?>">
                <?php esc_html_e('Domains', 'gpt3-ai-content-generator'); ?>
              </label>
              <textarea
                id="aipkit_embed_allowed_domains_<?php echo esc_attr($bot_id); ?>"
                name="embed_allowed_domains"
                class="aipkit_form-input"
                rows="3"
                placeholder="<?php esc_attr_e('e.g., https://example.com, https://www.another-domain.org', 'gpt3-ai-content-generator'); ?>"
              ><?php echo esc_textarea($allowed_domains); ?></textarea>
              <div class="aipkit_form-help">
                <?php esc_html_e('Enter full domains (including https://) separated by commas or new lines. Leave empty to allow all domains.', 'gpt3-ai-content-generator'); ?>
              </div>
            </div>
          </div>
        </div>
    </div>
</div>
