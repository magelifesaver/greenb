<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro;

use Tmeister\JWTAuthPro\Container\Container;
use Tmeister\JWTAuthPro\Database\MigrationManager;
use Tmeister\JWTAuthPro\Providers\AdminServiceProvider;
use Tmeister\JWTAuthPro\Providers\AnalyticsServiceProvider;
use Tmeister\JWTAuthPro\Providers\CoreServiceProvider;
use Tmeister\JWTAuthPro\Providers\ServiceProvider;
use Tmeister\JWTAuthPro\Services\LicenseService;

class JWTAuthPro
{
    private static ?self $instance = null;
    private Container $container;
    /** @var array<int, ServiceProvider> */
    private array $serviceProviders = [];

    private function __construct()
    {
        $this->container = new Container();
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        $this->maybeUpdate();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    public function maybeUpdate(): void
    {
        if (!defined('JWT_AUTH_PRO_VERSION')) {
            return;
        }

        $current_version = get_option('jwt_auth_pro_version', '0.0.0');
        if (version_compare($current_version, JWT_AUTH_PRO_VERSION, '<')) {
            $migration_manager = new MigrationManager();
            $migration_manager->migrate();
            update_option('jwt_auth_pro_version', JWT_AUTH_PRO_VERSION);
        }
    }

    private function registerServiceProviders(): void
    {
        // Register License Service first
        LicenseService::getInstance();

        // Register service providers
        $this->registerProvider(new CoreServiceProvider());
        $this->registerProvider(new AnalyticsServiceProvider());
        $this->registerProvider(new AdminServiceProvider());
    }

    private function registerProvider(ServiceProvider $provider): void
    {
        $provider->register($this->container);
        $this->serviceProviders[] = $provider;
    }

    private function bootServiceProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            $provider->boot($this->container);
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    private function __clone() {}
}
