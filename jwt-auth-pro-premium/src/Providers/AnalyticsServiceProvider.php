<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Providers;

use Tmeister\JWTAuthPro\Actions\Analytics\CleanupAnalyticsAction;
use Tmeister\JWTAuthPro\Container\Container;
use Tmeister\JWTAuthPro\Database\AnalyticsRepository;
use Tmeister\JWTAuthPro\Services\AnalyticsCollectionService;
use Tmeister\JWTAuthPro\Services\AnalyticsRoutesService;
use Tmeister\JWTAuthPro\Services\SettingsService;

class AnalyticsServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // Register repositories
        $container->singleton(
            AnalyticsRepository::class,
            fn(): AnalyticsRepository => new AnalyticsRepository()
        );

        // Register analytics services
        $container->singleton(
            AnalyticsRoutesService::class,
            fn(Container $container): AnalyticsRoutesService => new AnalyticsRoutesService(
                $container->make(AnalyticsRepository::class)
            )
        );

        $container->singleton(
            AnalyticsCollectionService::class,
            fn(Container $container): AnalyticsCollectionService => new AnalyticsCollectionService(
                $container->make(AnalyticsRepository::class),
                $container->make(AnalyticsRoutesService::class),
                $container->make(SettingsService::class)
            )
        );

        // Register cleanup action
        $container->singleton(
            CleanupAnalyticsAction::class,
            fn(Container $container): CleanupAnalyticsAction => new CleanupAnalyticsAction(
                $container->make(AnalyticsRepository::class),
                $container->make(SettingsService::class)
            )
        );
    }

    public function boot(Container $container): void
    {
        // Register hooks for analytics services
        $container->make(AnalyticsRoutesService::class)->registerHooks();
        $container->make(AnalyticsCollectionService::class)->registerHooks();

        // Register cleanup schedule
        $container->make(CleanupAnalyticsAction::class)->registerCleanupSchedule();
    }
}
