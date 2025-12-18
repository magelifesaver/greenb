<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/hook-registrars/class-ajax-hooks-registrar.php
// Status: MODIFIED

namespace WPAICG\Includes\HookRegistrars;

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
use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Tags_Action;
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
// --- ADDED: Use statement for Semantic Search handler ---
use WPAICG\Core\Ajax\AIPKit_Semantic_Search_Ajax_Handler;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\AIPKit_Realtime_Session_Ajax_Handler;
use WPAICG\Chat\Ajax\AIPKit_Chatbot_Index_Content_Ajax_Handler;


// --- END ADDED ---

// Migration action classes are handled by AIPKit_Migration_Handler, so no need for `use` statements for them here.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Registers AJAX action hooks.
 */
class Ajax_Hooks_Registrar
{
    public static function register(
        AIPKit_Image_Settings_Ajax_Handler $image_settings_ajax_handler,
        AIPKit_Vector_Post_Processor_Ajax_Handler $vector_post_processor_ajax_handler,
        AIPKit_OpenAI_Vector_Stores_Ajax_Handler $openai_vs_stores_ajax_handler,
        AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler $openai_vs_files_ajax_handler,
        AIPKit_OpenAI_WP_Content_Indexing_Ajax_Handler $openai_wp_content_indexing_ajax_handler,
        AIPKit_Vector_Store_Pinecone_Ajax_Handler $pinecone_vector_store_ajax_handler,
        AIPKit_Vector_Store_Qdrant_Ajax_Handler $qdrant_vector_store_ajax_handler,
        AIPKit_Core_Ajax_Handler $core_ajax_handler,
        AIPKit_Automated_Task_Manager $automated_task_manager,
        AIPKit_Content_Writer_Init_Stream_Action $content_writer_init_stream_action,
        AIPKit_Content_Writer_Standard_Generation_Action $content_writer_standard_gen_action,
        AIPKit_Content_Writer_Generate_Title_Action $content_writer_generate_title_action,
        AIPKit_Content_Writer_Save_Post_Action $content_writer_save_post_action,
        AIPKit_Content_Writer_Create_Task_Action $content_writer_create_task_action,
        ?AIPKit_Content_Writer_Template_Ajax_Handler $content_writer_template_ajax_handler = null,
        ?AIPKit_AI_Form_Ajax_Handler $ai_form_ajax_handler = null,
        ?AIPKit_AI_Form_Settings_Ajax_Handler $ai_form_settings_ajax_handler = null, // NEW
        ?ChatFormSubmissionAjaxHandler $chat_form_submission_ajax_handler = null,
        ?LibChatFileUploadAjaxDispatcher $chat_file_upload_ajax_dispatcher = null,
        ?SettingsAjaxHandler $settings_ajax_handler = null,
        ?ModelsAjaxHandler $models_ajax_handler = null,
        ?AIPKit_Realtime_Session_Ajax_Handler $realtime_session_ajax_handler = null,
        ?AIPKit_Semantic_Search_Ajax_Handler $semantic_search_ajax_handler = null // ADDED
    ) {
        // --- MODIFIED: Instantiate new SEO action classes ---
        $content_writer_generate_meta_action = class_exists(AIPKit_Content_Writer_Generate_Meta_Action::class) ? new AIPKit_Content_Writer_Generate_Meta_Action() : null;
        $content_writer_generate_keyword_action = class_exists(AIPKit_Content_Writer_Generate_Keyword_Action::class) ? new AIPKit_Content_Writer_Generate_Keyword_Action() : null;
        $content_writer_generate_excerpt_action = class_exists(AIPKit_Content_Writer_Generate_Excerpt_Action::class) ? new AIPKit_Content_Writer_Generate_Excerpt_Action() : null;
        $content_writer_generate_tags_action = class_exists(AIPKit_Content_Writer_Generate_Tags_Action::class) ? new AIPKit_Content_Writer_Generate_Tags_Action() : null;
        $content_writer_generate_images_action = class_exists(AIPKit_Content_Writer_Generate_Images_Action::class) ? new AIPKit_Content_Writer_Generate_Images_Action() : null;
        // --- MODIFIED: Instantiate new CSV action class ---
        $content_writer_parse_csv_action = class_exists(AIPKit_Content_Writer_Parse_Csv_Action::class) ? new AIPKit_Content_Writer_Parse_Csv_Action() : null;
        // --- END MODIFICATION ---
        $enhancer_actions_ajax_handler = class_exists(AIPKit_Enhancer_Actions_Ajax_Handler::class) ? new AIPKit_Enhancer_Actions_Ajax_Handler() : null;

        // --- MODIFIED: Add NEW AJAX handler for Meta Desc ---
        if ($settings_ajax_handler) {
            if (method_exists($settings_ajax_handler, 'ajax_save_settings')) {
                add_action('wp_ajax_aipkit_save_ai_settings', [$settings_ajax_handler, 'ajax_save_settings']);
            }
            if (method_exists($settings_ajax_handler, 'ajax_handle_migration_choice')) {
                add_action('wp_ajax_aipkit_handle_migration_choice', [$settings_ajax_handler, 'ajax_handle_migration_choice']);
            }
            // --- ADDED: Register the new migration dismiss action ---
            if (method_exists($settings_ajax_handler, 'ajax_dismiss_migration_notice')) {
                add_action('wp_ajax_aipkit_dismiss_migration_notice', [$settings_ajax_handler, 'ajax_dismiss_migration_notice']);
            }
            // --- ADDED: Register the Chatolia notice dismiss action ---
            if (method_exists($settings_ajax_handler, 'ajax_dismiss_chatolia_notice')) {
                add_action('wp_ajax_aipkit_dismiss_chatolia_notice', [$settings_ajax_handler, 'ajax_dismiss_chatolia_notice']);
            }
            // --- END ADDED ---
        }

        if ($models_ajax_handler) {
            if (method_exists($models_ajax_handler, 'ajax_sync_models')) {
                add_action('wp_ajax_aipkit_sync_models', [$models_ajax_handler, 'ajax_sync_models']);
            }
        }
        // --- END MODIFICATION ---

        if (class_exists(AIPKit_Migration_Handler::class)) {
            $migration_handler = new AIPKit_Migration_Handler();
            if (method_exists($migration_handler, 'register_ajax_hooks')) {
                $migration_handler->register_ajax_hooks();
            }
        }

        if (class_exists(AIPKit_Analyze_Old_Data_Action::class)) {
            $analyze_action = new AIPKit_Analyze_Old_Data_Action();
            if (method_exists($analyze_action, 'handle_request')) {
                add_action('wp_ajax_aipkit_analyze_old_data', [$analyze_action, 'handle_request']);
            }
        }

        // --- ADDED: Register deletion AJAX hooks ---
        $deletion_actions = [
            'aipkit_delete_old_global_settings' => AIPKit_Delete_Old_Global_Settings_Action::class,
            'aipkit_delete_old_chatbot_data'    => AIPKit_Delete_Old_Chatbot_Data_Action::class,
            'aipkit_delete_old_image_data'      => AIPKit_Delete_Old_Image_Data_Action::class,
            'aipkit_delete_old_cpt_data'        => AIPKit_Delete_Old_CPT_Data_Action::class,
            'aipkit_delete_old_cron_jobs'       => AIPKit_Delete_Old_Cron_Jobs_Action::class,
        ];
        foreach ($deletion_actions as $action_name => $class_name) {
            if (class_exists($class_name)) {
                $action_instance = new $class_name();
                if (method_exists($action_instance, 'handle_request')) {
                    add_action('wp_ajax_' . $action_name, [$action_instance, 'handle_request']);
                }
            }
        }
        // --- END ADDED ---

        if (method_exists($image_settings_ajax_handler, 'ajax_save_image_settings')) {
            add_action('wp_ajax_aipkit_save_image_settings', [$image_settings_ajax_handler, 'ajax_save_image_settings']);
        }

        // --- NEW: Register AI Forms Settings AJAX action ---
        if ($ai_form_settings_ajax_handler && method_exists($ai_form_settings_ajax_handler, 'ajax_save_ai_forms_settings')) {
            add_action('wp_ajax_aipkit_save_ai_forms_settings', [$ai_form_settings_ajax_handler, 'ajax_save_ai_forms_settings']);
        }
        // --- END NEW ---

        if (method_exists($vector_post_processor_ajax_handler, 'ajax_index_posts_to_vector_store')) {
            add_action('wp_ajax_aipkit_index_posts_to_vector_store', [$vector_post_processor_ajax_handler, 'ajax_index_posts_to_vector_store']);
        }

        add_action('wp_ajax_aipkit_list_vector_stores_openai', [$openai_vs_stores_ajax_handler, 'ajax_list_vector_stores_openai']);
        add_action('wp_ajax_aipkit_create_vector_store_openai', [$openai_vs_stores_ajax_handler, 'ajax_create_vector_store_openai']);
        add_action('wp_ajax_aipkit_delete_vector_store_openai', [$openai_vs_stores_ajax_handler, 'ajax_delete_vector_store_openai']);
        add_action('wp_ajax_aipkit_search_vector_store_openai', [$openai_vs_stores_ajax_handler, 'ajax_search_vector_store_openai']);

        add_action('wp_ajax_aipkit_upload_file_to_openai', [$openai_vs_files_ajax_handler, 'ajax_upload_file_to_openai']);
        add_action('wp_ajax_aipkit_add_files_to_vector_store_openai', [$openai_vs_files_ajax_handler, 'ajax_add_files_to_vector_store_openai']);
        add_action('wp_ajax_aipkit_list_files_in_vector_store_openai', [$openai_vs_files_ajax_handler, 'ajax_list_files_in_vector_store_openai']);
        add_action('wp_ajax_aipkit_delete_file_from_vector_store_openai', [$openai_vs_files_ajax_handler, 'ajax_delete_file_from_vector_store_openai']);
        add_action('wp_ajax_aipkit_add_text_to_vector_store_openai', [$openai_vs_files_ajax_handler, 'ajax_add_text_to_vector_store_openai']);
        add_action('wp_ajax_aipkit_upload_and_add_file_to_store_direct_openai', [$openai_vs_files_ajax_handler, 'ajax_upload_and_add_file_to_store_direct_openai']);
        add_action('wp_ajax_aipkit_get_openai_indexing_logs', [$openai_vs_files_ajax_handler, 'ajax_get_openai_indexing_logs']);

        add_action('wp_ajax_aipkit_fetch_wp_content_for_indexing', [$openai_wp_content_indexing_ajax_handler, 'ajax_fetch_wp_content_for_indexing']);
        add_action('wp_ajax_aipkit_index_selected_wp_content', [$openai_wp_content_indexing_ajax_handler, 'ajax_index_selected_wp_content']);

        add_action('wp_ajax_aipkit_list_indexes_pinecone', [$pinecone_vector_store_ajax_handler, 'ajax_list_indexes_pinecone']);
        add_action('wp_ajax_aipkit_get_pinecone_index_details', [$pinecone_vector_store_ajax_handler, 'ajax_get_pinecone_index_details']);
        add_action('wp_ajax_aipkit_create_index_pinecone', [$pinecone_vector_store_ajax_handler, 'ajax_create_index_pinecone']);
        add_action('wp_ajax_aipkit_upsert_to_pinecone_index', [$pinecone_vector_store_ajax_handler, 'ajax_upsert_to_pinecone_index']);
        add_action('wp_ajax_aipkit_search_pinecone_index', [$pinecone_vector_store_ajax_handler, 'ajax_search_pinecone_index']);
        add_action('wp_ajax_aipkit_upload_file_and_upsert_to_pinecone', [$pinecone_vector_store_ajax_handler, 'ajax_upload_file_and_upsert_to_pinecone']);
        add_action('wp_ajax_aipkit_get_pinecone_indexing_logs', [$pinecone_vector_store_ajax_handler, 'ajax_get_pinecone_indexing_logs']);
        add_action('wp_ajax_aipkit_delete_index_pinecone', [$pinecone_vector_store_ajax_handler, 'ajax_delete_index_pinecone']);

        add_action('wp_ajax_aipkit_list_collections_qdrant', [$qdrant_vector_store_ajax_handler, 'ajax_list_collections_qdrant']);
        add_action('wp_ajax_aipkit_create_collection_qdrant', [$qdrant_vector_store_ajax_handler, 'ajax_create_collection_qdrant']);
        add_action('wp_ajax_aipkit_delete_collection_qdrant', [$qdrant_vector_store_ajax_handler, 'ajax_delete_collection_qdrant']);
        add_action('wp_ajax_aipkit_upsert_to_qdrant_collection', [$qdrant_vector_store_ajax_handler, 'ajax_upsert_to_qdrant_collection']);
        add_action('wp_ajax_aipkit_upload_file_and_upsert_to_qdrant', [$qdrant_vector_store_ajax_handler, 'ajax_upload_file_and_upsert_to_qdrant']);
        add_action('wp_ajax_aipkit_search_qdrant_collection', [$qdrant_vector_store_ajax_handler, 'ajax_search_qdrant_collection']);
        add_action('wp_ajax_aipkit_get_qdrant_collection_stats', [$qdrant_vector_store_ajax_handler, 'ajax_get_qdrant_collection_stats']);
        add_action('wp_ajax_aipkit_get_vector_data_source_logs_for_store', [$qdrant_vector_store_ajax_handler, 'ajax_get_vector_data_source_logs_for_store']);

        if (method_exists($core_ajax_handler, 'ajax_get_upload_limits')) {
            add_action('wp_ajax_aipkit_get_upload_limits', [$core_ajax_handler, 'ajax_get_upload_limits']);
        }
        if (method_exists($core_ajax_handler, 'ajax_generate_embedding')) {
            add_action('wp_ajax_aipkit_generate_embedding', [$core_ajax_handler, 'ajax_generate_embedding']);
        }
        if (method_exists($core_ajax_handler, 'ajax_delete_vector_data_source_entry')) {
            add_action('wp_ajax_aipkit_delete_vector_data_source_entry', [$core_ajax_handler, 'ajax_delete_vector_data_source_entry']);
        }
        if (method_exists($core_ajax_handler, 'ajax_get_chunk_logs_by_batch')) {
            add_action('wp_ajax_aipkit_get_chunk_logs_by_batch', [$core_ajax_handler, 'ajax_get_chunk_logs_by_batch']);
        }
        if (method_exists($core_ajax_handler, 'ajax_reindex_vector_data_source_entry')) {
            add_action('wp_ajax_aipkit_reindex_vector_data_source_entry', [$core_ajax_handler, 'ajax_reindex_vector_data_source_entry']);
        }
        if (method_exists($core_ajax_handler, 'ajax_get_cpt_indexing_options')) {
            add_action('wp_ajax_aipkit_get_cpt_indexing_options', [$core_ajax_handler, 'ajax_get_cpt_indexing_options']);
        }
        if (method_exists($core_ajax_handler, 'ajax_save_cpt_indexing_options')) {
            add_action('wp_ajax_aipkit_save_cpt_indexing_options', [$core_ajax_handler, 'ajax_save_cpt_indexing_options']);
        }
        // NEW: AJAX action for fetching knowledge base stats
        if (method_exists($core_ajax_handler, 'ajax_get_knowledge_base_stats')) {
            add_action('wp_ajax_aipkit_get_knowledge_base_stats', [$core_ajax_handler, 'ajax_get_knowledge_base_stats']);
        }
        // NEW: AJAX action for SYNCING knowledge base stats
        if (method_exists($core_ajax_handler, 'ajax_sync_knowledge_base_stats')) {
            add_action('wp_ajax_aipkit_sync_knowledge_base_stats', [$core_ajax_handler, 'ajax_sync_knowledge_base_stats']);
        }
        // NEW: AJAX action for refreshing knowledge base cards
        if (method_exists($core_ajax_handler, 'ajax_refresh_knowledge_base_cards')) {
            add_action('wp_ajax_aipkit_refresh_knowledge_base_cards', [$core_ajax_handler, 'ajax_refresh_knowledge_base_cards']);
        }

        // --- NEW: Chatbot Index Content AJAX Handlers ---
        if (class_exists('\WPAICG\Chat\Ajax\AIPKit_Chatbot_Index_Content_Ajax_Handler')) {
            $chatbot_index_content_ajax_handler = new \WPAICG\Chat\Ajax\AIPKit_Chatbot_Index_Content_Ajax_Handler();
            add_action('wp_ajax_aipkit_check_indexing_status', [$chatbot_index_content_ajax_handler, 'ajax_check_indexing_status']);
            add_action('wp_ajax_aipkit_analyze_express_setup', [$chatbot_index_content_ajax_handler, 'ajax_analyze_express_setup']);
            add_action('wp_ajax_aipkit_start_content_indexing', [$chatbot_index_content_ajax_handler, 'ajax_start_content_indexing']);
            add_action('wp_ajax_aipkit_cancel_content_indexing', [$chatbot_index_content_ajax_handler, 'ajax_cancel_content_indexing']);
            add_action('wp_ajax_aipkit_get_indexing_progress', [$chatbot_index_content_ajax_handler, 'ajax_get_indexing_progress']);
            
            // Register cron action for background processing
            add_action('aipkit_process_content_indexing', ['\WPAICG\Chat\Ajax\AIPKit_Chatbot_Index_Content_Ajax_Handler', 'process_content_indexing']);
        }
        // --- END NEW ---

        if (method_exists($automated_task_manager, 'init_ajax_hooks')) {
            $automated_task_manager->init_ajax_hooks();
        }

        if (method_exists($content_writer_init_stream_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_init_stream', [$content_writer_init_stream_action, 'handle']);
        }
        if (method_exists($content_writer_standard_gen_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_standard', [$content_writer_standard_gen_action, 'handle']);
        }
        if (method_exists($content_writer_generate_title_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_title', [$content_writer_generate_title_action, 'handle']);
        }
        add_action('wp_ajax_aipkit_save_cw_template', [$content_writer_template_ajax_handler, 'ajax_save_template']);
        add_action('wp_ajax_aipkit_load_cw_template', [$content_writer_template_ajax_handler, 'ajax_load_template']);
        add_action('wp_ajax_aipkit_delete_cw_template', [$content_writer_template_ajax_handler, 'ajax_delete_template']);
        add_action('wp_ajax_aipkit_list_cw_templates', [$content_writer_template_ajax_handler, 'ajax_list_templates']);

        if (method_exists($content_writer_save_post_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_save_post', [$content_writer_save_post_action, 'handle']);
        }
        if (method_exists($content_writer_create_task_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_create_task', [$content_writer_create_task_action, 'handle']);
        }
        // --- NEW: Register new SEO action hooks ---
        if ($content_writer_generate_meta_action && method_exists($content_writer_generate_meta_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_meta_desc', [$content_writer_generate_meta_action, 'handle']);
        }
        if ($content_writer_generate_keyword_action && method_exists($content_writer_generate_keyword_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_focus_keyword', [$content_writer_generate_keyword_action, 'handle']);
        }
        if ($content_writer_generate_excerpt_action && method_exists($content_writer_generate_excerpt_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_excerpt', [$content_writer_generate_excerpt_action, 'handle']);
        }
        if ($content_writer_generate_tags_action && method_exists($content_writer_generate_tags_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_tags', [$content_writer_generate_tags_action, 'handle']);
        }
        // --- ADDED: Register new Image action hook ---
        if ($content_writer_generate_images_action && method_exists($content_writer_generate_images_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_generate_images', [$content_writer_generate_images_action, 'handle']);
        }
        // --- MODIFIED: Register new CSV and URL action hooks ---
        if ($content_writer_parse_csv_action && method_exists($content_writer_parse_csv_action, 'handle')) {
            add_action('wp_ajax_aipkit_content_writer_parse_csv', [$content_writer_parse_csv_action, 'handle']);
        }
        // --- END MODIFICATION ---

        if ($ai_form_ajax_handler && method_exists($ai_form_ajax_handler, 'register_ajax_hooks')) {
            $ai_form_ajax_handler->register_ajax_hooks();
        }

        if ($chat_form_submission_ajax_handler && method_exists($chat_form_submission_ajax_handler, 'ajax_handle_form_submission')) {
            add_action('wp_ajax_aipkit_handle_form_submission', [$chat_form_submission_ajax_handler, 'ajax_handle_form_submission']);
            add_action('wp_ajax_nopriv_aipkit_handle_form_submission', [$chat_form_submission_ajax_handler, 'ajax_handle_form_submission']);
        }
        if ($chat_file_upload_ajax_dispatcher && method_exists($chat_file_upload_ajax_dispatcher, 'ajax_handle_frontend_file_upload')) {
            add_action('wp_ajax_aipkit_frontend_chat_upload_file', [$chat_file_upload_ajax_dispatcher, 'ajax_handle_frontend_file_upload']);
            add_action('wp_ajax_nopriv_aipkit_frontend_chat_upload_file', [$chat_file_upload_ajax_dispatcher, 'ajax_handle_frontend_file_upload']);
        }

        if ($realtime_session_ajax_handler && method_exists($realtime_session_ajax_handler, 'ajax_create_session')) {
            add_action('wp_ajax_aipkit_create_realtime_session', [$realtime_session_ajax_handler, 'ajax_create_session']);
            add_action('wp_ajax_nopriv_aipkit_create_realtime_session', [$realtime_session_ajax_handler, 'ajax_create_session']);
        }
        
        // --- ADDED: Register new Realtime Session logging AJAX action ---
        if ($realtime_session_ajax_handler && method_exists($realtime_session_ajax_handler, 'ajax_log_session_turn')) {
            add_action('wp_ajax_aipkit_log_realtime_session_turn', [$realtime_session_ajax_handler, 'ajax_log_session_turn']);
            add_action('wp_ajax_nopriv_aipkit_log_realtime_session_turn', [$realtime_session_ajax_handler, 'ajax_log_session_turn']);
        }
        // --- END ADDED ---

        // --- NEW: Register Enhancer Actions AJAX hooks ---
        if ($enhancer_actions_ajax_handler) {
            if (method_exists($enhancer_actions_ajax_handler, 'ajax_get_actions')) {
                add_action('wp_ajax_aipkit_get_enhancer_actions', [$enhancer_actions_ajax_handler, 'ajax_get_actions']);
            }
            if (method_exists($enhancer_actions_ajax_handler, 'ajax_save_action')) {
                add_action('wp_ajax_aipkit_save_enhancer_action', [$enhancer_actions_ajax_handler, 'ajax_save_action']);
            }
            if (method_exists($enhancer_actions_ajax_handler, 'ajax_delete_action')) {
                add_action('wp_ajax_aipkit_delete_enhancer_action', [$enhancer_actions_ajax_handler, 'ajax_delete_action']);
            }
            if (method_exists($enhancer_actions_ajax_handler, 'ajax_reset_actions')) {
                add_action('wp_ajax_aipkit_reset_enhancer_actions', [$enhancer_actions_ajax_handler, 'ajax_reset_actions']);
            }
        }
        // --- END NEW ---

        // --- NEW: Register Semantic Search AJAX hooks ---
        if ($semantic_search_ajax_handler && method_exists($semantic_search_ajax_handler, 'ajax_perform_semantic_search')) {
            add_action('wp_ajax_aipkit_perform_semantic_search', [$semantic_search_ajax_handler, 'ajax_perform_semantic_search']);
            add_action('wp_ajax_nopriv_aipkit_perform_semantic_search', [$semantic_search_ajax_handler, 'ajax_perform_semantic_search']);
        }
        // --- END NEW ---
    }
}
