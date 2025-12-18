<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-chat-dependencies-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Dependencies_Loader
{
    public static function load()
    {
        $chat_base_path = WPAICG_PLUGIN_DIR . 'classes/chat/';
        $frontend_chat_ajax_path = $chat_base_path . 'core/ajax-processor/frontend-chat/';
        $frontend_ajax_handlers_path = $chat_base_path . 'frontend/ajax/'; // Path for ChatFormSubmissionAjaxHandler

        $paths = [
            'core/ai_service.php',
            'core/ajax_processor.php',
            'core/class-aipkit_content_aware.php',
            $frontend_chat_ajax_path . 'class-chat-message-validator.php',
            $frontend_chat_ajax_path . 'class-chat-image-input-processor.php',
            $frontend_chat_ajax_path . 'class-chat-trigger-runner.php',
            $frontend_chat_ajax_path . 'class-chat-context-builder.php',
            $frontend_chat_ajax_path . 'class-chat-history-manager.php',
            $frontend_chat_ajax_path . 'class-chat-ai-request-runner.php',
            $frontend_chat_ajax_path . 'class-chat-response-logger.php',
            $frontend_ajax_handlers_path . 'class-chat-form-submission-ajax-handler.php',
            // --- REMOVED: class-chat-file-upload-ajax-handler.php is now a Pro feature ---
            // $frontend_ajax_handlers_path . 'class-chat-file-upload-ajax-handler.php',
            // --- END REMOVED ---
            'utils/class-aipkit_chat_utils.php',
            'utils/class-aipkit-svg-icons.php',
            'utils/class-log-status-renderer.php',
            'utils/class-log-config.php',
            'admin/chat_admin_setup.php',
            'storage/class-aipkit_log_query_helper.php', 'storage/class-aipkit_site_wide_bot_manager.php',
            'storage/class-aipkit_default_bot_setup.php', 'storage/class-aipkit_bot_settings_manager.php',
            'storage/class-aipkit_bot_lifecycle_manager.php',
            'storage/logger/generate-parent-id.php',
            'storage/logger/generate-message-id.php',
            'storage/logger/build-message-object.php',
            'storage/logger/build-where-clauses.php',
            'storage/logger/update-existing-log.php',
            'storage/logger/insert-new-log.php',
            'storage/class-aipkit_conversation_logger.php',
            'storage/class-aipkit_conversation_reader.php',
            'storage/class-aipkit_feedback_manager.php',
            'storage/class-aipkit_log_manager.php',
            'storage/class-aipkit_log_cron_manager.php', // NEW
            'storage/class-aipkit_chat_bot_storage.php',
            'storage/class-aipkit_chat_log_storage.php', 'storage/class-aipkit-bot-settings-getter.php',
            'storage/class-aipkit-bot-settings-saver.php', 'storage/class-aipkit-bot-settings-initializer.php',
            'admin/ajax/chatbot_ajax_handler.php', 'admin/ajax/chatbot_export_ajax_handler.php',
            'admin/ajax/chatbot_import_ajax_handler.php', 'admin/ajax/conversation_ajax_handler.php',
            'admin/ajax/log_ajax_handler.php', 'admin/ajax/user_credits_ajax_handler.php',
            'admin/ajax/class-aipkit-chatbot-image-ajax-handler.php',
            'ajax/class-aipkit-chatbot-index-content-ajax-handler.php',
            'frontend/chat_assets.php', 'frontend/chat_shortcode.php',
            'frontend/shortcode/shortcode_configurator.php', 'frontend/shortcode/shortcode_dataprovider.php',
            'frontend/shortcode/shortcode_featuremanager.php', 'frontend/shortcode/shortcode_renderer.php',
            'frontend/shortcode/shortcode_sitewidehandler.php', 'frontend/shortcode/shortcode_validator.php',
            'class-aipkit_chat_initializer.php'
        ];
        foreach ($paths as $file) {
            if (strpos($file, $frontend_chat_ajax_path) === 0 || strpos($file, $frontend_ajax_handlers_path) === 0) {
                $full_path = $file;
            } else {
                $full_path = $chat_base_path . $file;
            }

            if (file_exists($full_path)) {
                $class_name_from_file = basename($file, '.php');
                if (strpos($class_name_from_file, 'class-') === 0) {
                    $class_name_from_file = substr($class_name_from_file, strlen('class-'));
                }
                $class_name_from_file = str_replace('-', '_', $class_name_from_file);
                $class_name_from_file = preg_replace_callback('/(?:^|_)([a-z])/', function ($matches) {
                    return strtoupper($matches[1]);
                }, $class_name_from_file);

                $namespace_parts = explode('/', dirname(str_replace($chat_base_path, '', $full_path)));
                $namespace = 'WPAICG\\Chat';
                foreach ($namespace_parts as $part) {
                    if ($part !== '.' && !empty($part)) {
                        $namespace .= '\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $part)));
                    }
                }
                $potential_class_name = rtrim($namespace, '\\') . '\\' . $class_name_from_file;

                $final_class_check_name = $potential_class_name;
                if ($file === 'frontend/ajax/class-chat-form-submission-ajax-handler.php') {
                    $final_class_check_name = \WPAICG\Chat\Frontend\Ajax\ChatFormSubmissionAjaxHandler::class;
                } elseif ($file === 'admin/ajax/class-aipkit-chatbot-image-ajax-handler.php') {
                    $final_class_check_name = \WPAICG\Chat\Admin\Ajax\ChatbotImageAjaxHandler::class;
                }


                if (!class_exists($final_class_check_name) && !function_exists($final_class_check_name . '_logic') && substr($file, -4) === '.php') {
                    require_once $full_path;
                } elseif (substr($file, -4) !== '.php') {
                    require_once $full_path;
                }
            }
        }
    }
}