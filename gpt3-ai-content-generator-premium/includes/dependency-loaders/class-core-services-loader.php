<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-core-services-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Core_Services_Loader {
    public static function load() {
        $core_path = WPAICG_PLUGIN_DIR . 'classes/core/';
        // --- MODIFIED: Load new Token Manager path ---
        $token_manager_path = $core_path . 'token-manager/AIPKit_Token_Manager.php';
        if (file_exists($token_manager_path)) {
            require_once $token_manager_path;
        }
        // --- END MODIFICATION ---
        require_once $core_path . 'class-aipkit_ai_caller.php';
        require_once $core_path . 'models_api.php';
        require_once $core_path . 'class-aipkit-instruction-manager.php';
        require_once $core_path . 'class-aipkit-content-moderator.php';
        require_once $core_path . 'class-aipkit-payload-sanitizer.php';

        $stream_path_base = $core_path . 'stream/';
        $sse_classes_to_load = [
            $stream_path_base . 'formatter/class-sse-response-formatter.php',
            $stream_path_base . 'cache/class-sse-message-cache.php',
            $stream_path_base . 'vector/class-sse-vector-context-helper.php',
            $stream_path_base . 'contexts/chat/class-chat-context-handler.php',
            $stream_path_base . 'contexts/content-writer/class-content-writer-context-handler.php',
            $stream_path_base . 'contexts/ai-forms/class-ai-forms-context-handler.php',
            $stream_path_base . 'request/class-sse-request-handler.php',
            $stream_path_base . 'processor/class-sse-stream-processor.php',
            $stream_path_base . 'handler/class-sse-handler.php',
        ];
        foreach ($sse_classes_to_load as $sse_class_file) {
            if (file_exists($sse_class_file)) {
                require_once $sse_class_file;
            }
        }
    }
}