<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Providers;

use Tmeister\JWTAuthPro\Actions\Auth\AuthenticateRequestAction;
use Tmeister\JWTAuthPro\Container\Container;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\Services\AdminService;
use Tmeister\JWTAuthPro\Services\AuthenticationService;
use Tmeister\JWTAuthPro\Services\SecurityService;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Services\TokenService;

class CoreServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // Register repositories
        $container->singleton(
            TokenRepository::class,
            fn(): TokenRepository => new TokenRepository()
        );

        // Register core services
        $container->singleton(
            SettingsService::class,
            fn(): SettingsService => new SettingsService()
        );

        $container->singleton(
            SecurityService::class,
            fn(): SecurityService => new SecurityService()
        );

        $container->singleton(
            AuthenticateRequestAction::class,
            fn(): AuthenticateRequestAction => new AuthenticateRequestAction()
        );

        $container->singleton(
            AuthenticationService::class,
            fn(Container $container): AuthenticationService => new AuthenticationService(
                $container->make(AuthenticateRequestAction::class),
                $container->make(SettingsService::class)
            )
        );

        $container->singleton(
            TokenService::class,
            fn(Container $container): TokenService => new TokenService(
                $container->make(TokenRepository::class),
                $container->make(SettingsService::class)
            )
        );

        $container->singleton(
            AdminService::class,
            fn(Container $container): AdminService => new AdminService(
                $container->make(TokenRepository::class),
                $container->make(SettingsService::class)
            )
        );
    }

    public function boot(Container $container): void
    {
        // Register hooks for core services
        $container->make(AuthenticationService::class)->registerHooks();
        $container->make(SettingsService::class)->registerHooks();
    }
}
