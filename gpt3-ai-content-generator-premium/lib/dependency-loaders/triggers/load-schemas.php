<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-schemas.php
// Status: NEW FILE

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads trigger schema definitions.
 */
function load_schemas(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }

    $trigger_schemas_path = WPAICG_LIB_DIR . 'schemas/triggers/trigger-schemas.php';
    if (file_exists($trigger_schemas_path)) {
        require_once $trigger_schemas_path;
    }
}