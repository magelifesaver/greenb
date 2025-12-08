<?php
/**
 * AIPKit Chatbot Module - Admin View (Revised Tab Structure & Single Preview Pane)
 *
 * Displays a tab for each saved chatbot, plus a "New Bot" tab,
 * and an extra "Settings" tab on the right.
 * MODIFIED: Uses BotStorage::get_chatbots_with_settings() for optimized data fetching.
 * MODIFIED: Removed logic for splitting bots into buttons and a dropdown to support a single unified dropdown.
 * MODIFIED: Renders only the settings pane for the currently active bot to improve performance.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\DefaultBotSetup;
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


// Instantiate the storage classes
$bot_storage = new BotStorage();
$default_setup = new DefaultBotSetup();

// Fetch bots with their settings optimized
$all_chatbots_with_settings = $bot_storage->get_chatbots_with_settings();

// Grab forced active bot/tab. These variables are now defined and sanitized
// by the including file (the AJAX handler), which has already verified a nonce.
// This avoids direct use of $_REQUEST and satisfies security scans.
$force_active_bot_id = isset($force_active_bot_id) ? intval($force_active_bot_id) : 0;
$force_active_tab = isset($force_active_tab) ? sanitize_key($force_active_tab) : '';

// Get the ID of the default bot
$default_bot_id = $default_setup->get_default_bot_id();

// Separate the default bot and sort the others alphabetically
$default_bot_entry = null;
$other_bots_entries = [];
if (!empty($all_chatbots_with_settings)) {
    foreach ($all_chatbots_with_settings as $bot_entry) {
        if ($bot_entry['post']->ID === $default_bot_id) {
            $default_bot_entry = $bot_entry;
        } else {
            $other_bots_entries[] = $bot_entry;
        }
    }
    // Sort other bots by post_title
    usort($other_bots_entries, function ($a, $b) {
        return strcmp($a['post']->post_title, $b['post']->post_title);
    });
}
// Combine all bots into one list for the dropdown
$all_bots_ordered_entries = [];
if ($default_bot_entry) {
    $all_bots_ordered_entries[] = $default_bot_entry;
}
$all_bots_ordered_entries = array_merge($all_bots_ordered_entries, $other_bots_entries);


// Provide the array of possible providers (always show, lock via disabled when not eligible)
$providers = ['OpenAI', 'OpenRouter', 'Google', 'Azure', 'DeepSeek', 'Ollama'];
// Eligibility flags for UI (used to disable options and label them)
$is_pro = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_pro_plan();
$deepseek_addon_active = aipkit_dashboard::is_addon_active('deepseek');
$ollama_addon_active = aipkit_dashboard::is_addon_active('ollama');

// Model lists (needed by chatbot-settings-pane.php -> accordion-ai-config.php -> provider-model.php)
$grouped_openai_models = get_option('aipkit_openai_model_list', array());
$openrouter_model_list = get_option('aipkit_openrouter_model_list', array());
$google_model_list     = get_option('aipkit_google_model_list', array());
$azure_deployment_list = \WPAICG\AIPKit_Providers::get_azure_deployments();
$deepseek_model_list   = \WPAICG\AIPKit_Providers::get_deepseek_models();
$ollama_model_list     = \WPAICG\AIPKit_Providers::get_ollama_models();
$replicate_model_list = \WPAICG\AIPKit_Providers::get_replicate_models();


// Determine the initial active bot or tab
$initial_active_bot_id = null;
$create_new_active_class = '';
$initial_placeholder_key = 'previewPlaceholderSelect';
$initial_placeholder_text = __('Select a bot to see the preview.', 'gpt3-ai-content-generator');

if ($force_active_tab === 'create') {
    $create_new_active_class = 'aipkit_active';
    $initial_placeholder_key = 'previewPlaceholderCreate';
    $initial_placeholder_text = __('Configure the new bot and save it to see the preview.', 'gpt3-ai-content-generator');
} elseif ($force_active_bot_id > 0) {
    $initial_active_bot_id = $force_active_bot_id;
    $initial_placeholder_key = 'previewLoading';
    $initial_placeholder_text = __('Loading preview...', 'gpt3-ai-content-generator');
} else {
    // Initial page load logic (not from a specific action)
    if ($default_bot_entry) {
        $initial_active_bot_id = $default_bot_entry['post']->ID;
        $initial_placeholder_key = 'previewLoading';
        $initial_placeholder_text = __('Loading preview...', 'gpt3-ai-content-generator');
    } elseif (!empty($other_bots_entries)) {
        $initial_active_bot_id = $other_bots_entries[0]['post']->ID;
        $initial_placeholder_key = 'previewLoading';
        $initial_placeholder_text = __('Loading preview...', 'gpt3-ai-content-generator');
    } else {
        $create_new_active_class = 'aipkit_active';
        $initial_placeholder_key = 'previewPlaceholderCreate';
        $initial_placeholder_text = __('Configure the new bot and save it to see the preview.', 'gpt3-ai-content-generator');
    }
}

// Find the name of the initially active bot for the dropdown trigger
$initial_active_bot_name = __('New Bot', 'gpt3-ai-content-generator');
if ($initial_active_bot_id) {
    foreach ($all_bots_ordered_entries as $bot_entry) {
        if ($bot_entry['post']->ID === $initial_active_bot_id) {
            $initial_active_bot_name = $bot_entry['post']->post_title;
            break;
        }
    }
}

// Check addon statuses
$is_token_management_active = aipkit_dashboard::is_addon_active('token_management');
$is_voice_playback_active = aipkit_dashboard::is_addon_active('voice_playback');
$starters_addon_active = aipkit_dashboard::is_addon_active('conversation_starters');
$is_realtime_voice_active = (aipkit_dashboard::is_pro_plan() && aipkit_dashboard::is_addon_active('realtime_voice'));

?>
<?php
// Check if Chatolia notice should be displayed
$chatolia_notice_dismissed = get_option('aipkit_chatolia_notice_dismissed', '0') === '1';
if (!$chatolia_notice_dismissed) {
    include __DIR__ . '/partials/chatolia-notice.php';
}
?>

<div class="aipkit_container aipkit_chatbot_module_container">
    <?php include __DIR__ . '/partials/chatbot-main-selector.php'; ?>

    <!-- Two-column layout for content + preview -->
    <div class="aipkit_chatbots-split-layout">
        <!-- Left Column: Chatbot Settings -->
        <div class="aipkit_chatbot-list-column">
            <?php if (empty($create_new_active_class)) : ?>
            <div class="aipkit_chatbot-list-column-header">
                <div class="aipkit_chatbot_header_bar">
                    <div class="aipkit_chatbot_header_left">
                        <div class="aipkit_segmented_controls" role="tablist" aria-label="<?php esc_attr_e('Chatbot Sections', 'gpt3-ai-content-generator'); ?>">
                            <button type="button" class="aipkit_segmented_btn is-active" data-segment="ai_config" role="tab" aria-selected="true"><?php esc_html_e('AI', 'gpt3-ai-content-generator'); ?></button>
                            <button type="button" class="aipkit_segmented_btn" data-segment="appearance" role="tab" aria-selected="false"><?php esc_html_e('Style', 'gpt3-ai-content-generator'); ?></button>
                            <?php // Popup & Images moved into modals; tabs removed ?>
                                <?php // Audio settings moved to a modal; tab removed ?>
                            <?php // Token management moved into modal; tab removed ?>

                            <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('embed_anywhere')): ?>
                                <button type="button" class="aipkit_segmented_btn" data-segment="embed_anywhere" role="tab" aria-selected="false"><?php esc_html_e('Embed', 'gpt3-ai-content-generator'); ?></button>
                            <?php endif; ?>
                            <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('triggers')): ?>
                                <button type="button" class="aipkit_segmented_btn" data-segment="triggers" role="tab" aria-selected="false"><?php esc_html_e('Triggers', 'gpt3-ai-content-generator'); ?></button>
                            <?php endif; ?>
                            <?php if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('whatsapp')): ?>
                                <button type="button" class="aipkit_segmented_btn" data-segment="whatsapp" role="tab" aria-selected="false"><?php esc_html_e('WhatsApp', 'gpt3-ai-content-generator'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="aipkit_chatbot_header_right">
                        <button
                            type="button"
                            id="aipkit_chatbot_header_save_btn"
                            class="aipkit_btn aipkit_btn-primary"
                            title="<?php esc_attr_e('Save chatbot settings', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="aipkit_btn-text"><?php esc_html_e('Save', 'gpt3-ai-content-generator'); ?></span>
                            <span class="aipkit_spinner"></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="aipkit_tab-content-area" id="aipkit_chatbot_main_tab_content_container">
                <?php
                // --- NEW: Find the single active bot entry to render its pane ---
                $active_bot_entry_to_render = null;
if ($initial_active_bot_id) {
    foreach ($all_bots_ordered_entries as $bot_entry) {
        if ($bot_entry['post']->ID === $initial_active_bot_id) {
            $active_bot_entry_to_render = $bot_entry;
            break;
        }
    }
}

// --- NEW: Render only the active pane ---
if ($create_new_active_class) {
    $createTabContentActiveClass = 'aipkit_active'; // ensure it's active
    include __DIR__ . '/partials/chatbot-create-new-pane.php';
} elseif ($active_bot_entry_to_render) {
    $bot_post     = $active_bot_entry_to_render['post'];
    $bot_settings = $active_bot_entry_to_render['settings'];
    $is_default   = ($bot_post->ID === $default_bot_id);
    $active_class = 'aipkit_active'; // The single rendered pane is always active
    $bot_id       = $bot_post->ID;
    $bot_name     = esc_html($bot_post->post_title);
    include __DIR__ . '/partials/chatbot-settings-pane.php';
} else {
    // This is a fallback case where there's no active bot and 'create new' isn't active either.
    // This can happen if the forced bot ID doesn't exist.
    // We'll show the create new pane as a safe default.
    $create_new_active_class = 'aipkit_active'; // Mark as active for CSS
    $createTabContentActiveClass = 'aipkit_active'; // Pass to partial
    include __DIR__ . '/partials/chatbot-create-new-pane.php';
}
?>
            </div><!-- /.aipkit_tab-content-area -->
        </div><!-- /.aipkit_chatbot-list-column -->

        <!-- Right Column: Chat Preview -->
        <div class="aipkit_chatbot-preview-column">
            <div id="aipkit_admin_chat_preview_container">
                <p
                    class="aipkit_preview_placeholder"
                    data-key="<?php echo esc_attr($initial_placeholder_key); ?>"
                >
                    <?php echo esc_html($initial_placeholder_text); ?>
                </p>
            </div>
        </div>
    </div><!-- /.aipkit_chatbots-split-layout -->
</div><!-- /.aipkit_container -->

<?php // Hidden divs for JS data - these are fine here?>
<div id="aipkit_available_bots_json" style="display:none;" data-bots="<?php
    $bot_list_for_filter = [];
// --- MODIFIED: Use $all_bots_ordered_entries to get post objects ---
if (!empty($all_bots_ordered_entries)) {
    foreach ($all_bots_ordered_entries as $bot_entry_filter) {
        $bot_list_for_filter[] = ['id' => $bot_entry_filter['post']->ID, 'title' => $bot_entry_filter['post']->post_title];
    }
}
// --- END MODIFICATION ---
echo esc_attr(wp_json_encode($bot_list_for_filter));
?>"></div>

<?php if ($is_voice_playback_active) : ?>
    <div id="aipkit_google_tts_voices_json_main" style="display:none;" data-voices="<?php
        $google_voices_main = class_exists('\WPAICG\Core\Providers\Google\GoogleSettingsHandler') ? \WPAICG\Core\Providers\Google\GoogleSettingsHandler::get_synced_google_tts_voices() : [];
    echo esc_attr(wp_json_encode($google_voices_main ?: []));
    ?>"></div>
     <?php
        // --- MODIFIED: Use $all_bots_ordered_entries to iterate ---
        foreach ($all_bots_ordered_entries as $bot_entry_for_json) {
            $bot_id_for_json = $bot_entry_for_json['post']->ID;
            // --- END MODIFICATION ---
            $elevenlabs_voices_for_bot = \WPAICG\AIPKit_Providers::get_elevenlabs_voices(); // Global list
            $elevenlabs_models_for_bot = \WPAICG\AIPKit_Providers::get_elevenlabs_models(); // Global list
            ?>
            <div id="aipkit_elevenlabs_voices_json_<?php echo esc_attr($bot_id_for_json); ?>" style="display:none;" data-voices="<?php echo esc_attr(wp_json_encode($elevenlabs_voices_for_bot ?: [])); ?>"></div>
            <div id="aipkit_elevenlabs_models_json_<?php echo esc_attr($bot_id_for_json); ?>" style="display:none;" data-models="<?php echo esc_attr(wp_json_encode($elevenlabs_models_for_bot ?: [])); ?>"></div>
            <?php
        }
    ?>
<?php endif; ?>
