<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs/ai-settings.php
// Status: MODIFIED
/**
 * Partial: Content Writer Form - AI Settings
 */
if (!defined('ABSPATH')) {
    exit;
}
// Variables from loader-vars.php: $providers_for_select, $default_provider, $default_temperature, $default_max_tokens
use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

// --- Define Default SEO Prompt Templates ---
$default_custom_content_prompt = AIPKit_Content_Writer_Prompts::get_default_content_prompt();
$default_custom_title_prompt = AIPKit_Content_Writer_Prompts::get_default_title_prompt();
// --- End Definitions ---

?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('AI & Prompts', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_content_writer_provider"><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_content_writer_provider" name="ai_provider" class="aipkit_form-input">
                    <?php
                    // Always render base providers from provided list, but skip DeepSeek/Ollama here to avoid duplicates
                    if (!empty($providers_for_select) && is_array($providers_for_select)) {
                        foreach ($providers_for_select as $p_value) {
                            if ($p_value === 'DeepSeek' || $p_value === 'Ollama') {
                                continue;
                            }
                            $val = strtolower($p_value);
                            echo '<option value="' . esc_attr($val) . '"' . selected($default_provider, $val, false) . '>' . esc_html($p_value) . '</option>';
                        }
                    }

                    // Compute gating flags for DeepSeek and Ollama
                    $is_pro = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan();
                    $deepseek_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('deepseek');
                    $ollama_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('ollama');

                    // DeepSeek option (always listed, disabled when addon inactive)
                    $ds_disabled = !$deepseek_addon_active;
                    $ds_label = 'DeepSeek' . ($ds_disabled ? ' (' . esc_html__('Enable in Addons', 'gpt3-ai-content-generator') . ')' : '');
                    echo '<option value="deepseek"' . selected($default_provider, 'deepseek', false) . ($ds_disabled ? ' disabled' : '') . '>' . esc_html($ds_label) . '</option>';

                    // Ollama option (always listed, disabled unless Pro + addon active)
                    $ollama_enabled = ($is_pro && $ollama_addon_active);
                    $ol_disabled = !$ollama_enabled;
                    $ol_label = 'Ollama' . ($ol_disabled ? ' (' . esc_html__('Enable in Addons', 'gpt3-ai-content-generator') . ')' : '');
                    echo '<option value="ollama"' . selected($default_provider, 'ollama', false) . ($ol_disabled ? ' disabled' : '') . '>' . esc_html($ol_label) . '</option>';
                    ?>
                </select>
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_content_writer_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
                 <div class="aipkit_input-with-button">
                    <select id="aipkit_content_writer_model" name="ai_model" class="aipkit_form-input"></select>
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_ai_settings_toggle" title="<?php esc_attr_e('Toggle Advanced AI Parameters', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </button>
                </div>
            </div>
        </div>
         <div class="aipkit_cw_ai_parameters_row" style="display: none;"> <?php // This row is now hidden by default?>
            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_content_writer_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_content_writer_temperature" name="ai_temperature" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_temperature); ?>">
                        <span id="aipkit_content_writer_temperature_value" class="aipkit_slider_value"><?php echo esc_html($default_temperature); ?></span>
                    </div>
                </div>
                <div class="aipkit_form-group aipkit_form-col"> 
                    <label class="aipkit_form-label" for="aipkit_content_writer_max_tokens"><?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_content_writer_max_tokens" name="content_max_tokens" class="aipkit_form-input aipkit_range_slider" min="100" max="128000" step="100" value="<?php echo esc_attr($default_max_tokens); ?>">
                        <span id="aipkit_content_writer_max_tokens_value" class="aipkit_slider_value"><?php echo esc_html($default_max_tokens); ?></span>
                    </div>
                </div>
            </div>
            <!-- Reasoning Effort (Conditional) -->
            <div class="aipkit_form-row aipkit_cw_reasoning_effort_field" style="display: none;">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_content_writer_reasoning_effort"><?php esc_html_e('Reasoning Effort', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_content_writer_reasoning_effort" name="reasoning_effort" class="aipkit_form-input">
                        <option value="low"><?php esc_html_e('Low (Default)', 'gpt3-ai-content-generator'); ?></option>
                        <option value="minimal"><?php esc_html_e('Minimal', 'gpt3-ai-content-generator'); ?></option>
                        <option value="medium"><?php esc_html_e('Medium', 'gpt3-ai-content-generator'); ?></option>
                        <option value="high"><?php esc_html_e('High', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <p class="aipkit_form-help"><?php esc_html_e('For o-series and gpt-5 models. Controls the amount of reasoning performed.', 'gpt3-ai-content-generator'); ?></p>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <!-- empty column for alignment -->
                </div>
            </div>
        </div>

        <hr class="aipkit_hr">

        <?php // Hidden input to ensure prompt_mode is always 'custom'?>
        <input type="hidden" name="prompt_mode" id="aipkit_cw_prompt_mode_hidden_input" value="custom">

        <!-- Custom Prompt Fields (Always Visible Now) -->
        <div id="aipkit_cw_custom_prompt_fields">
             <div class="aipkit_form-group">
                <div class="aipkit_form_label_with_toggle">
                    <label class="aipkit_form-label" for="aipkit_cw_custom_content_prompt"><?php esc_html_e('Content Prompt', 'gpt3-ai-content-generator'); ?></label>
                    <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_cw_custom_content_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </button>
                </div>
                <div id="aipkit_cw_custom_content_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
                    <textarea id="aipkit_cw_custom_content_prompt" name="custom_content_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6"><?php echo esc_textarea($default_custom_content_prompt); ?></textarea>
                    <p class="aipkit_form-help" id="aipkit_cw_content_prompt_placeholders"><?php
                        $text = __('Use placeholders: {topic}, {keywords}.', 'gpt3-ai-content-generator');
                        $html = preg_replace_callback(
                            '/(\{[a-zA-Z0-9_]+\})/',
                            function ($matches) {
                                return sprintf(
                                    '<code class="aipkit-placeholder" title="%s">%s</code>',
                                    esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
                                    esc_html($matches[0])
                                );
                            },
                            $text
                        );
                        echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
                    ?></p>
                </div>
            </div>
             <div class="aipkit_form-group" id="aipkit_cw_custom_title_prompt_field">
                <div class="aipkit_form_label_with_toggle">
                    <label class="aipkit_form-label" for="aipkit_cw_custom_title_prompt"><?php esc_html_e('Title Prompt', 'gpt3-ai-content-generator'); ?></label>
                    <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_cw_custom_title_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </button>
                </div>
                 <div id="aipkit_cw_custom_title_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
                    <textarea id="aipkit_cw_custom_title_prompt" name="custom_title_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6" ><?php echo esc_textarea($default_custom_title_prompt); ?></textarea>
                    <p class="aipkit_form-help"><?php
                        $text = __('Use {topic} and {keywords}.', 'gpt3-ai-content-generator');
                        $html = preg_replace_callback(
                            '/(\{[a-zA-Z0-9_]+\})/',
                            function ($matches) {
                                return sprintf(
                                    '<code class="aipkit-placeholder" title="%s">%s</code>',
                                    esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
                                    esc_html($matches[0])
                                );
                            },
                            $text
                        );
                        echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
                    ?></p>
                </div>
            </div>
        </div>
        <!-- End Custom Prompt Fields -->

    </div>
</div>