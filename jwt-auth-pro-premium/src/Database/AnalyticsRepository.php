<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Database;

use DateTime;
use Exception;
use Tmeister\JWTAuthPro\Traits\HasTableName;
use WP_Error;
use wpdb;

class AnalyticsRepository
{
    use HasTableName;

    private string $analytics_table;
    private string $summary_table;
    private string $tokens_table;
    private wpdb $wpdb;
    private array $allowedColumns = [
        'event_type',
        'event_status',
        'failure_reason',
        'user_id',
        'token_id',
        'token_family',
        'ip_address',
        'user_agent',
        'country_code',
        'request_path',
        'request_method',
        'response_time',
        'blog_id',
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->analytics_table = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_TABLE);
        $this->summary_table = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_SUMMARY_TABLE);
        $this->tokens_table = $this->getTableName(JWT_AUTH_PRO_TOKENS_TABLE);
    }

    public function insert(array $data): int|false
    {
        // Validate event_type to prevent SQL injection
        if (isset($data['event_type']) && !in_array($data['event_type'], ['token_generation', 'token_validation', 'token_refresh', 'token_revocation'], true)) {
            return false;
        }

        // Validate event_status
        if (isset($data['event_status']) && !in_array($data['event_status'], [1, 2], true)) {
            return false;
        }

        // Sanitize numeric fields
        $numericFields = ['user_id', 'token_id', 'response_time', 'blog_id'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_INT);
                if ($data[$field] === false) {
                    return false;
                }
            }
        }

        // Sanitize string fields
        $stringFields = ['failure_reason', 'token_family', 'ip_address', 'user_agent', 'request_path', 'request_method'];
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = sanitize_text_field($data[$field]);
            }
        }

        // Set event_timestamp to current UTC time if not provided
        if (!isset($data['event_timestamp'])) {
            $data['event_timestamp'] = gmdate('Y-m-d H:i:s');
        } else {
            // If timestamp is provided, ensure it's in UTC format
            $timestamp = strtotime($data['event_timestamp']);
            if ($timestamp === false) {
                return false;
            }
            $data['event_timestamp'] = gmdate('Y-m-d H:i:s', $timestamp);
        }

        // Prepare data for insertion
        $sanitizedData = array_intersect_key($data, array_flip(array_merge($this->allowedColumns, ['event_timestamp'])));

        if (empty($sanitizedData)) {
            return false;
        }

        $formats = [];
        foreach ($sanitizedData as $value) {
            $formats[] = is_int($value) ? '%d' : '%s';
        }

        $result = $this->wpdb->insert(
            $this->analytics_table,
            $sanitizedData,
            $formats
        );

        if ($result === false) {
            return false;
        }

        return $this->wpdb->insert_id;
    }

    private function upsertSummaryRecord(array $data, int $summaryType): bool
    {
        // Check if record exists
        $exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT 1 FROM {$this->summary_table}
                WHERE summary_date = %s
                AND summary_type = %d
                AND blog_id = %d",
                $data['summary_date'],
                $summaryType,
                $data['blog_id']
            )
        );

        if ($exists) {
            // Update existing record
            return (bool) $this->wpdb->update(
                $this->summary_table,
                [
                    'metrics' => $data['metrics'],
                    'metadata' => $data['metadata'],
                    'updated_at' => current_time('mysql', true),
                ],
                [
                    'summary_date' => $data['summary_date'],
                    'summary_type' => $summaryType,
                    'blog_id' => $data['blog_id'],
                ],
                ['%s', '%s', '%s'],
                ['%s', '%d', '%d']
            );
        }

        // Insert new record
        return (bool) $this->wpdb->insert(
            $this->summary_table,
            [
                'summary_date' => $data['summary_date'],
                'summary_type' => $summaryType,
                'blog_id' => $data['blog_id'],
                'metrics' => $data['metrics'],
                'metadata' => $data['metadata'],
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );
    }

    private function ensureZeroValueRecords(string $date): void
    {
        $transientKey = 'jwt_auth_zero_values_' . $date;
        if (get_transient($transientKey)) {
            return;
        }

        // Token stats zero-value record
        $tokenData = [
            'summary_date' => $date,
            'blog_id' => get_current_blog_id(),
            'metrics' => json_encode([
                'total_tokens' => 0,
                'user_count' => 0,
                'active_tokens' => 0,
                'revoked_tokens' => 0,
                'expired_tokens' => 0,
            ]),
            'metadata' => json_encode([
                'token_families' => 0,
                'growth_rate' => [
                    'daily' => 0,
                    'total' => 0,
                    'percentage' => 0,
                ],
            ]),
        ];

        // Analytics stats zero-value record
        $analyticsData = [
            'summary_date' => $date,
            'blog_id' => get_current_blog_id(),
            'metrics' => json_encode([
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'unique_users' => 0,
                'avg_response_time' => 0,
            ]),
            'metadata' => json_encode([
                'events_by_type' => [
                    'token_generation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                    'token_validation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                    'token_refresh' => ['total' => 0, 'success' => 0, 'failure' => 0],
                ],
            ]),
        ];

        // Insert or update token stats
        $this->upsertSummaryRecord($tokenData, 1);

        // Insert or update analytics stats
        $this->upsertSummaryRecord($analyticsData, 2);

        // Cache the result for 24 hours
        set_transient($transientKey, true, DAY_IN_SECONDS);
    }

    public function aggregateDaily(string $startDate, string $endDate): bool|WP_Error
    {
        // Validate date formats to prevent SQL injection
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $endDate)) {
            return new WP_Error(
                'invalid_date_format',
                'Invalid date format. Expected YYYY-MM-DD',
                ['status' => 400]
            );
        }

        try {
            // Start transaction
            $this->wpdb->query('START TRANSACTION');

            // Generate all dates in the range
            $dates = [];
            $current = new DateTime($startDate);
            $end = new DateTime($endDate);

            while ($current <= $end) {
                $dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }

            // Get token stats
            $tokenStats = $this->getTokenStats($startDate, $endDate);
            if (is_wp_error($tokenStats)) {
                throw new Exception($tokenStats->get_error_message());
            }

            // Get analytics stats
            $analyticsStats = $this->getAnalyticsStats($startDate, $endDate);
            if (is_wp_error($analyticsStats)) {
                throw new Exception($analyticsStats->get_error_message());
            }

            // Create lookup arrays for easy access
            $tokenStatsByDate = array_column($tokenStats, null, 'summary_date');
            $analyticsStatsByDate = array_column($analyticsStats, null, 'summary_date');

            // Process each date in the range
            foreach ($dates as $date) {
                // Prepare token stats data
                $tokenData = [
                    'summary_date' => $date,
                    'blog_id' => get_current_blog_id(),
                    'metrics' => isset($tokenStatsByDate[$date])
                        ? json_encode($tokenStatsByDate[$date]['metrics'])
                        : json_encode([
                            'total_tokens' => 0,
                            'user_count' => 0,
                            'active_tokens' => 0,
                            'revoked_tokens' => 0,
                            'expired_tokens' => 0,
                        ]),
                    'metadata' => isset($tokenStatsByDate[$date])
                        ? json_encode($tokenStatsByDate[$date]['metadata'])
                        : json_encode([
                            'token_families' => 0,
                            'growth_rate' => [
                                'daily' => 0,
                                'total' => 0,
                                'percentage' => 0,
                            ],
                        ]),
                ];

                // Prepare analytics stats data
                $analyticsData = [
                    'summary_date' => $date,
                    'blog_id' => get_current_blog_id(),
                    'metrics' => isset($analyticsStatsByDate[$date])
                        ? json_encode($analyticsStatsByDate[$date]['metrics'])
                        : json_encode([
                            'total_requests' => 0,
                            'successful_requests' => 0,
                            'failed_requests' => 0,
                            'unique_users' => 0,
                            'avg_response_time' => 0,
                        ]),
                    'metadata' => isset($analyticsStatsByDate[$date])
                        ? json_encode($analyticsStatsByDate[$date]['metadata'])
                        : json_encode([
                            'events_by_type' => [
                                'token_generation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                                'token_validation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                                'token_refresh' => ['total' => 0, 'success' => 0, 'failure' => 0],
                            ],
                        ]),
                ];

                // Insert or update records
                $this->upsertSummaryRecord($tokenData, 1);
                $this->upsertSummaryRecord($analyticsData, 2);
            }

            // Commit transaction
            $this->wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $this->wpdb->query('ROLLBACK');

            error_log(sprintf(
                'Analytics aggregation failed: %s. Start Date: %s, End Date: %s',
                $e->getMessage(),
                $startDate,
                $endDate
            ));

            return new WP_Error(
                'jwt_auth_analytics_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    private function getTokenStats(string $startDate, string $endDate): array|WP_Error
    {
        $query = $this->wpdb->prepare(
            'SELECT
                DATE(t.issued_at) as summary_date,
                t.blog_id,
                COUNT(1) as total_tokens,
                COUNT(DISTINCT t.user_id) as user_count,
                SUM(CASE WHEN t.revoked_at IS NULL AND t.expires_at > DATE_ADD(DATE(t.issued_at), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as active_tokens,
                SUM(CASE WHEN t.revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked_tokens,
                SUM(CASE WHEN t.expires_at <= DATE_ADD(DATE(t.issued_at), INTERVAL 1 DAY) AND t.revoked_at IS NULL THEN 1 ELSE 0 END) as expired_tokens,
                COUNT(DISTINCT t.token_family) as token_families,
                (SELECT COUNT(1) FROM %i) as total_tokens_all_time
            FROM %i t
            WHERE t.issued_at >= %s
            AND t.issued_at < %s
            GROUP BY DATE(t.issued_at), t.blog_id',
            $this->tokens_table,
            $this->tokens_table,
            $startDate,
            $endDate
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);
        if ($results === null) {
            return new WP_Error('query_failed', $this->wpdb->last_error);
        }

        return array_map(function ($row) {
            return [
                'summary_date' => $row['summary_date'],
                'blog_id' => $row['blog_id'],
                'metrics' => [
                    'total_tokens' => (int) $row['total_tokens'],
                    'user_count' => (int) $row['user_count'],
                    'active_tokens' => (int) $row['active_tokens'],
                    'revoked_tokens' => (int) $row['revoked_tokens'],
                    'expired_tokens' => (int) $row['expired_tokens'],
                ],
                'metadata' => [
                    'token_families' => (int) $row['token_families'],
                    'growth_rate' => [
                        'daily' => (int) $row['total_tokens'],
                        'total' => (int) $row['total_tokens_all_time'],
                        'percentage' => $row['total_tokens_all_time'] > 0
                            ? round(($row['total_tokens'] / $row['total_tokens_all_time']) * 100, 2)
                            : 0,
                    ],
                ],
            ];
        }, $results);
    }

    private function getAnalyticsStats(string $startDate, string $endDate): array|WP_Error
    {
        $query = $this->wpdb->prepare(
            'WITH event_stats AS (
                SELECT
                    DATE(event_timestamp) as summary_date,
                    blog_id,
                    event_type,
                    COUNT(*) as total,
                    SUM(CASE WHEN event_status = 1 THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN event_status = 2 THEN 1 ELSE 0 END) as failure,
                    COUNT(DISTINCT user_id) as unique_users,
                    CAST(AVG(response_time) AS UNSIGNED) as avg_response_time
                FROM %i
                WHERE event_timestamp >= %s
                AND event_timestamp < %s
                GROUP BY DATE(event_timestamp), blog_id, event_type
            )
            SELECT
                summary_date,
                blog_id,
                SUM(total) as total_requests,
                SUM(success) as successful_requests,
                SUM(failure) as failed_requests,
                MAX(unique_users) as unique_users,
                AVG(avg_response_time) as avg_response_time,
                GROUP_CONCAT(
                    CONCAT(
                        event_type, ":",
                        total, ":",
                        success, ":",
                        failure
                    )
                ) as event_details
            FROM event_stats
            GROUP BY summary_date, blog_id',
            $this->analytics_table,
            $startDate,
            $endDate
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);
        if ($results === null) {
            return new WP_Error('query_failed', $this->wpdb->last_error);
        }

        return array_map(function ($row) {
            $eventsByType = [];
            $eventDetails = explode(',', $row['event_details']);
            foreach ($eventDetails as $detail) {
                [$type, $total, $success, $failure] = explode(':', $detail);
                $eventsByType[$type] = [
                    'total' => (int) $total,
                    'success' => (int) $success,
                    'failure' => (int) $failure,
                ];
            }

            return [
                'summary_date' => $row['summary_date'],
                'blog_id' => $row['blog_id'],
                'metrics' => [
                    'total_requests' => (int) $row['total_requests'],
                    'successful_requests' => (int) $row['successful_requests'],
                    'failed_requests' => (int) $row['failed_requests'],
                    'unique_users' => (int) $row['unique_users'],
                    'avg_response_time' => (int) $row['avg_response_time'],
                ],
                'metadata' => [
                    'events_by_type' => $eventsByType,
                ],
            ];
        }, $results);
    }

    public function getTokenSummary(string $period): array|WP_Error
    {
        try {
            $startDate = $this->getStartDateForPeriod($period);
            $currentTime = gmdate('Y-m-d H:i:s');

            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    'SELECT
                        COUNT(1) as total_tokens,
                        COUNT(DISTINCT user_id) as user_count,
                        SUM(
                            CASE
                                WHEN t.revoked_at IS NULL
                                AND t.expires_at > %s
                                THEN 1
                                ELSE 0
                            END
                        ) as active_tokens,
                        SUM(
                            CASE
                                WHEN t.revoked_at IS NOT NULL
                                THEN 1
                                ELSE 0
                            END
                        ) as revoked_tokens,
                        SUM(
                            CASE
                                WHEN t.expires_at <= %s
                                AND t.revoked_at IS NULL
                                THEN 1
                                ELSE 0
                            END
                        ) as expired_tokens
                    FROM (
                        SELECT user_id, expires_at, revoked_at
                        FROM %i
                        FORCE INDEX (issued_at_idx)
                        WHERE issued_at >= %s
                    ) t',
                    $currentTime,
                    $currentTime,
                    $this->tokens_table,
                    $startDate
                ),
                ARRAY_A
            );

            return $result ?: [
                'total_tokens' => 0,
                'user_count' => 0,
                'active_tokens' => 0,
                'revoked_tokens' => 0,
                'expired_tokens' => 0,
            ];
        } catch (Exception $e) {
            return new WP_Error('analytics_error', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function getStartDateForPeriod(string $period): string
    {
        // Validate period format to prevent SQL injection
        if (!preg_match('/^[0-9]+d$/', $period)) {
            throw new Exception('Invalid period format');
        }

        return match ($period) {
            '7d' => gmdate('Y-m-d', strtotime('-7 days')),
            '30d' => gmdate('Y-m-d', strtotime('-30 days')),
            '90d' => gmdate('Y-m-d', strtotime('-90 days')),
            default => throw new Exception('Invalid period'),
        };
    }

    public function deleteOldEvents(int $days): bool|WP_Error
    {
        // Validate days to prevent SQL injection
        if ($days < 1 || $days > 365) {
            return new WP_Error(
                'invalid_days_range',
                'Days must be between 1 and 365',
                ['status' => 400]
            );
        }

        try {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'DELETE FROM %i
                    WHERE event_timestamp < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)',
                    $this->analytics_table,
                    $days
                )
            );
            return true;
        } catch (Exception $e) {
            return new WP_Error('cleanup_failed', $e->getMessage());
        }
    }

    public function deleteOldSummaries(int $days): bool|WP_Error
    {
        // Validate days to prevent SQL injection
        if ($days < 1 || $days > 365) {
            return new WP_Error(
                'invalid_days_range',
                'Days must be between 1 and 365',
                ['status' => 400]
            );
        }

        try {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'DELETE FROM %i
                    WHERE summary_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)',
                    $this->summary_table,
                    $days
                )
            );
            return true;
        } catch (Exception $e) {
            return new WP_Error('cleanup_failed', $e->getMessage());
        }
    }

    public function getDashboardSummary(): array|WP_Error
    {
        try {
            // First try to get today's data
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    'SELECT
                        s.summary_type,
                        s.metrics,
                        s.metadata
                    FROM %i s
                    WHERE s.summary_date = UTC_DATE()
                    AND s.blog_id = %d
                    ORDER BY s.summary_type ASC',
                    $this->summary_table,
                    get_current_blog_id()
                ),
                ARRAY_A
            );

            // print the raw query

            // If no data for today, get the latest available data
            if (empty($results)) {
                $results = $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        'SELECT
                            s.summary_type,
                            s.metrics,
                            s.metadata
                        FROM %i s
                        WHERE s.blog_id = %d
                        ORDER BY s.summary_date DESC, s.summary_type ASC
                        LIMIT 2',
                        $this->summary_table,
                        get_current_blog_id()
                    ),
                    ARRAY_A
                );
            }

            $summary = [
                'token_stats' => null,
                'analytics' => null,
            ];

            foreach ($results as $result) {
                if ((int) $result['summary_type'] === 1) {
                    $summary['token_stats'] = [
                        'metrics' => json_decode($result['metrics'], true),
                        'metadata' => json_decode($result['metadata'], true),
                    ];
                } else {
                    $summary['analytics'] = [
                        'metrics' => json_decode($result['metrics'], true),
                        'metadata' => json_decode($result['metadata'], true),
                    ];
                }
            }

            // If still no data, try to get data from the analytics table directly
            if ($summary['token_stats'] === null || $summary['analytics'] === null) {
                $tokenStats = $this->getTokenSummary('24h');
                if (!is_wp_error($tokenStats)) {
                    $summary['token_stats'] = [
                        'metrics' => $tokenStats,
                        'metadata' => [
                            'growth_rate' => [
                                'daily' => $tokenStats['total_tokens'],
                                'total' => $tokenStats['total_tokens'],
                                'percentage' => 0,
                            ],
                            'token_families' => $tokenStats['total_tokens'],
                        ],
                    ];
                }
            }

            return $summary;
        } catch (Exception $e) {
            return new WP_Error('analytics_error', $e->getMessage());
        }
    }

    public function getHistoricalData(string $period = '7d'): array|WP_Error
    {
        try {
            $startDate = $this->getStartDateForPeriod($period);
            $endDate = gmdate('Y-m-d');

            // Generate all dates in the range
            $dates = [];
            $currentDate = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);

            while ($currentDate <= $endDateTime) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    'SELECT
                        s.summary_date,
                        s.summary_type,
                        s.metrics,
                        s.metadata
                    FROM %i s
                    WHERE s.summary_date >= %s
                    AND s.summary_date <= %s
                    AND s.blog_id = %d
                    ORDER BY s.summary_date ASC, s.summary_type ASC',
                    $this->summary_table,
                    $startDate,
                    $endDate,
                    get_current_blog_id()
                ),
                ARRAY_A
            );

            $historicalData = [];

            // Initialize all dates with zero values
            foreach ($dates as $date) {
                $historicalData[$date] = [
                    'summary_date' => $date,
                    'token_stats' => [
                        'metrics' => [
                            'total_tokens' => 0,
                            'user_count' => 0,
                            'active_tokens' => 0,
                            'revoked_tokens' => 0,
                            'expired_tokens' => 0,
                        ],
                        'metadata' => [
                            'token_families' => 0,
                            'growth_rate' => [
                                'daily' => 0,
                                'total' => 0,
                                'percentage' => 0,
                            ],
                        ],
                    ],
                    'analytics' => [
                        'metrics' => [
                            'total_requests' => 0,
                            'successful_requests' => 0,
                            'failed_requests' => 0,
                            'unique_users' => 0,
                            'avg_response_time' => 0,
                        ],
                        'metadata' => [
                            'events_by_type' => [
                                'token_generation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                                'token_validation' => ['total' => 0, 'success' => 0, 'failure' => 0],
                                'token_refresh' => ['total' => 0, 'success' => 0, 'failure' => 0],
                            ],
                        ],
                    ],
                ];
            }

            // Fill in actual data where available
            if (!empty($results)) {
                foreach ($results as $result) {
                    $date = $result['summary_date'];
                    $type = $result['summary_type'] === '1' ? 'token_stats' : 'analytics';

                    if (isset($historicalData[$date])) {
                        $historicalData[$date][$type] = [
                            'metrics' => json_decode($result['metrics'], true),
                            'metadata' => json_decode($result['metadata'], true),
                        ];
                    }
                }
            }

            // Convert to indexed array and maintain date order
            return array_values($historicalData);
        } catch (Exception $e) {
            return new WP_Error('analytics_error', $e->getMessage());
        }
    }

    public function getAllTimeStats(): array|WP_Error
    {
        try {
            $currentTime = gmdate('Y-m-d H:i:s');
            $yesterday = gmdate('Y-m-d H:i:s', strtotime('-1 day'));
            $dayBefore = gmdate('Y-m-d H:i:s', strtotime('-2 days'));

            // Get current token statistics
            $token_stats = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    'SELECT
                        COUNT(1) as total_tokens,
                        SUM(CASE WHEN revoked_at IS NULL AND expires_at > %s THEN 1 ELSE 0 END) as active_tokens,
                        SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked_tokens,
                        SUM(CASE WHEN expires_at <= %s AND revoked_at IS NULL THEN 1 ELSE 0 END) as expired_tokens
                    FROM %i',
                    $currentTime,
                    $currentTime,
                    $this->tokens_table
                ),
                ARRAY_A
            );

            // Get tokens created in the last 24 hours
            $today_tokens = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    'SELECT COUNT(1)
                    FROM %i
                    WHERE issued_at > %s AND issued_at <= %s',
                    $this->tokens_table,
                    $yesterday,
                    $currentTime
                )
            );

            // Get tokens created in the previous 24 hours
            $yesterday_tokens = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    'SELECT COUNT(1)
                    FROM %i
                    WHERE issued_at > %s AND issued_at <= %s',
                    $this->tokens_table,
                    $dayBefore,
                    $yesterday
                )
            );

            // Calculate token growth rate
            $today_count = (int) $today_tokens;
            $yesterday_count = (int) $yesterday_tokens;
            $token_growth = $today_count - $yesterday_count;

            // Get current analytics statistics
            $analytics_stats = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    'SELECT
                        COUNT(1) as total_requests,
                        SUM(CASE WHEN event_status = 1 THEN 1 ELSE 0 END) as successful_requests,
                        SUM(CASE WHEN event_status = 2 THEN 1 ELSE 0 END) as failed_requests
                    FROM %i',
                    $this->analytics_table
                ),
                ARRAY_A
            );

            // Get requests in the last 24 hours
            $today_requests = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    'SELECT COUNT(1)
                    FROM %i
                    WHERE event_timestamp > %s AND event_timestamp <= %s',
                    $this->analytics_table,
                    $yesterday,
                    $currentTime
                )
            );

            // Get requests in the previous 24 hours
            $yesterday_requests = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    'SELECT COUNT(1)
                    FROM %i
                    WHERE event_timestamp > %s AND event_timestamp <= %s',
                    $this->analytics_table,
                    $dayBefore,
                    $yesterday
                )
            );

            // Calculate requests growth rate
            $today_req_count = (int) $today_requests;
            $yesterday_req_count = (int) $yesterday_requests;
            $requests_growth = $yesterday_req_count > 0
                ? round((($today_req_count - $yesterday_req_count) / $yesterday_req_count) * 100, 1)
                : ($today_req_count > 0 ? 100 : 0);

            return array_merge(
                $token_stats ?? [
                    'total_tokens' => 0,
                    'active_tokens' => 0,
                    'revoked_tokens' => 0,
                    'expired_tokens' => 0,
                ],
                ['token_growth_rate' => $token_growth],
                $analytics_stats ?? [
                    'total_requests' => 0,
                    'successful_requests' => 0,
                    'failed_requests' => 0,
                ],
                ['requests_growth_rate' => $requests_growth],
                ['total_tokens_today' => $today_tokens],
                ['total_tokens_yesterday' => $yesterday_tokens],
                ['total_requests_today' => $today_requests],
                ['total_requests_yesterday' => $yesterday_requests],
            );
        } catch (Exception $e) {
            return new WP_Error('analytics_error', $e->getMessage());
        }
    }

    /**
     * Delete analytics records older than the given date
     */
    public function deleteOlderThan(string $date): int
    {
        global $wpdb;
        $table = $this->getTableName('jwt_auth_analytics');

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM %i WHERE event_timestamp < %s",
                $table,
                $date
            )
        );
    }
}
