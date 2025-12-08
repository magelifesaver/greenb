<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/index.php
// Status: MODIFIED
// I have added hidden divs to store Pinecone and Qdrant data for the Semantic Search UI.
/**
 * AIPKit Global Settings Module
 */

use WPAICG\AIPKIT_AI_Settings;
use WPAICG\AIPKit_Providers;
use WPAICG\Stats\AIPKit_Stats;
use WPAICG\aipkit_dashboard;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation;
use WPAICG\Lib\Addons\AIPKit_Consent_Compliance;

if (!defined('ABSPATH')) {
    exit;
}

// Ensure GoogleSettingsHandler is loaded before use
$google_settings_handler_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/google/GoogleSettingsHandler.php';
if (!class_exists(GoogleSettingsHandler::class) && file_exists($google_settings_handler_path)) {
    require_once $google_settings_handler_path;
} elseif (!class_exists(GoogleSettingsHandler::class)) {
    echo '<div class="notice notice-error"><p>Error: Google Settings component failed to load. Safety settings cannot be displayed.</p></div>';
}

// --- Variable Definitions ---
$aipkit_options = get_option('aipkit_options', array());

// Force the "providers" array to exist
AIPKit_Providers::get_all_providers();

$ai_params = AIPKIT_AI_Settings::get_ai_parameters();
$all_api_keys = AIPKIT_AI_Settings::get_api_keys();
$public_api_key = $all_api_keys['public_api_key'] ?? '';

$security_options = AIPKIT_AI_Settings::get_security_settings();
$banned_words_settings = $security_options['bannedwords'] ?? ['words' => '', 'message' => ''];
$saved_banned_words = $banned_words_settings['words'] ?? '';
$saved_word_notification_message = $banned_words_settings['message'] ?? '';
$placeholder_word_message = AIPKIT_AI_Settings::$default_security_settings['bannedwords']['message'] ?: __('Sorry, your message could not be sent as it contains prohibited words.', 'gpt3-ai-content-generator');
$banned_ips_settings = $security_options['bannedips'] ?? ['ips' => '', 'message' => ''];
$saved_banned_ips = $banned_ips_settings['ips'] ?? '';
$saved_ip_notification_message = $banned_ips_settings['message'] ?? '';
$placeholder_ip_message = AIPKIT_AI_Settings::$default_security_settings['bannedips']['message'] ?: __('Access from your IP address has been blocked.', 'gpt3-ai-content-generator');

$is_pro = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_pro_plan();
$openai_mod_addon_helper_exists = class_exists('\WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation');
$openai_mod_addon_active = $openai_mod_addon_helper_exists && $is_pro && class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active(AIPKit_OpenAI_Moderation::ADDON_KEY);
$openai_moderation_enabled = $security_options['openai_moderation_enabled'] ?? AIPKIT_AI_Settings::$default_security_settings['openai_moderation_enabled'];
$openai_moderation_message = $security_options['openai_moderation_message'] ?? AIPKIT_AI_Settings::$default_security_settings['openai_moderation_message'];
$placeholder_openai_message = AIPKIT_AI_Settings::$default_security_settings['openai_moderation_message'] ?: __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator');
if (empty($openai_moderation_message) && $openai_mod_addon_active) {
    $openai_moderation_message = $placeholder_openai_message;
}

$consent_addon_helper_exists = class_exists('\WPAICG\Lib\Addons\AIPKit_Consent_Compliance');
$consent_addon_active = $consent_addon_helper_exists && $is_pro && class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active(AIPKit_Consent_Compliance::ADDON_KEY);
$consent_settings = $security_options['consent'] ?? AIPKIT_AI_Settings::$default_security_settings['consent'];
$saved_consent_title = $consent_settings['title'] ?? '';
$saved_consent_message = $consent_settings['message'] ?? '';
$saved_consent_button = $consent_settings['button'] ?? '';
$placeholder_consent_title = AIPKIT_AI_Settings::$default_security_settings['consent']['title'] ?: __('Consent Required', 'gpt3-ai-content-generator');
$placeholder_consent_message = AIPKIT_AI_Settings::$default_security_settings['consent']['message'] ?: __('Before starting the conversation, please agree to our Terms of Service and Privacy Policy.', 'gpt3-ai-content-generator');
$placeholder_consent_button = AIPKIT_AI_Settings::$default_security_settings['consent']['button'] ?: __('I Agree', 'gpt3-ai-content-generator');
if (empty($saved_consent_title) && $consent_addon_active) {
    $saved_consent_title = $placeholder_consent_title;
}
if (empty($saved_consent_message) && $consent_addon_active) {
    $saved_consent_message = $placeholder_consent_message;
}
if (empty($saved_consent_button) && $consent_addon_active) {
    $saved_consent_button = $placeholder_consent_button;
}

