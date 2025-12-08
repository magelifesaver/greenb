<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Post_Enhancer_Core_Loader
{
    public static function load()
    {
        $post_enhancer_core_path = WPAICG_PLUGIN_DIR . 'classes/post-enhancer/class-aipkit-post-enhancer-core.php';
        $post_enhancer_ajax_path = WPAICG_PLUGIN_DIR . 'classes/post-enhancer/class-aipkit-post-enhancer-ajax.php';
        // --- ADDED: Path to new actions AJAX handler class ---
        $post_enhancer_actions_ajax_path = WPAICG_PLUGIN_DIR . 'classes/post-enhancer/ajax/class-aipkit-enhancer-actions-ajax-handler.php';
        // --- END ADDED ---

        if (file_exists($post_enhancer_core_path)) {
            require_once $post_enhancer_core_path;
        }

        if (file_exists($post_enhancer_ajax_path)) {
            require_once $post_enhancer_ajax_path;
        }

        // --- ADDED: Require new handler class file ---
        if (file_exists($post_enhancer_actions_ajax_path)) {
            require_once $post_enhancer_actions_ajax_path;
        }
        // --- END ADDED ---
    }
}
