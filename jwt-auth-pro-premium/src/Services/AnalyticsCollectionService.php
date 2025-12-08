<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use DateTime;
use DateTimeZone;
use Exception;
use Tmeister\JWTAuthPro\Database\AnalyticsRepository;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class AnalyticsCollectionService
{
    use HasRequestIP;

    public function __construct(
        private readonly AnalyticsRepository $repository,
        private readonly AnalyticsRoutesService $routesService,
        private readonly SettingsService $settingsService,
    ) {}

    public function aggregateDaily(): bool|WP_Error
    {
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day', strtotime(current_time('mysql', true))));
        $endDate = date('Y-m-d 00:00:00', strtotime(current_time('mysql', true)));

        $result = $this->repository->aggregateDaily($startDate, $endDate);

        if (!is_wp_error($result)) {
            $this->routesService->deleteAnalyticsTransients();

            do_action('jwt_auth_analytics_aggregated', [
                'rows_affected' => $result,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }

        return $result;
    }

    public function consolidateWeeklyData(): bool|WP_Error
    {
        try {
            $endDate = new DateTime('yesterday', new DateTimeZone('UTC'));
            $startDate = clone $endDate;
            $startDate->modify('-6 days'); // This gives us 7 complete days, excluding today

            // Process each day individually
            $currentDate = clone $startDate;
            $results = [];

            while ($currentDate <= $endDate) {
                $dayStart = $currentDate->format('Y-m-d 00:00:00');
                $dayEnd = $currentDate->format('Y-m-d 23:59:59');

                // Aggregate data for this specific day
                $result = $this->repository->aggregateDaily($dayStart, $dayEnd);

                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'Failed to consolidate data for %s: %s',
                        $currentDate->format('Y-m-d'),
                        $result->get_error_message()
                    ));
                    $results[] = false;
                } else {
                    $results[] = true;
                }

                $currentDate->modify('+1 day');
            }

            // Clear cache if any day was successfully processed
            if (in_array(true, $results, true)) {
                $this->routesService->deleteAnalyticsTransients();

                do_action('jwt_auth_analytics_consolidated', [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days_processed' => count(array_filter($results)),
                ]);
            }

            // Return true if at least one day was processed successfully
            return in_array(true, $results, true);
        } catch (Exception $e) {
            return new WP_Error(
                'consolidation_failed',
                'Failed to consolidate weekly data: ' . $e->getMessage()
            );
        }
    }

    public function registerHooks(): void
    {
        // Register analytics hooks
        add_action('jwt_auth_daily_analytics', [$this, 'aggregateDaily']);
        add_action('jwt_auth_weekly_consolidation', [$this, 'consolidateWeeklyData']);
    }
}