$safety_settings = class_exists(GoogleSettingsHandler::class) ? GoogleSettingsHandler::get_safety_settings() : [];
$category_thresholds = array();
if (is_array($safety_settings)) {
    foreach ($safety_settings as $setting) {
        if (isset($setting['category'], $setting['threshold'])) {
            $category_thresholds[$setting['category']] = $setting['threshold'];
        }
    }
}

$current_provider = AIPKit_Providers::get_current_provider();

$openai_data     = AIPKit_Providers::get_provider_data('OpenAI');
$openrouter_data = AIPKit_Providers::get_provider_data('OpenRouter');
$google_data     = AIPKit_Providers::get_provider_data('Google');
$azure_data      = AIPKit_Providers::get_provider_data('Azure');
$deepseek_data   = AIPKit_Providers::get_provider_data('DeepSeek');
$ollama_data     = AIPKit_Providers::get_provider_data('Ollama');
$elevenlabs_data = AIPKit_Providers::get_provider_data('ElevenLabs');
$pexels_data     = AIPKit_Providers::get_provider_data('Pexels');
$pixabay_data    = AIPKit_Providers::get_provider_data('Pixabay');
$pinecone_data   = AIPKit_Providers::get_provider_data('Pinecone');
$qdrant_data     = AIPKit_Providers::get_provider_data('Qdrant');


$max_completion_tokens = $ai_params['max_completion_tokens'];
$temperature       = $ai_params['temperature'];
$top_p             = $ai_params['top_p'];
$openai_store_conversation = isset($openai_data['store_conversation']) ? $openai_data['store_conversation'] : '0';

$safety_thresholds = array(
    'BLOCK_NONE'             => 'Block None',
    'BLOCK_LOW_AND_ABOVE'    => 'Block Few',
    'BLOCK_MEDIUM_AND_ABOVE' => 'Block Some',
    'BLOCK_ONLY_HIGH'        => 'Block Most',
);

$openai_defaults     = AIPKit_Providers::get_provider_defaults('OpenAI');
$openrouter_defaults = AIPKit_Providers::get_provider_defaults('OpenRouter');
$google_defaults     = AIPKit_Providers::get_provider_defaults('Google');
$azure_defaults      = AIPKit_Providers::get_provider_defaults('Azure');
$deepseek_defaults   = AIPKit_Providers::get_provider_defaults('DeepSeek');
$ollama_defaults     = AIPKit_Providers::get_provider_defaults('Ollama');
$elevenlabs_defaults = AIPKit_Providers::get_provider_defaults('ElevenLabs');
$pexels_defaults     = AIPKit_Providers::get_provider_defaults('Pexels');
$pixabay_defaults    = AIPKit_Providers::get_provider_defaults('Pixabay');
$pinecone_defaults   = AIPKit_Providers::get_provider_defaults('Pinecone');
$qdrant_defaults     = AIPKit_Providers::get_provider_defaults('Qdrant');


