<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-core-moderation-dependencies-loader.php
// Status: NEW FILE

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Core_Moderation_Dependencies_Loader
 * Handles loading core moderation classes.
 */
class Core_Moderation_Dependencies_Loader {

    public static function load() {
        $moderation_base_path = WPAICG_PLUGIN_DIR . 'classes/core/moderation/';

        $moderation_files = [
            'AIPKit_BannedIP_Checker.php',
            'AIPKit_BannedWords_Checker.php',
            'AIPKit_OpenAI_Moderation_Checker.php',
        ];

        foreach ($moderation_files as $file) {
            $full_path = $moderation_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}