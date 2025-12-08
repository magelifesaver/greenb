<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/assets/class-assets-hooks.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Assets;

use WPAICG\Chat\Frontend\Assets as AssetsOrchestrator; // Use the main orchestrator

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Manages the registration of WordPress action hooks for chat assets.
 */
class AssetsHooks {
    private $orchestrator;

    public function __construct(AssetsOrchestrator $orchestrator) {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Registers the necessary WordPress hooks.
     */
    public function register(): void {
        // Note: The orchestrator's methods are now public wrappers.
        add_action('wp_enqueue_scripts', [$this->orchestrator, 'register_and_enqueue_frontend_assets_public_wrapper'], 99);
        add_action('template_redirect', [$this->orchestrator, 'check_for_site_wide_bot_public_wrapper']);
    }
}