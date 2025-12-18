<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/_form-editor-main-settings.php
// Status: MODIFIED

/**
 * Partial: AI Form Editor - Main Settings
 * Renders the content for the right-hand column of the form editor, including
 * Form Title, Prompt, and AI Configuration, now organized into collapsible accordions.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables passed from parent (form-editor.php):
// $providers, $default_temp, $default_max_tokens, $default_top_p, $default_frequency_penalty, $default_presence_penalty
// NEW: Variables passed down from ai-forms/index.php
// $openai_vector_stores, $pinecone_indexes, $qdrant_collections, $openai_embedding_models, $google_embedding_models
?>
<div class="aipkit_accordion-group">
    <!-- Accordion 1: Prompt (active by default) -->
    <div class="aipkit_accordion">
        <div class="aipkit_accordion-header aipkit_active">
            <span class="dashicons dashicons-format-aside"></span>
            <?php esc_html_e('Prompt Template', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_accordion-content aipkit_active">
            <div class="aipkit_form-group">
                <textarea id="aipkit_ai_form_prompt_template" name="prompt_template" class="aipkit_form-input" rows="12" placeholder="<?php esc_attr_e('e.g., Generate a meta description for: {your_field_name}', 'gpt3-ai-content-generator'); ?>"></textarea>
                <p class="aipkit_form-help">
                    <?php esc_html_e('Use placeholders like {your_field_variable_name} for each field you add to the form. These placeholders will be replaced with user input.', 'gpt3-ai-content-generator'); ?>
                </p>
                <!-- END NEW -->
                <div class="aipkit_prompt_snippets_container" id="aipkit_prompt_snippets_container">
                    <!-- Snippets will be injected here by JS -->
                </div>
                <!-- NEW: Prompt Validation -->
                <div class="aipkit_prompt_validation_area">
                    <button type="button" id="aipkit_validate_prompt_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small">
                        <span class="dashicons dashicons-editor-spellcheck"></span>
                        <span class="aipkit_btn-text"><?php esc_html_e('Validate Prompt', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                    <div id="aipkit_prompt_validation_results" class="aipkit_form-help" style="margin-top: 8px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion 2: General Settings -->
    <div class="aipkit_accordion">
        <div class="aipkit_accordion-header">
             <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('General Settings', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_accordion-content">
            <div class="aipkit_form-group">
                <label class="aipkit_form-label" for="aipkit_ai_form_title"><?php esc_html_e('Form Title', 'gpt3-ai-content-generator'); ?></label>
                <input type="text" id="aipkit_ai_form_title" name="title" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., Product Description Generator', 'gpt3-ai-content-generator'); ?>" required>
            </div>
        </div>
    </div>

    <!-- Accordion 3: AI Configuration -->
    <div class="aipkit_accordion">
        <div class="aipkit_accordion-header">
             <span class="dashicons dashicons-analytics"></span>
            <?php esc_html_e('AI Configuration', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_accordion-content">
            <p class="aipkit_form-help" style="margin-top: 0; margin-bottom: 15px;">
                <?php esc_html_e('Set the AI model and parameters for this form. These settings will override the global defaults.', 'gpt3-ai-content-generator'); ?>
            </p>
            <!-- Provider, Model & Settings Icon Row -->
            <div class="aipkit_form-row aipkit_ai_form_model_config_row" style="flex-wrap: unset;">
                <div class="aipkit_form-group aipkit_form-col aipkit_ai_form_provider_col">
                    <label class="aipkit_form-label" for="aipkit_ai_form_ai_provider"><?php esc_html_e('AI Provider', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_ai_form_ai_provider" name="ai_provider" class="aipkit_form-input">
                        <?php foreach ($providers as $p_value) :
                            $disabled = false;
                            $label = $p_value;
                            if ($p_value === 'DeepSeek' && (empty($deepseek_addon_active) || !$deepseek_addon_active)) {
                                $disabled = true;
                                $label = __('DeepSeek (Enable in Addons)', 'gpt3-ai-content-generator');
                            }
                            if ($p_value === 'Ollama' && (empty($is_pro) || !$is_pro || empty($ollama_addon_active) || !$ollama_addon_active)) {
                                $disabled = true;
                                $label = __('Ollama (Enable in Addons)', 'gpt3-ai-content-generator');
                            }
                        ?>
                            <option value="<?php echo esc_attr($p_value); ?>" <?php echo $disabled ? 'disabled' : ''; ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col aipkit_ai_form_model_col">
                    <label class="aipkit_form-label" for="aipkit_ai_form_ai_model"><?php esc_html_e('AI Model', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_ai_form_ai_model" name="ai_model" class="aipkit_form-input">
                        <option value=""><?php esc_html_e('Sync provider to see models', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col aipkit_ai_form_settings_col">
                     <button type="button" id="aipkit_ai_form_toggle_params_btn" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn" title="<?php esc_attr_e('Toggle advanced AI parameters', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </button>
                </div>
            </div>

            <!-- Hidden Parameters Container -->
            <div id="aipkit_ai_form_advanced_params_container" style="display:none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
                <!-- Temperature & Tokens Row -->
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="aipkit_ai_form_temperature" name="temperature" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_temp); ?>" />
                            <span id="aipkit_ai_form_temperature_value" class="aipkit_slider_value"><?php echo esc_html($default_temp); ?></span>
                        </div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_max_tokens"><?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="aipkit_ai_form_max_tokens" name="max_tokens" class="aipkit_form-input aipkit_range_slider" min="1" max="128000" step="1" value="<?php echo esc_attr($default_max_tokens); ?>" />
                            <span id="aipkit_ai_form_max_tokens_value" class="aipkit_slider_value"><?php echo esc_html($default_max_tokens); ?></span>
                        </div>
                    </div>
                </div>
                <!-- Top P & Frequency Penalty Row -->
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_top_p"><?php esc_html_e('Top P', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="aipkit_ai_form_top_p" name="top_p" class="aipkit_form-input aipkit_range_slider" min="0" max="1" step="0.01" value="<?php echo esc_attr($default_top_p); ?>" />
                            <span id="aipkit_ai_form_top_p_value" class="aipkit_slider_value"><?php echo esc_html($default_top_p); ?></span>
                        </div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_frequency_penalty"><?php esc_html_e('Frequency Penalty', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="aipkit_ai_form_frequency_penalty" name="frequency_penalty" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_frequency_penalty); ?>" />
                            <span id="aipkit_ai_form_frequency_penalty_value" class="aipkit_slider_value"><?php echo esc_html($default_frequency_penalty); ?></span>
                        </div>
                    </div>
                </div>
                <!-- Presence Penalty Row -->
                <div class="aipkit_form-row">
                     <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_presence_penalty"><?php esc_html_e('Presence Penalty', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="aipkit_ai_form_presence_penalty" name="presence_penalty" class="aipkit_form-input aipkit_range_slider" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_presence_penalty); ?>" />
                            <span id="aipkit_ai_form_presence_penalty_value" class="aipkit_slider_value"><?php echo esc_html($default_presence_penalty); ?></span>
                        </div>
                    </div>
                     <div class="aipkit_form-group aipkit_form-col">
                        <!-- empty column for alignment -->
                    </div>
                </div>
                <!-- Reasoning Effort (Conditional) -->
                <div class="aipkit_form-row aipkit_ai_form_reasoning_effort_field" style="display: none;">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_ai_form_reasoning_effort"><?php esc_html_e('Reasoning Effort', 'gpt3-ai-content-generator'); ?></label>
                        <select id="aipkit_ai_form_reasoning_effort" name="reasoning_effort" class="aipkit_form-input">
                            <option value="low"><?php esc_html_e('Low (Default)', 'gpt3-ai-content-generator'); ?></option>
                            <option value="minimal"><?php esc_html_e('Minimal', 'gpt3-ai-content-generator'); ?></option>
                            <option value="medium"><?php esc_html_e('Medium', 'gpt3-ai-content-generator'); ?></option>
                            <option value="high"><?php esc_html_e('High', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <p class="aipkit_form-help"><?php esc_html_e('For select o-series and gpt-5 models. Controls the amount of reasoning performed.', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion 4: Vector Configuration (NEW) -->
    <div class="aipkit_accordion">
        <div class="aipkit_accordion-header">
            <span class="dashicons dashicons-database"></span>
            <?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_accordion-content">
            <?php include __DIR__ . '/vector-config.php'; ?>
        </div>
    </div>
</div>