<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/modules/autogpt/partials/content-enhancement/ai-and-prompts.php
// Status: MODIFIED

/**
 * Partial: Automated Task Form - Content Enhancement AI & Prompt Settings
 * This is the content pane for the "AI Settings & Prompts" step in the wizard.
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent: $cw_providers_for_select, $cw_default_temperature, $cw_default_max_tokens

/**
 * Helper function to render placeholder help text with copy-to-clipboard functionality.
 * @param string $base_text The base text with placeholders.
 * @param string $product_text The additional text for WooCommerce placeholders.
 */
function aipkit_render_enhancer_placeholders(string $base_text, string $product_text): void
{
    $base_html = preg_replace_callback('/(\{[a-zA-Z0-9_]+\})/', function ($m) {
        return sprintf('<code class="aipkit-placeholder" title="%s">%s</code>', esc_attr__('Click to copy', 'gpt3-ai-content-generator'), esc_html($m[0]));
    }, esc_html($base_text));

    $product_html = preg_replace_callback('/(\{[a-zA-Z0-9_]+\})/', function ($m) {
        return sprintf('<code class="aipkit-placeholder" title="%s">%s</code>', esc_attr__('Click to copy', 'gpt3-ai-content-generator'), esc_html($m[0]));
    }, esc_html($product_text));

    echo wp_kses($base_html, ['code' => ['class' => true, 'title' => true]]);
    echo '<span class="aipkit-product-placeholders" style="display:none;">' . wp_kses($product_html, ['code' => ['class' => true, 'title' => true]]) . '</span>';
}

$default_title_prompt = "You are an expert SEO copywriter. Generate the single best and most compelling SEO title based on the provided information. The title must:\n- Be under 60 characters\n- Start with the main focus keyword\n- Include at least one power word (e.g., Stunning, Must-Have, Exclusive)\n- Include a positive or negative sentiment word (e.g., Best, Effortless, Risky)\n\nReturn ONLY the new title text. Do not include any introduction, explanation, or quotation marks.\n\nOriginal title: \"{original_title}\"\nPost content snippet: \"{original_content}\"\nFocus keyword: \"{original_focus_keyword}\"";
$default_excerpt_prompt = "Rewrite the post excerpt to be more compelling and engaging based on the information provided. Use a friendly tone and aim for 1–2 concise sentences. Return ONLY the new excerpt without any explanation or formatting.\n\nPost title: \"{original_title}\"\nPost content snippet: \"{original_content}\"";
$default_meta_prompt = "Generate a single, concise, and SEO-friendly meta description (under 155 characters) for a web page based on the provided information. The description must:\n- Begin with or include the focus keyword near the start\n- Use an active voice\n- Include a clear call-to-action\n\nReturn ONLY the new meta description without any introduction or formatting.\n\nPage title: \"{original_title}\"\nPage content snippet: \"{original_content}\"\nFocus keyword: \"{original_focus_keyword}\"";
$default_content_prompt = "You are an expert editor. Rewrite and improve the following article to make it more engaging, clear, and informative. Maintain the original tone and intent, but enhance the writing quality. Ensure the following:\n- The revised content is at least 600 words long\n- The focus keyword appears in one or more subheadings (H2 or H3)\n- The focus keyword is used naturally throughout the article, especially in the introduction and conclusion\n\nThe article title is: {original_title}\nFocus keyword: {original_focus_keyword}\n\nOriginal Content:\n{original_content}";
$product_placeholders_help_text = __(' For products: {price}, {regular_price}, {sku}, {attributes}, {stock_quantity}, {stock_status}, {weight}, {length}, {width}, {height}, {purchase_note}, {product_categories}.', 'gpt3-ai-content-generator');

