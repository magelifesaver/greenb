<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-utils.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading Chat Utility dependencies.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_utils_logic(): void {
    $svg_icons_path = WPAICG_PLUGIN_DIR . 'classes/chat/utils/class-aipkit-svg-icons.php';
    if (file_exists($svg_icons_path) && !class_exists(\WPAICG\Chat\Utils\AIPKit_SVG_Icons::class)) {
        require_once $svg_icons_path;
    }
}