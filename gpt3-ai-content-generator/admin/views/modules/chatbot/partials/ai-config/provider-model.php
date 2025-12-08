<?php

/**
 * Partial: AI Config - Provider and Model Selection
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager; // Use new class for constants

$saved_stream_enabled = isset($bot_settings['stream_enabled'])
                        ? $bot_settings['stream_enabled']
                        : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STREAM_ENABLED;

// Get saved Azure deployment name if applicable
$saved_azure_deployment = ($saved_provider === 'Azure') ? $saved_model : '';

?>
<!-- Row container for Provider + Model -->
<div class="aipkit_form-row aipkit_form-row-align-bottom" style="flex-wrap: nowrap; gap: 10px;">
    <!-- AI Provider Column -->
    <div class="aipkit_form-group aipkit_form-col" style="flex: 0 1 160px;">
        <label
            class="aipkit_form-label"
            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_provider"
        >
            <?php esc_html_e('Engine', 'gpt3-ai-content-generator'); ?>
        </label>
        <select
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_provider"
            name="provider"
            class="aipkit_form-input aipkit_chatbot_provider_select" <?php // JS targets this class?>
        >
            <?php foreach ($providers as $p_value) :
                $disabled = false;
                $label = $p_value;

                if ($p_value === 'DeepSeek' && (empty($deepseek_addon_active) || !$deepseek_addon_active)) {
                    $disabled = true;
                    $label = __('DeepSeek (Enable in Addons)', 'gpt3-ai-content-generator');
                }
                if ($p_value === 'Ollama' && (!$is_pro || empty($ollama_addon_active) || !$ollama_addon_active)) {
                    $disabled = true;
                    $label = __('Ollama (Enable in Addons)', 'gpt3-ai-content-generator');
                }
            ?>
                <option
                    value="<?php echo esc_attr($p_value); ?>"
                    <?php selected($saved_provider, $p_value); ?> <?php echo $disabled ? 'disabled' : ''; ?>
                >
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Model Selection Column -->
    <div class="aipkit_form-group aipkit_form-col" style="flex: 1 1 auto;">
        <!-- OpenAI Model -->
        <div
            class="aipkit_chatbot_model_field" <?php // JS targets this class?>
            data-provider="OpenAI"
            style="display: <?php echo $saved_provider === 'OpenAI' ? 'block' : 'none'; ?>;"
        >
            <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_model"
            >
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
            <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
                <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_model"
                    name="openai_model"
                    class="aipkit_form-input"
                >
                    <?php
                     // $grouped_openai_models now only contains chat models (already filtered if applicable)
                     $foundCurrentOpenAI = false;
if (!empty($grouped_openai_models) && is_array($grouped_openai_models)): ?>
                        <?php foreach ($grouped_openai_models as $groupLabel => $groupItems): ?>
                            <optgroup label="<?php echo esc_attr($groupLabel); ?>">
                                <?php foreach ($groupItems as $m):
                                    $model_id   = $m['id'] ?? '';
                                    $model_name = $m['name'] ?? $model_id;
                                    if ($model_id === $saved_model) {
                                        $foundCurrentOpenAI = true;
                                    }
                                    ?>
                                    <option
                                        value="<?php echo esc_attr($model_id); ?>"
                                        <?php selected($saved_model, $model_id); ?>
                                    >
                                        <?php echo esc_html($model_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php
                    // If saved model not found OR list is empty
                    // AND the saved model is NOT an OpenAI TTS model (as this dropdown is for CHAT models)
                    if (!$foundCurrentOpenAI && !empty($saved_model) && $saved_provider === 'OpenAI' && strpos($saved_model, 'tts-') !== 0) {
                        echo '<option value="' . esc_attr($saved_model) . '" selected>' . esc_html($saved_model) . ' (Manual)</option>';
                    } elseif (empty($grouped_openai_models) && (!$foundCurrentOpenAI || empty($saved_model) || strpos($saved_model, 'tts-') === 0)) {
                        echo '<option value="">'.esc_html__('(Sync models in main AI Settings)', 'gpt3-ai-content-generator').'</option>';
                    }
?>
                </select>
                <!-- OpenAI Web Search checkbox moved to Features subsection -->
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="OpenAI" title="<?php esc_attr_e('Sync models', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
                <!-- Feature toggles moved to Features subsection; keep only parameters button -->
            </div> <?php // END WRAPPER?>
        </div>

        <!-- OpenRouter Model -->
        <div
            class="aipkit_chatbot_model_field"
            data-provider="OpenRouter"
            style="display: <?php echo $saved_provider === 'OpenRouter' ? 'block' : 'none'; ?>;"
        >
             <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openrouter_model"
            >
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
             <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
               <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openrouter_model"
                    name="openrouter_model"
                    class="aipkit_form-input"
                >
                    <?php
$foundCurrentOR = false;
if (!empty($openrouter_model_list)) {
    $grouped = [];
    foreach ($openrouter_model_list as $model) {
        if (!empty($model['id']) && !empty($model['name'])) {
            $parts  = explode('/', $model['id']);
            $prefix = strtolower(trim($parts[0]));
            if (!isset($grouped[$prefix])) {
                $grouped[$prefix] = [];
            }
            $grouped[$prefix][] = $model;
        }
    }
    ksort($grouped);
    foreach ($grouped as $prefix => $modelsInGroup): ?>
                            <optgroup label="<?php echo esc_attr(ucfirst($prefix)); ?>">
                                <?php
            usort($modelsInGroup, fn ($a, $b) => strcmp($a['name'], $b['name']));
        foreach ($modelsInGroup as $m):
            if ($m['id'] === $saved_model) {
                $foundCurrentOR = true;
            } ?>
                                    <option
                                        value="<?php echo esc_attr($m['id']); ?>"
                                        <?php selected($saved_model, $m['id']); ?>
                                    >
                                        <?php echo esc_html($m['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach;
}
if (!$foundCurrentOR && !empty($saved_model) && $saved_provider === 'OpenRouter') { ?>
                        <option value="<?php echo esc_attr($saved_model); ?>" selected><?php echo esc_html($saved_model); ?> (Manual)</option>
                    <?php } elseif (empty($openrouter_model_list) && empty($saved_model)) { ?>
                        <option value=""><?php esc_html_e('(Sync models in main AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="OpenRouter" title="<?php esc_attr_e('Sync models', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
                <!-- Feature toggles moved to Features subsection; keep only parameters button -->
            </div> <?php // END WRAPPER?>
        </div>

        <!-- Google Model -->
        <div
            class="aipkit_chatbot_model_field"
            data-provider="Google"
            style="display: <?php echo $saved_provider === 'Google' ? 'block' : 'none'; ?>;"
        >
             <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_model"
            >
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
             <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
                <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_model"
                    name="google_model"
                    class="aipkit_form-input"
                >
                     <?php
$foundCurrentGoogle = false;
if (!empty($google_model_list)): ?>
                        <?php foreach ($google_model_list as $gm):
                            $gId   = $gm['id'] ?? ($gm['name'] ?? '');
                            $gName = $gm['name'] ?? $gId;
                            $selectedValue = $gId;
                            $isSelected = ($saved_model === $selectedValue || $saved_model === 'models/'.$selectedValue);
                            if ($isSelected) {
                                $foundCurrentGoogle = true;
                            }
                            ?>
                            <option
                                value="<?php echo esc_attr($selectedValue); ?>"
                                <?php echo $isSelected ? 'selected' : ''; ?>
                            >
                                <?php echo esc_html($gName); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php
                    if (!$foundCurrentGoogle && !empty($saved_model) && $saved_provider === 'Google'): ?>
                         <?php $displayModel = (strpos($saved_model, 'models/') === 0) ? substr($saved_model, 7) : $saved_model; ?>
                        <option value="<?php echo esc_attr($saved_model); ?>" selected><?php echo esc_html($displayModel); ?> (Manual)</option>
                    <?php elseif (empty($google_model_list) && !$foundCurrentGoogle && empty($saved_model)): ?>
                        <option value=""><?php esc_html_e('(Sync models in main AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
                <!-- Google Search Grounding checkbox moved to Features subsection -->
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="Google" title="<?php esc_attr_e('Sync models', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
                <!-- Feature toggles moved to Features subsection; keep only parameters button -->
            </div> <?php // END WRAPPER?>
        </div>

        <!-- Azure Deployment Only -->
        <div
            class="aipkit_chatbot_model_field"
            data-provider="Azure"
            style="display: <?php echo $saved_provider === 'Azure' ? 'block' : 'none'; ?>;"
        >
             <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_azure_deployment"
            >
                <?php esc_html_e('Deployment', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
             <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
                <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_azure_deployment"
                    name="azure_deployment"
                    class="aipkit_form-input"
                >
                    <?php
                    $foundOldAzure = false;
if (is_array($azure_deployment_list) && !empty($azure_deployment_list)) {
    foreach ($azure_deployment_list as $dep) {
        $dep_id   = $dep['id'] ?? '';
        $dep_name = $dep['name'] ?? $dep_id;
        $label = $dep_id;
        if (!empty($dep_name) && $dep_name !== $dep_id) {
            $label .= ' (model: ' . $dep_name . ')';
        }
        $selected = selected($saved_azure_deployment, $dep_id, false);
        if (!empty($selected)) {
            $foundOldAzure = true;
        }
        echo '<option value="' . esc_attr($dep_id) . '" ' . esc_attr($selected) . '>' . esc_html($label) . '</option>';
    }
}
if (!$foundOldAzure && !empty($saved_azure_deployment)) {
    echo '<option value="'.esc_attr($saved_azure_deployment).'" selected>'.esc_html($saved_azure_deployment . ($foundOldAzure === false && !empty($azure_deployment_list) ? ' (not in synced list)' : '')).' (Manual)</option>';
} elseif (empty($saved_azure_deployment) && empty($azure_deployment_list)) {
    echo '<option value="">'.esc_html__('(Sync deployments in main AI Settings)', 'gpt3-ai-content-generator').'</option>';
}
?>
                </select>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="Azure" title="<?php esc_attr_e('Sync deployments', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
            </div> <?php // END WRAPPER?>
        </div>

        <!-- DeepSeek Model -->
        <div
            class="aipkit_chatbot_model_field"
            data-provider="DeepSeek"
            style="display: <?php echo $saved_provider === 'DeepSeek' ? 'block' : 'none'; ?>;"
        >
             <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_deepseek_model"
            >
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
             <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
                <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_deepseek_model"
                    name="deepseek_model"
                    class="aipkit_form-input"
                >
                    <?php
 $foundCurrentDeepSeek = false;
if (!empty($deepseek_model_list)): ?>
                        <?php foreach ($deepseek_model_list as $m):
                            $model_id   = $m['id'] ?? '';
                            $model_name = $m['name'] ?? $model_id;
                            if ($model_id === $saved_model) {
                                $foundCurrentDeepSeek = true;
                            }
                            ?>
                            <option
                                value="<?php echo esc_attr($model_id); ?>"
                                <?php selected($saved_model, $model_id); ?>
                            >
                                <?php echo esc_html($model_name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!$foundCurrentDeepSeek && !empty($saved_model) && $saved_provider === 'DeepSeek'): ?>
                        <option value="<?php echo esc_attr($saved_model); ?>" selected><?php echo esc_html($saved_model); ?> (Manual)</option>
                    <?php elseif (empty($deepseek_model_list) && !$foundCurrentDeepSeek && empty($saved_model)): ?>
                        <option value=""><?php esc_html_e('(Sync models in main AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="DeepSeek" title="<?php esc_attr_e('Sync models', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
            </div> <?php // END WRAPPER?>
        </div>

        <!-- Ollama Model -->
        <div
            class="aipkit_chatbot_model_field"
            data-provider="Ollama"
            style="display: <?php echo $saved_provider === 'Ollama' ? 'block' : 'none'; ?>;"
        >
             <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_ollama_model"
            >
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_model_sync_status" aria-live="polite"></span>
            </label>
             <div class="aipkit_input-with-button"> <?php // NEW WRAPPER?>
                <select
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_ollama_model"
                    name="ollama_model"
                    class="aipkit_form-input"
                >
                    <?php
                    $foundCurrentOllama = false;
                    if (!empty($ollama_model_list)): ?>
                        <?php foreach ($ollama_model_list as $m):
                            $model_id   = $m['id'] ?? '';
                            $model_name = $m['name'] ?? $model_id;
                            if ($model_id === $saved_model) {
                                $foundCurrentOllama = true;
                            }
                            ?>
                            <option
                                value="<?php echo esc_attr($model_id); ?>"
                                <?php selected($saved_model, $model_id); ?>
                            >
                                <?php echo esc_html($model_name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!$foundCurrentOllama && !empty($saved_model) && $saved_provider === 'Ollama'): ?>
                        <option value="<?php echo esc_attr($saved_model); ?>" selected><?php echo esc_html($saved_model); ?> (Manual)</option>
                    <?php elseif (empty($ollama_model_list) && !$foundCurrentOllama && empty($saved_model)): ?>
                        <option value=""><?php esc_html_e('(Sync models in main AI Settings)', 'gpt3-ai-content-generator'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_sync_btn" data-provider="Ollama" title="<?php esc_attr_e('Sync models', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_cb_ai_settings_toggle" title="<?php esc_attr_e('Toggle AI Parameters', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </button>
            </div> <?php // END WRAPPER?>
        </div>

    </div><!-- /Model Selection Column -->
</div> <!-- /Row container -->
