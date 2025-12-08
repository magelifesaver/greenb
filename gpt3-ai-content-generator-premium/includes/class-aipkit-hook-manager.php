<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-aipkit-hook-manager.php
// Status: MODIFIED

namespace WPAICG\Includes;

// --- Use statements for ALL handlers/services needed by ANY registrar ---
// Core Functionality
use WPAICG\WP_AI_Content_Generator_i18n;
use WPAICG\Public\WP_AI_Content_Generator_Public;
use WPAICG\Shortcodes\AIPKit_Shortcodes_Manager;
use WPAICG\PostEnhancer\Core as PostEnhancerCore;
use WPAICG\Speech\AIPKit_Speech_Manager;
use WPAICG\STT\AIPKit_STT_Manager;
use WPAICG\Images\AIPKit_Image_Manager;
// AJAX Handlers
use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler;
use WPAICG\Vector\AIPKit_Vector_Post_Processor_Ajax_Handler;
use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_Vector_Stores_Ajax_Handler;
use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler;
use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_WP_Content_Indexing_Ajax_Handler;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Qdrant_Ajax_Handler;
use WPAICG\Core\Ajax\AIPKit_Core_Ajax_Handler;
use WPAICG\AutoGPT\AIPKit_Automated_Task_Manager;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Init_Stream_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Standard_Generation_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Title_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Excerpt_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Save_Post_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Create_Task_Action;
// --- MODIFIED: Use new SEO action classes ---
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Images_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Meta_Action;
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Keyword_Action;
// --- MODIFIED: Use new CSV parsing action class ---
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Parse_Csv_Action;
// --- END MODIFICATION ---
use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Template_Ajax_Handler;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Ajax_Handler;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler; // NEW
use WPAICG\Chat\Frontend\Ajax\ChatFormSubmissionAjaxHandler;
use WPAICG\Lib\Chat\Frontend\Ajax\ChatFileUploadAjaxDispatcher as LibChatFileUploadAjaxDispatcher;
use WPAICG\Dashboard\Ajax\SettingsAjaxHandler;
use WPAICG\Dashboard\Ajax\ModelsAjaxHandler;
use WPAICG\Admin\AIPKit_Migration_Handler;
use WPAICG\Admin\Ajax\Migration\AIPKit_Analyze_Old_Data_Action;
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_Global_Settings_Action;
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_Chatbot_Data_Action;
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_Image_Data_Action;
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_CPT_Data_Action;
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_Cron_Jobs_Action;
// --- NEW: Post Enhancer Actions Handler ---
use WPAICG\PostEnhancer\Ajax\AIPKit_Enhancer_Actions_Ajax_Handler;
// Migration action classes are handled by AIPKit_Migration_Handler, so no need for `use` statements for them here.
// --- ADDED: Use statement for new Semantic Search handler ---
use WPAICG\Core\Ajax\AIPKit_Semantic_Search_Ajax_Handler;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\AIPKit_Realtime_Session_Ajax_Handler;
use WPAICG\REST\AIPKit_REST_Controller;
// --- END ADDED ---

// --- Use statements for the NEW Hook Registrar classes ---
use WPAICG\Includes\HookRegistrars\Core_Hooks_Registrar;
use WPAICG\Includes\HookRegistrars\Admin_Asset_Hooks_Registrar;
use WPAICG\Includes\HookRegistrars\Ajax_Hooks_Registrar;
use WPAICG\Includes\HookRegistrars\Rest_Api_Hooks_Registrar;
use WPAICG\Includes\HookRegistrars\Module_Initializer_Hooks_Registrar;

// --- END NEW ---


// Ensure this file is only loaded by WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Hook_Manager
 * Centralizes the registration of WordPress hooks for the plugin by orchestrating sub-registrars.
 * MODIFIED: Conditional instantiation and error logging for LibChatFileUploadAjaxDispatcher based on Pro plan and addon status.
 */
