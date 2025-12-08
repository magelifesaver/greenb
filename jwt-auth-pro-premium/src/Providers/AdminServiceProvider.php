<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Providers;

use Tmeister\JWTAuthPro\Container\Container;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\Services\AdminRoutesService;
use Tmeister\JWTAuthPro\Services\AdminService;
use Tmeister\JWTAuthPro\Services\SecurityService;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Services\TokenService;

class AdminServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // Register admin services
        $container->singleton(
            AdminService::class,
            fn(Container $container): AdminService => new AdminService(
                $container->make(TokenRepository::class),
                $container->make(SettingsService::class)
            )
        );

        $container->singleton(
            AdminRoutesService::class,
            fn(Container $container): AdminRoutesService => new AdminRoutesService(
                $container->make(SecurityService::class),
                $container->make(TokenService::class)
            )
        );
    }

    public function boot(Container $container): void
    {
        // Register hooks for admin services
        $container->make(AdminService::class)->registerHooks();
        $container->make(AdminRoutesService::class)->registerHooks();
    }
}
