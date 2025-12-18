<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/settings-ai-forms.php
// Status: MODIFIED

/**
 * Partial: AI Forms Token Management Settings
 * Renders token limit settings for the AI Forms module.
 */

if (!defined('ABSPATH')) {
    exit;
}

// NOTE: This class will be created in a subsequent task.
// Using a placeholder check for now to prevent fatal errors.
if (class_exists('\\WPAICG\\AIForms\\Admin\\AIPKit_AI_Form_Settings_Ajax_Handler')) {
    $settings_data = \WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
} else {
    // Provide default structure if the handler isn't ready yet, so the view doesn't break.
    $settings_data = ['token_management' => [], 'custom_theme' => [], 'frontend_display' => []]; // ADDED frontend_display default
}

use WPAICG\Chat\Storage\BotSettingsManager; // Use for default constants

// Get token management settings from the settings data
$token_settings = $settings_data['token_management'] ?? [];
// --- NEW: Get custom theme settings ---
$custom_theme_settings = $settings_data['custom_theme'] ?? [];
$custom_css = $custom_theme_settings['custom_css'] ?? '';
// --- NEW: Get frontend display settings ---
$frontend_display_settings = $settings_data['frontend_display'] ?? [];
$allowed_providers_str = $frontend_display_settings['allowed_providers'] ?? '';
$allowed_models_str = $frontend_display_settings['allowed_models'] ?? '';


$default_css_template = "/* --- AIPKit AI Forms Custom CSS Example --- */
.aipkit-ai-form-wrapper.aipkit-theme-custom {
    background-color: #f0f4f8;
    border: 1px solid #d1d9e4;
    color: #2c3e50;
}
.aipkit-ai-form-wrapper.aipkit-theme-custom h5 {
    color: #2c3e50;
    border-bottom: 1px solid #d1d9e4;
}
.aipkit-ai-form-wrapper.aipkit-theme-custom .aipkit_btn-primary {
    background-color: #3498db;
    border-color: #2980b9;
}
.aipkit-ai-form-wrapper.aipkit-theme-custom .aipkit_btn-primary:hover {
    background-color: #2980b9;
}
";
// --- END NEW ---

$settings_nonce = wp_create_nonce('aipkit_ai_forms_settings_nonce'); // Nonce for saving these settings

// --- Defaults ---
$default_reset_period = BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD;
$default_limit_message = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MESSAGE;
$default_limit_mode = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;

// --- Get saved values ---
$guest_limit = $token_settings['token_guest_limit'] ?? null;
$user_limit = $token_settings['token_user_limit'] ?? null;
$reset_period = $token_settings['token_reset_period'] ?? $default_reset_period;
$limit_message = $token_settings['token_limit_message'] ?? $default_limit_message;
$limit_mode = $token_settings['token_limit_mode'] ?? $default_limit_mode;
$role_limits = $token_settings['token_role_limits'] ?? [];

