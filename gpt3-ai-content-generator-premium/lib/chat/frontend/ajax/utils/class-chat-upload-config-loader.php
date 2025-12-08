<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/utils/class-chat-upload-config-loader.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax\Utils;

use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;
use WPAICG\Utils\AIPKit_Identifier_Utils;
use WPAICG\Vector\AIPKit_Vector_Provider_Strategy_Factory;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatUploadConfigLoader
 *
 * Ensures necessary global dependencies are loaded and provides access to them.
 */
class ChatUploadConfigLoader
{
    private $vector_store_manager;
    private $vector_store_registry;
    private $providers_helper;
    private $identifier_utils;
    private $strategy_factory;
    private $wpdb;
    private $data_source_table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';

        // Ensure dependencies are loaded (these should typically be loaded by the main plugin)
        $dependencies = [
            AIPKit_Vector_Store_Manager::class => WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php',
            AIPKit_Vector_Store_Registry::class => WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-registry.php',
            AIPKit_Providers::class => WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php',
            AIPKit_Identifier_Utils::class => WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-identifier-utils.php',
            AIPKit_Vector_Provider_Strategy_Factory::class => WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-provider-strategy-factory.php',
        ];

        foreach ($dependencies as $class => $path) {
            if (!class_exists($class) && file_exists($path)) {
                require_once $path;
            }
        }

        if (class_exists(AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new AIPKit_Vector_Store_Manager();
        }
        if (class_exists(AIPKit_Vector_Store_Registry::class)) {
            $this->vector_store_registry = new AIPKit_Vector_Store_Registry();
        }
        if (class_exists(AIPKit_Providers::class)) {
            $this->providers_helper = new AIPKit_Providers();
        } // Instance not strictly needed for static methods
        if (class_exists(AIPKit_Identifier_Utils::class)) {
            $this->identifier_utils = new AIPKit_Identifier_Utils();
        } // Instance not strictly needed for static methods
        if (class_exists(AIPKit_Vector_Provider_Strategy_Factory::class)) {
            $this->strategy_factory = new AIPKit_Vector_Provider_Strategy_Factory();
        } // Instance not strictly needed for static methods
    }

    public function get_vector_store_manager(): ?AIPKit_Vector_Store_Manager
    {
        return $this->vector_store_manager;
    }
    public function get_vector_store_registry(): ?AIPKit_Vector_Store_Registry
    {
        return $this->vector_store_registry;
    }
    public function get_providers_helper(): ?AIPKit_Providers
    {
        return $this->providers_helper;
    } // Returns instance, but static methods are used
    public function get_identifier_utils(): ?AIPKit_Identifier_Utils
    {
        return $this->identifier_utils;
    } // Returns instance, but static methods are used
    public function get_strategy_factory(): ?AIPKit_Vector_Provider_Strategy_Factory
    {
        return $this->strategy_factory;
    } // Returns instance, but static methods are used
    public function get_wpdb(): \wpdb
    {
        return $this->wpdb;
    }
    public function get_data_source_table_name(): string
    {
        return $this->data_source_table_name;
    }
}
