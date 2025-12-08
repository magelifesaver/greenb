<?php
/**
 * Partial: Chatbot Settings Pane Content
 *
 * Renders the settings form for a single existing chatbot.
 * Included within a loop in the main chatbot module view.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\aipkit_dashboard; // Required for checking addon status
// --- Global Settings Dependencies for "Chat Settings" tab ---
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\AIPKit_Providers;
use WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation;
use WPAICG\Lib\Addons\AIPKit_Consent_Compliance;

// --- End Global Settings Dependencies ---

// --- ADDED: Ensure SVG Icons utility class is loaded ---
$svg_icons_util_path = WPAICG_PLUGIN_DIR . 'classes/chat/utils/class-aipkit-svg-icons.php';
if (file_exists($svg_icons_util_path) && !class_exists('\\WPAICG\\Chat\\Utils\\AIPKit_SVG_Icons')) {
    require_once $svg_icons_util_path;
}
// --- END ADDED ---


// Variables passed from parent (chatbot/index.php loop):
// $bot_post, $bot_id, $bot_name, $bot_settings, $active_class, $is_default
// Also, all variables needed by the included accordion partials must be available in this scope:
// $providers, $grouped_openai_models, $openrouter_model_list, $google_model_list,
// $azure_deployment_list, $deepseek_model_list, $is_token_management_active, $is_voice_playback_active
// $saved_provider, $saved_model (these should be part of $bot_settings)

$saved_provider = $bot_settings['provider'] ?? 'OpenAI';
$saved_model = $bot_settings['model'] ?? '';

// Audio (STT) defaults
$enable_voice_input = isset($bot_settings['enable_voice_input'])
    ? $bot_settings['enable_voice_input']
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;
$stt_provider = isset($bot_settings['stt_provider'])
    ? $bot_settings['stt_provider']
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STT_PROVIDER;
$stt_openai_model_id = isset($bot_settings['stt_openai_model_id'])
    ? $bot_settings['stt_openai_model_id']
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID;
// Get synced OpenAI STT models
$openai_stt_models = \WPAICG\AIPKit_Providers::get_openai_stt_models();

?>
<div class="aipkit_tab-content <?php echo esc_attr($active_class); ?>" id="chatbot-<?php echo esc_attr($bot_id); ?>-content">
    <!-- Settings Form -->
    <div class="aipkit_chatbot-settings-area">
        <form
            class="aipkit_chatbot_settings_form"
            data-bot-id="<?php echo esc_attr($bot_id); ?>"
            onsubmit="return false;"
        >
            <div class="aipkit_settings_sections">
                <div class="aipkit_segmented_container aipkit_settings_sections aipkit_settings_sections--segmented">
                    <section class="aipkit_settings_section is-active-segment" data-segment="ai_config">
                        <div class="aipkit_settings_section-body">
                            <?php include __DIR__ . '/accordion-ai-config.php'; ?>
                        </div>
                    </section>
                    <section class="aipkit_settings_section" data-segment="appearance">
                        <div class="aipkit_settings_section-body">
                            <?php include __DIR__ . '/accordion-appearance.php'; ?>
                        </div>
                    </section>
                    <?php // Audio settings moved into a modal; audio segmented section removed ?>
                    
                    <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('embed_anywhere')): ?>
                    <section class="aipkit_settings_section" data-segment="embed_anywhere">
                        <div class="aipkit_settings_section-body">
                            <?php
                            $embed_accordion_path = WPAICG_LIB_DIR . 'views/chatbot/partials/accordion-embed.php';
                            if (file_exists($embed_accordion_path)) {
                                include $embed_accordion_path;
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('triggers')): ?>
                    <section class="aipkit_settings_section" data-segment="triggers">
                        <div class="aipkit_settings_section-body">
                            <?php
                            $triggers_accordion_path = WPAICG_LIB_DIR . 'views/chatbot/partials/accordion-triggers.php';
                            if (file_exists($triggers_accordion_path)) {
                                include $triggers_accordion_path;
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('whatsapp')): ?>
                    <section class="aipkit_settings_section" data-segment="whatsapp">
                        <div class="aipkit_settings_section-body">
                            <?php
                            $wa_accordion_path = WPAICG_LIB_DIR . 'views/chatbot/partials/accordion-whatsapp.php';
                            if (file_exists($wa_accordion_path)) {
                                include $wa_accordion_path;
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div><!-- /.aipkit_segmented_container -->
            </div>


        </form>
    </div>
</div>
