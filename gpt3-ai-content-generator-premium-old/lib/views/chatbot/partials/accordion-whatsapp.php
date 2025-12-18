<?php
if (!defined('ABSPATH')) { exit; }

use WPAICG\Lib\WhatsApp\Admin\WhatsApp_Settings;

$connectors = get_option(WhatsApp_Settings::OPTION_KEY, []);
if (!is_array($connectors)) { $connectors = []; }
$saved_map = get_post_meta($bot_id, '_aipkit_whatsapp_connector_ids', true);
if (!is_array($saved_map)) { $saved_map = []; }
$webhook_url = rest_url('aipkit/v1/webhooks/whatsapp');
?>
<div class="aipkit_accordion">
  <div class="aipkit_accordion-header">
    <span class="dashicons dashicons-arrow-right-alt2"></span>
    <?php esc_html_e('WhatsApp', 'gpt3-ai-content-generator'); ?>
  </div>
  <div class="aipkit_accordion-content">
    <!-- Subsection: Webhook -->
    <div class="aipkit_settings_subsection">
      <div class="aipkit_settings_subsection-header">
        <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Webhook', 'gpt3-ai-content-generator'); ?></h5>
      </div>
      <div class="aipkit_settings_subsection-body">
        <div class="aipkit_form-group" style="margin-bottom:0;">
          <label class="aipkit_form-label"><?php esc_html_e('Webhook URL', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" value="<?php echo esc_attr($webhook_url); ?>" readonly>
          <p class="aipkit_form-help"><?php esc_html_e('Use this URL in your Meta app webhook configuration.', 'gpt3-ai-content-generator'); ?></p>
        </div>
      </div>
    </div>

    <!-- Subsection: Connectors -->
    <div class="aipkit_settings_subsection" style="margin-top:12px;">
      <div class="aipkit_settings_subsection-header">
        <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Connectors', 'gpt3-ai-content-generator'); ?></h5>
      </div>
      <div class="aipkit_settings_subsection-body">
        <div class="aipkit_form-group" style="margin-bottom:0;">
          <label class="aipkit_form-label"><?php esc_html_e('Attach Connectors to this Bot', 'gpt3-ai-content-generator'); ?></label>
          <div class="aipkit_form-checkbox-group">
            <?php if (empty($connectors)): ?>
              <p class="description"><?php esc_html_e('No connectors found. Add connectors under Settings > Integrations > WhatsApp.', 'gpt3-ai-content-generator'); ?></p>
            <?php else: foreach ($connectors as $c): $cid = $c['id'] ?? ''; if (!$cid) continue; ?>
              <label class="aipkit_checkbox">
                <input type="checkbox" name="whatsapp_connector_ids[]" value="<?php echo esc_attr($cid); ?>" <?php checked(in_array($cid, $saved_map, true)); ?>>
                <span><?php echo esc_html(($c['label'] ?? $cid) . ' — ' . ($c['phone_number_id'] ?? '')); ?></span>
              </label>
            <?php endforeach; endif; ?>
          </div>
          <p class="aipkit_form-help"><?php esc_html_e('Incoming messages to these numbers will be routed to this bot.', 'gpt3-ai-content-generator'); ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