$stats_error_message = null;
$stats_data = null;
// Default to a smaller period to keep memory low
$aipkit_stats_default_days = 3;
if (class_exists('\\WPAICG\\Stats\\AIPKit_Stats')) {
    $stats_calculator = new AIPKit_Stats();
    $stats_data = $stats_calculator->get_token_stats_last_days($aipkit_stats_default_days);
    if (is_wp_error($stats_data)) {
        if ($stats_data->get_error_code() === 'stats_volume_too_large') {
            $err = $stats_data; // keep reference to error for volume data
            // Fallback to quick stats (interactions + module counts only)
            $quick = $stats_calculator->get_quick_interaction_stats($aipkit_stats_default_days);
            if (!is_wp_error($quick)) {
                $stats_data = [
                    'days_period' => $quick['days_period'] ?? $aipkit_stats_default_days,
                    'total_tokens' => null,
                    'total_interactions' => $quick['total_interactions'] ?? 0,
                    'avg_tokens_per_interaction' => null,
                    'module_counts' => $quick['module_counts'] ?? [],
                ];
                // Show a friendly notice rather than an error
                $err_data = is_wp_error($err) ? $err->get_error_data() : null;
                $rows = is_array($err_data) && isset($err_data['rows']) ? (int)$err_data['rows'] : 0;
                $bytes = is_array($err_data) && isset($err_data['bytes']) ? (int)$err_data['bytes'] : 0;
                $stats_notice_message = sprintf(
                    /* translators: 1: rows, 2: bytes */
                    __('Usage data for the selected period is very large (rows: %1$s, size: %2$s). Token metrics are not shown. Consider deleting logs.', 'gpt3-ai-content-generator'),
                    number_format_i18n($rows),
                    size_format($bytes)
                );
                $stats_notice_link = admin_url('admin.php?page=wpaicg#logs');
                $stats_error_message = null;
            } else {
                $stats_error_message = $stats_data->get_error_message();
                $stats_data = null;
            }
        } else {
            $stats_error_message = $stats_data->get_error_message();
            $stats_data = null;
        }
    }
} else {
    $stats_error_message = __('Statistics component is unavailable.', 'gpt3-ai-content-generator');
}

$deepseek_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('deepseek');
$voice_playback_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('voice_playback');
$vector_databases_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('vector_databases');
$stock_images_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('stock_images');
$replicate_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('replicate');
$post_enhancer_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('ai_post_enhancer');
$semantic_search_addon_active = class_exists('\WPAICG\aipkit_dashboard') && aipkit_dashboard::is_addon_active('semantic_search');

$whatsapp_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_addon_active('whatsapp');
$integrations_tab_visible = $voice_playback_addon_active || $vector_databases_addon_active || $stock_images_addon_active || $replicate_addon_active || $post_enhancer_addon_active || $semantic_search_addon_active || $whatsapp_addon_active;


$providers = ['OpenAI', 'OpenRouter', 'Google', 'Azure', 'DeepSeek', 'Ollama'];
$ollama_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_addon_active('ollama');

$grouped_openai_models = AIPKit_Providers::get_openai_models();
$openrouter_model_list = AIPKit_Providers::get_openrouter_models();
$google_model_list     = AIPKit_Providers::get_google_models();
$azure_deployment_list = AIPKit_Providers::get_azure_all_models_grouped();
$deepseek_model_list   = AIPKit_Providers::get_deepseek_models();
$ollama_model_list     = AIPKit_Providers::get_ollama_models();
$elevenlabs_voice_list = AIPKit_Providers::get_elevenlabs_voices();
$elevenlabs_model_list = AIPKit_Providers::get_elevenlabs_models();
// --- NEW: Get Pinecone & Qdrant lists (initially empty) ---
$pinecone_index_list = AIPKit_Providers::get_pinecone_indexes();
$qdrant_collection_list = AIPKit_Providers::get_qdrant_collections();
// --- END NEW ---

