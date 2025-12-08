<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro;

use Tmeister\JWTAuthPro\Database\MigrationManager;
use Tmeister\JWTAuthPro\Services\SettingsService;

function activate(): void
{
    // Run database migrations
    $migration_manager = new MigrationManager();
    $migration_manager->migrate();

    // Initialize settings
    $settingsService = new SettingsService();
    $settingsService->initializeSettings();
}
