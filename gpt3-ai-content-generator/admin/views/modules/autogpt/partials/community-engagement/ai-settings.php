<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/community-engagement/ai-settings.php
// Status: MODIFIED

/**
 * Partial: Community Engagement Automated Task - AI Settings
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent: $cw_providers_for_select, $cw_default_temperature
?>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_cc_ai_provider"><?php esc_html_e('AI Provider', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_cc_ai_provider" name="cc_ai_provider" class="aipkit_form-input">
            <?php
            // Render base providers but skip DeepSeek/Ollama to avoid duplicates
            if (!empty($cw_providers_for_select) && is_array($cw_providers_for_select)) {
                foreach ($cw_providers_for_select as $p_value) {
                    if ($p_value === 'DeepSeek' || $p_value === 'Ollama') {
                        continue;
                    }
                    $val = strtolower($p_value);
                    echo '<option value="' . esc_attr($val) . '"' . selected(strtolower(WPAICG\AIPKit_Providers::get_current_provider()), $val, false) . '>' . esc_html($p_value) . '</option>';
                }
            }

            // Gating flags
            $is_pro = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan();
            $deepseek_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('deepseek');
            $ollama_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('ollama');

            // DeepSeek
            $ds_disabled = !$deepseek_addon_active;
            $ds_label = 'DeepSeek' . ($ds_disabled ? ' (' . esc_html__('Enable in Addons', 'gpt3-ai-content-generator') . ')' : '');
            echo '<option value="deepseek"' . selected(strtolower(WPAICG\AIPKit_Providers::get_current_provider()), 'deepseek', false) . ($ds_disabled ? ' disabled' : '') . '>' . esc_html($ds_label) . '</option>';

            // Ollama
            $ollama_enabled = ($is_pro && $ollama_addon_active);
            $ol_disabled = !$ollama_enabled;
            $ol_label = 'Ollama' . ($ol_disabled ? ' (' . esc_html__('Enable in Addons', 'gpt3-ai-content-generator') . ')' : '');
            echo '<option value="ollama"' . selected(strtolower(WPAICG\AIPKit_Providers::get_current_provider()), 'ollama', false) . ($ol_disabled ? ' disabled' : '') . '>' . esc_html($ol_label) . '</option>';
            ?>
        </select>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_cc_ai_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipkit_input-with-button">
             <select id="aipkit_task_cc_ai_model" name="cc_ai_model" class="aipkit_form-input">
                <option value=""><?php esc_html_e('-- Select Provider First --', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <button type="button" id="aipkit_task_cc_ai_settings_toggle" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn" title="<?php esc_attr_e('Toggle Advanced AI Parameters', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
    </div>
</div>
<div id="aipkit_task_cc_ai_parameters_row" style="display: none;">
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_cc_ai_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_slider_wrapper">
                <input type="range" id="aipkit_task_cc_ai_temperature" name="cc_ai_temperature" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($cw_default_temperature); ?>">
                <span id="aipkit_task_cc_ai_temperature_value" class="aipkit_slider_value"><?php echo esc_html($cw_default_temperature); ?></span>
            </div>
        </div>
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_cc_content_max_tokens"><?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_slider_wrapper">
                <input type="range" id="aipkit_task_cc_content_max_tokens" name="cc_content_max_tokens" class="aipkit_form-input aipkit_range_slider" min="10" max="128000" step="10" value="4000">
                <span id="aipkit_task_cc_content_max_tokens_value" class="aipkit_slider_value">4000</span>
            </div>
        </div>
    </div>
    <!-- Reasoning Effort (Conditional) -->
    <div class="aipkit_form-row aipkit_task_cc_reasoning_effort_field" style="display: none;">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_cc_reasoning_effort"><?php esc_html_e('Reasoning Effort', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_cc_reasoning_effort" name="cc_reasoning_effort" class="aipkit_form-input">
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
<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_task_cc_custom_content_prompt"><?php esc_html_e('Reply Prompt', 'gpt3-ai-content-generator'); ?></label>
    <textarea id="aipkit_task_cc_custom_content_prompt" name="cc_custom_content_prompt" class="aipkit_form-input" rows="6"><?php echo esc_textarea("Write a helpful and friendly reply to this comment on my blog post titled '{post_title}'.\n\nComment: {comment_content}"); ?></textarea>
    <p class="aipkit_form-help"><?php
        $text = __('Use placeholders: {comment_content}, {comment_author}, {post_title}.', 'gpt3-ai-content-generator');
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