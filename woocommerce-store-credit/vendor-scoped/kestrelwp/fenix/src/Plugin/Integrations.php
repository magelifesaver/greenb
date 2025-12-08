<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Integrations handler.
 *
 * @since 1.0.0
 */
class Integrations
{
    use Is_Handler;
    /** @var class-string<Integration>[] integrations to load */
    protected array $integrations = [];
    /**
     * Initializes the integrations' handler.
     *
     * @since 1.0.0
     *
     * @param WordPress_Plugin $plugin
     */
    public function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        $this->initialize_integrations();
    }
    /**
     * Returns the integrations to load.
     *
     * @since 1.0.0
     *
     * @return class-string<Integration>[]
     */
    protected function get_integrations(): array
    {
        if (!empty($this->integrations)) {
            return $this->integrations;
        }
        $this->integrations = static::plugin()->config()->get('integrations', []);
        return $this->integrations;
    }
    /**
     * Initializes the plugin integrations.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize_integrations(): void
    {
        foreach ($this->get_integrations() as $integration) {
            // @phpstan-ignore-next-line sanity check
            if (!is_string($integration)) {
                _doing_it_wrong(__METHOD__, 'Invalid integration. An integration must be a valid class that implements ' . Integration::class . '.', '');
                continue;
            }
            // @phpstan-ignore-next-line sanity check
            if (!is_a($integration, Integration::class, \true)) {
                _doing_it_wrong(__METHOD__, esc_html(sprintf('Cannot load integration. %1$s must be a valid class that implements %2$s.', $integration, Integration::class)), '');
                continue;
            }
            if (!$integration::should_initialize()) {
                continue;
            }
            $integration::initialize(static::plugin());
        }
    }
}