?>
<div id="aipkit_task_config_enhancement_ai_and_prompts_main" class="aipkit_task_config_section">

    <!-- AI Settings Section -->
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_ce_ai_provider"><?php esc_html_e('AI Provider', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_ce_ai_provider" name="ce_ai_provider" class="aipkit_form-input">
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
            <label class="aipkit_form-label" for="aipkit_task_ce_ai_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                 <select id="aipkit_task_ce_ai_model" name="ce_ai_model" class="aipkit_form-input">
                    <option value=""><?php esc_html_e('-- Select Provider First --', 'gpt3-ai-content-generator'); ?></option>
                </select>
                <button type="button" id="aipkit_task_ce_ai_settings_toggle" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn" title="<?php esc_attr_e('Toggle Advanced AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
            </div>
        </div>
    </div>
    <div id="aipkit_task_ce_ai_parameters_row" style="display: none;">
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_task_ce_ai_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipkit_slider_wrapper">
                    <input type="range" id="aipkit_task_ce_ai_temperature" name="ce_ai_temperature" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($cw_default_temperature); ?>">
                    <span id="aipkit_task_ce_ai_temperature_value" class="aipkit_slider_value"><?php echo esc_html($cw_default_temperature); ?></span>
                </div>
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_task_ce_content_max_tokens"><?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipkit_slider_wrapper">
                    <input type="range" id="aipkit_task_ce_content_max_tokens" name="ce_content_max_tokens" class="aipkit_form-input aipkit_range_slider" min="100" max="128000" step="100" value="4000">
                    <span id="aipkit_task_ce_content_max_tokens_value" class="aipkit_slider_value">4000</span>
                </div>
            </div>
        </div>
        <!-- Reasoning Effort (Conditional) -->
        <div class="aipkit_form-row aipkit_task_ce_reasoning_effort_field" style="display: none;">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_task_ce_reasoning_effort"><?php esc_html_e('Reasoning Effort', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_task_ce_reasoning_effort" name="ce_reasoning_effort" class="aipkit_form-input">
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

    <!-- Prompts Section -->

    <!-- Title Prompt Section (conditionally shown by JS) -->
    <div id="aipkit_task_ce_title_prompt_section" class="aipkit_form-group" style="display: none;">
        <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_ce_title_prompt"><?php esc_html_e('Title Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_ce_title_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_ce_title_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_ce_title_prompt" name="ce_title_prompt" class="aipkit_form-input" rows="5"><?php echo esc_textarea($default_title_prompt); ?></textarea>
            <p class="aipkit_form-help aipkit-enhancer-placeholders-help">
                <?php aipkit_render_enhancer_placeholders(esc_html__('Placeholders: {original_title}, {original_content}, {original_excerpt}, {original_tags}, {categories}.', 'gpt3-ai-content-generator'), $product_placeholders_help_text); ?>
            </p>
        </div>
    </div>

    <!-- Excerpt Prompt Section (conditionally shown by JS) -->
    <div id="aipkit_task_ce_excerpt_prompt_section" class="aipkit_form-group" style="display: none;">
         <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_ce_excerpt_prompt"><?php esc_html_e('Excerpt Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_ce_excerpt_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_ce_excerpt_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_ce_excerpt_prompt" name="ce_excerpt_prompt" class="aipkit_form-input" rows="5"><?php echo esc_textarea($default_excerpt_prompt); ?></textarea>
            <p class="aipkit_form-help aipkit-enhancer-placeholders-help">
                <?php aipkit_render_enhancer_placeholders(esc_html__('Placeholders: {original_title}, {original_content}, {original_excerpt}, {original_tags}, {categories}.', 'gpt3-ai-content-generator'), $product_placeholders_help_text); ?>
            </p>
        </div>
    </div>

    <!-- Content Prompt Section (conditionally shown by JS) -->
    <div id="aipkit_task_ce_content_prompt_section" class="aipkit_form-group" style="display: none;">
        <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_ce_content_prompt"><?php esc_html_e('Content Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_ce_content_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_ce_content_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_ce_content_prompt" name="ce_content_prompt" class="aipkit_form-input" rows="5"><?php echo esc_textarea($default_content_prompt); ?></textarea>
            <p class="aipkit_form-help aipkit-enhancer-placeholders-help">
                <?php aipkit_render_enhancer_placeholders(esc_html__('Placeholders: {original_title}, {original_content}, {original_excerpt}, {original_tags}, {categories}.', 'gpt3-ai-content-generator'), $product_placeholders_help_text); ?>
            </p>
        </div>
    </div>

    <!-- Meta Description Prompt Section (conditionally shown by JS) -->
    <div id="aipkit_task_ce_meta_prompt_section" class="aipkit_form-group" style="display: none;">
         <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_ce_meta_prompt"><?php esc_html_e('Meta Description Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_ce_meta_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_ce_meta_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_ce_meta_prompt" name="ce_meta_prompt" class="aipkit_form-input" rows="5"><?php echo esc_textarea($default_meta_prompt); ?></textarea>
            <p class="aipkit_form-help aipkit-enhancer-placeholders-help">
                <?php aipkit_render_enhancer_placeholders(esc_html__('Placeholders: {original_title}, {original_content}, {original_meta_description}, {original_tags}, {categories}.', 'gpt3-ai-content-generator'), $product_placeholders_help_text); ?>
            </p>
        </div>
    </div>

</div>