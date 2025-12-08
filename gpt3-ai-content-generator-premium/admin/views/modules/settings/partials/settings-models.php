<?php

/**
 * Partial: Model/Deployment Selection Fields
 */
if (!defined('ABSPATH')) exit;

// Variables required: $current_provider, $openai_data, $openrouter_data, $google_data, $azure_data, $deepseek_data,
// $grouped_openai_models (THIS IS NOW THE FILTERED LIST), $openrouter_model_list, $google_model_list, $azure_deployment_list, $deepseek_model_list,
// $deepseek_addon_active

// REMOVED outer .aipkit_settings-section div
?>
<!-- OpenAI Model -->
<div
    class="aipkit_form-group aipkit_model_field" 
    id="aipkit_openai_model_group" 
    data-provider="OpenAI"
    style="display: <?php echo ($current_provider === 'OpenAI') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_openai_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_openai_model" name="openai_model" class="aipkit_form-input aipkit_autosave_trigger">
            <?php
            $currentOpenAIModel = $openai_data['model'];
            $foundCurrent = false;
            if (!empty($grouped_openai_models) && is_array($grouped_openai_models)) {
                foreach ($grouped_openai_models as $groupLabel => $groupItems) {
                    echo '<optgroup label="' . esc_attr($groupLabel) . '">';
                    foreach ($groupItems as $m) {
                        $model_id = $m['id'] ?? '';
                        $model_name = $m['name'] ?? $model_id;
                         if($model_id === $currentOpenAIModel) $foundCurrent = true;
                        echo '<option value="' . esc_attr($model_id) . '" ' . selected($currentOpenAIModel, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                    }
                    echo '</optgroup>';
                }
            }
            if (!$foundCurrent && !empty($currentOpenAIModel) && strpos($currentOpenAIModel, 'tts-') !== 0) {
                echo '<option value="' . esc_attr($currentOpenAIModel) . '" selected>' . esc_html($currentOpenAIModel) . ' (Manual)</option>';
            }
            if (empty($grouped_openai_models) && !$foundCurrent && (empty($currentOpenAIModel) || strpos($currentOpenAIModel, 'tts-') === 0) ) {
                 echo '<option value="">'.esc_html__('(Sync to load models)', 'gpt3-ai-content-generator').'</option>';
            }
            ?>
        </select>
        <button id="aipkit_sync_openai_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="OpenAI">
            <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>

<!-- OpenRouter Model -->
<div
    class="aipkit_form-group aipkit_model_field" 
    id="aipkit_openrouter_model_group" 
    data-provider="OpenRouter"
    style="display: <?php echo ($current_provider === 'OpenRouter') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_openrouter_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_openrouter_model" name="openrouter_model" class="aipkit_form-input aipkit_autosave_trigger">
        <?php
        $currentORModel = $openrouter_data['model'];
        $foundCurrentOR = false;
        if (!empty($openrouter_model_list)) {
            $grouped = array();
            foreach ($openrouter_model_list as $model) {
                if (!empty($model['id']) && !empty($model['name'])) {
                    $parts = explode('/', $model['id']);
                    $prefix = strtolower(trim($parts[0]));
                    if (!isset($grouped[$prefix])) $grouped[$prefix] = array();
                    $grouped[$prefix][] = $model;
                }
            }
            ksort($grouped);
            foreach ($grouped as $prefix => $modelsArr) {
                echo '<optgroup label="' . esc_attr(ucfirst($prefix)) . '">';
                usort($modelsArr, fn($a, $b) => strcmp($a['name'], $b['name']));
                foreach ($modelsArr as $m) {
                     if($m['id'] === $currentORModel) $foundCurrentOR = true;
                    echo '<option value="' . esc_attr($m['id']) . '" ' . selected($currentORModel, $m['id'], false) . '>' . esc_html($m['name']) . '</option>';
                }
                echo '</optgroup>';
            }
        }
         if (!$foundCurrentOR && !empty($currentORModel)) {
             echo '<option value="' . esc_attr($currentORModel) . '" selected>' . esc_html($currentORModel) . ' (Manual)</option>';
         } elseif (empty($openrouter_model_list) && empty($currentORModel)) {
             echo '<option value="">'.esc_html__('(Sync to load models)', 'gpt3-ai-content-generator').'</option>';
         }
        ?>
        </select>
        <button id="aipkit_sync_openrouter_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="OpenRouter">
           <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
           <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>

<!-- Google Model -->
<div
    class="aipkit_form-group aipkit_model_field" 
    id="aipkit_google_model_group" 
    data-provider="Google"
    style="display: <?php echo ($current_provider === 'Google') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_google_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_google_model" name="google_model" class="aipkit_form-input aipkit_autosave_trigger">
            <?php
            $currentGoogleModel = $google_data['model'];
            $foundCurrentGoogle = false;
            if (!empty($google_model_list)) {
                foreach ($google_model_list as $gm) {
                    $gId = isset($gm['id']) ? $gm['id'] : (isset($gm['name']) ? $gm['name'] : '');
                    $gName = isset($gm['name']) ? $gm['name'] : $gId;
                    $selectedValue = $gId;
                    $isSelected = ($currentGoogleModel === $selectedValue || $currentGoogleModel === 'models/'.$selectedValue);
                    if ($isSelected) $foundCurrentGoogle = true;
                    echo '<option value="' . esc_attr($selectedValue) . '" ' . ($isSelected ? 'selected' : '') . '>' . esc_html($gName) . '</option>';
                }
            }
             if (!$foundCurrentGoogle && !empty($currentGoogleModel)) {
                 $displayModel = (strpos($currentGoogleModel, 'models/') === 0) ? substr($currentGoogleModel, 7) : $currentGoogleModel;
                echo '<option value="' . esc_attr($currentGoogleModel) . '" selected>' . esc_html($displayModel) . ' (Manual)</option>';
            } elseif (empty($google_model_list) && empty($currentGoogleModel)) {
                 echo '<option value="">'.esc_html__('(Sync to load models)', 'gpt3-ai-content-generator').'</option>';
            }
            ?>
        </select>
        <button id="aipkit_sync_google_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="Google">
            <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
             <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>

<!-- Azure Deployment -->
<div
    class="aipkit_form-group aipkit_model_field" <?php // Ensure this has aipkit_model_field for JS toggle ?>
    id="aipkit_azure_deployment_group" 
    data-provider="Azure"
    style="display: <?php echo ($current_provider === 'Azure') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_azure_deployment"><?php esc_html_e('Deployment', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_azure_deployment" name="azure_deployment" class="aipkit_form-input aipkit_autosave_trigger">
            <?php
            $currentAzureDeployment = $azure_data['model']; // 'model' key stores deployment name for Azure
            $foundOldAzure = false;
            if (is_array($azure_deployment_list) && !empty($azure_deployment_list)) {
                // Check if we have grouped models or flat array
                $isGrouped = !empty($azure_deployment_list) && isset($azure_deployment_list['Chat Models']) || isset($azure_deployment_list['Embedding Models']) || isset($azure_deployment_list['Image Models']);
                
                if ($isGrouped) {
                    // Handle grouped models with optgroups
                    foreach ($azure_deployment_list as $groupName => $models) {
                        if (!empty($models) && is_array($models)) {
                            echo '<optgroup label="' . esc_attr($groupName) . '">';
                            foreach ($models as $dep) {
                                $dep_id   = $dep['id'] ?? '';
                                $dep_name = $dep['name'] ?? $dep_id;
                                $label = $dep_id;
                                if (!empty($dep_name) && $dep_name !== $dep_id) {
                                    $label .= ' (model: ' . $dep_name . ')';
                                }
                                $selected = selected($currentAzureDeployment, $dep_id, false);
                                if (!empty($selected)) $foundOldAzure = true;
                                echo '<option value="' . esc_attr($dep_id) . '" ' . esc_attr( $selected ) . '>' . esc_html( $label ) . '</option>';
                            }
                            echo '</optgroup>';
                        }
                    }
                } else {
                    // Handle flat array (backward compatibility)
                    foreach ($azure_deployment_list as $dep) {
                        $dep_id   = $dep['id'] ?? '';
                        $dep_name = $dep['name'] ?? $dep_id;
                        $label = $dep_id;
                        if (!empty($dep_name) && $dep_name !== $dep_id) {
                            $label .= ' (model: ' . $dep_name . ')';
                        }
                        $selected = selected($currentAzureDeployment, $dep_id, false);
                        if (!empty($selected)) $foundOldAzure = true;
                        echo '<option value="' . esc_attr($dep_id) . '" ' . esc_attr( $selected ) . '>' . esc_html( $label ) . '</option>';
                    }
                }
            }
            if (!$foundOldAzure && !empty($currentAzureDeployment)) {
                echo '<option value="'.esc_attr($currentAzureDeployment).'" selected>'.esc_html($currentAzureDeployment . ($foundOldAzure === false && !empty($azure_deployment_list) ? ' (not in synced list)' : '')).' (Manual)</option>';
            } elseif (empty($currentAzureDeployment) && empty($azure_deployment_list)) {
                echo '<option value="">'.esc_html__('(Sync to load deployments)', 'gpt3-ai-content-generator').'</option>';
            }
            ?>
        </select>
        <button id="aipkit_sync_azure_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="Azure">
            <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>


<!-- DeepSeek Model (Conditionally Rendered) -->
  <?php if ($deepseek_addon_active) : ?>
<div
    class="aipkit_form-group aipkit_model_field" 
    id="aipkit_deepseek_model_group" 
    data-provider="DeepSeek"
    style="display: <?php echo ($current_provider === 'DeepSeek') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_deepseek_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_deepseek_model" name="deepseek_model" class="aipkit_form-input aipkit_autosave_trigger">
            <?php
            $currentDeepSeekModel = $deepseek_data['model'];
            $foundCurrentDeepSeek = false;
            if (!empty($deepseek_model_list)) {
                foreach ($deepseek_model_list as $m) {
                    $model_id = $m['id'] ?? '';
                    $model_name = $m['name'] ?? $model_id;
                     if($model_id === $currentDeepSeekModel) $foundCurrentDeepSeek = true;
                    echo '<option value="' . esc_attr($model_id) . '" ' . selected($currentDeepSeekModel, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                }
            }
             if (!$foundCurrentDeepSeek && !empty($currentDeepSeekModel)) {
                echo '<option value="' . esc_attr($currentDeepSeekModel) . '" selected>' . esc_html($currentDeepSeekModel) . ' (Manual)</option>';
            } elseif(empty($deepseek_model_list) && empty($currentDeepSeekModel)) {
                 echo '<option value="">'.esc_html__('(Sync to load models)', 'gpt3-ai-content-generator').'</option>';
            }
            ?>
        </select>
        <button id="aipkit_sync_deepseek_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="DeepSeek">
            <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
             <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Ollama Model -->
<div
    class="aipkit_form-group aipkit_model_field"
    id="aipkit_ollama_model_group"
    data-provider="Ollama"
    style="display: <?php echo ($current_provider === 'Ollama') ? 'block' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_ollama_model"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_ollama_model" name="ollama_model" class="aipkit_form-input aipkit_autosave_trigger">
            <?php
            $currentOllamaModel = $ollama_data['model'] ?? '';
            $foundCurrentOllama = false;
            if (!empty($ollama_model_list)) {
                foreach ($ollama_model_list as $m) {
                    $model_id = $m['id'] ?? '';
                    $model_name = $m['name'] ?? $model_id;
                     if($model_id === $currentOllamaModel) $foundCurrentOllama = true;
                    echo '<option value="' . esc_attr($model_id) . '" ' . selected($currentOllamaModel, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                }
            }
             if (!$foundCurrentOllama && !empty($currentOllamaModel)) {
                echo '<option value="' . esc_attr($currentOllamaModel) . '" selected>' . esc_html($currentOllamaModel) . ' (Manual)</option>';
            } elseif(empty($ollama_model_list) && empty($currentOllamaModel)) {
                 echo '<option value="">'.esc_html__('(Sync to load models)', 'gpt3-ai-content-generator').'</option>';
            }
            ?>
        </select>
        <button id="aipkit_sync_ollama_models" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn " data-provider="Ollama">
            <span class="aipkit_btn-text"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator'); ?></span>
             <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>
