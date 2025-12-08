<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1\Logger_Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1\Onboarding_Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1\Plugin_Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1\Settings_Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * REST API handler.
 *
 * Plugins implementing routes in the WordPress REST API should extend this class and add their controllers to the list.
 *
 * This handler will automatically add the following controllers:
 *
 * @see Logger_Controller route: `/<vendor>/<plugin_id>/<version>/log` (POST)
 * @see Onboarding_Controller route: `/<vendor>/<plugin_id>/<version>/onboarding` (GET, POST, PUT, PATCH, DELETE)
 * @see Plugin_Controller route: `/<vendor>/<plugin_id>/<version>/plugin` (GET)
 * @see Settings_Controller route: `/<vendor>/<plugin_id>/<version>/settings` (GET, PUT, PATCH, DELETE)
 *
 * @since 1.0.0
 */
class REST_API
{
    use Is_Handler;
    /** @var bool|null */
    protected static ?bool $is_rest_api_enabled = null;
    /** @var class-string<Controller>[] */
    protected array $controllers = [Logger_Controller::class, Onboarding_Controller::class, Plugin_Controller::class, Settings_Controller::class];
    /**
     * REST API handler constructor.
     *
     * @since 1.0.0
     *
     * @param WordPress_Plugin $plugin
     * @throws Error
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        self::add_action('rest_api_init', [$this, 'register_routes']);
    }
    /**
     * Returns the REST API namespace.
     *
     * By default, this will use the vendor name and the plugin name, e.g. `vendor/plugin`.
     * Plugins extending this class may override this method to return a different namespace used by their controllers.
     *
     * @see Controller::__construct()
     *
     * @since 1.0.0
     *
     * @param string $version the API version
     * @return string
     */
    public function namespace(string $version = 'v1'): string
    {
        return strtolower(static::plugin()->vendor()) . '/' . static::plugin()->id() . '/' . $version;
    }
    /**
     * Gets the controllers.
     *
     * @since 1.0.0
     *
     * @return class-string<Controller>[]
     */
    protected function get_controllers(): array
    {
        return $this->controllers;
    }
    /**
     * Registers new REST API routes.
     *
     * @since 1.0.0
     *
     * @return void
     * @throws Error
     */
    protected function register_routes(): void
    {
        foreach ($this->get_controllers() as $controller) {
            // @phpstan-ignore-next-line sanity check
            if (!is_string($controller)) {
                _doing_it_wrong(__METHOD__, 'Invalid controller. A controller must be a valid class that extends ' . Controller::class . '.', '');
                continue;
            }
            // @phpstan-ignore-next-line sanity check
            if (!class_exists($controller) || !is_a($controller, Controller::class, \true)) {
                _doing_it_wrong(__METHOD__, esc_html(sprintf('Cannot load controller: %1$s must be a valid class that extends %2$s.', $controller, Controller::class)), '');
                continue;
            }
            (new $controller($this))->register_routes();
        }
    }
    /**
     * Determines if the REST API is enabled and available.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public static function is_available(): bool
    {
        if (is_bool(self::$is_rest_api_enabled)) {
            return self::$is_rest_api_enabled;
        }
        $response = wp_remote_get(rest_url());
        if (is_wp_error($response)) {
            self::$is_rest_api_enabled = \false;
        } else {
            self::$is_rest_api_enabled = \true;
        }
        return self::$is_rest_api_enabled;
    }
}
