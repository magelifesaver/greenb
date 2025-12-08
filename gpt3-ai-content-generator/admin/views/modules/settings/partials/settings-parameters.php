<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/settings-parameters.php
/**
 * Partial: AI Parameters & Advanced Accordion (Combined)
 */
if (!defined('ABSPATH')) exit;

// Variables required: $current_provider, $max_completion_tokens, $temperature, $top_p
// Also, variables required by the included partials settings-advanced-provider.php and settings-safety-google.php
// must be available here or passed down.
// For settings-advanced-provider.php: $openai_data, $openrouter_data, $google_data, $azure_data, $deepseek_data, $openai_defaults, etc.
// For settings-safety-google.php: $category_thresholds, $safety_thresholds
// $is_pro (from settings/index.php)

use WPAICG\Core\Providers\Google\GoogleSettingsHandler; // For settings-safety-google.php

?>
<div class="aipkit_settings-section">
    <div class="aipkit_accordion"> <?php // Single accordion, no group needed ?>
        <div class="aipkit_accordion-header" id="aipkit_parameters_advanced_settings_header"> <?php // New ID if needed ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
            <?php echo esc_html__('Parameters', 'gpt3-ai-content-generator'); // Renamed title ?>
        </div>
        <div class="aipkit_accordion-content" id="aipkit_parameters_advanced_settings_content"> <?php // New ID if needed ?>

            <!-- Original Parameters: Max Tokens, Temperature, Top P -->
            <div class="aipkit_form-row">
                <!-- Max Tokens Column -->
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_max_completion_tokens"><?php echo esc_html__('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_max_completion_tokens" name="max_completion_tokens" class="aipkit_form-input aipkit_range_slider aipkit_autosave_trigger" min="1" max="128000" step="1" value="<?php echo esc_attr($max_completion_tokens); ?>" />
                        <span id="aipkit_max_completion_tokens_value" class="aipkit_slider_value"><?php echo esc_html($max_completion_tokens); ?></span>
                    </div>
                </div>

                <!-- Temperature Column -->
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_temperature"><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_temperature" name="temperature" class="aipkit_form-input aipkit_range_slider aipkit_autosave_trigger" min="0" max="2" step="0.1" value="<?php echo esc_attr($temperature); ?>" />
                        <span id="aipkit_temperature_value" class="aipkit_slider_value"><?php echo esc_html($temperature); ?></span>
                    </div>
                </div>

                <!-- Top P Column -->
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_top_p"><?php echo esc_html__('Top P', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_top_p" name="top_p" class="aipkit_form-input aipkit_range_slider aipkit_autosave_trigger" min="0" max="1" step="0.01" value="<?php echo esc_attr($top_p); ?>" />
                        <span id="aipkit_top_p_value" class="aipkit_slider_value"><?php echo esc_html($top_p); ?></span>
                    </div>
                </div>
            </div> <!-- End Original Parameters Row -->

            <hr class="aipkit_hr">

            <!-- OpenAI Advanced Settings -->
            <div
                class="aipkit_settings-section aipkit_advanced_settings_provider"
                data-provider-setting="OpenAI"
                style="display: <?php echo ($current_provider === 'OpenAI') ? 'block' : 'none'; ?>;"
            >
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_openai_base_url"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_openai_base_url" name="openai_base_url" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openai_data['base_url']); ?>" />
                            <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openai_defaults['base_url']); ?>" data-target-input="aipkit_openai_base_url">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_openai_api_version"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                         <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_openai_api_version" name="openai_api_version" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openai_data['api_version']); ?>" />
                            <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openai_defaults['api_version']); ?>" data-target-input="aipkit_openai_api_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Store Conversation Checkbox -->
                 <div class="aipkit_form-group aipkit_openai_store_field">
                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_openai_store">
                        <input
                            type="checkbox"
                            id="aipkit_openai_store"
                            name="openai_store_conversation" <?php // Corrected name attribute ?>
                            class="aipkit_autosave_trigger"
                            value="1"
                            <?php checked($openai_store_conversation, '1'); ?>
                        >
                        <?php esc_html_e('Store Conversation', 'gpt3-ai-content-generator'); ?>
                    </label>
                </div>
                <?php // --- ADDED: Expiration Policy Field (Pro only) --- ?>
                <?php if ($is_pro): ?>
                <div class="aipkit_form-group aipkit_openai_expiration_policy_field">
                    <label class="aipkit_form-label" for="aipkit_openai_expiration_policy"><?php esc_html_e('Vector Store File Expiration (Days)', 'gpt3-ai-content-generator'); ?></label>
                    <input
                        type="number"
                        id="aipkit_openai_expiration_policy"
                        name="openai_expiration_policy"
                        class="aipkit_form-input aipkit_autosave_trigger"
                        value="<?php echo esc_attr(isset($openai_data['expiration_policy']) ? $openai_data['expiration_policy'] : 7); ?>"
                        min="1"
                        max="365"
                        step="1"
                        style="max-width: 100px;"
                    />
                    <div class="aipkit_form-help">
                        <?php esc_html_e('Set how many days uploaded files for Vector Stores should be kept before auto-deletion. (1-365).', 'gpt3-ai-content-generator'); ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php // --- END ADDED --- ?>
            </div>

            <!-- OpenRouter Advanced Settings -->
            <div
                class="aipkit_settings-section aipkit_advanced_settings_provider"
                data-provider-setting="OpenRouter"
                style="display: <?php echo ($current_provider === 'OpenRouter') ? 'block' : 'none'; ?>;"
            >
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_openrouter_base_url"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                         <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_openrouter_base_url" name="openrouter_base_url" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openrouter_data['base_url']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openrouter_defaults['base_url']); ?>" data-target-input="aipkit_openrouter_base_url">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                     <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_openrouter_api_version"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_openrouter_api_version" name="openrouter_api_version" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openrouter_data['api_version']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openrouter_defaults['api_version']); ?>" data-target-input="aipkit_openrouter_api_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Google Advanced Settings -->
            <div
                class="aipkit_settings-section aipkit_advanced_settings_provider"
                data-provider-setting="Google"
                style="display: <?php echo ($current_provider === 'Google') ? 'block' : 'none'; ?>;"
            >
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_google_base_url"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_google_base_url" name="google_base_url" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($google_data['base_url']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($google_defaults['base_url']); ?>" data-target-input="aipkit_google_base_url">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                     <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_google_api_version"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                         <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_google_api_version" name="google_api_version" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($google_data['api_version']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($google_defaults['api_version']); ?>" data-target-input="aipkit_google_api_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Azure Advanced Settings -->
            <div
                class="aipkit_settings-section aipkit_advanced_settings_provider"
                data-provider-setting="Azure"
                style="display: <?php echo ($current_provider === 'Azure') ? 'block' : 'none'; ?>;"
            >
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_azure_authoring_version"><?php echo esc_html__('Authoring API Version', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_azure_authoring_version" name="azure_authoring_version" class="aipkit_form-input aipkit_autosave_trigger aipkit_input-with-icon" value="<?php echo esc_attr($azure_data['api_version_authoring']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($azure_defaults['api_version_authoring']); ?>" data-target-input="aipkit_azure_authoring_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_azure_inference_version"><?php echo esc_html__('Inference API Version', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_azure_inference_version" name="azure_inference_version" class="aipkit_form-input aipkit_autosave_trigger aipkit_input-with-icon" value="<?php echo esc_attr($azure_data['api_version_inference']); ?>" />
                            <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($azure_defaults['api_version_inference']); ?>" data-target-input="aipkit_azure_inference_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DeepSeek Advanced Settings (Conditionally Rendered) -->
            <?php if ($deepseek_addon_active) : ?>
            <div
                class="aipkit_settings-section aipkit_advanced_settings_provider"
                data-provider-setting="DeepSeek"
                style="display: <?php echo ($current_provider === 'DeepSeek') ? 'block' : 'none'; ?>;"
            >
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_deepseek_base_url"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                         <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_deepseek_base_url" name="deepseek_base_url" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($deepseek_data['base_url']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($deepseek_defaults['base_url']); ?>" data-target-input="aipkit_deepseek_base_url">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                     <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_deepseek_api_version"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_input-with-icon-wrapper">
                            <input type="text" id="aipkit_deepseek_api_version" name="deepseek_api_version" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($deepseek_data['api_version']); ?>" />
                             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($deepseek_defaults['api_version']); ?>" data-target-input="aipkit_deepseek_api_version">
                                <span class="dashicons dashicons-undo"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <hr class="aipkit_hr">

            <!-- Google Safety Settings Accordion (no longer a separate accordion, just displayed if Google is selected) -->
            <?php
            // Ensure $current_provider and $category_thresholds are available for settings-safety-google.php
            if (class_exists(GoogleSettingsHandler::class)) {
                include __DIR__ . '/settings-safety-google.php'; // This partial handles its own visibility based on $current_provider
            }
            ?>
        </div>
    </div>
</div>