<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST\v1;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST\Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
/**
 * Plugin controller.
 *
 * @since 1.0.0
 */
class Plugin_Controller extends Controller
{
    /** @var string rest base */
    protected $rest_base = 'plugin';
    /**
     * Registers the routes to handle the onboarding from the REST API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route($this->get_namespace(), $this->build_route(), [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'get_items_permissions_check']], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Gets the plugin data as the response.
     *
     * @since 1.0.0
     *
     * @phpstan-param  WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        return rest_ensure_response(static::plugin()->to_array());
    }
    /**
     * Checks whether the user has permissions to read the onboarding data.
     *
     * @since 1.0.0
     *
     * @phpstan-param  WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request)
    {
        // there is no "read_plugins" or "view_plugins" capability in WordPress among the standard capabilities
        return $this->permissions_check(WP_REST_Server::READABLE, 'activate_plugins', $request);
    }
    /**
     * Gets the plugin data schema.
     *
     * @see Plugin::to_array()
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_public_item_schema(): array
    {
        return ['type' => 'object', 'properties' => ['id' => ['type' => 'string', 'description' => __('The plugin identifier.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'name' => ['type' => 'string', 'description' => __('The plugin name.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'path' => ['type' => 'object', 'properties' => ['file' => ['type' => 'string', 'description' => __('The absolute path to the plugin file.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'directory' => ['type' => 'string', 'description' => __('The absolute path to the plugin directory.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'relative' => ['type' => 'string', 'description' => __('The path relative to the plugins directory in the WordPress installation.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true]]], 'textdomain' => ['type' => 'string', 'description' => __('The plugin textdomain.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'url' => ['type' => 'object', 'properties' => ['assets' => ['type' => 'string', 'description' => __('The URL to the plugin assets.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'base' => ['type' => 'string', 'description' => __('The URL to the plugin root.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'documentation' => ['type' => 'string', 'description' => __('The URL to the plugin documentation.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'reviews' => ['type' => 'string', 'description' => __('The URL to the plugin reviews.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'sales_page' => ['type' => 'string', 'description' => __('The URL to the plugin sales page.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'settings' => ['type' => 'string', 'description' => __('The URL to the plugin settings.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'support' => ['type' => 'string', 'description' => __('The URL to the plugin support.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true]]], 'vendor' => ['type' => 'string', 'description' => __('The plugin vendor.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true], 'version' => ['type' => 'string', 'description' => __('The plugin version.', static::plugin()->textdomain()), 'context' => ['view'], 'readonly' => \true]]];
    }
}
