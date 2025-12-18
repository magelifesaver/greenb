<?php

// File: includes/dependency-loaders/class-provider-dependencies-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Provider_Dependencies_Loader
{
    public static function load()
    {
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/';
        $traits_path = $providers_path . 'traits/';
        require_once $providers_path . 'interface-provider-strategy.php';
        require_once $providers_path . 'base-provider-strategy.php';
        require_once $traits_path . 'trait-aipkit-chat-completions-payload.php';
        require_once $traits_path . 'trait-aipkit-chat-completions-response-parser.php';
        require_once $traits_path . 'trait-aipkit-chat-completions-sse-parser.php';

        // Load OpenAI provider and its components
        require_once $providers_path . 'openai/bootstrap-url-builder.php';
        require_once $providers_path . 'openai/bootstrap-payload-formatter.php';
        require_once $providers_path . 'openai/bootstrap-response-parser.php';
        require_once $providers_path . 'openai/bootstrap-conversation-helper.php';
        require_once $providers_path . 'openai/bootstrap-provider-strategy.php';


        // Load other providers
        $openrouter_dir = $providers_path . 'openrouter/';
        if (file_exists($openrouter_dir . 'bootstrap-provider-strategy.php')) {
            require_once $openrouter_dir . 'bootstrap-provider-strategy.php';
        }
        if (file_exists($openrouter_dir . 'bootstrap-url-builder.php')) {
            require_once $openrouter_dir . 'bootstrap-url-builder.php';
        }
        if (file_exists($openrouter_dir . 'bootstrap-payload-formatter.php')) {
            require_once $openrouter_dir . 'bootstrap-payload-formatter.php';
        }
        if (file_exists($openrouter_dir . 'bootstrap-response-parser.php')) {
            require_once $openrouter_dir . 'bootstrap-response-parser.php';
        }

        // MODIFIED: Load Google bootstraps instead of direct class file
        $google_dir = $providers_path . 'google/';
        $google_bootstraps = [
            'bootstrap-provider-strategy.php',
            'bootstrap-url-builder.php',
            'bootstrap-payload-formatter.php',
            'bootstrap-response-parser.php',
            'bootstrap-settings-handler.php'
        ];
        foreach ($google_bootstraps as $g_bootstrap) {
            $g_bootstrap_path = $google_dir . $g_bootstrap;
            if (file_exists($g_bootstrap_path)) {
                require_once $g_bootstrap_path;
            }
        }
        // END MODIFICATION

        require_once $providers_path . 'azure/bootstrap-provider-strategy.php';
        require_once $providers_path . 'deepseek-provider-strategy.php';
        // Load Ollama bootstrap (Pro addon strategy lives under lib)
        if (file_exists($providers_path . 'ollama/bootstrap-provider-strategy.php')) {
            require_once $providers_path . 'ollama/bootstrap-provider-strategy.php';
        }
        require_once $providers_path . 'provider-strategy-factory.php';

        // Load sub-components for Azure (these bootstraps load their respective classes)
        $azure_dir = $providers_path . 'azure/';
        if (file_exists($azure_dir . 'bootstrap-url-builder.php')) {
            require_once $azure_dir . 'bootstrap-url-builder.php';
        }
        if (file_exists($azure_dir . 'bootstrap-payload-formatter.php')) {
            require_once $azure_dir . 'bootstrap-payload-formatter.php';
        }
        if (file_exists($azure_dir . 'bootstrap-response-parser.php')) {
            require_once $azure_dir . 'bootstrap-response-parser.php';
        }
    }
}
