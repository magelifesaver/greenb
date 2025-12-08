<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Providers;

use Tmeister\JWTAuthPro\Container\Container;

interface ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(Container $container): void;

    /**
     * Bootstrap any application services.
     */
    public function boot(Container $container): void;
}
