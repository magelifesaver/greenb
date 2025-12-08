<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-frontend.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading Chat Frontend dependencies.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_frontend_logic(): void {
    $base_path = WPAICG_PLUGIN_DIR . 'classes/chat/frontend/';
    $frontend_paths = [
        'chat_assets.php' => \WPAICG\Chat\Frontend\Assets::class,
        'shortcode/shortcode_validator.php' => \WPAICG\Chat\Frontend\Shortcode\Validator::class,
        'shortcode/shortcode_dataprovider.php' => \WPAICG\Chat\Frontend\Shortcode\DataProvider::class,
        'shortcode/shortcode_featuremanager.php' => \WPAICG\Chat\Frontend\Shortcode\FeatureManager::class,
        'shortcode/shortcode_configurator.php' => \WPAICG\Chat\Frontend\Shortcode\Configurator::class,
        'shortcode/shortcode_renderer.php' => \WPAICG\Chat\Frontend\Shortcode\Renderer::class,
        'shortcode/shortcode_sitewidehandler.php' => \WPAICG\Chat\Frontend\Shortcode\SiteWideHandler::class,
        'chat_shortcode.php' => \WPAICG\Chat\Frontend\Shortcode::class,
    ];

    foreach ($frontend_paths as $file => $class_name) {
        $full_path = $base_path . $file;
        if (file_exists($full_path) && !class_exists($class_name)) {
            require_once $full_path;
        }
    }
}