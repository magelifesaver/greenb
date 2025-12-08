<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/register-hooks-core.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

use WPAICG\Chat\Admin\AdminSetup;
use WPAICG\Chat\Frontend\Shortcode;
use WPAICG\Chat\Frontend\Assets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for registering core Chat module hooks (CPT, shortcode, assets).
 * Called by WPAICG\Chat\Initializer::register_hooks().
 *
 * @param AdminSetup $admin_setup
 * @param Shortcode $shortcode
 * @param Assets $assets
 * @return void
 */
function register_hooks_core_logic(
    AdminSetup $admin_setup,
    Shortcode $shortcode,
    Assets $assets
): void {
    add_action('init', [$admin_setup, 'register_chatbot_post_type']);
    add_shortcode('aipkit_chatbot', [$shortcode, 'render_chatbot_shortcode']);
    $assets->register_hooks(); // This internally adds 'wp_enqueue_scripts' and 'template_redirect'
}