class AIPKit_Hook_Manager
{
    /**
     * Define the core hooks (actions and filters) by calling specialized registrars.
     *
     * @param string $plugin_version The current plugin version.
     */
    public static function register_hooks(string $plugin_version)
    {

        // --- Instantiate ALL services/handlers needed by ANY registrar ---
        $i18n            = new WP_AI_Content_Generator_i18n();
        $public_handler  = new WP_AI_Content_Generator_Public();
        $shortcodes      = new AIPKit_Shortcodes_Manager($plugin_version);
        $post_enhancer   = new PostEnhancerCore();
        $speech_manager  = class_exists(AIPKit_Speech_Manager::class) ? new AIPKit_Speech_Manager() : null;
        $stt_manager     = class_exists(AIPKit_STT_Manager::class) ? new AIPKit_STT_Manager() : null;
        $image_manager   = class_exists(AIPKit_Image_Manager::class) ? new AIPKit_Image_Manager() : null;
        $image_settings_ajax_handler = class_exists(AIPKit_Image_Settings_Ajax_Handler::class) ? new AIPKit_Image_Settings_Ajax_Handler() : null;
        $rest_controller = class_exists(AIPKit_REST_Controller::class) ? new AIPKit_REST_Controller() : null;
        $vector_post_processor_ajax_handler = class_exists(AIPKit_Vector_Post_Processor_Ajax_Handler::class) ? new AIPKit_Vector_Post_Processor_Ajax_Handler() : null;
        $openai_vs_stores_ajax_handler = class_exists(AIPKit_OpenAI_Vector_Stores_Ajax_Handler::class) ? new AIPKit_OpenAI_Vector_Stores_Ajax_Handler() : null;
        $openai_vs_files_ajax_handler = class_exists(AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler::class) ? new AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler() : null;
        $openai_wp_content_indexing_ajax_handler = class_exists(AIPKit_OpenAI_WP_Content_Indexing_Ajax_Handler::class) ? new AIPKit_OpenAI_WP_Content_Indexing_Ajax_Handler() : null;
        $pinecone_vector_store_ajax_handler = class_exists(AIPKit_Vector_Store_Pinecone_Ajax_Handler::class) ? new AIPKit_Vector_Store_Pinecone_Ajax_Handler() : null;
        $qdrant_vector_store_ajax_handler = class_exists(AIPKit_Vector_Store_Qdrant_Ajax_Handler::class) ? new AIPKit_Vector_Store_Qdrant_Ajax_Handler() : null;
        $core_ajax_handler = class_exists(AIPKit_Core_Ajax_Handler::class) ? new AIPKit_Core_Ajax_Handler() : null;
        $automated_task_manager = class_exists(AIPKit_Automated_Task_Manager::class) ? new AIPKit_Automated_Task_Manager() : null;
        // $automated_task_cron = class_exists(AIPKit_Automated_Task_Cron::class) ? new AIPKit_Automated_Task_Cron() : null; // No longer instantiated here
        $content_writer_init_stream_action = class_exists(AIPKit_Content_Writer_Init_Stream_Action::class) ? new AIPKit_Content_Writer_Init_Stream_Action() : null;
        $content_writer_standard_gen_action = class_exists(AIPKit_Content_Writer_Standard_Generation_Action::class) ? new AIPKit_Content_Writer_Standard_Generation_Action() : null;
        $content_writer_generate_title_action = class_exists(AIPKit_Content_Writer_Generate_Title_Action::class) ? new AIPKit_Content_Writer_Generate_Title_Action() : null;
        $content_writer_save_post_action = class_exists(AIPKit_Content_Writer_Save_Post_Action::class) ? new AIPKit_Content_Writer_Save_Post_Action() : null;
        $content_writer_create_task_action = class_exists(AIPKit_Content_Writer_Create_Task_Action::class) ? new AIPKit_Content_Writer_Create_Task_Action() : null;
        $content_writer_template_ajax_handler = class_exists(AIPKit_Content_Writer_Template_Ajax_Handler::class) ? new AIPKit_Content_Writer_Template_Ajax_Handler() : null;
        $ai_form_ajax_handler = class_exists(AIPKit_AI_Form_Ajax_Handler::class) ? new AIPKit_AI_Form_Ajax_Handler() : null;
        $ai_form_settings_ajax_handler = class_exists(AIPKit_AI_Form_Settings_Ajax_Handler::class) ? new AIPKit_AI_Form_Settings_Ajax_Handler() : null; // NEW
        $settings_ajax_handler = class_exists(SettingsAjaxHandler::class) ? new SettingsAjaxHandler() : null;
        $models_ajax_handler = class_exists(ModelsAjaxHandler::class) ? new ModelsAjaxHandler() : null;
        // --- ADDED: Instantiate Semantic Search handler ---
        $semantic_search_ajax_handler = class_exists(AIPKit_Semantic_Search_Ajax_Handler::class) ? new AIPKit_Semantic_Search_Ajax_Handler() : null;
        // --- END ADDED ---

        $chat_form_submission_ajax_handler = null;
        if (class_exists(ChatFormSubmissionAjaxHandler::class)) {
            $chat_form_submission_ajax_handler = new ChatFormSubmissionAjaxHandler();
        }

        // --- MODIFIED: Conditional instantiation and error logging for LibChatFileUploadAjaxDispatcher ---
        $chat_file_upload_ajax_dispatcher = null;
        // Ensure aipkit_dashboard class and its methods are available before calling them
        if (class_exists('\WPAICG\aipkit_dashboard') &&
            method_exists('\WPAICG\aipkit_dashboard', 'is_pro_plan') &&
            method_exists('\WPAICG\aipkit_dashboard', 'is_addon_active')) {

            if (\WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('file_upload')) {
                // Pro plan is active and file_upload addon is active, so the class is expected.
                if (class_exists(LibChatFileUploadAjaxDispatcher::class)) {
                    $chat_file_upload_ajax_dispatcher = new LibChatFileUploadAjaxDispatcher();
                }
            }
            // If not Pro or addon not active, $chat_file_upload_ajax_dispatcher remains null and no warning is logged.
        }
        // --- END MODIFICATION ---

        $realtime_session_ajax_handler = null;
        if (class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('realtime_voice')) {
            if (class_exists(AIPKit_Realtime_Session_Ajax_Handler::class)) {
                $realtime_session_ajax_handler = new AIPKit_Realtime_Session_Ajax_Handler();
            }
        }

        // --- End Instantiations ---

        // --- Call Specialized Registrars ---
        if (class_exists(Core_Hooks_Registrar::class)) {
            Core_Hooks_Registrar::register(
                $i18n,
                $public_handler,
                $shortcodes,
                $post_enhancer,
                $speech_manager,
                $stt_manager,
                $image_manager
            );
        }

        if (class_exists(Admin_Asset_Hooks_Registrar::class)) {
            Admin_Asset_Hooks_Registrar::register();
        }

        // --- MODIFIED: Add $semantic_search_ajax_handler and check all handlers ---
        if (class_exists(Ajax_Hooks_Registrar::class) &&
            $image_settings_ajax_handler && $vector_post_processor_ajax_handler &&
            $openai_vs_stores_ajax_handler && $openai_vs_files_ajax_handler &&
            $openai_wp_content_indexing_ajax_handler && $pinecone_vector_store_ajax_handler &&
            $qdrant_vector_store_ajax_handler && $core_ajax_handler &&
            $automated_task_manager && $content_writer_init_stream_action &&
            $content_writer_standard_gen_action && $content_writer_generate_title_action &&
            $content_writer_save_post_action && $content_writer_create_task_action &&
            $content_writer_template_ajax_handler && $ai_form_ajax_handler &&
            $ai_form_settings_ajax_handler && // NEW CHECK
            $chat_form_submission_ajax_handler && $settings_ajax_handler && $models_ajax_handler &&
            $semantic_search_ajax_handler // ADDED
        ) {
            Ajax_Hooks_Registrar::register(
                $image_settings_ajax_handler,
                $vector_post_processor_ajax_handler,
                $openai_vs_stores_ajax_handler,
                $openai_vs_files_ajax_handler,
                $openai_wp_content_indexing_ajax_handler,
                $pinecone_vector_store_ajax_handler,
                $qdrant_vector_store_ajax_handler,
                $core_ajax_handler,
                $automated_task_manager,
                $content_writer_init_stream_action,
                $content_writer_standard_gen_action,
                $content_writer_generate_title_action,
                $content_writer_save_post_action,
                $content_writer_create_task_action,
                $content_writer_template_ajax_handler,
                $ai_form_ajax_handler,
                $ai_form_settings_ajax_handler, // NEW: Pass handler
                $chat_form_submission_ajax_handler,
                $chat_file_upload_ajax_dispatcher,
                $settings_ajax_handler,
                $models_ajax_handler,
                $realtime_session_ajax_handler,
                $semantic_search_ajax_handler // ADDED
            );
        }
        // --- END MODIFICATION ---


        if (class_exists(Rest_Api_Hooks_Registrar::class)) {
            Rest_Api_Hooks_Registrar::register($rest_controller);
        }

        if (class_exists(Module_Initializer_Hooks_Registrar::class)) {
            Module_Initializer_Hooks_Registrar::register(
                // null, // ChatInitializer uses static methods
                // $automated_task_cron // No longer pass instance
            );
        }
    }
}