<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/elevenlabs.php
// Status: NEW FILE

/**
 * Partial: ElevenLabs TTS integration settings.
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent: $current_elevenlabs_api_key, $elevenlabs_voice_list, $current_elevenlabs_default_voice, $elevenlabs_model_list, $current_elevenlabs_default_model
?>
<!-- ElevenLabs TTS Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('ElevenLabs', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_elevenlabs_api_key"><?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_api-key-wrapper">
                <input
                    type="password"
                    id="aipkit_elevenlabs_api_key"
                    name="elevenlabs_api_key"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    value="<?php echo esc_attr($current_elevenlabs_api_key); ?>"
                    placeholder="<?php esc_attr_e('Enter your ElevenLabs API key', 'gpt3-ai-content-generator'); ?>"
                />
                <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
            </div>
        </div>

        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_elevenlabs_voice_id"><?php esc_html_e('Default Voice', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <select id="aipkit_elevenlabs_voice_id" name="elevenlabs_voice_id" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value=""><?php esc_html_e('-- Select a Voice (Optional) --', 'gpt3-ai-content-generator'); ?></option>
                    <?php
                    if (!empty($elevenlabs_voice_list)) {
                        foreach ($elevenlabs_voice_list as $voice) {
                            $voice_id = $voice['id'] ?? '';
                            $voice_name = $voice['name'] ?? $voice_id;
                            echo '<option value="' . esc_attr($voice_id) . '" ' . selected($current_elevenlabs_default_voice, $voice_id, false) . '>' . esc_html($voice_name) . '</option>';
                        }
                    } elseif (!empty($current_elevenlabs_default_voice)) {
                        echo '<option value="' . esc_attr($current_elevenlabs_default_voice) . '" selected>' . esc_html($current_elevenlabs_default_voice) . ' (Manual/Not Synced)</option>';
                    }
                    ?>
                </select>
                <button id="aipkit_sync_elevenlabs_voices" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn" data-provider="ElevenLabs">
                    <span class="aipkit_btn-text"><?php echo esc_html__('Sync Voices', 'gpt3-ai-content-generator'); ?></span>
                     <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </div>
             <div class="aipkit_form-help">
                <?php esc_html_e('Sync available voices from ElevenLabs. API Key must be set above.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>

        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_elevenlabs_tts_model_id"><?php esc_html_e('Default Model', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_input-with-button">
                <select id="aipkit_elevenlabs_tts_model_id" name="elevenlabs_model_id" class="aipkit_form-input aipkit_autosave_trigger">
                    <option value=""><?php esc_html_e('-- Select a Model (Optional) --', 'gpt3-ai-content-generator'); ?></option>
                     <?php
                     if (!empty($elevenlabs_model_list)) {
                         foreach ($elevenlabs_model_list as $model) {
                             $model_id_val = $model['id'] ?? '';
                             $model_name_val = $model['name'] ?? $model_id_val;
                             echo '<option value="' . esc_attr($model_id_val) . '" ' . selected($current_elevenlabs_default_model, $model_id_val, false) . '>' . esc_html($model_name_val) . '</option>';
                         }
                     } elseif (!empty($current_elevenlabs_default_model)) {
                         echo '<option value="' . esc_attr($current_elevenlabs_default_model) . '" selected>' . esc_html($current_elevenlabs_default_model) . ' (Manual/Not Synced)</option>';
                     }
                     ?>
                </select>
                <button id="aipkit_sync_elevenlabs_models_btn" class="aipkit_btn aipkit_btn-secondary aipkit_sync_btn" data-provider="ElevenLabsModels">
                    <span class="aipkit_btn-text"><?php echo esc_html__('Sync Models', 'gpt3-ai-content-generator'); ?></span>
                     <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </div>
             <div class="aipkit_form-help">
                <?php esc_html_e('Sync available synthesis models from ElevenLabs. API Key must be set above.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>
    </div>
</div>