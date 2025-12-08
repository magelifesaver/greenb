<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-core-services.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading Core Chat Service dependencies.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_core_services_logic(): void {
    $base_path = WPAICG_PLUGIN_DIR . 'classes/chat/core/';
    $core_service_paths = [
        'ai_service.php' => \WPAICG\Chat\Core\AIService::class,
        'ajax_processor.php' => \WPAICG\Chat\Core\AjaxProcessor::class,
        'class-aipkit_content_aware.php' => \WPAICG\Chat\Core\AIPKit_Content_Aware::class,
    ];

    foreach ($core_service_paths as $file => $class_name) {
        $full_path = $base_path . $file;
        if (file_exists($full_path) && !class_exists($class_name)) {
            require_once $full_path;
        }
    }
}