$guest_limit_value = ($guest_limit === null) ? '' : (string)$guest_limit;
$user_limit_value = ($user_limit === null) ? '' : (string)$user_limit;
?>
<div class="aipkit_container-body">
    <form id="aipkit_ai_forms_settings_form" onsubmit="return false;" style="max-width: 700px;">
        <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($settings_nonce); ?>">
        <div class="aipkit_accordion-group">
            <div class="aipkit_accordion">
                <div class="aipkit_accordion-header">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e('Token Management', 'gpt3-ai-content-generator'); ?>
                </div>
                <div class="aipkit_accordion-content aipkit_active">
                    <p class="aipkit_form-help" style="margin-top: 0; margin-bottom: 15px;">
                        <?php esc_html_e('Control token usage for the AI Forms module. Limits apply per form submission.', 'gpt3-ai-content-generator'); ?>
                    </p>

                    <div class="aipkit_form-row" style="align-items: flex-start;">
                        <div class="aipkit_form-col aipkit_form-group">
                            <label class="aipkit_form-label" for="aipkit_aiforms_token_guest_limit"><?php esc_html_e('Guest Limit', 'gpt3-ai-content-generator'); ?></label>
                            <input type="number" id="aipkit_aiforms_token_guest_limit" name="aiforms_token_guest_limit" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($guest_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
                            <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
                        </div>

                        <div class="aipkit_form-col aipkit_form-group">
                            <label class="aipkit_form-label" for="aipkit_aiforms_token_limit_mode"><?php esc_html_e('User Limit Type', 'gpt3-ai-content-generator'); ?></label>
                            <select id="aipkit_aiforms_token_limit_mode" name="aiforms_token_limit_mode" class="aipkit_form-input aipkit_token_limit_mode_select">
                                <option value="general" <?php selected($limit_mode, 'general'); ?>><?php esc_html_e('General', 'gpt3-ai-content-generator'); ?></option>
                                <option value="role_based" <?php selected($limit_mode, 'role_based'); ?>><?php esc_html_e('Role-Based', 'gpt3-ai-content-generator'); ?></option>
                            </select>
                        </div>

                        <div class="aipkit_form-col aipkit_form-group aipkit_token_general_user_limit_field" style="display: <?php echo ($limit_mode === 'general') ? 'block' : 'none'; ?>;">
                            <label class="aipkit_form-label" for="aipkit_aiforms_token_user_limit"><?php esc_html_e('General User Limit', 'gpt3-ai-content-generator'); ?></label>
                            <input type="number" id="aipkit_aiforms_token_user_limit" name="aiforms_token_user_limit" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($user_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
                            <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
                        </div>
                        
                        <div class="aipkit_form-col aipkit_form-group">
                            <label class="aipkit_form-label" for="aipkit_aiforms_token_reset_period"><?php esc_html_e('Reset Period', 'gpt3-ai-content-generator'); ?></label>
                            <select id="aipkit_aiforms_token_reset_period" name="aiforms_token_reset_period" class="aipkit_form-input aipkit_settings_input">
                                <option value="never" <?php selected($reset_period, 'never'); ?>><?php esc_html_e('Never', 'gpt3-ai-content-generator'); ?></option>
                                <option value="daily" <?php selected($reset_period, 'daily'); ?>><?php esc_html_e('Daily', 'gpt3-ai-content-generator'); ?></option>
                                <option value="weekly" <?php selected($reset_period, 'weekly'); ?>><?php esc_html_e('Weekly', 'gpt3-ai-content-generator'); ?></option>
                                <option value="monthly" <?php selected($reset_period, 'monthly'); ?>><?php esc_html_e('Monthly', 'gpt3-ai-content-generator'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="aipkit_token_role_limits_container" style="display: <?php echo ($limit_mode === 'role_based') ? 'block' : 'none'; ?>;">
                        <hr class="aipkit_hr" style="margin-top:0; margin-bottom: 15px;">
                        <h4><?php esc_html_e('Role-Based Token Limits', 'gpt3-ai-content-generator'); ?></h4>
                        <div class="aipkit_form-help" style="margin-bottom: 10px;"><?php esc_html_e('Set limits for specific roles. Leave empty for unlimited, use 0 to disable access for a role.', 'gpt3-ai-content-generator'); ?></div>
                        <?php
                        $editable_roles = get_editable_roles();
foreach ($editable_roles as $role_slug => $role_info) :
    $role_name = translate_user_role($role_info['name']);
    $role_limit = $role_limits[$role_slug] ?? null;
    $role_limit_value = ($role_limit === null) ? '' : (string)$role_limit;
    ?>
                            <div class="aipkit_form-group" style="margin-bottom: 8px;">
                                <label class="aipkit_form-label" for="aipkit_aiforms_token_role_<?php echo esc_attr($role_slug); ?>" style="width: 150px; display: inline-block; margin-right: 10px; text-align: right;"><?php echo esc_html($role_name); ?>:</label>
                                <input type="number" id="aipkit_aiforms_token_role_<?php echo esc_attr($role_slug); ?>" name="aiforms_token_role_limits[<?php echo esc_attr($role_slug); ?>]" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($role_limit_value); ?>" min="0" step="1" style="max-width: 150px; display: inline-block;" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr class="aipkit_hr">

                    <div class="aipkit_form-group">
                        <label class="aipkit_form-label" for="aipkit_aiforms_token_limit_message"><?php esc_html_e('Token Limit Message', 'gpt3-ai-content-generator'); ?></label>
                        <input type="text" id="aipkit_aiforms_token_limit_message" name="aiforms_token_limit_message" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($limit_message); ?>" placeholder="<?php echo esc_attr($default_limit_message); ?>" />
                        <div class="aipkit_form-help"><?php esc_html_e('The message shown to users when they exceed their token limit for the period.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Appearance & Theming Accordion -->
            <div class="aipkit_accordion">
                 <div class="aipkit_accordion-header">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php esc_html_e('Custom CSS', 'gpt3-ai-content-generator'); ?>
                </div>
                <div class="aipkit_accordion-content">
                    <p class="aipkit_form-help">
                        <?php esc_html_e('Use the "Custom" theme for a form shortcode to apply these CSS rules.', 'gpt3-ai-content-generator'); ?>
                    </p>
                    <div class="aipkit_form-group">
                        <label class="aipkit_form-label" for="aipkit_aiforms_custom_css"><?php esc_html_e('Custom CSS Rules', 'gpt3-ai-content-generator'); ?></label>
                        <textarea
                            id="aipkit_aiforms_custom_css"
                            name="custom_css"
                            class="aipkit_form-input aipkit_settings_input"
                            rows="15"
                            style="font-family: monospace; font-size: 12px; white-space: pre; overflow-x: auto;"
                            placeholder="<?php echo esc_attr($default_css_template); ?>"
                        ><?php echo esc_textarea($custom_css ?: $default_css_template); ?></textarea>
                         <div class="aipkit_form-help">
                            <?php esc_html_e('Add your custom CSS to style forms using the "custom" theme. Target the wrapper with ".aipkit-ai-form-wrapper.aipkit-theme-custom".', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Frontend Display Accordion -->
            <div class="aipkit_accordion">
                 <div class="aipkit_accordion-header">
                    <span class="dashicons dashicons-desktop"></span>
                    <?php esc_html_e('Provider & Model Filtering', 'gpt3-ai-content-generator'); ?>
                </div>
                <div class="aipkit_accordion-content">
                    <p class="aipkit_form-help">
                        <?php esc_html_e('Control which AI providers and models are available to users on the frontend AI Forms. Leave blank to show all available options.', 'gpt3-ai-content-generator'); ?>
                    </p>
                    <div class="aipkit_form-group">
                        <label class="aipkit_form-label" for="aipkit_aiforms_frontend_providers"><?php esc_html_e('Allowed Providers (comma-separated)', 'gpt3-ai-content-generator'); ?></label>
                        <textarea
                            id="aipkit_aiforms_frontend_providers"
                            name="frontend_providers"
                            class="aipkit_form-input aipkit_settings_input"
                            rows="2"
                            placeholder="<?php esc_attr_e('e.g., OpenAI, Google, Ollama', 'gpt3-ai-content-generator'); ?>"
                        ><?php echo esc_textarea($allowed_providers_str); ?></textarea>
                         <div class="aipkit_form-help">
                            <?php esc_html_e('Enter provider names exactly as they appear (OpenAI, OpenRouter, Google, Azure, DeepSeek, Ollama).', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                    <div class="aipkit_form-group">
                        <label class="aipkit_form-label" for="aipkit_aiforms_frontend_models"><?php esc_html_e('Allowed Models (comma-separated)', 'gpt3-ai-content-generator'); ?></label>
                        <textarea
                            id="aipkit_aiforms_frontend_models"
                            name="frontend_models"
                            class="aipkit_form-input aipkit_settings_input"
                            rows="4"
                            placeholder="<?php esc_attr_e('e.g., gpt-4o, gpt-4-turbo, gemini-1.5-pro-latest', 'gpt3-ai-content-generator'); ?>"
                        ><?php echo esc_textarea($allowed_models_str); ?></textarea>
                         <div class="aipkit_form-help">
                            <?php esc_html_e('Enter the exact model IDs. This will filter models across all selected providers.', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Save Button -->
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--aipkit_container-border);">
            <div id="aipkit_aiforms_settings_save_status" class="aipkit_form-help" style="min-height: 1.5em; margin-bottom: 8px;"></div>
            <button type="button" id="aipkit_save_ai_forms_settings_btn" class="aipkit_btn aipkit_btn-primary">
                <span class="aipkit_btn-text"><?php esc_html_e('Save', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
        </div>
    </form>
</div>