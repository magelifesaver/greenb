<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\REST_API;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
/**
 * WordPress REST API controller.
 *
 * @since 1.0.0
 *
 * @method static Controller initialize( REST_API $rest_api )
 */
abstract class Controller extends WP_REST_Controller
{
    use Has_Plugin_Instance;
    /** @var string the endpoint version */
    protected string $version = 'v1';
    /** @var REST_API the REST API handler */
    protected REST_API $rest_api;
    /**
     * REST API controller constructor.
     *
     * @since 1.0.0
     *
     * @param REST_API $rest_api handler
     */
    public function __construct(REST_API $rest_api)
    {
        $this->namespace = $rest_api->namespace($this->version);
        $this->rest_api = $rest_api;
    }
    /**
     * Gets the route namespace.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function get_namespace(): string
    {
        return untrailingslashit($this->namespace);
    }
    /**
     * Gets the route base.
     *
     * @since 1.0.0
     *
     * @param string $route
     * @return string
     */
    protected function build_route(string $route = ''): string
    {
        return trailingslashit('/' . $this->rest_base . $route);
    }
    /**
     * Performs a permissions check for the REST API.
     *
     * @since 1.0.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param string $method
     * @param string $capability
     * @param WP_REST_Request $request
     * @param string|null $message
     * @return bool|WP_Error
     */
    protected function permissions_check(string $method, string $capability, WP_REST_Request $request, ?string $message = null)
    {
        /**
         * Filter the permissions check for the REST API.
         *
         * @since 1.0.0
         *
         * @param string $method transport method
         * @param WP_REST_Request<array<mixed>> $request current request
         * @param bool $has_permissions
         * @param Controller $controller originating controller
         */
        $has_permissions = (bool) apply_filters(static::plugin()->hook('rest_permissions_check'), current_user_can($capability), $method, $request, $this);
        return $has_permissions ?: $this->error_response(Response_Error::for_method($method), $message);
    }
    /**
     * Return an error response object.
     *
     * @since 1.0.0
     *
     * @param string $code
     * @param string|null $message
     * @param int|null $status_code
     * @return WP_Error
     */
    protected function error_response(string $code, ?string $message = null, ?int $status_code = null): WP_Error
    {
        if (!$message) {
            $message = Response_Error::get_status_message($code);
        }
        return new WP_Error($code, $message, ['status' => !$status_code ? Response_Error::get_status_code($code) : $status_code]);
    }
}
