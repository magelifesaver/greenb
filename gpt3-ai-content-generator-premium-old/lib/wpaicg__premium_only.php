<?php

/**
 * Loads all Pro-only library files.
 * This file is included by the main plugin file only when a premium plan is active.
 * Ensures that Pro classes are available when needed without cluttering the free version.
 */

if (!defined('WPINC')) {
    die;
}

// --- Function to register Pro-specific assets ---
if (!function_exists('aipkit_register_pro_assets')) {
    function aipkit_register_pro_assets()
    {
        $jspdf_lib_url  = WPAICG_PLUGIN_URL . 'lib/js/jspdf.umd.min.js';
        $jspdf_handle = 'aipkit_jspdf';
        $version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';

        // Only proceed if the Pro plan is active
        if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan()) {
            // Register jsPDF script - it will be enqueued by AssetsEnqueuer if needed
            $jspdf_file_path = WPAICG_LIB_DIR . 'js/jspdf.umd.min.js';
            if (!wp_script_is($jspdf_handle, 'registered')) {
                if (file_exists($jspdf_file_path)) {
                    wp_register_script($jspdf_handle, $jspdf_lib_url, [], '2.5.1', true); // No dependencies here
                }
            }
        }
    }
}
// Hook to 'init' to ensure it runs after Freemius is loaded but before wp_enqueue_scripts for frontend.
// Priority 5 should be fine.
add_action('init', 'aipkit_register_pro_assets', 5);
// --- End Pro Asset Registration ---


// --- NEW: Include Composer Autoloader for Pro features ---
// This autoloader is specific to dependencies managed within the /lib/ directory.
$aipkit_lib_vendor_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($aipkit_lib_vendor_autoload)) {
    require_once $aipkit_lib_vendor_autoload;
}
// --- END NEW ---


// Define the base path for the lib directory
$aipkit_lib_dir = __DIR__; // This will resolve to /lib/

// --- Addon Classes ---

// Consent Compliance Components (Load these BEFORE the facade)
$aipkit_consent_compliance_components_base_path = $aipkit_lib_dir . '/addons/consent-compliance/';
$consent_is_active_checker_path = $aipkit_consent_compliance_components_base_path . 'AIPKit_Consent_IsActiveChecker.php';
$consent_is_required_checker_path = $aipkit_consent_compliance_components_base_path . 'AIPKit_Consent_IsRequiredChecker.php';

if (file_exists($consent_is_active_checker_path)) {
    require_once $consent_is_active_checker_path;
}
if (file_exists($consent_is_required_checker_path)) {
    require_once $consent_is_required_checker_path;
}

// Consent Compliance Facade (original file path, now containing the facade)
$aipkit_consent_compliance_facade_path = $aipkit_lib_dir . '/addons/class-aipkit-consent-compliance.php';
if (file_exists($aipkit_consent_compliance_facade_path)) {
    require_once $aipkit_consent_compliance_facade_path;
}


// OpenAI Moderation Components (Load these BEFORE the facade)
$aipkit_openai_moderation_components_base_path = $aipkit_lib_dir . '/addons/openai/moderation/';
$moderation_is_required_path = $aipkit_openai_moderation_components_base_path . 'AIPKit_Moderation_IsRequired.php';
$moderation_message_provider_path = $aipkit_openai_moderation_components_base_path . 'AIPKit_Moderation_MessageProvider.php';
$moderation_executor_path = $aipkit_openai_moderation_components_base_path . 'AIPKit_Moderation_Executor.php';

if (file_exists($moderation_is_required_path)) {
    require_once $moderation_is_required_path;
}
if (file_exists($moderation_message_provider_path)) {
    require_once $moderation_message_provider_path;
}
if (file_exists($moderation_executor_path)) {
    require_once $moderation_executor_path;
}

// OpenAI Moderation Facade (original file path, now containing the facade)
$aipkit_openai_moderation_facade_path = $aipkit_lib_dir . '/addons/class-aipkit-openai-moderation.php';
if (file_exists($aipkit_openai_moderation_facade_path)) {
    require_once $aipkit_openai_moderation_facade_path;
}

// PDF Parser Utility (Pro)
$aipkit_pdf_parser_path = $aipkit_lib_dir . '/utils/class-aipkit-pdf-parser.php';
if (file_exists($aipkit_pdf_parser_path)) {
    require_once $aipkit_pdf_parser_path;
}

// File Text Extractor (Pro)
$aipkit_file_text_extractor_path = $aipkit_lib_dir . '/utils/class-aipkit-file-text-extractor.php';
if (file_exists($aipkit_file_text_extractor_path)) {
    require_once $aipkit_file_text_extractor_path;
}

// --- NEW: Load URL Scraper Utility (Pro) ---
$aipkit_url_scraper_path = $aipkit_lib_dir . '/utils/class-aipkit-url-scraper.php';
if (file_exists($aipkit_url_scraper_path)) {
    require_once $aipkit_url_scraper_path;
}
// --- END NEW ---

