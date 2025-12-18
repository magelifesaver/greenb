<?php
if (!defined('ABSPATH')) { exit; }

use WPAICG\Lib\WhatsApp\Admin\WhatsApp_Settings;

$webhook_url = rest_url('aipkit/v1/webhooks/whatsapp');
?>
<div class="aipkit_accordion">
  <div class="aipkit_accordion-header">
    <span class="dashicons dashicons-arrow-right-alt2"></span>
    <?php echo esc_html__('WhatsApp', 'gpt3-ai-content-generator'); ?>
  </div>
  <div class="aipkit_accordion-content">
    <div class="aipkit_settings-section">
      <p><strong><?php esc_html_e('Webhook URL:', 'gpt3-ai-content-generator'); ?></strong> <code><?php echo esc_html($webhook_url); ?></code></p>
      <p class="description"><?php esc_html_e('Paste this URL into your Meta App Webhooks (WhatsApp) configuration.', 'gpt3-ai-content-generator'); ?></p>

      <hr class="aipkit_hr" />

      <div class="aipkit_form-group">
        <div class="aipkit_form-row" style="justify-content: space-between; align-items:center;">
          <div><strong><?php esc_html_e('Connectors', 'gpt3-ai-content-generator'); ?></strong></div>
          <div>
            <button type="button" class="aipkit_btn aipkit_btn-secondary" id="aipkit_whatsapp_load_btn"><?php esc_html_e('Load Existing', 'gpt3-ai-content-generator'); ?></button>
            <button type="button" class="aipkit_btn aipkit_btn-secondary" id="aipkit_whatsapp_add_btn"><?php esc_html_e('Add Connector', 'gpt3-ai-content-generator'); ?></button>
          </div>
        </div>
      </div>

      <div id="aipkit_whatsapp_connectors_container"></div>
      <input type="hidden" id="aipkit_whatsapp_nonce" value="<?php echo esc_attr( wp_create_nonce( WhatsApp_Settings::NONCE_ACTION ) ); ?>" />

      <div style="margin-top:10px;">
        <span class="aipkit_spinner" id="aipkit_whatsapp_spinner" style="display:none;"></span>
        <div id="aipkit_whatsapp_status" class="aipkit_form-help"></div>
      </div>
      
    </div>
  </div>
</div>

<template id="aipkit_whatsapp_connector_tpl">
  <div class="aipkit_sub_container" data-connector>
    <div class="aipkit_sub_container_header" style="display:flex; justify-content:space-between; align-items:center;">
      <div class="aipkit_sub_container_title"><?php esc_html_e('Connector', 'gpt3-ai-content-generator'); ?></div>
      <button type="button" class="aipkit_btn aipkit_btn-small aipkit_btn-danger" data-remove><?php esc_html_e('Remove', 'gpt3-ai-content-generator'); ?></button>
    </div>
    <div class="aipkit_sub_container_body">
      <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Label', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="label" placeholder="Main Number" />
        </div>
      </div>
      <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Phone Number ID', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="phone_number_id" placeholder="1234567890" />
        </div>
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="api_version" placeholder="v20.0" />
        </div>
      </div>
      <input type="hidden" data-field="id" />
      <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Access Token', 'gpt3-ai-content-generator'); ?></label>
          <input type="password" class="aipkit_form-input" data-field="access_token" placeholder="EAAG..." />
        </div>
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('App Secret', 'gpt3-ai-content-generator'); ?></label>
          <input type="password" class="aipkit_form-input" data-field="app_secret" placeholder="xxxxxxxx" />
        </div>
      </div>
      <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Verify Token', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="verify_token" placeholder="my-verify-token" />
        </div>
        
      </div>
      <div class="aipkit_form-row" style="margin-top:8px;">
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Test Recipient (E.164)', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="test_to" placeholder="+1234567890" />
        </div>
        <div class="aipkit_form-group aipkit_form-col">
          <label class="aipkit_form-label"><?php esc_html_e('Test Message', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" class="aipkit_form-input" data-field="test_message" placeholder="Hello from AIP!" />
        </div>
        <div class="aipkit_form-group aipkit_form-col" style="align-self:flex-end;">
          <button type="button" class="aipkit_btn aipkit_btn-small aipkit_btn-primary" data-test-send><?php esc_html_e('Send Test', 'gpt3-ai-content-generator'); ?></button>
        </div>
      </div>
    </div>
  </div>
  <hr class="aipkit_hr"/>
</template>

<!-- JS logic moved to lib/admin/js/settings/whatsapp-settings.js -->