?>
<div class="aipkit_container aipkit_settings_main_container" id="aipkit_settings_container">
    <div class="aipkit_container-header">
        <div class="aipkit_settings_heading_with_messages">
            <div class="aipkit_container-title">
                <?php echo esc_html__('AI Settings & Usage', 'gpt3-ai-content-generator'); ?>
            </div>
            <div id="aipkit_settings_messages" class="aipkit_settings_messages"></div>
        </div>
    </div>

    <div class="aipkit_container-body aipkit_settings_container_body">
        <div class="aipkit_settings_layout">

            <!-- Left Column: Configuration Settings (Tabbed) -->
            <div class="aipkit_settings_column aipkit_settings_column-left aipkit_sub_container">
                 <div class="aipkit_sub_container_header">
                    <div class="aipkit_sub_container_title"><?php echo esc_html__('Configuration', 'gpt3-ai-content-generator'); ?></div>
                 </div>
                 <div class="aipkit_sub_container_body">
                    <div class="aipkit_tabs">
                        <div class="aipkit_tab aipkit_active" data-tab="providers">
                            <?php esc_html_e('Providers', 'gpt3-ai-content-generator'); ?>
                        </div>
                        <div class="aipkit_tab" data-tab="settings">
                            <?php esc_html_e('Advanced', 'gpt3-ai-content-generator'); ?>
                        </div>
                        <?php if ($integrations_tab_visible): // Conditionally show Integrations tab?>
                            <div class="aipkit_tab" data-tab="integrations">
                                <?php esc_html_e('Integrations', 'gpt3-ai-content-generator'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="aipkit_tab_content_container">
                        <div class="aipkit_tab-content aipkit_active" id="providers-content">
                            <div class="aipkit_settings-tab-content-inner-padding">
                                <div class="aipkit_accordion-group"> <?php // Single group for all "Providers" tab accordions?>

                                    <!-- Accordion: API Configuration (Provider, Model, API Keys) -->
                                    <div class="aipkit_accordion">
                                        <div class="aipkit_accordion-header aipkit_active"> <?php // Make first one active?>
                                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                                            <?php echo esc_html__('API', 'gpt3-ai-content-generator'); ?>
                                        </div>
                                        <div class="aipkit_accordion-content aipkit_active">
                                            <div class="aipkit_settings-section">
                                                <div class="aipkit_form-row aipkit_settings-form-row--provider-model">
                                                    <div class="aipkit_form-group aipkit_form-col aipkit_settings-form-col--provider-select">
                                                        <?php include __DIR__ . '/partials/settings-provider-select.php'; ?>
                                                    </div>
                                                    <div class="aipkit_form-group aipkit_form-col aipkit_settings-form-col--model-select">
                                                        <?php include __DIR__ . '/partials/settings-models.php'; ?>
                                                    </div>
                                                </div>
                                                <hr class="aipkit_hr"> <?php // Separator before API Keys?>
                                                <div class="aipkit_form-row aipkit_settings-form-row--api-keys">
                                                    <div class="aipkit_form-group aipkit_form-col aipkit_settings-form-col--full-width">
                                                        <?php include __DIR__ . '/partials/settings-api-keys.php'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Parameters & Advanced Accordion (Combined) -->
                                    <?php // This now includes parameters, provider-specific advanced, and Google safety settings?>
                                    <?php include __DIR__ . '/partials/settings-parameters.php'; ?>

                                </div> <?php // End of the accordion group for Providers Tab?>
                            </div>
                        </div>
                        <div class="aipkit_tab-content" id="settings-content">
                             <?php // Content for "Advanced" tab?>
                            <?php include __DIR__ . '/partials/settings-advanced.php'; ?>
                        </div>
                        <?php if ($integrations_tab_visible): ?>
                             <div class="aipkit_tab-content" id="integrations-content">
                                <?php // Content for "Integrations" tab?>
                                <?php include __DIR__ . '/partials/settings-advanced-integrations.php'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Token Stats -->
            <?php include __DIR__ . '/partials/token-stats.php'; ?>

        </div>
    </div>
</div>

<!-- Hidden div for storing synced Google TTS Voices -->
<div id="aipkit_google_tts_voices_json_main" style="display:none;" data-voices="<?php
    $google_voices_main = class_exists(GoogleSettingsHandler::class) ? GoogleSettingsHandler::get_synced_google_tts_voices() : [];
echo esc_attr(wp_json_encode($google_voices_main ?: []));
?>"></div>

<!-- Hidden div for storing synced ElevenLabs Voices -->
<div id="aipkit_elevenlabs_voices_json_main" style="display:none;" data-voices="<?php
    echo esc_attr(wp_json_encode($elevenlabs_voice_list ?: []));
?>"></div>

<!-- NEW: Hidden div for storing synced ElevenLabs Models -->
<div id="aipkit_elevenlabs_models_json_main" style="display:none;" data-models="<?php
    echo esc_attr(wp_json_encode($elevenlabs_model_list ?: []));
?>"></div>

<!-- NEW: Hidden divs for Pinecone & Qdrant -->
<div id="aipkit_pinecone_indexes_json_main" style="display:none;" data-indexes="<?php
    echo esc_attr(wp_json_encode($pinecone_index_list ?: []));
?>"></div>
<div id="aipkit_qdrant_collections_json_main" style="display:none;" data-collections="<?php
    echo esc_attr(wp_json_encode($qdrant_collection_list ?: []));
?>"></div>
