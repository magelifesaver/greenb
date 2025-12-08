<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger\Contracts\Logger as Logger_Interface;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter\Newsletter_Provider;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Dashboard;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Blocks;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Milestone;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Installer;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Privacy;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Requirements\Requirement;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\REST_API;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Features\Feature;
use WC_Logger_Interface;
use WC_Payment_Gateway;
/**
 * WooCommerce extension plugin.
 *
 * Plugin implementations intended to extend WooCommerce should extend this class.
 *
 * @since 1.0.0
 */
abstract class Extension extends Plugin implements WooCommerce_Extension
{
    /**
     * WooCommerce extension constructor.
     *
     * A concrete extension plugin constructor normally would not take any args but instead pass arguments to this parent constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $config
     *
     * @phpstan-param array{
     *     admin?: array{
     *          handler?: class-string<Admin>,
     *          dashboard?: class-string<Dashboard>,
     *     },
     *     blocks?: array{
     *          handler?: class-string<Blocks>,
     *     },
     *     file: string,
     *     integrations?: array<class-string<Integration>>,
     *     lifecycle?: array{
     *          installer?: class-string<Installer>,
     *          migrations?: array<string, class-string<Migration>>,
     *          milestones?: class-string<Milestone>[],
     *     },
     *     logger?: class-string<Logger_Interface>|class-string<WC_Logger_Interface>,
     *     marketing?: array{
     *          newsletter?: array{
     *              provider: class-string<Newsletter_Provider>,
     *              config: array<string, mixed>,
     *          },
     *     },
     *     privacy?: array{
     *          handler?: class-string<Privacy>,
     *     },
     *     requirements?: array<class-string<Requirement>, array<string, mixed>>,
     *     rest_api?: array{
     *          handler?: class-string<REST_API>,
     *     },
     *     woocommerce?: array{
     *          payment_gateways?: class-string<Gateway>[],
     *          supported_features?: string[],
     *          system_status_handler?: class-string<System_Status_Report>,
     *     },
     *  } $config
     */
    protected function __construct(array $config)
    {
        parent::__construct($config);
        static::add_action('before_woocommerce_init', [$this, 'declare_features_compatibility']);
        static::add_action('init', [$this, 'initialize_payment_gateways']);
        static::add_filter('woocommerce_payment_gateways', [$this, 'register_payment_gateways']);
    }
    /**
     * Initializes the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize(): void
    {
        parent::initialize();
        Settings::initialize($this);
        $this->initialize_system_status_report();
    }
    /**
     * Initializes any payment gateways.
     *
     * @NOTE This runs on `init` to ensure that translations for the payment gateways settings are loaded.
     *
     * @since 1.7.0
     *
     * @return void
     */
    protected function initialize_payment_gateways(): void
    {
        $gateways = $this->config()->get('woocommerce.payment_gateways', []);
        if (!is_array($gateways) || empty($gateways) || !class_exists('\WC_Payment_Gateway')) {
            return;
        }
        foreach ($gateways as $gateway) {
            if (!is_string($gateway) || !class_exists($gateway) || !is_a($gateway, Gateway::class, \true)) {
                _doing_it_wrong(__METHOD__, 'A payment gateway must be a valid class that extends ' . Gateway::class . '.', '');
                continue;
            }
            $gateway::instance($this);
        }
    }
    /**
     * Initializes the system status report handler.
     *
     * @since 1.3.0
     *
     * @return void
     */
    protected function initialize_system_status_report(): void
    {
        $handler = $this->config()->get('woocommerce.system_status_handler');
        if (!$handler) {
            return;
        }
        if (!is_string($handler) || !class_exists($handler) || !is_a($handler, System_Status_Report::class, \true)) {
            _doing_it_wrong(__METHOD__, 'The system status report handler must be a valid class that extends ' . System_Status_Report::class . '.', '');
        } else {
            $handler::initialize($this);
        }
    }
    /**
     * Declares support with various WooCommerce features.
     *
     * Plugins extending this class can supply the supported features in the configuration in `woocommerce.supported_features`.
     * Alternatively, they can also override and extend this method.
     *
     * @see Feature for a list of supported features
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function declare_features_compatibility(): void
    {
        foreach ((array) $this->config()->get('woocommerce.supported_features', []) as $feature) {
            if (!is_string($feature)) {
                _doing_it_wrong(__METHOD__, 'The feature name must be a string.', '');
                continue;
            }
            // @phpstan-ignore-next-line sanity check on external class method existence
            if (!class_exists(FeaturesUtil::class) || !is_callable(FeaturesUtil::class . '::declare_compatibility') || !in_array($feature, Feature::values(), \true)) {
                continue;
            }
            FeaturesUtil::declare_compatibility($feature, $this->absolute_file_path(), \true);
        }
    }
    /**
     * Registers the payment gateways introduced by the plugin with WooCommerce.
     *
     * @since 1.7.0
     *
     * @param array<int, class-string<WC_Payment_Gateway>>|mixed $payment_gateways
     * @return array<int, class-string<WC_Payment_Gateway>>|mixed
     */
    protected function register_payment_gateways($payment_gateways)
    {
        if (!is_array($payment_gateways)) {
            return $payment_gateways;
        }
        $gateways = $this->config()->get('woocommerce.payment_gateways', []);
        if (!is_array($gateways) || empty($gateways)) {
            return $payment_gateways;
        }
        foreach ($gateways as $gateway) {
            if (!is_a($gateway, Gateway::class, \true)) {
                _doing_it_wrong(__METHOD__, 'A payment gateway must be a valid class that extends ' . Gateway::class . '.', '');
                continue;
            }
            $payment_gateways[] = $gateway;
        }
        return $payment_gateways;
    }
}
