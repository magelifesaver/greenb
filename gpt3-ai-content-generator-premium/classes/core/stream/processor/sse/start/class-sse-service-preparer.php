<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/sse/start/class-sse-service-preparer.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Processor\SSE\Start;

use WPAICG\Core\Providers\ProviderStrategyFactory;
use WPAICG\Core\Providers\ProviderStrategyInterface;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the AI provider strategy for SSE streaming.
 */
class SSEServicePreparer {

    public function __construct() {
        // Ensure ProviderStrategyFactory is loaded
        if (!class_exists(ProviderStrategyFactory::class)) {
            $factory_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/provider-strategy-factory.php';
            if (file_exists($factory_path)) {
                require_once $factory_path;
            }
        }
    }

    /**
     * Gets the appropriate AI provider strategy.
     *
     * @param string $provider The name of the AI provider.
     * @param array $api_params API parameters for the provider (unused by factory but good for context).
     * @return ProviderStrategyInterface|WP_Error The strategy instance or WP_Error on failure.
     */
    public function prepare_strategy(string $provider, array $api_params): ProviderStrategyInterface|WP_Error {
        if (!class_exists(ProviderStrategyFactory::class)) {
            return new WP_Error('factory_missing_preparer', 'Provider strategy factory is unavailable.', ['status' => 500]);
        }
        return ProviderStrategyFactory::get_strategy($provider);
    }
}