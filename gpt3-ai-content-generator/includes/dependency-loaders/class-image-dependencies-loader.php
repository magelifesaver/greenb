<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-image-dependencies-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Dependencies_Loader
{
    public static function load()
    {
        $images_base_path = WPAICG_PLUGIN_DIR . 'classes/images/';
        $image_settings_ajax_handler_path = $images_base_path . 'class-aipkit-image-settings-ajax-handler.php';
        if (file_exists($image_settings_ajax_handler_path)) {
            require_once $image_settings_ajax_handler_path;
        }
        $paths = [
            'interface-aipkit-image-provider-strategy.php', 'class-aipkit-image-base-provider-strategy.php',
            'class-aipkit-image-manager.php', 'class-aipkit-image-provider-strategy-factory.php',
            'class-aipkit-image-storage-helper.php',
            'providers/class-aipkit-image-openai-provider-strategy.php',
            'providers/class-aipkit-image-azure-provider-strategy.php',
            'providers/class-aipkit-image-google-provider-strategy.php',
            'providers/class-aipkit-image-pexels-provider-strategy.php',
            'providers/class-aipkit-image-pixabay-provider-strategy.php',
            'providers/class-aipkit-image-replicate-provider-strategy.php',
        ];
        foreach ($paths as $file) {
            $full_path = $images_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
        $openai_image_dir = $images_base_path . 'providers/openai/';
        if (!class_exists(\WPAICG\Images\Providers\OpenAI\OpenAIImageUrlBuilder::class)) {
            require_once $openai_image_dir . 'OpenAIImageUrlBuilder.php';
        }
        if (!class_exists(\WPAICG\Images\Providers\OpenAI\OpenAIPayloadFormatter::class)) {
            require_once $openai_image_dir . 'OpenAIPayloadFormatter.php';
        }
        if (!class_exists(\WPAICG\Images\Providers\OpenAI\OpenAIImageResponseParser::class)) {
            require_once $openai_image_dir . 'OpenAIImageResponseParser.php';
        }
        $google_image_dir = $images_base_path . 'providers/google/';
        if (!class_exists(\WPAICG\Images\Providers\Google\GoogleImageUrlBuilder::class)) {
            require_once $google_image_dir . 'GoogleImageUrlBuilder.php';
        }
        if (!class_exists(\WPAICG\Images\Providers\Google\GoogleImagePayloadFormatter::class)) {
            require_once $google_image_dir . 'GoogleImagePayloadFormatter.php';
        }
        if (!class_exists(\WPAICG\Images\Providers\Google\GoogleImageResponseParser::class)) {
            require_once $google_image_dir . 'GoogleImageResponseParser.php';
        }
    }
}