// --- NEW: Load Google Credentials Handler Utility (Pro) ---
$aipkit_google_credentials_handler_path = $aipkit_lib_dir . '/utils/class-aipkit-google-credentials-handler.php';
if (file_exists($aipkit_google_credentials_handler_path)) {
    require_once $aipkit_google_credentials_handler_path;
}
// --- END NEW ---

// --- NEW: Load AI Form File Parser ---
$aipkit_ai_form_file_parser_path = $aipkit_lib_dir . '/ai-forms/class-aipkit-ai-form-file-parser.php';
if (file_exists($aipkit_ai_form_file_parser_path)) {
    require_once $aipkit_ai_form_file_parser_path;
}
// --- END NEW ---

// --- MODIFIED: Load Pro assets and logic for Content Writer / AutoGPT ---
if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan()) {
    $module_settings = \WPAICG\aipkit_dashboard::get_module_settings();
    // Load if either Content Writer or AutoGPT (which includes Content Writing tasks) is active.
    if (!empty($module_settings['content_writer']) || !empty($module_settings['autogpt'])) {
        // --- Load PRO PHP Classes for CW ---
        $rss_parser_path = $aipkit_lib_dir . '/content-writer/class-aipkit-rss-feed-parser.php';
        if (file_exists($rss_parser_path)) {
            require_once $rss_parser_path;
        }
        $gsheets_parser_path = $aipkit_lib_dir . '/content-writer/class-aipkit-google-sheets-parser.php';
        if (file_exists($gsheets_parser_path)) {
            require_once $gsheets_parser_path;
        }

        // --- Load PRO AJAX Actions for CW on init ---
        add_action('init', function () {
            // Google Sheets Verifier
            $gsheets_verifier_path = WPAICG_LIB_DIR . 'content-writer/ajax/actions/class-aipkit-content-writer-verify-gsheets-action.php';
            if (file_exists($gsheets_verifier_path)) {
                require_once $gsheets_verifier_path;
                if (class_exists('\WPAICG\Lib\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Verify_Gsheets_Action')) {
                    $gsheets_verifier_handler = new \WPAICG\Lib\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Verify_Gsheets_Action();
                    add_action('wp_ajax_aipkit_content_writer_verify_gsheets', [$gsheets_verifier_handler, 'handle']);
                }
            }
            // URL Scraper
            $url_scraper_action_path = WPAICG_LIB_DIR . 'content-writer/ajax/actions/class-aipkit-content-writer-scrape-url-action.php';
            if (file_exists($url_scraper_action_path)) {
                require_once $url_scraper_action_path;
                if (class_exists('\WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Scrape_Url_Action')) {
                    $url_scraper_handler = new \WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Scrape_Url_Action();
                    add_action('wp_ajax_aipkit_content_writer_scrape_url', [$url_scraper_handler, 'handle']);
                }
            }
        }, 20);

        // --- Load PRO Assets for CW ---
        $cw_pro_assets_loader_path = WPAICG_LIB_DIR . 'admin/assets/class-aipkit-content-writer-pro-assets.php';
        if (file_exists($cw_pro_assets_loader_path)) {
            require_once $cw_pro_assets_loader_path;
            if (class_exists('\WPAICG\Lib\Admin\Assets\AIPKit_Content_Writer_Pro_Assets')) {
                $cw_pro_assets = new \WPAICG\Lib\Admin\Assets\AIPKit_Content_Writer_Pro_Assets();
                $cw_pro_assets->register_hooks();
            }
        }
    }
}
// --- END MODIFICATION ---


// --- NEW: Conditional Loading for Chatbot Triggers Addon ---
if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('triggers')) {

    // Load PHP Dependencies
    // Corrected path to Triggers_Dependencies_Loader.php which is now directly in /lib/dependency-loaders/
    $triggers_loader_path = WPAICG_LIB_DIR . 'dependency-loaders/class-triggers-dependencies-loader.php';
    if (file_exists($triggers_loader_path)) {
        require_once $triggers_loader_path;
        // Corrected class name for Triggers_Dependencies_Loader and its namespace
        if (class_exists('\WPAICG\Lib\DependencyLoaders\Triggers_Dependencies_Loader')) {
            \WPAICG\Lib\DependencyLoaders\Triggers_Dependencies_Loader::load();
        }
    }

    // Load Assets
    // Corrected path to AIPKit_Trigger_Builder_Assets.php
    $triggers_assets_loader_path = WPAICG_LIB_DIR . 'admin/assets/class-aipkit-trigger-builder-assets.php';
    if (file_exists($triggers_assets_loader_path)) {
        require_once $triggers_assets_loader_path;
        // Corrected class name for AIPKit_Trigger_Builder_Assets and its namespace
        if (class_exists('\WPAICG\Lib\Admin\Assets\AIPKit_Trigger_Builder_Assets')) {
            $trigger_assets = new \WPAICG\Lib\Admin\Assets\AIPKit_Trigger_Builder_Assets();
            $trigger_assets->register_hooks();
        }
    }
}
// --- END NEW ---

