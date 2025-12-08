<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vector_Store_Dependencies_Loader
{
    public static function load()
    {
        $vector_base_path = WPAICG_PLUGIN_DIR . 'classes/vector/';
        $providers_path = $vector_base_path . 'providers/'; // Base path for provider strategies
        $manager_methods_path = $vector_base_path . 'manager/'; // NEW: Path for manager methods

        // Core vector store classes (interfaces, base classes, factories, main manager)
        $core_paths = [
            $vector_base_path . 'interface-aipkit-vector-provider-strategy.php',
            $vector_base_path . 'class-aipkit-vector-base-provider-strategy.php',
            $vector_base_path . 'class-aipkit-vector-provider-strategy-factory.php',
            $vector_base_path . 'class-aipkit-vector-store-manager.php', // This class now loads its own method files
            $vector_base_path . 'class-aipkit-vector-store-registry.php',
        ];

        foreach ($core_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Provider-specific strategy bootstrap files
        $provider_bootstraps = [
            $providers_path . 'pinecone/bootstrap.php',
            $providers_path . 'qdrant/bootstrap.php',
            $providers_path . 'openai/bootstrap.php',
        ];

        foreach ($provider_bootstraps as $bootstrap_file) {
            if (file_exists($bootstrap_file)) {
                require_once $bootstrap_file;
            }
        }
    }
}
