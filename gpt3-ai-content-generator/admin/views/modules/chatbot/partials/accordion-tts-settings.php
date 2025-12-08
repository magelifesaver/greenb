<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/chatbot/partials/accordion-tts-settings.php
// Status: MODIFIED

/**
 * Partial: Chatbot Text-to-Speech Settings Accordion Content
 * ADDED: OpenAI TTS Model selection dropdown.
 * UPDATED: Populate OpenAI TTS models using AIPKit_Providers::get_openai_tts_models().
 * MODIFIED: Layout of all TTS settings for a more compact, inline appearance.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\AIPKit_Providers; // Use for ElevenLabs/OpenAI voices & models

// Variables available from parent script:
// $bot_id, $bot_settings

$tts_enabled = isset($bot_settings['tts_enabled'])
               ? $bot_settings['tts_enabled']
               : BotSettingsManager::DEFAULT_TTS_ENABLED;
$tts_provider = isset($bot_settings['tts_provider'])
                ? $bot_settings['tts_provider']
                : BotSettingsManager::DEFAULT_TTS_PROVIDER;
$tts_google_voice_id = isset($bot_settings['tts_google_voice_id'])
                       ? $bot_settings['tts_google_voice_id']
                       : '';
$tts_openai_voice_id = isset($bot_settings['tts_openai_voice_id']) // Get OpenAI voice ID
                       ? $bot_settings['tts_openai_voice_id']
                       : 'alloy'; // Default OpenAI voice
$tts_openai_model_id = isset($bot_settings['tts_openai_model_id']) // NEW: Get OpenAI TTS Model
                       ? $bot_settings['tts_openai_model_id']
                       : BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID;
$tts_elevenlabs_voice_id = isset($bot_settings['tts_elevenlabs_voice_id'])
                           ? $bot_settings['tts_elevenlabs_voice_id']
                           : '';
$tts_elevenlabs_model_id = isset($bot_settings['tts_elevenlabs_model_id'])
                           ? $bot_settings['tts_elevenlabs_model_id']
                           : BotSettingsManager::DEFAULT_TTS_ELEVENLABS_MODEL_ID;
$tts_auto_play = isset($bot_settings['tts_auto_play'])
                 ? $bot_settings['tts_auto_play']
                 : BotSettingsManager::DEFAULT_TTS_AUTO_PLAY;

// Available TTS providers
$tts_providers = ['Google', 'OpenAI', 'ElevenLabs'];

// Fetch synced Google Voices
$google_tts_voices = [];
if (class_exists(GoogleSettingsHandler::class)) {
    $google_tts_voices = GoogleSettingsHandler::get_synced_google_tts_voices();
}

// Fetch synced ElevenLabs Voices
$elevenlabs_tts_voices = [];
if (class_exists(AIPKit_Providers::class)) {
    $elevenlabs_tts_voices = AIPKit_Providers::get_elevenlabs_voices();
}

// Fetch synced ElevenLabs Models
$elevenlabs_tts_models = [];
if (class_exists(AIPKit_Providers::class)) {
    $elevenlabs_tts_models = AIPKit_Providers::get_elevenlabs_models();
}
// Fetch synced OpenAI TTS Models
$openai_tts_models = [];
if (class_exists(AIPKit_Providers::class)) {
    $openai_tts_models = AIPKit_Providers::get_openai_tts_models(); // Use the new getter
}

// Hardcoded OpenAI voices
$openai_tts_voices = [
    ['id' => 'alloy', 'name' => 'Alloy'], ['id' => 'echo', 'name' => 'Echo'],
    ['id' => 'fable', 'name' => 'Fable'], ['id' => 'onyx', 'name' => 'Onyx'],
    ['id' => 'nova', 'name' => 'Nova'], ['id' => 'shimmer', 'name' => 'Shimmer'],
];


?>
<div class="aipkit_accordion" data-section="tts-settings">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Text to Speech', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">

        <!-- Subsection: Voice Playback Toggle -->
        <div class="aipkit_settings_subsection">
          <div class="aipkit_settings_subsection-header">
            <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Text to Speech', 'gpt3-ai-content-generator'); ?></h5>
          </div>
          <div class="aipkit_settings_subsection-body">
            <!-- Enable TTS Checkbox -->
            <div class="aipkit_form-row aipkit_checkbox-row">
            <div class="aipkit_form-group">
                <label
                    class="aipkit_form-label aipkit_checkbox-label"
                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_enabled"
                >
                    <input
                        type="checkbox"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_enabled"
                        name="tts_enabled"
                        class="aipkit_toggle_switch aipkit_tts_toggle_switch"
                        value="1"
                        <?php checked($tts_enabled, '1'); ?>
                    >
                    <?php esc_html_e('Enable Text to Speech', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_form-help">
                    <?php esc_html_e('Enable text to speech for bot responses.', 'gpt3-ai-content-generator'); ?>
                </div>
            </div>
            <div
                class="aipkit_form-group aipkit_tts_auto_play_container"
                style="display: <?php echo $tts_enabled === '1' ? 'block' : 'none'; ?>;"
            >
                 <label
                    class="aipkit_form-label aipkit_checkbox-label"
                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_auto_play"
                >
                    <input
                        type="checkbox"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_auto_play"
                        name="tts_auto_play"
                        class="aipkit_toggle_switch"
                        value="1"
                        <?php checked($tts_auto_play, '1'); ?>
                    >
                    <?php esc_html_e('Auto Play', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_form-help">
                    <?php esc_html_e('Auto-play bot responses.', 'gpt3-ai-content-generator'); ?>
                </div>
            </div>
        
        <!-- Provider & Voice/Model (shown only when TTS is enabled) -->
        <div class="aipkit_tts_conditional_settings"
             id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_conditional"
             style="display: <?php echo $tts_enabled === '1' ? 'block' : 'none'; ?>; "
        >
            <div class="aipkit_form-row" style="align-items: center; gap: 10px; display:flex; flex-wrap: nowrap;">
                <!-- Provider Selection Column -->
                <div class="aipkit_form-group" style="flex: 0 0 200px; min-width: 180px;">
                    <label
                        class="aipkit_form-label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_provider"
                    >
                        <?php esc_html_e('TTS Provider', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_provider"
                        name="tts_provider"
                        class="aipkit_form-input aipkit_tts_provider_select"
                    >
                        <?php foreach ($tts_providers as $provider_name): ?>
                            <option
                                value="<?php echo esc_attr($provider_name); ?>"
                                <?php selected($tts_provider, $provider_name); ?>
                            >
                                <?php echo esc_html($provider_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Provider-specific fields Column -->
                <div class="aipkit_form-group" style="flex: 1 1 auto; margin:0;">
                     <!-- Google TTS Settings -->
                    <div
                        class="aipkit_tts_provider_settings"
                        data-provider="Google"
                        style="display: <?php echo $tts_provider === 'Google' ? 'block' : 'none'; ?>;"
                    >
                        <div class="aipkit_input-with-button" style="display:flex; gap:10px; align-items:flex-start;">
                            <div class="aipkit_form-group" style="flex: 1 1 auto;">
                                <label
                                    class="aipkit_form-label"
                                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id"
                                ><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                <select
                                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id"
                                    name="tts_google_voice_id"
                                    class="aipkit_form-input"
                                >
                                 <option value=""><?php esc_html_e('-- Select Voice --', 'gpt3-ai-content-generator'); ?></option>
                                 <?php
                                 // Repopulate based on potentially synced voices
                                 if (!empty($google_tts_voices) && is_array($google_tts_voices)) {
                                     $voices_by_lang = [];
                                     foreach ($google_tts_voices as $v) {
                                          if (!isset($v['id']) || !isset($v['name']) || !isset($v['languageCodes'][0])) continue;
                                          $langCode = $v['languageCodes'][0];
                                          if (!isset($voices_by_lang[$langCode])) $voices_by_lang[$langCode] = [];
                                          $voices_by_lang[$langCode][] = $v;
                                     }
                                     ksort($voices_by_lang);
                                     foreach ($voices_by_lang as $langCode => $voices) {
                                          $langName = $langCode;
                                          if (class_exists('IntlDisplayNames')) {
                                              try { $langName = \IntlDisplayNames::forLanguageTag($langCode, 'en'); } catch (\Exception $e) {}
                                          }
                                          echo '<optgroup label="' . esc_attr("{$langName} ({$langCode})") . '">';
                                          usort($voices, fn($a, $b) => strcmp($a['name'], $b['name']));
                                          foreach ($voices as $v) {
                                              echo '<option value="' . esc_attr($v['id']) . '" ' . selected($tts_google_voice_id, $v['id'], false) . '>' . esc_html($v['name']) . '</option>';
                                          }
                                          echo '</optgroup>';
                                     }
                                 } elseif (!empty($tts_google_voice_id)) { // Show saved if sync failed or empty
                                      echo '<option value="'.esc_attr($tts_google_voice_id).'" selected>'.esc_html($tts_google_voice_id).' (Saved)</option>';
                                 }
                                 ?>
                                </select>
                            </div>
                            <div class="aipkit_form-group" style="flex: 0 0 auto; margin: 0;">
                                <label class="aipkit_form-label">&nbsp;</label>
                                <button
                                    type="button"
                                    class="aipkit_btn aipkit_btn-secondary aipkit_sync_tts_voices_btn"
                                    data-provider="Google"
                                    data-target-select="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id"
                                >
                                    <span class="aipkit_btn-text"><?php esc_html_e('Sync Voices', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_spinner" style="display:none;"></span>
                                </button>
                            </div>
                        </div>
                    </div><!-- /Google Settings -->

                    <!-- OpenAI TTS Settings -->
                    <div
                        class="aipkit_tts_provider_settings"
                        data-provider="OpenAI"
                        style="display: <?php echo $tts_provider === 'OpenAI' ? 'block' : 'none'; ?>;"
                    >
                        <div class="aipkit_input-with-button" style="display:flex; gap: 10px;">
                            <div class="aipkit_form-group" style="flex: 1 1 50%;">
                                <label
                                    class="aipkit_form-label"
                                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_voice_id"
                                ><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                <select
                                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_voice_id"
                                    name="tts_openai_voice_id"
                                    class="aipkit_form-input"
                                >
                                    <?php foreach ($openai_tts_voices as $voice): ?>
                                        <option value="<?php echo esc_attr($voice['id']); ?>" <?php selected($tts_openai_voice_id, $voice['id']); ?>>
                                            <?php echo esc_html($voice['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="aipkit_form-group" style="flex: 1 1 50%;">
                                <label
                                    class="aipkit_form-label"
                                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_model_id"
                                ><?php esc_html_e('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                                <select
                                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_model_id"
                                    name="tts_openai_model_id"
                                    class="aipkit_form-input"
                                >
                                    <?php
                                    if (!empty($openai_tts_models)) {
                                        foreach ($openai_tts_models as $model) {
                                            $model_id_val = $model['id'] ?? '';
                                            $model_name_val = $model['name'] ?? $model_id_val;
                                            echo '<option value="' . esc_attr($model_id_val) . '" ' . selected($tts_openai_model_id, $model_id_val, false) . '>' . esc_html($model_name_val) . '</option>';
                                        }
                                    } elseif (!empty($tts_openai_model_id)) { // Show saved if sync failed or empty
                                        echo '<option value="'.esc_attr($tts_openai_model_id).'" selected>'.esc_html($tts_openai_model_id).' (Saved)</option>';
                                    } else { // Fallback if nothing saved and sync failed
                                        echo '<option value="'.esc_attr(BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID).'" selected>'.esc_html(BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID).' (Default)</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div><!-- /OpenAI Settings -->

                    <!-- ElevenLabs TTS Settings -->
                    <div
                        class="aipkit_tts_provider_settings"
                        data-provider="ElevenLabs"
                        style="display: <?php echo $tts_provider === 'ElevenLabs' ? 'block' : 'none'; ?>;"
                    >
                        <div class="aipkit_input-with-button" style="display:flex; gap: 10px;">
                            <div class="aipkit_form-group" style="flex: 1 1 50%;">
                                <label
                                    class="aipkit_form-label"
                                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_voice_id"
                                ><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                <select
                                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_voice_id"
                                    name="tts_elevenlabs_voice_id"
                                    class="aipkit_form-input"
                                >
                                    <option value=""><?php esc_html_e('-- Select Voice --', 'gpt3-ai-content-generator'); ?></option>
                                    <?php
                                    if (!empty($elevenlabs_tts_voices) && is_array($elevenlabs_tts_voices)) {
                                        foreach ($elevenlabs_tts_voices as $voice) {
                                            if (!isset($voice['id']) || !isset($voice['name'])) continue;
                                            echo '<option value="' . esc_attr($voice['id']) . '" ' . selected($tts_elevenlabs_voice_id, $voice['id'], false) . '>' . esc_html($voice['name']) . '</option>';
                                        }
                                    } elseif (!empty($tts_elevenlabs_voice_id)) {
                                        echo '<option value="'.esc_attr($tts_elevenlabs_voice_id).'" selected>'.esc_html($tts_elevenlabs_voice_id).' (Saved)</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aipkit_form-group" style="flex: 1 1 50%;">
                                <label
                                    class="aipkit_form-label"
                                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_model_id"
                                ><?php esc_html_e('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                                <select
                                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_model_id"
                                    name="tts_elevenlabs_model_id"
                                    class="aipkit_form-input"
                                >
                                    <option value=""><?php esc_html_e('-- Select Model (Optional) --', 'gpt3-ai-content-generator'); ?></option>
                                    <?php
                                    if (!empty($elevenlabs_tts_models) && is_array($elevenlabs_tts_models)) {
                                        foreach ($elevenlabs_tts_models as $model) {
                                            if (!isset($model['id']) || !isset($model['name'])) continue;
                                            echo '<option value="' . esc_attr($model['id']) . '" ' . selected($tts_elevenlabs_model_id, $model['id'], false) . '>' . esc_html($model['name']) . '</option>';
                                        }
                                    } elseif (!empty($tts_elevenlabs_model_id)) {
                                        echo '<option value="'.esc_attr($tts_elevenlabs_model_id).'" selected>'.esc_html($tts_elevenlabs_model_id).' (Saved)</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div><!-- /ElevenLabs Settings -->

                </div>
            </div> <!-- /.aipkit_form-row for provider settings -->
        </div><!-- /.aipkit_tts_conditional_settings -->
        </div><!-- /.aipkit_settings_subsection-body -->
        </div><!-- /.aipkit_settings_subsection -->
    </div><!-- /.aipkit_accordion-content -->
</div><!-- /.aipkit_accordion -->