// --- MODIFIED: Conditional Loading for Chat File Upload ---
if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('file_upload')) {
    // Trait Dependencies (loaded from main plugin's classes directory)
    $trait_check_frontend_permissions_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/traits/Trait_CheckFrontendPermissions.php';
    if (file_exists($trait_check_frontend_permissions_path) && !trait_exists('\WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckFrontendPermissions')) {
        require_once $trait_check_frontend_permissions_path;
    }
    $trait_send_wp_error_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/traits/Trait_SendWPError.php';
    if (file_exists($trait_send_wp_error_path) && !trait_exists('\WPAICG\Chat\Admin\Ajax\Traits\Trait_SendWPError')) {
        require_once $trait_send_wp_error_path;
    }

    // Load new Chat File Upload AJAX Dispatcher and its specific dependencies from /lib/
    $chat_file_upload_base_path = WPAICG_LIB_DIR . 'chat/frontend/ajax/';
    $config_loader_path = $chat_file_upload_base_path . 'utils/class-chat-upload-config-loader.php';
    $validator_path = $chat_file_upload_base_path . 'utils/class-chat-upload-validator.php';
    $logger_path = $chat_file_upload_base_path . 'utils/class-chat-upload-logger.php';

    $openai_handler_path = $chat_file_upload_base_path . 'handlers/class-chat-file-upload-openai-handler.php';
    $pinecone_handler_path = $chat_file_upload_base_path . 'handlers/class-chat-file-upload-pinecone-handler.php';
    $qdrant_handler_path = $chat_file_upload_base_path . 'handlers/class-chat-file-upload-qdrant-handler.php';
    $main_handler_path = $chat_file_upload_base_path . 'handlers/class-chat-file-upload-handler.php';
    $dispatcher_path = $chat_file_upload_base_path . 'class-chat-file-upload-ajax-dispatcher.php';

    $files_to_load_for_upload = [
        $config_loader_path, $validator_path, $logger_path,
        $openai_handler_path, $pinecone_handler_path, $qdrant_handler_path,
        $main_handler_path, $dispatcher_path
    ];

    foreach ($files_to_load_for_upload as $file_path) {
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
// --- END MODIFICATION ---

// --- NEW: Conditional Loading for Realtime Voice ---
if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('realtime_voice')) {
    // --- START FIX: Load dependencies for Realtime Session Handler ---
    $traits_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/traits/';
    $trait_files_to_load = [
        'Trait_CheckFrontendPermissions.php',
        'Trait_SendWPError.php',
        'Trait_CheckAdminPermissions.php',
        'Trait_CheckModuleAccess.php'
    ];
    foreach ($trait_files_to_load as $trait_file) {
        if (file_exists($traits_path . $trait_file)) {
            require_once $traits_path . $trait_file;
        }
    }
    $base_ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/base_ajax_handler.php';
    if (file_exists($base_ajax_handler_path) && !class_exists('\WPAICG\Chat\Admin\Ajax\BaseAjaxHandler')) {
        require_once $base_ajax_handler_path;
    }
    // --- END FIX ---
    $realtime_handler_path = WPAICG_LIB_DIR . 'chat/frontend/ajax/handlers/class-aipkit-realtime-session-ajax-handler.php';
    if (file_exists($realtime_handler_path)) {
        require_once $realtime_handler_path;
    }
}
// --- END NEW ---

// --- NEW: Conditional Loading for WhatsApp Addon ---
if (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('whatsapp')) {
    // Load WhatsApp core classes
    $wa_base = WPAICG_LIB_DIR . 'whatsapp/';
    $wa_files = [
        $wa_base . 'core/class-whatsapp-client.php',
        $wa_base . 'core/class-whatsapp-processor.php',
        $wa_base . 'rest/class-whatsapp-webhook-controller.php',
        $wa_base . 'admin/class-whatsapp-settings.php',
    ];
    foreach ($wa_files as $wa_file) {
        if (file_exists($wa_file)) {
            require_once $wa_file;
        }
    }

    // Register REST routes
    if (class_exists('\\WPAICG\\Lib\\WhatsApp\\Rest\\WhatsApp_Webhook_Controller')) {
        add_action('rest_api_init', [\WPAICG\Lib\WhatsApp\Rest\WhatsApp_Webhook_Controller::class, 'register_routes']);
    }

    // Register Admin AJAX for connectors management
    if (class_exists('\\WPAICG\\Lib\\WhatsApp\\Admin\\WhatsApp_Settings')) {
        add_action('admin_init', function() {
            \WPAICG\Lib\WhatsApp\Admin\WhatsApp_Settings::register_admin_hooks();
        });
    }
}
// --- END NEW ---
