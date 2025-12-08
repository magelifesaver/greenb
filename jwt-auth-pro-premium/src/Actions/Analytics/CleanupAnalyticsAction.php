<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Analytics;

use Tmeister\JWTAuthPro\Database\AnalyticsRepository;
use Tmeister\JWTAuthPro\Services\SettingsService;

class CleanupAnalyticsAction
{
    public function __construct(
        private readonly AnalyticsRepository $repository = new AnalyticsRepository(),
        private readonly SettingsService $settingsService = new SettingsService(),
    ) {}

    public function execute(): int
    {
        // Get retention days from settings
        $retentionDays = (int) $this->settingsService->getSetting(
            'data_management',
            'analytics_retention_days'
        );

        // If forever is selected just return
        if ($retentionDays === -1) {
            return 0;
        }

        // Calculate the cutoff date
        $cutoffDate = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

        // Delete old records and return the number of deleted rows
        return $this->repository->deleteOlderThan($cutoffDate);
    }

    /**
     * Register the cleanup cron job
     */
    public function registerCleanupSchedule(): void
    {
        if (!wp_next_scheduled('jwt_auth_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'jwt_auth_cleanup_analytics');
        }

        add_action('jwt_auth_cleanup_analytics', [$this, 'execute']);
    }
}
