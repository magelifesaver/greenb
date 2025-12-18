<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/class-triggers-dependencies-loader.php
// Status: MODIFIED

namespace WPAICG\Lib\DependencyLoaders;

// Use statements for the new loader functions
use function WPAICG\Lib\DependencyLoaders\Triggers\load_schemas;
use function WPAICG\Lib\DependencyLoaders\Triggers\load_actions;
use function WPAICG\Lib\DependencyLoaders\Triggers\load_conditions;
use function WPAICG\Lib\DependencyLoaders\Triggers\load_manager;
use function WPAICG\Lib\DependencyLoaders\Triggers\load_storage;
use function WPAICG\Lib\DependencyLoaders\Triggers\load_core;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Triggers_Dependencies_Loader
 * Handles loading all trigger-related classes by delegating to modular loader functions.
 */
class Triggers_Dependencies_Loader {

    public static function load() {
        $triggers_loader_path = __DIR__ . '/triggers/'; // Path to the new subdirectory

        // Load the modular loader files
        require_once $triggers_loader_path . 'load-schemas.php';
        require_once $triggers_loader_path . 'load-actions.php';
        require_once $triggers_loader_path . 'load-conditions.php';
        require_once $triggers_loader_path . 'load-manager.php';
        require_once $triggers_loader_path . 'load-storage.php';
        require_once $triggers_loader_path . 'load-core.php';

        // Call the loader functions
        load_schemas();
        load_actions();
        load_conditions();
        load_manager();
        load_storage();
        load_core();
    }
}