<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/chatbot/partials/accordion-ai-config.php
/**
 * Partial: Chatbot AI Configuration Accordion Content

 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager; // Use new class for constants
use WPAICG\Vector\AIPKit_Vector_Store_Registry; // For fetching OpenAI vector stores
use WPAICG\AIPKit_Providers; // For fetching embedding models and Pinecone/Qdrant options
use WPAICG\aipkit_dashboard; // For Pro plan and addon checks

// Variables available from parent script (chatbot/index.php):
// $bot_id, $providers, $saved_provider, $grouped_openai_models, $saved_model,
// $openrouter_model_list, $google_model_list,
// $azure_deployment_list, $deepseek_model_list
// $bot_settings (contains stream_enabled, temperature, max_completion_tokens, max_messages etc.)

// --- NEW: Get value for Instructions ---
$saved_instructions = isset($bot_settings['instructions']) ? $bot_settings['instructions'] : '';
// --- END NEW ---

// Get current provider for this bot for conditional display
$current_provider_for_this_bot = $bot_settings['provider'] ?? 'OpenAI';
// Get OpenAI Conversation State setting value
$openai_conversation_state_enabled_val = $bot_settings['openai_conversation_state_enabled']
                                          ?? BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED;
// Get OpenAI Web Search settings
$openai_web_search_enabled_val = $bot_settings['openai_web_search_enabled']
                                  ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED;
$openai_web_search_context_size_val = $bot_settings['openai_web_search_context_size']
                                      ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE;
$openai_web_search_loc_type_val = $bot_settings['openai_web_search_loc_type']
                                  ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE;
$openai_web_search_loc_country_val = $bot_settings['openai_web_search_loc_country'] ?? '';
$openai_web_search_loc_city_val = $bot_settings['openai_web_search_loc_city'] ?? '';
$openai_web_search_loc_region_val = $bot_settings['openai_web_search_loc_region'] ?? '';
$openai_web_search_loc_timezone_val = $bot_settings['openai_web_search_loc_timezone'] ?? '';

// --- NEW: Get Google Search Grounding settings ---
$google_search_grounding_enabled_val = $bot_settings['google_search_grounding_enabled']
                                     ?? BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED;
$google_grounding_mode_val = $bot_settings['google_grounding_mode']
                             ?? BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
$google_grounding_dynamic_threshold_val = isset($bot_settings['google_grounding_dynamic_threshold'])
                                          ? floatval($bot_settings['google_grounding_dynamic_threshold'])
                                          : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
// Ensure threshold is within bounds (0.0 to 1.0)
$google_grounding_dynamic_threshold_val = max(0.0, min($google_grounding_dynamic_threshold_val, 1.0));
// --- END NEW ---

// --- NEW: Get Image Upload setting for icon button state ---
$enable_image_upload = isset($bot_settings['enable_image_upload'])
                        ? $bot_settings['enable_image_upload']
                        : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD;
// --- END NEW ---

// --- NEW: Get voice input setting value ---
$enable_voice_input = isset($bot_settings['enable_voice_input'])
                      ? $bot_settings['enable_voice_input']
                      : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;
// --- END NEW ---
// --- NEW: STT provider and models (for Audio modal)
$stt_provider = isset($bot_settings['stt_provider'])
    ? $bot_settings['stt_provider']
    : BotSettingsManager::DEFAULT_STT_PROVIDER;
$stt_openai_model_id = isset($bot_settings['stt_openai_model_id'])
    ? $bot_settings['stt_openai_model_id']
    : BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID;
$openai_stt_models = \WPAICG\AIPKit_Providers::get_openai_stt_models();
// --- END NEW ---

// --- NEW: TTS values for Audio modal
$tts_enabled = isset($bot_settings['tts_enabled'])
               ? $bot_settings['tts_enabled']
               : BotSettingsManager::DEFAULT_TTS_ENABLED;
$tts_provider = isset($bot_settings['tts_provider'])
                ? $bot_settings['tts_provider']
                : BotSettingsManager::DEFAULT_TTS_PROVIDER;
$tts_google_voice_id = isset($bot_settings['tts_google_voice_id'])
                       ? $bot_settings['tts_google_voice_id']
                       : '';
$tts_openai_voice_id = isset($bot_settings['tts_openai_voice_id'])
                       ? $bot_settings['tts_openai_voice_id']
                       : 'alloy';
$tts_openai_model_id = isset($bot_settings['tts_openai_model_id'])
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
$tts_providers = ['Google', 'OpenAI', 'ElevenLabs'];

$google_tts_voices = [];
if (class_exists('\\WPAICG\\Core\\Providers\\Google\\GoogleSettingsHandler')) {
    $google_tts_voices = \WPAICG\Core\Providers\Google\GoogleSettingsHandler::get_synced_google_tts_voices();
}
$elevenlabs_tts_voices = [];
if (class_exists('\\WPAICG\\AIPKit_Providers')) {
    $elevenlabs_tts_voices = \WPAICG\AIPKit_Providers::get_elevenlabs_voices();
}
$elevenlabs_tts_models = [];
if (class_exists('\\WPAICG\\AIPKit_Providers')) {
    $elevenlabs_tts_models = \WPAICG\AIPKit_Providers::get_elevenlabs_models();
}
$openai_tts_models = [];
if (class_exists('\\WPAICG\\AIPKit_Providers')) {
    $openai_tts_models = \WPAICG\AIPKit_Providers::get_openai_tts_models();
}
$openai_tts_voices = [
    ['id' => 'alloy', 'name' => 'Alloy'], ['id' => 'echo', 'name' => 'Echo'],
    ['id' => 'fable', 'name' => 'Fable'], ['id' => 'onyx', 'name' => 'Onyx'],
    ['id' => 'nova', 'name' => 'Nova'], ['id' => 'shimmer', 'name' => 'Shimmer'],
];
// --- END NEW ---

// --- NEW: Realtime Voice Agent values for Audio modal
$popup_enabled = isset($bot_settings['popup_enabled']) ? $bot_settings['popup_enabled'] : '0';
$enable_realtime_voice = $bot_settings['enable_realtime_voice'] ?? BotSettingsManager::DEFAULT_ENABLE_REALTIME_VOICE;
$direct_voice_mode = $bot_settings['direct_voice_mode'] ?? BotSettingsManager::DEFAULT_DIRECT_VOICE_MODE;
$realtime_model = $bot_settings['realtime_model'] ?? BotSettingsManager::DEFAULT_REALTIME_MODEL;
$realtime_voice = $bot_settings['realtime_voice'] ?? BotSettingsManager::DEFAULT_REALTIME_VOICE;
$turn_detection = $bot_settings['turn_detection'] ?? BotSettingsManager::DEFAULT_TURN_DETECTION;
$speed = isset($bot_settings['speed']) ? floatval($bot_settings['speed']) : BotSettingsManager::DEFAULT_SPEED;
$input_audio_format = $bot_settings['input_audio_format'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_FORMAT;
$output_audio_format = $bot_settings['output_audio_format'] ?? BotSettingsManager::DEFAULT_OUTPUT_AUDIO_FORMAT;
$input_audio_noise_reduction = $bot_settings['input_audio_noise_reduction'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_NOISE_REDUCTION;
$realtime_models = ['gpt-4o-realtime-preview', 'gpt-4o-mini-realtime'];
$realtime_voices = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'verse'];
$direct_voice_mode_disabled = !($popup_enabled === '1' && $enable_realtime_voice === '1');
$direct_voice_mode_tooltip = $direct_voice_mode_disabled ? __('Requires "Popup Enabled" (in Appearance) and "Enable Realtime Voice Agent" to be active.', 'gpt3-ai-content-generator') : '';

// Determine plan/addon state for Realtime Voice Agent UI
$is_pro_plan = class_exists('\\WPAICG\\aipkit_dashboard') ? \WPAICG\aipkit_dashboard::is_pro_plan() : false;
$realtime_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') ? \WPAICG\aipkit_dashboard::is_addon_active('realtime_voice') : false;
$rt_disabled_by_plan = !$is_pro_plan;
$rt_addon_inactive = ($is_pro_plan && !$realtime_addon_active);
$rt_controls_disabled = ($rt_disabled_by_plan || $rt_addon_inactive);
$rt_force_visible = $rt_controls_disabled; // Show container even when toggle is off

// --- NEW: Max Messages value for Conversations modal ---
$saved_max_messages = isset($bot_settings['max_messages'])
    ? absint($bot_settings['max_messages'])
    : BotSettingsManager::DEFAULT_MAX_MESSAGES;
// --- END NEW ---

// --- NEW: Get reasoning effort value ---
$reasoning_effort_val = $bot_settings['reasoning_effort'] ?? BotSettingsManager::DEFAULT_REASONING_EFFORT;
// --- END NEW ---

// NEW: Stream mode (moved from provider-model toolbar)
$saved_stream_enabled = isset($bot_settings['stream_enabled'])
                        ? $bot_settings['stream_enabled']
                        : BotSettingsManager::DEFAULT_STREAM_ENABLED;
// duplicate file-upload logic removed

// --- NEW: Context & Vector Store variables (moved from Context tab) ---
$content_aware_enabled = isset($bot_settings['content_aware_enabled'])
    ? $bot_settings['content_aware_enabled']
    : BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED;

$vector_store_provider = isset($bot_settings['vector_store_provider'])
    ? $bot_settings['vector_store_provider']
    : BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER;

// OpenAI Specific
$openai_vector_store_ids_saved = isset($bot_settings['openai_vector_store_ids']) && is_array($bot_settings['openai_vector_store_ids'])
    ? $bot_settings['openai_vector_store_ids']
    : [];
// Pinecone Specific
$pinecone_index_name = $bot_settings['pinecone_index_name'] ?? BotSettingsManager::DEFAULT_PINECONE_INDEX_NAME;
$vector_embedding_provider = $bot_settings['vector_embedding_provider'] ?? BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER;
$vector_embedding_model = $bot_settings['vector_embedding_model'] ?? BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_MODEL;
// Qdrant Specific
$qdrant_collection_name = $bot_settings['qdrant_collection_name'] ?? BotSettingsManager::DEFAULT_QDRANT_COLLECTION_NAME; // legacy single
$qdrant_collection_names = [];
if (!empty($bot_settings['qdrant_collection_names']) && is_array($bot_settings['qdrant_collection_names'])) {
    $qdrant_collection_names = $bot_settings['qdrant_collection_names'];
} elseif (!empty($qdrant_collection_name)) {
    $qdrant_collection_names = [$qdrant_collection_name];
}

$vector_store_top_k = isset($bot_settings['vector_store_top_k'])
    ? absint($bot_settings['vector_store_top_k'])
    : BotSettingsManager::DEFAULT_VECTOR_STORE_TOP_K;
$vector_store_top_k = max(1, min($vector_store_top_k, 20));

$vector_store_confidence_threshold = $bot_settings['vector_store_confidence_threshold']
    ?? BotSettingsManager::DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD;
$vector_store_confidence_threshold = max(0, min(absint($vector_store_confidence_threshold), 100));

// Fetch available OpenAI vector stores
$openai_vector_stores = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
}
// Fetch available Pinecone indexes
$pinecone_indexes = [];
if (class_exists(AIPKit_Providers::class)) {
    $pinecone_indexes = AIPKit_Providers::get_pinecone_indexes();
}
// Fetch available Qdrant Collections
$qdrant_collections = [];
if (class_exists(AIPKit_Providers::class)) {
    $qdrant_collections = AIPKit_Providers::get_qdrant_collections();
}
// Embedding models
$openai_embedding_models = [];
$google_embedding_models = [];
$azure_embedding_models = [];
if (class_exists(AIPKit_Providers::class)) {
    $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
    $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
    $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
}
// --- END NEW ---
// --- NEW: File Upload toggle logic (moved from Context tab) ---
$enable_vector_store = isset($bot_settings['enable_vector_store'])
                      ? $bot_settings['enable_vector_store']
                      : BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE;
$enable_file_upload = $bot_settings['enable_file_upload'] ?? BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD;
$can_enable_file_upload = false;
$file_upload_disabled_reason = '';
$is_pro_plan_for_data_attr = 'false';
$file_upload_addon_active_for_data_attr = 'false';

if (class_exists(aipkit_dashboard::class)) {
    $is_pro_plan = aipkit_dashboard::is_pro_plan();
    $file_upload_addon_active = aipkit_dashboard::is_addon_active('file_upload');
    $is_vector_store_enabled_for_bot = ($enable_vector_store === '1');

    $is_pro_plan_for_data_attr = $is_pro_plan ? 'true' : 'false';
    $file_upload_addon_active_for_data_attr = $file_upload_addon_active ? 'true' : 'false';

    if (!$is_pro_plan) {
        $file_upload_disabled_reason = __('File upload is a Pro feature. Please upgrade.', 'gpt3-ai-content-generator');
    } elseif (!$file_upload_addon_active) {
        $file_upload_disabled_reason = 'The "File Upload" addon is not active. Please activate it in Add-ons.';
    } elseif (!$is_vector_store_enabled_for_bot) {
        $file_upload_disabled_reason = '"Enable Vector Store" must be active for this bot to use file uploads.';
    } else {
        $can_enable_file_upload = true;
    }
} else {
    $file_upload_disabled_reason = __('Cannot determine Pro status or addon activation.', 'gpt3-ai-content-generator');
}
// --- END NEW ---
?>
<div class="aipkit_accordion" data-section="ai-config-general">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('General', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <!-- Subsection: Engine & Model (segmented style) -->
        <div class="aipkit_settings_subsection">
            <div class="aipkit_settings_subsection-header">
                <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Engine & Model', 'gpt3-ai-content-generator'); ?></h5>
            </div>
            <div class="aipkit_settings_subsection-body">
                <?php
                // Provider and model selection + quick toggles
                include __DIR__ . '/ai-config/provider-model.php';
                ?>
                <!-- Instructions + Add Content (moved from Context) -->
                <div class="aipkit_form-group--mt12-mb0">
                    <label
                        class="aipkit_form-label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_instructions"
                    >
                        <?php esc_html_e('Instructions', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <textarea
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_instructions"
                        name="instructions"
                        class="aipkit_form-input"
                        rows="2"
                        placeholder="<?php esc_attr_e('e.g., You are a helpful AI Assistant. Please be friendly.', 'gpt3-ai-content-generator'); ?>"
                    ><?php echo esc_textarea($saved_instructions); ?></textarea>

                    <!-- Index Content Button -->
                    <div class="aipkit_form-help aipkit_form-help--mt8">
                        <button
                            type="button"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_index_content_btn"
                            class="aipkit_btn aipkit_btn-secondary aipkit_index_content_trigger"
                            data-bot-id="<?php echo esc_attr($bot_id); ?>"
                        >
                            <?php esc_html_e('Add Your Content', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <span class="aipkit_form-help-text"><?php esc_html_e('Make your site content available to this chatbot.', 'gpt3-ai-content-generator'); ?></span>
                    </div>

                    <!-- Inline Index Setup Container -->
                    <div
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_index_setup_container"
                        class="aipkit_index_setup_container"
                    >
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Subsection: Features -->
        <div class="aipkit_settings_subsection aipkit_settings_subsection--mt12">
            <div class="aipkit_settings_subsection-header">
                <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Features', 'gpt3-ai-content-generator'); ?></h5>
            </div>
            <div class="aipkit_settings_subsection-body aipkit_features_grid">
                <!-- Conversations (pseudo) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="*">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_conversations_feature_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_conversations_feature_enabled"
                                   name="conversations_feature_enabled_placeholder"
                                   class="aipkit_toggle_switch"
                                   value="1" checked disabled />
                            <span><?php esc_html_e('Conversations', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $convo_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_conversations_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="conversations"
                                data-modal-target="<?php echo esc_attr($convo_modal_id); ?>"
                                title="<?php echo esc_attr(__('Configure', 'gpt3-ai-content-generator')); ?>"><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Streaming and stateful settings.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                <!-- Context (pseudo) combines Content Awareness + Vector Store modal -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="*">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_context_feature_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_context_feature_enabled"
                                   name="context_feature_enabled_placeholder"
                                   class="aipkit_toggle_switch"
                                   value="1" checked disabled />
                            <span><?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $vector_store_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_vector_store_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="context"
                                data-modal-target="<?php echo esc_attr($vector_store_modal_id); ?>"
                                title="<?php echo esc_attr(__('Configure', 'gpt3-ai-content-generator')); ?>"><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Use page context and connect vector stores.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                <?php
                // Show Token Management only if addon is active. If addon status cannot be determined, default to showing.
                $aipkit_show_token_mgmt = true;
                if (class_exists('\\WPAICG\\aipkit_dashboard')) {
                    $aipkit_show_token_mgmt = \WPAICG\aipkit_dashboard::is_addon_active('token_management');
                }
                if ($aipkit_show_token_mgmt):
                ?>
                <!-- Token Management (Pseudo toggle: always enabled) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="*">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_mgmt_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_mgmt_enabled"
                                   name="token_mgmt_enabled_placeholder"
                                   class="aipkit_toggle_switch"
                                   value="1" checked disabled />
                            <span><?php esc_html_e('Limits', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $token_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_token_mgmt_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="token_management"
                                data-modal-target="<?php echo esc_attr($token_modal_id); ?>"
                                title="<?php echo esc_attr(__('Configure', 'gpt3-ai-content-generator')); ?>"><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Set token limits and reset policy.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <?php endif; ?>
                <!-- Images (Pseudo toggle + Configure modal) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="*">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_images_feature_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_images_feature_enabled"
                                   name="images_feature_enabled_placeholder"
                                   class="aipkit_toggle_switch"
                                   value="1" checked disabled />
                            <span><?php esc_html_e('Image', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $images_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_images_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="images"
                                data-modal-target="<?php echo esc_attr($images_modal_id); ?>"
                                title="<?php echo esc_attr(__('Configure', 'gpt3-ai-content-generator')); ?>"><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Text-to-image and image analysis.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                
                <!-- Audio (Pseudo toggle + Configure modal) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="*">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_audio_feature_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_audio_feature_enabled"
                                   name="audio_feature_enabled_placeholder"
                                   class="aipkit_toggle_switch"
                                   value="1" checked disabled />
                            <span><?php esc_html_e('Audio', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $audio_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_audio_settings_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="audio"
                                data-modal-target="<?php echo esc_attr($audio_modal_id); ?>"
                                title="<?php echo esc_attr(__('Configure', 'gpt3-ai-content-generator')); ?>"><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Speech to text, text to speech, and realtime.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                <!-- OpenAI Web Search (OpenAI only) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="OpenAI">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_enabled"
                                   name="openai_web_search_enabled"
                                   class="aipkit_toggle_switch aipkit_openai_web_search_enable_toggle"
                                   value="1" <?php checked($openai_web_search_enabled_val, '1'); ?> />
                            <span><?php esc_html_e('Web search', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $web_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_web_settings_modal'; ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="openai_web_search"
                                data-modal-target="<?php echo esc_attr($web_modal_id); ?>"
                                title="<?php echo esc_attr( ($openai_web_search_enabled_val === '1') ? __('Configure', 'gpt3-ai-content-generator') : __('Enable to configure', 'gpt3-ai-content-generator') ); ?>" <?php echo ($openai_web_search_enabled_val === '1') ? '' : 'disabled'; ?>><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Let the assistant browse the web.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                <!-- Google Search Grounding (Google only) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group" data-providers="Google">
                    <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_search_grounding_enabled">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_search_grounding_enabled"
                                   name="google_search_grounding_enabled"
                                   class="aipkit_toggle_switch aipkit_google_search_grounding_enable_toggle"
                                   value="1" <?php checked($google_search_grounding_enabled_val, '1'); ?> />
                            <span><?php esc_html_e('Web search', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php $web_modal_id = isset($web_modal_id) ? $web_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_web_settings_modal'); ?>
                        <button type="button"
                                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn"
                                data-feature="google_search_grounding"
                                data-modal-target="<?php echo esc_attr($web_modal_id); ?>"
                                title="<?php echo esc_attr( ($google_search_grounding_enabled_val === '1') ? __('Configure', 'gpt3-ai-content-generator') : __('Enable to configure', 'gpt3-ai-content-generator') ); ?>" <?php echo ($google_search_grounding_enabled_val === '1') ? '' : 'disabled'; ?>><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Let the assistant browse the web.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                <?php // Stateful Conversation moved into Conversations modal; tile removed ?>

                <?php // Image analysis control moved into the Images modal; tile removed ?>

                <!-- File Upload (requires Vector Store + Pro + Addon) -->
                <div class="aipkit_feature_toggle_item aipkit_form-group aipkit_file_upload_field_group<?php echo (!$can_enable_file_upload ? ' aipkit_dimmed_row' : ''); ?>" data-providers="*"<?php if (!empty($file_upload_disabled_reason)): ?> title="<?php echo esc_attr($file_upload_disabled_reason); ?>"<?php endif; ?>>
                <div class="aipkit_feature_row">
                        <label class="aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_file_upload">
                            <input type="checkbox"
                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_file_upload"
                                   name="enable_file_upload"
                                   class="aipkit_toggle_switch aipkit_file_upload_toggle_switch"
                                   value="1"
                                   <?php checked($can_enable_file_upload && ($enable_file_upload === '1')); ?>
                                   <?php disabled(!$can_enable_file_upload); ?>
                                   data-is-pro-plan="<?php echo esc_attr($is_pro_plan_for_data_attr); ?>"
                                   data-addon-active="<?php echo esc_attr($file_upload_addon_active_for_data_attr); ?>" />
                            <span><?php esc_html_e('File upload', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <?php if (isset($is_pro_plan) && !$is_pro_plan): ?>
                            <a href="<?php echo esc_url( admin_url('admin.php?page=wpaicg-pricing') ); ?>"
                               class="aipkit_btn aipkit_btn-secondary aipkit_btn-small"
                               title="<?php esc_attr_e('Upgrade to Pro', 'gpt3-ai-content-generator'); ?>">
                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Allow users upload files and chat with them.', 'gpt3-ai-content-generator'); ?></div>
                </div>

                
            </div>
        </div>

        <!-- Audio Settings Modal -->
        <?php $audio_modal_id = isset($audio_modal_id) ? $audio_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_audio_settings_modal'); ?>
        <div id="<?php echo esc_attr($audio_modal_id); ?>"
             class="aipkit_chatbot_settings_modal"
             aria-hidden="true"
             style="display:none;">
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($audio_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($audio_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Audio Settings', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <div class="aipkit_settings_sections">
                    <div class="aipkit_settings_sections">
                    <!-- Speech to Text -->
                    <section class="aipkit_settings_section" data-section="stt">
                        <div class="aipkit_settings_section-header">
                            <h5 class="aipkit_settings_section-title"><?php esc_html_e('Speech to Text', 'gpt3-ai-content-generator'); ?></h5>
                        </div>
                        <div class="aipkit_settings_section-body">
                            <div class="aipkit_settings_grid aipkit_settings_grid--3">
                                <!-- Enable Speech to Text -->
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_voice_input_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_voice_input_modal" name="enable_voice_input" class="aipkit_toggle_switch aipkit_voice_input_toggle_switch" value="1" <?php checked($enable_voice_input, '1'); ?>>
                                        <?php esc_html_e('Enable Speech to Text', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Speak to the bot using your mic.', 'gpt3-ai-content-generator'); ?></div>
                                </div>

                                <!-- Provider + Model (conditional) as grid items -->
                                <div class="aipkit_stt_provider_conditional_row" style="display: <?php echo $enable_voice_input === '1' ? 'contents' : 'none'; ?>;">
                                    <!-- STT Provider -->
                                    <div class="aipkit_form-group aipkit_stt_provider_group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stt_provider_modal"><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stt_provider_modal" name="stt_provider" class="aipkit_form-input aipkit_stt_provider_select">
                                            <option value="OpenAI" <?php selected($stt_provider, 'OpenAI'); ?>><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                                        </select>
                                    </div>

                                    <!-- OpenAI STT Model -->
                                    <div class="aipkit_form-group aipkit_stt_model_field" data-stt-provider="OpenAI" style="display: <?php echo $stt_provider === 'OpenAI' ? 'block' : 'none'; ?>;">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stt_openai_model_id_modal"><?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stt_openai_model_id_modal" name="stt_openai_model_id" class="aipkit_form-input">
                                            <?php
                                            $foundCurrentSTT = false;
                                            if (!empty($openai_stt_models)) {
                                                foreach ($openai_stt_models as $model) {
                                                    $model_id_val = $model['id'] ?? '';
                                                    $model_name_val = $model['name'] ?? $model_id_val;
                                                    if ($model_id_val === $stt_openai_model_id) $foundCurrentSTT = true;
                                                    echo '<option value="' . esc_attr($model_id_val) . '" ' . selected($stt_openai_model_id, $model_id_val, false) . '>' . esc_html($model_name_val) . '</option>';
                                                }
                                            }
                                            if (!$foundCurrentSTT && !empty($stt_openai_model_id)) {
                                                echo '<option value="'.esc_attr($stt_openai_model_id).'" selected>'.esc_html($stt_openai_model_id).' (Manual/Not Synced)</option>';
                                            } elseif (empty($openai_stt_models) && empty($stt_openai_model_id)) {
                                                echo '<option value="whisper-1" selected>whisper-1 (Default)</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Text to Speech (container) -->
                    <?php if ($is_voice_playback_active): ?>
                    <section class="aipkit_settings_section" data-section="tts">
                        <div class="aipkit_settings_section-header">
                            <h5 class="aipkit_settings_section-title"><?php esc_html_e('Text to Speech', 'gpt3-ai-content-generator'); ?></h5>
                        </div>
                        <div class="aipkit_settings_section-body">
                            <div class="aipkit_settings_grid aipkit_settings_grid--3">
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_enabled_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_enabled_modal" name="tts_enabled" class="aipkit_toggle_switch aipkit_tts_toggle_switch" value="1" <?php checked($tts_enabled, '1'); ?>>
                                        <?php esc_html_e('Enable Text to Speech', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Enable text to speech for bot responses.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group aipkit_tts_auto_play_container" style="display: <?php echo $tts_enabled === '1' ? 'block' : 'none'; ?>;">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_auto_play_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_auto_play_modal" name="tts_auto_play" class="aipkit_toggle_switch" value="1" <?php checked($tts_auto_play, '1'); ?>>
                                        <?php esc_html_e('Auto Play', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Auto-play bot responses.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>

                            <div class="aipkit_tts_conditional_settings" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_conditional_modal" style="display: <?php echo $tts_enabled === '1' ? 'block' : 'none'; ?>;">
                                <div class="aipkit_settings_grid aipkit_settings_grid--3">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_provider_modal"><?php esc_html_e('TTS Provider', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_provider_modal" name="tts_provider" class="aipkit_form-input aipkit_tts_provider_select">
                                            <?php foreach ($tts_providers as $provider_name): ?>
                                                <option value="<?php echo esc_attr($provider_name); ?>" <?php selected($tts_provider, $provider_name); ?>><?php echo esc_html($provider_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <!-- Voice Name column (changes by provider) -->
                                        <div class="aipkit_tts_field" data-provider="Google" style="display: <?php echo $tts_provider === 'Google' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id_modal"><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id_modal" name="tts_google_voice_id" class="aipkit_form-input">
                                                <option value=""><?php esc_html_e('-- Select Voice --', 'gpt3-ai-content-generator'); ?></option>
                                                <?php
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
                                                } elseif (!empty($tts_google_voice_id)) {
                                                    echo '<option value="' . esc_attr($tts_google_voice_id) . '" selected>' . esc_html($tts_google_voice_id) . ' (Saved)</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="aipkit_tts_field" data-provider="OpenAI" style="display: <?php echo $tts_provider === 'OpenAI' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_voice_id_modal"><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_voice_id_modal" name="tts_openai_voice_id" class="aipkit_form-input">
                                                <?php foreach ($openai_tts_voices as $voice): ?>
                                                    <option value="<?php echo esc_attr($voice['id']); ?>" <?php selected($tts_openai_voice_id, $voice['id']); ?>><?php echo esc_html($voice['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="aipkit_tts_field" data-provider="ElevenLabs" style="display: <?php echo $tts_provider === 'ElevenLabs' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_voice_id_modal"><?php esc_html_e('Voice Name', 'gpt3-ai-content-generator'); ?></label>
                                            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_voice_id_modal" name="tts_elevenlabs_voice_id" class="aipkit_form-input">
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
                                    </div>

                                    <div class="aipkit_form-group">
                                        <!-- Third column by provider: Google Actions / OpenAI Model / ElevenLabs Model -->
                                        <div class="aipkit_tts_field" data-provider="Google" style="display: <?php echo $tts_provider === 'Google' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label">&nbsp;</label>
                                            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sync_tts_voices_btn" data-provider="Google" data-target-select="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_google_voice_id_modal">
                                                <span class="aipkit_btn-text"><?php esc_html_e('Sync Voices', 'gpt3-ai-content-generator'); ?></span>
                                                <span class="aipkit_spinner" style="display:none;"></span>
                                            </button>
                                        </div>
                                        <div class="aipkit_tts_field" data-provider="OpenAI" style="display: <?php echo $tts_provider === 'OpenAI' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_model_id_modal"><?php esc_html_e('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                                            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_openai_model_id_modal" name="tts_openai_model_id" class="aipkit_form-input">
                                                <?php
                                                if (!empty($openai_tts_models)) {
                                                    foreach ($openai_tts_models as $model) {
                                                        $model_id_val = $model['id'] ?? '';
                                                        $model_name_val = $model['name'] ?? $model_id_val;
                                                        echo '<option value="' . esc_attr($model_id_val) . '" ' . selected($tts_openai_model_id, $model_id_val, false) . '>' . esc_html($model_name_val) . '</option>';
                                                    }
                                                } elseif (!empty($tts_openai_model_id)) {
                                                    echo '<option value="'.esc_attr($tts_openai_model_id).'" selected>'.esc_html($tts_openai_model_id).' (Saved)</option>';
                                                } else {
                                                    echo '<option value="'.esc_attr(BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID).'" selected>'.esc_html(BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID).' (Default)</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="aipkit_tts_field" data-provider="ElevenLabs" style="display: <?php echo $tts_provider === 'ElevenLabs' ? 'block' : 'none'; ?>;">
                                            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_model_id_modal"><?php esc_html_e('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                                            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_tts_elevenlabs_model_id_modal" name="tts_elevenlabs_model_id" class="aipkit_form-input">
                                                <option value=""><?php esc_html_e('-- Select Model (Optional) --', 'gpt3-ai-content-generator'); ?></option>
                                                <?php
                                                if (!empty($elevenlabs_tts_models) && is_array($elevenlabs_tts_models)) {
                                                    foreach ($elevenlabs_tts_models as $model) {
                                                        if (!isset($model['id']) || !isset($model['name'])) continue;
                                                        echo '<option value="' . esc_attr($model['id']) . '" ' . selected($tts_elevenlabs_model_id, $model['id'], false) . '>' . esc_html($model['name']) . '</option>';
                                                    }
                                                } elseif (!empty($tts_elevenlabs_model_id)) {
                                                    echo '<option value="' . esc_attr($tts_elevenlabs_model_id) . '" selected>' . esc_html($tts_elevenlabs_model_id) . ' (Saved)</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php /* end realtime voice section */ ?>

                    <!-- Realtime Voice Agent (container) -->
                    <?php /* always render realtime voice UI; disabled if not eligible */ ?>
                    <section class="aipkit_settings_section" data-section="voice-agent">
                        <div class="aipkit_settings_section-header">
                            <h5 class="aipkit_settings_section-title"><?php esc_html_e('Realtime Voice Agent', 'gpt3-ai-content-generator'); ?></h5>
                        </div>
                        <div class="aipkit_settings_section-body">
                            <div class="aipkit_settings_grid">
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_realtime_voice_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_realtime_voice_modal" name="enable_realtime_voice" class="aipkit_toggle_switch aipkit_enable_realtime_voice_toggle" value="1" <?php checked($enable_realtime_voice, '1'); ?> <?php echo $rt_controls_disabled ? 'disabled' : ''; ?> title="<?php echo esc_attr($rt_disabled_by_plan ? __('Upgrade to Pro to enable.', 'gpt3-ai-content-generator') : ($rt_addon_inactive ? __('Enable in Addons.', 'gpt3-ai-content-generator') : '')); ?>">
                                        <?php esc_html_e('Enable Realtime Voice Agent', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <?php if ($rt_disabled_by_plan): ?>
                                        <a href="<?php echo esc_url( admin_url('admin.php?page=wpaicg-pricing') ); ?>" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" title="<?php esc_attr_e('Upgrade to Pro', 'gpt3-ai-content-generator'); ?>"><?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?></a>
                                    <?php elseif ($rt_addon_inactive): ?>
                                        <div class="aipkit_form-help"><?php esc_html_e('Addon inactive. Go to Addons and enable Realtime Voice Agent.', 'gpt3-ai-content-generator'); ?></div>
                                    <?php else: ?>
                                        <div class="aipkit_form-help"><?php esc_html_e('Enable live voice conversation.', 'gpt3-ai-content-generator'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="aipkit_form-group aipkit_rt_dependent" data-tooltip-disabled="<?php echo esc_attr($direct_voice_mode_tooltip); ?>" title="<?php echo esc_attr($direct_voice_mode_tooltip); ?>">
                                    <label class="aipkit_form-label aipkit_checkbox-label <?php echo $direct_voice_mode_disabled ? 'aipkit-disabled-tooltip' : ''; ?>" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_direct_voice_mode_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_direct_voice_mode_modal" name="direct_voice_mode" class="aipkit_toggle_switch" value="1" <?php checked($direct_voice_mode, '1'); ?> <?php echo $rt_controls_disabled ? 'disabled' : ''; ?> <?php disabled($direct_voice_mode_disabled); ?>>
                                        <?php esc_html_e('Enable Direct Voice Mode', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Auto-listen when the chat opens.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group aipkit_rt_dependent">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_noise_reduction_modal">
                                        <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_noise_reduction_modal" name="input_audio_noise_reduction" class="aipkit_toggle_switch" value="1" <?php checked($input_audio_noise_reduction, '1'); ?> <?php echo $rt_controls_disabled ? 'disabled' : ''; ?> >
                                        <?php esc_html_e('Noise Reduction', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Reduce background noise.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>

                            <div class="aipkit_realtime_voice_settings_container" style="display: <?php echo $rt_force_visible ? 'block' : (($enable_realtime_voice === '1') ? 'block' : 'none'); ?>;" <?php echo $rt_force_visible ? 'data-force-visible="1"' : ''; ?>>
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_model_modal"><?php esc_html_e('Realtime Model', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_model_modal" name="realtime_model" class="aipkit_form-input" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?>>
                                            <?php foreach ($realtime_models as $model_id): ?>
                                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($realtime_model, $model_id); ?>><?php echo esc_html($model_id); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('Choose an OpenAI realtime model.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_voice_modal"><?php esc_html_e('Voice', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_voice_modal" name="realtime_voice" class="aipkit_form-input" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?>>
                                            <?php foreach ($realtime_voices as $voice_id): ?>
                                                <option value="<?php echo esc_attr($voice_id); ?>" <?php selected($realtime_voice, $voice_id); ?>><?php echo esc_html(ucfirst($voice_id)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('Pick the synthetic voice for replies.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_turn_detection_modal"><?php esc_html_e('Turn Detection', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_turn_detection_modal" name="turn_detection" class="aipkit_form-input" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?>>
                                            <option value="none" <?php selected($turn_detection, 'none'); ?>><?php esc_html_e('None (Push-to-Talk)', 'gpt3-ai-content-generator'); ?></option>
                                            <option value="server_vad" <?php selected($turn_detection, 'server_vad'); ?>><?php esc_html_e('Automatic (Voice Activity)', 'gpt3-ai-content-generator'); ?></option>
                                            <option value="semantic_vad" <?php selected($turn_detection, 'semantic_vad'); ?>><?php esc_html_e('Smart (Semantic Detection)', 'gpt3-ai-content-generator'); ?></option>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('Decide when speech has ended.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                                <div class="aipkit_settings_grid aipkit_mt_lg">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_format_modal"><?php esc_html_e('Input Audio Format', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_format_modal" name="input_audio_format" class="aipkit_form-input" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?>>
                                            <option value="pcm16" <?php selected($input_audio_format, 'pcm16'); ?>>pcm16</option>
                                            <option value="g711_ulaw" <?php selected($input_audio_format, 'g711_ulaw'); ?>>g711_ulaw</option>
                                            <option value="g711_alaw" <?php selected($input_audio_format, 'g711_alaw'); ?>>g711_alaw</option>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('Format of audio sent.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_output_audio_format_modal"><?php esc_html_e('Output Audio Format', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_output_audio_format_modal" name="output_audio_format" class="aipkit_form-input" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?>>
                                            <option value="pcm16" <?php selected($output_audio_format, 'pcm16'); ?>>pcm16</option>
                                            <option value="g711_ulaw" <?php selected($output_audio_format, 'g711_ulaw'); ?>>g711_ulaw</option>
                                            <option value="g711_alaw" <?php selected($output_audio_format, 'g711_alaw'); ?>>g711_alaw</option>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('Format of audio received.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed_modal"><?php esc_html_e('Response Speed', 'gpt3-ai-content-generator'); ?></label>
                                        <div class="aipkit_slider_wrapper">
                                            <input type="range" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed_modal" name="speed" class="aipkit_form-input aipkit_range_slider" min="0.25" max="1.5" step="0.05" value="<?php echo esc_attr($speed); ?>" <?php echo $rt_controls_disabled ? 'disabled' : ''; ?> />
                                            <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed_value_modal" class="aipkit_slider_value"><?php echo esc_html(number_format($speed, 2)); ?></span>
                                        </div>
                                        <div class="aipkit_form-help"><?php esc_html_e('Controls reply pacing.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                    </div>
                    </div>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>

        <!-- Image Settings Modal -->
        <?php $images_modal_id = isset($images_modal_id) ? $images_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_images_modal'); ?>
        <div id="<?php echo esc_attr($images_modal_id); ?>"
             class="aipkit_chatbot_settings_modal"
             aria-hidden="true"
             style="display:none;">
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($images_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($images_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Image Settings', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <div class="aipkit_settings_sections">
                        <!-- Image Generation: render self-contained partial (includes its own header/body) -->
                        <?php include __DIR__ . '/ai-config/image-settings.php'; ?>

                        <!-- Image Analysis (OpenAI only) -->
                        <section class="aipkit_settings_section" data-section="image_analysis">
                            <div class="aipkit_settings_section-header">
                                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Image Analysis', 'gpt3-ai-content-generator'); ?></h5>
                            </div>
                            <div class="aipkit_settings_section-body">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group aipkit_image_analysis_group" <?php if ($current_provider_for_this_bot !== 'OpenAI') { echo 'title="'.esc_attr__('Available for OpenAI only','gpt3-ai-content-generator').'"'; } ?>>
                                        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_image_upload_modal">
                                            <input type="checkbox"
                                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_image_upload_modal"
                                                   name="enable_image_upload"
                                                   class="aipkit_toggle_switch aipkit_image_analysis_checkbox"
                                                   value="1" <?php checked($enable_image_upload, '1'); ?> <?php disabled($current_provider_for_this_bot !== 'OpenAI'); ?> />
                                            <?php esc_html_e('Enable Image Analysis (OpenAI only)', 'gpt3-ai-content-generator'); ?>
                                        </label>
                                        <div class="aipkit_form-help"><?php esc_html_e('Let users upload an image to analyze.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>

        <!-- Token Management Modal -->
        <?php
        if (!isset($aipkit_show_token_mgmt)) {
            $aipkit_show_token_mgmt = class_exists('\\WPAICG\\aipkit_dashboard')
                ? \WPAICG\aipkit_dashboard::is_addon_active('token_management')
                : true; // default to showing if unknown
        }
        if ($aipkit_show_token_mgmt):
            $token_modal_id = isset($token_modal_id) ? $token_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_token_mgmt_modal'); ?>
        <div id="<?php echo esc_attr($token_modal_id); ?>"
             class="aipkit_chatbot_settings_modal"
             aria-hidden="true"
             style="display:none;">
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($token_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($token_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Token Management', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <?php include __DIR__ . '/ai-config/token-settings.php'; ?>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal: Conversations Settings -->
        <?php $convo_modal_id = isset($convo_modal_id) ? $convo_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_conversations_modal'); ?>
        <div id="<?php echo esc_attr($convo_modal_id); ?>"
             class="aipkit_chatbot_settings_modal"
             aria-hidden="true"
             style="display:none;">
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($convo_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($convo_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Conversations', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <div class="aipkit_settings_sections">
                        <!-- Streaming -->
                        <section class="aipkit_settings_section" data-section="streaming">
                            <div class="aipkit_settings_section-header">
                                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Streaming', 'gpt3-ai-content-generator'); ?></h5>
                            </div>
                            <div class="aipkit_settings_section-body">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stream_enabled_modal">
                                            <input type="checkbox"
                                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stream_enabled_modal"
                                                   name="stream_enabled"
                                                   class="aipkit_toggle_switch aipkit_stream_enable_toggle"
                                                   value="1" <?php checked($saved_stream_enabled, '1'); ?> />
                                            <?php esc_html_e('Stream responses', 'gpt3-ai-content-generator'); ?>
                                        </label>
                                        <div class="aipkit_form-help"><?php esc_html_e('Faster, incremental responses.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Stateful Conversation (OpenAI only: disabled when not OpenAI) -->
                        <section class="aipkit_settings_section" data-section="stateful-conversation">
                            <div class="aipkit_settings_section-header">
                                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Stateful Conversation', 'gpt3-ai-content-generator'); ?></h5>
                            </div>
                            <div class="aipkit_settings_section-body">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group aipkit_stateful_convo_group" <?php if ($current_provider_for_this_bot !== 'OpenAI') { echo 'title="'.esc_attr__('Available for OpenAI only','gpt3-ai-content-generator').'"'; } ?>>
                                        <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_conversation_state_enabled_modal">
                                            <input type="checkbox"
                                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_conversation_state_enabled_modal"
                                                   name="openai_conversation_state_enabled"
                                                   class="aipkit_toggle_switch aipkit_openai_conversation_state_enable_toggle aipkit_stateful_convo_checkbox"
                                                   value="1" <?php checked($openai_conversation_state_enabled_val, '1'); ?> <?php disabled($current_provider_for_this_bot !== 'OpenAI'); ?> />
                                            <?php esc_html_e('Enable stateful memory', 'gpt3-ai-content-generator'); ?>
                                        </label>
                                        <div class="aipkit_form-help"><?php esc_html_e('Persist conversation memory per user for continuity (OpenAI only).', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Message History (Max Messages) -->
                        <section class="aipkit_settings_section" data-section="message-history">
                            <div class="aipkit_settings_section-header">
                                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Message History', 'gpt3-ai-content-generator'); ?></h5>
                            </div>
                            <div class="aipkit_settings_section-body">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_messages_modal"><?php esc_html_e('Max Messages', 'gpt3-ai-content-generator'); ?></label>
                                        <div class="aipkit_slider_wrapper">
                                            <input type="range"
                                                   id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_messages_modal"
                                                   name="max_messages"
                                                   class="aipkit_form-input aipkit_range_slider"
                                                   min="1" max="1024" step="1"
                                                   value="<?php echo esc_attr($saved_max_messages); ?>" />
                                            <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_messages_modal_value" class="aipkit_slider_value"><?php echo esc_html($saved_max_messages); ?></span>
                                        </div>
                                        <div class="aipkit_form-help"><?php esc_html_e('Controls how many prior messages are kept.', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Reasoning Effort (OpenAI reasoning models only: disabled when not applicable) -->
                        <section class="aipkit_settings_section" data-section="reasoning">
                            <div class="aipkit_settings_section-header">
                                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Reasoning', 'gpt3-ai-content-generator'); ?></h5>
                            </div>
                            <div class="aipkit_settings_section-body">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group aipkit_reasoning_effort_field">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_reasoning_effort_modal"><?php esc_html_e('Reasoning', 'gpt3-ai-content-generator'); ?></label>
                                        <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_reasoning_effort_modal" name="reasoning_effort" class="aipkit_form-input">
                                            <option value="minimal" <?php selected($reasoning_effort_val, 'minimal'); ?>>Minimal</option>
                                            <option value="low" <?php selected($reasoning_effort_val, 'low'); ?>>Low (Default)</option>
                                            <option value="medium" <?php selected($reasoning_effort_val, 'medium'); ?>>Medium</option>
                                            <option value="high" <?php selected($reasoning_effort_val, 'high'); ?>>High</option>
                                        </select>
                                        <div class="aipkit_form-help"><?php esc_html_e('For reasoning models (OpenAI only), sets the effort/depth of reasoning (higher may improve quality at higher cost).', 'gpt3-ai-content-generator'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>
        <!-- Modal: AI Parameters (moved from inline to modal) -->
        <?php
        $ai_params_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_ai_params_modal';
        ?>
        <div
            id="<?php echo esc_attr($ai_params_modal_id); ?>"
            class="aipkit_chatbot_settings_modal"
            aria-hidden="true"
            
        >
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($ai_params_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($ai_params_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('AI Parameters', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <?php include __DIR__ . '/ai-config/parameters.php'; ?>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>

        <!-- Modal: Vector Store Settings -->
        <?php
        $vector_store_modal_id = isset($vector_store_modal_id) ? $vector_store_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_vector_store_modal');
        ?>
        <div
            id="<?php echo esc_attr($vector_store_modal_id); ?>"
            class="aipkit_chatbot_settings_modal"
            aria-hidden="true"
            
        >
            <div class="aipkit_modal_backdrop"></div>
            <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($vector_store_modal_id); ?>_title">
                <div class="aipkit_modal_header">
                    <h4 id="<?php echo esc_attr($vector_store_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?></h4>
                    <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
                </div>
                <div class="aipkit_modal_body">
                    <div class="aipkit_settings_sections">
                    <section class="aipkit_settings_section" data-section="vector-store">
                        <div class="aipkit_settings_section-header">
                            <h5 class="aipkit_settings_section-title"><?php esc_html_e('Vector Store', 'gpt3-ai-content-generator'); ?></h5>
                        </div>
                        <div class="aipkit_settings_section-body">
                            <!-- Actual enable switch for Vector Store -->
                            <div class="aipkit_form-group">
                                <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_vector_store_modal">
                                    <input
                                        type="checkbox"
                                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_vector_store_modal"
                                        name="enable_vector_store"
                                        class="aipkit_toggle_switch aipkit_vector_store_toggle_switch"
                                        value="1" <?php checked($enable_vector_store, '1'); ?>
                                    />
                                    <?php esc_html_e('Enable Vector Store', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <div class="aipkit_form-help"><?php esc_html_e('Use a knowledge base to provide additional context during chat.', 'gpt3-ai-content-generator'); ?></div>
                            </div>

                            <!-- Provider/fields shown only when enabled -->
                            <div class="aipkit_vector_store_settings_conditional_row" style="display: <?php echo ($enable_vector_store === '1') ? 'block' : 'none'; ?>;">
                            <div class="aipkit_settings_grid">
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_provider_modal">
                                        <?php esc_html_e('Vector Provider', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <select
                                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_provider_modal"
                                        name="vector_store_provider"
                                        class="aipkit_form-input aipkit_vector_store_provider_select"
                                    >
                                        <option value="openai" <?php selected($vector_store_provider, 'openai'); ?>>OpenAI</option>
                                        <option value="pinecone" <?php selected($vector_store_provider, 'pinecone'); ?>>Pinecone</option>
                                        <option value="qdrant" <?php selected($vector_store_provider, 'qdrant'); ?>>Qdrant</option>
                                    </select>
                                </div>

                                <!-- OpenAI Vector Store ID Select (Conditional) -->
                                <div class="aipkit_form-group aipkit_vector_store_openai_field">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_vector_store_ids_modal">
                                        <?php esc_html_e('Vector Stores (max 2)', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <select
                                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_vector_store_ids_modal"
                                        name="openai_vector_store_ids[]"
                                        class="aipkit_form-input aipkit_min-h-60"
                                        multiple
                                        size="3"
                                    >
                                        <?php
                                        if (!empty($openai_vector_stores)) {
                                            foreach ($openai_vector_stores as $store) {
                                                $store_id_val = $store['id'] ?? '';
                                                $store_name = $store['name'] ?? $store_id_val;
                                                $file_count_total = $store['file_counts']['total'] ?? null;
                                                $file_count_display = ($file_count_total !== null) ? " ({$file_count_total} " . _n('File', 'Files', (int)$file_count_total, 'gpt3-ai-content-generator') . ")" : ' (Files: N/A)';
                                                $option_text = esc_html($store_name . $file_count_display);
                                                $is_selected_attr = in_array($store_id_val, $openai_vector_store_ids_saved, true) ? 'selected="selected"' : '';
                                                echo '<option value="' . esc_attr($store_id_val) . '" ' . $is_selected_attr . '>' . $option_text . ' (ID: ' . esc_html(substr($store_id_val,0,15)).'...)</option>';
                                            }
                                        }
                                        foreach ($openai_vector_store_ids_saved as $saved_id) {
                                            $found_in_list = false;
                                            if (!empty($openai_vector_stores)) {
                                                foreach ($openai_vector_stores as $store) {
                                                    if (($store['id'] ?? '') === $saved_id) { $found_in_list = true; break; }
                                                }
                                            }
                                            if (!$found_in_list) {
                                                echo '<option value="' . esc_attr($saved_id) . '" selected="selected">' . esc_html($saved_id) . ' (Manual/Not Synced)</option>';
                                            }
                                        }
                                        if (empty($openai_vector_stores) && empty($openai_vector_store_ids_saved)) {
                                            echo '<option value="" disabled>'.esc_html__('-- No Vector Stores Found --', 'gpt3-ai-content-generator').'</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="aipkit_form-help"><?php esc_html_e('Hold Cmd/Ctrl to select multiple stores.', 'gpt3-ai-content-generator'); ?></div>
                                </div>

                                <!-- Pinecone Index Select (Conditional) -->
                                <div class="aipkit_form-group aipkit_vector_store_pinecone_field">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_pinecone_index_name_modal">
                                        <?php esc_html_e('Index Name', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <select
                                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_pinecone_index_name_modal"
                                        name="pinecone_index_name"
                                        class="aipkit_form-input"
                                    >
                                        <?php
                                        if (!empty($pinecone_indexes)) {
                                            foreach ($pinecone_indexes as $index) {
                                                $index_name = is_array($index) ? ($index['name'] ?? '') : (string)$index;
                                                $is_selected_attr = ($pinecone_index_name === $index_name) ? 'selected="selected"' : '';
                                                echo '<option value="' . esc_attr($index_name) . '" ' . $is_selected_attr . '>' . esc_html($index_name) . '</option>';
                                            }
                                        }
                                        if (!empty($pinecone_index_name) && (empty($pinecone_indexes) || !in_array($pinecone_index_name, array_column($pinecone_indexes, 'name')))) {
                                            echo '<option value="' . esc_attr($pinecone_index_name) . '" selected="selected">' . esc_html($pinecone_index_name) . ' (Manual/Not Synced)</option>';
                                        }
                                        if (empty($pinecone_indexes) && empty($pinecone_index_name)) {
                                            echo '<option value="" disabled>'.esc_html__('-- No Indexes Found --', 'gpt3-ai-content-generator').'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Qdrant Collection Select (Conditional) -->
                                <div class="aipkit_form-group aipkit_vector_store_qdrant_field">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_qdrant_collection_names_modal">
                                        <?php esc_html_e('Collections', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <select
                                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_qdrant_collection_names_modal"
                                        name="qdrant_collection_names[]"
                                        class="aipkit_form-input aipkit_min-h-60"
                                        multiple
                                        size="3"
                                    >
                                        <?php
                                        if (!empty($qdrant_collections)) {
                                            foreach ($qdrant_collections as $collection) {
                                                $collection_name = is_array($collection) ? ($collection['name'] ?? '') : (string)$collection;
                                                $is_selected_attr = in_array($collection_name, $qdrant_collection_names, true) ? 'selected="selected"' : '';
                                                echo '<option value="' . esc_attr($collection_name) . '" ' . $is_selected_attr . '>' . esc_html($collection_name) . '</option>';
                                            }
                                        }
                                        foreach ($qdrant_collection_names as $saved_name) {
                                            if (!in_array($saved_name, array_map(function($c){ return is_array($c) ? ($c['name'] ?? '') : (string)$c; }, $qdrant_collections), true)) {
                                                echo '<option value="' . esc_attr($saved_name) . '" selected="selected">' . esc_html($saved_name) . ' (Manual/Not Synced)</option>';
                                            }
                                        }
                                        if (empty($qdrant_collections) && empty($qdrant_collection_names)) {
                                            echo '<option value="" disabled>'.esc_html__('-- No Collections Found --', 'gpt3-ai-content-generator').'</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="aipkit_form-help"><?php esc_html_e('Hold Cmd/Ctrl to select multiple collections.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>

                            <!-- Embedding Provider & Model for Pinecone/Qdrant (Conditional) -->
                            <div class="aipkit_vector_store_embedding_config_row">
                                <div class="aipkit_settings_grid">
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_embedding_provider_modal">
                                            <?php esc_html_e('Embedding Provider', 'gpt3-ai-content-generator'); ?>
                                        </label>
                                        <select
                                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_embedding_provider_modal"
                                            name="vector_embedding_provider"
                                            class="aipkit_form-input aipkit_vector_embedding_provider_select"
                                        >
                                            <option value="openai" <?php selected($vector_embedding_provider, 'openai'); ?>>OpenAI</option>
                                            <option value="google" <?php selected($vector_embedding_provider, 'google'); ?>>Google</option>
                                            <option value="azure" <?php selected($vector_embedding_provider, 'azure'); ?>>Azure</option>
                                        </select>
                                    </div>
                                    <div class="aipkit_form-group">
                                        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_embedding_model_modal">
                                            <?php esc_html_e('Embedding Model', 'gpt3-ai-content-generator'); ?>
                                        </label>
                                        <select
                                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_embedding_model_modal"
                                            name="vector_embedding_model"
                                            class="aipkit_form-input aipkit_vector_embedding_model_select"
                                        >
                                            <option value=""><?php esc_html_e('-- Select Model --', 'gpt3-ai-content-generator'); ?></option>
                                            <?php
                                            $current_embedding_list = [];
                                            if ($vector_embedding_provider === 'openai') {
                                                $current_embedding_list = $openai_embedding_models;
                                            } elseif ($vector_embedding_provider === 'google') {
                                                $current_embedding_list = $google_embedding_models;
                                            } elseif ($vector_embedding_provider === 'azure') {
                                                $current_embedding_list = $azure_embedding_models;
                                            }
                                            if (!empty($current_embedding_list)) {
                                                foreach ($current_embedding_list as $model) {
                                                    $model_id_val = $model['id'] ?? '';
                                                    $model_name_val = $model['name'] ?? $model_id_val;
                                                    echo '<option value="' . esc_attr($model_id_val) . '" ' . selected($vector_embedding_model, $model_id_val, false) . '>' . esc_html($model_name_val) . '</option>';
                                                }
                                            }
                                            if (!empty($vector_embedding_model) && (empty($current_embedding_list) || !in_array($vector_embedding_model, array_column($current_embedding_list, 'id')))) {
                                                 echo '<option value="' . esc_attr($vector_embedding_model) . '" selected="selected">' . esc_html($vector_embedding_model) . ' (Manual/Not Synced)</option>';
                                            }
                                            if (empty($current_embedding_list) && empty($vector_embedding_model)) {
                                                 echo '<option value="" disabled>'.esc_html__('-- Select Provider or Sync Models --', 'gpt3-ai-content-generator').'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Limit and Score Threshold sliders -->
                            <div class="aipkit_settings_grid">
                                <!-- Top K Setting -->
                                <div class="aipkit_form-group aipkit_vector_store_top_k_field">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_top_k_modal">
                                        <?php esc_html_e('Limit', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_slider_wrapper">
                                        <input
                                            type="range"
                                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_top_k_modal"
                                            name="vector_store_top_k"
                                            class="aipkit_form-input aipkit_range_slider"
                                            min="1"
                                            max="20"
                                            step="1"
                                            value="<?php echo esc_attr($vector_store_top_k); ?>"
                                        />
                                        <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_top_k_value_modal" class="aipkit_slider_value">
                                            <?php echo esc_html($vector_store_top_k); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Confidence Threshold Setting -->
                                <div class="aipkit_form-group aipkit_vector_store_confidence_field">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_confidence_threshold_modal">
                                        <?php esc_html_e('Score Threshold', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_slider_wrapper">
                                        <input
                                            type="range"
                                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_confidence_threshold_modal"
                                            name="vector_store_confidence_threshold"
                                            class="aipkit_form-input aipkit_range_slider"
                                            min="0" max="100" step="1"
                                            value="<?php echo esc_attr($vector_store_confidence_threshold); ?>"
                                        />
                                        <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_vector_store_confidence_threshold_value_modal" class="aipkit_slider_value">
                                            <?php echo esc_html($vector_store_confidence_threshold); ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="aipkit_settings_grid">
                                <div class="aipkit_form-group">
                                    <div class="aipkit_form-help"><?php esc_html_e('Number of results to retrieve from vector store.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group">
                                    <div class="aipkit_form-help"><?php esc_html_e('Only use results with a similarity score above this.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>
                            </div> <!-- /.aipkit_vector_store_settings_conditional_row -->
                        </div>
                    </section>

                    <!-- Content Awareness (moved from Features) -->
                    <section class="aipkit_settings_section" data-section="content-aware">
                        <div class="aipkit_settings_section-header">
                            <h5 class="aipkit_settings_section-title"><?php esc_html_e('Content Awareness', 'gpt3-ai-content-generator'); ?></h5>
                        </div>
                        <div class="aipkit_settings_section-body">
                            <div class="aipkit_settings_grid">
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_content_aware_enabled_modal">
                                        <input type="checkbox"
                                               id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_content_aware_enabled_modal"
                                               name="content_aware_enabled"
                                               class="aipkit_toggle_switch"
                                               value="1" <?php checked($content_aware_enabled, '1'); ?> >
                                        <?php esc_html_e('Enable Content Awareness', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <div class="aipkit_form-help"><?php esc_html_e('Use page content as context during chat.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>
                        </div>
                    </section>
                    </div>
                </div>
                <div class="aipkit_modal_footer">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>

    </div><!-- /.aipkit_accordion-content -->
</div><!-- /.aipkit_accordion -->

<?php
    // Modal: Web & Grounding Settings (OpenAI + Google)
    $web_modal_id = isset($web_modal_id) ? $web_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_web_settings_modal');
?>
<div
    id="<?php echo esc_attr($web_modal_id); ?>"
    class="aipkit_chatbot_settings_modal"
    aria-hidden="true"
    
>
    <div class="aipkit_modal_backdrop"></div>
    <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($web_modal_id); ?>_title">
        <div class="aipkit_modal_header">
            <h4 id="<?php echo esc_attr($web_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Web & Grounding', 'gpt3-ai-content-generator'); ?></h4>
            <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
        </div>
        <div class="aipkit_modal_body">
            <!-- OpenAI Web Search Sub-settings -->
            <section class="aipkit_settings_subsection aipkit_web_modal_section_openai <?php echo ($current_provider_for_this_bot === 'OpenAI') ? '' : 'aipkit_hidden'; ?>">
                <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('OpenAI Web Search', 'gpt3-ai-content-generator'); ?></h5>
                </div>
                <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_openai_web_search_conditional_settings <?php echo ($current_provider_for_this_bot === 'OpenAI' && $openai_web_search_enabled_val === '1') ? '' : 'aipkit_hidden'; ?>">
                        <div class="aipkit_settings_grid">
                            <div class="aipkit_form-group">
                                <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_context_size_modal"><?php esc_html_e('Search Context Size', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_context_size_modal" name="openai_web_search_context_size" class="aipkit_form-input">
                                    <option value="low" <?php selected($openai_web_search_context_size_val, 'low'); ?>><?php esc_html_e('Low', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="medium" <?php selected($openai_web_search_context_size_val, 'medium'); ?>><?php esc_html_e('Medium (Default)', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="high" <?php selected($openai_web_search_context_size_val, 'high'); ?>><?php esc_html_e('High', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                                <div class="aipkit_form-help"><?php esc_html_e('Amount of web context to include.', 'gpt3-ai-content-generator'); ?></div>
                            </div>
                            <div class="aipkit_form-group">
                                <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_type_modal"><?php esc_html_e('User Location', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_type_modal" name="openai_web_search_loc_type" class="aipkit_form-input aipkit_openai_web_search_loc_type_select">
                                    <option value="none" <?php selected($openai_web_search_loc_type_val, 'none'); ?>><?php esc_html_e('None (Default)', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="approximate" <?php selected($openai_web_search_loc_type_val, 'approximate'); ?>><?php esc_html_e('Approximate', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                                <div class="aipkit_form-help"><?php esc_html_e('Improves local relevance when set to Approximate.', 'gpt3-ai-content-generator'); ?></div>
                            </div>
                        </div>
                        <div class="aipkit_openai_web_search_location_details aipkit_section_divider <?php echo ($current_provider_for_this_bot === 'OpenAI' && $openai_web_search_enabled_val === '1' && $openai_web_search_loc_type_val === 'approximate') ? '' : 'aipkit_hidden'; ?>">
                            <div class="aipkit_settings_grid aipkit_settings_grid--4">
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_country_modal"><?php esc_html_e('Country (ISO Code)', 'gpt3-ai-content-generator'); ?></label>
                                    <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_country_modal" name="openai_web_search_loc_country" class="aipkit_form-input" value="<?php echo esc_attr($openai_web_search_loc_country_val); ?>" placeholder="<?php esc_attr_e('e.g., US, GB', 'gpt3-ai-content-generator'); ?>" maxlength="2">
                                    <div class="aipkit_form-help"><?php esc_html_e('2-letter code, e.g., US or GB.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_city_modal"><?php esc_html_e('City', 'gpt3-ai-content-generator'); ?></label>
                                    <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_city_modal" name="openai_web_search_loc_city" class="aipkit_form-input" value="<?php echo esc_attr($openai_web_search_loc_city_val); ?>" placeholder="<?php esc_attr_e('e.g., London', 'gpt3-ai-content-generator'); ?>">
                                    <div class="aipkit_form-help"><?php esc_html_e('Optional city name.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_region_modal"><?php esc_html_e('Region/State', 'gpt3-ai-content-generator'); ?></label>
                                    <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_region_modal" name="openai_web_search_loc_region" class="aipkit_form-input" value="<?php echo esc_attr($openai_web_search_loc_region_val); ?>" placeholder="<?php esc_attr_e('e.g., California', 'gpt3-ai-content-generator'); ?>">
                                    <div class="aipkit_form-help"><?php esc_html_e('Optional region or state.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                                <div class="aipkit_form-group">
                                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_timezone_modal"><?php esc_html_e('Timezone (IANA)', 'gpt3-ai-content-generator'); ?></label>
                                    <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_openai_web_search_loc_timezone_modal" name="openai_web_search_loc_timezone" class="aipkit_form-input" value="<?php echo esc_attr($openai_web_search_loc_timezone_val); ?>" placeholder="<?php esc_attr_e('e.g., America/Chicago', 'gpt3-ai-content-generator'); ?>">
                                    <div class="aipkit_form-help"><?php esc_html_e('IANA format, e.g., America/Chicago.', 'gpt3-ai-content-generator'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Google Search Grounding Sub-settings -->
            <section class="aipkit_settings_subsection aipkit_web_modal_section_google <?php echo ($current_provider_for_this_bot === 'Google') ? '' : 'aipkit_hidden'; ?>">
                <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Google Search Grounding', 'gpt3-ai-content-generator'); ?></h5>
                </div>
                <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_google_search_grounding_conditional_settings <?php echo ($current_provider_for_this_bot === 'Google' && $google_search_grounding_enabled_val === '1') ? '' : 'aipkit_hidden'; ?>">
                        <div class="aipkit_settings_grid">
                            <div class="aipkit_form-group">
                                <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_grounding_mode_modal"><?php esc_html_e('Grounding Mode', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_grounding_mode_modal" name="google_grounding_mode" class="aipkit_form-input aipkit_google_grounding_mode_select">
                                    <option value="DEFAULT_MODE" <?php selected($google_grounding_mode_val, 'DEFAULT_MODE'); ?>><?php esc_html_e('Default (Model Decides/Search as Tool)', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="MODE_DYNAMIC" <?php selected($google_grounding_mode_val, 'MODE_DYNAMIC'); ?>><?php esc_html_e('Dynamic Retrieval (Gemini 1.5 Flash only)', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                                <div class="aipkit_form-help"><?php esc_html_e('Default lets the model decide; Dynamic always retrieves.', 'gpt3-ai-content-generator'); ?></div>
                            </div>
                            <div class="aipkit_form-group aipkit_google_grounding_dynamic_threshold_container" style="<?php echo ($current_provider_for_this_bot === 'Google' && $google_search_grounding_enabled_val === '1' && $google_grounding_mode_val === 'MODE_DYNAMIC') ? '' : 'display:none;'; ?>">
                                <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_grounding_dynamic_threshold_modal">
                                    <?php esc_html_e('Dynamic Retrieval Threshold', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <div class="aipkit_slider_wrapper aipkit_slider_wrapper--max400">
                                    <input type="range" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_grounding_dynamic_threshold_modal" name="google_grounding_dynamic_threshold" class="aipkit_form-input aipkit_range_slider" min="0.0" max="1.0" step="0.01" value="<?php echo esc_attr($google_grounding_dynamic_threshold_val); ?>">
                                    <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_google_grounding_dynamic_threshold_value_modal" class="aipkit_slider_value"><?php echo esc_html(number_format($google_grounding_dynamic_threshold_val, 2)); ?></span>
                                </div>
                                <div class="aipkit_form-help"><?php esc_html_e('Higher requires stronger evidence (0–1).', 'gpt3-ai-content-generator'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="aipkit_modal_footer">
            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
