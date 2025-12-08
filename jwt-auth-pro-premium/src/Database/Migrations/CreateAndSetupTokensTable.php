<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Database\Migrations;

use Tmeister\JWTAuthPro\Database\Migration;
use Tmeister\JWTAuthPro\Traits\HasTableName;

class CreateAndSetupTokensTable extends Migration
{
    use HasTableName;

    public function up(): void
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        // Create tokens table
        $tokens_table = $this->getTableName(JWT_AUTH_PRO_TOKENS_TABLE);
        $this->createTokensTable($tokens_table);

        // Create analytics table
        $analytics_table = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_TABLE);
        $this->createAnalyticsTable($analytics_table);

        // Create analytics summary table
        $summary_table = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_SUMMARY_TABLE);
        $this->createAnalyticsSummaryTable($summary_table);

        update_option('jwt_auth_db_version', '0.1.0');
    }

    private function createTokensTable(string $table_name): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hash VARCHAR(255) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            issued_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            revoked_at DATETIME NULL,
            refresh_token_hash VARCHAR(255) NULL,
            refresh_token_expires_at DATETIME NULL,
            token_family VARCHAR(255) NULL,
            metadata JSON NULL,
            user_agent VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY hash_idx (hash),
            KEY expires_at_idx (expires_at),
            KEY blog_id_idx (blog_id),
            KEY revoked_at_idx (revoked_at),
            KEY token_family_idx (token_family),
            KEY issued_at_idx (issued_at),
            KEY token_status_idx (revoked_at, expires_at),
            KEY token_user_status_idx (user_id, revoked_at, expires_at)
        ) {$this->wpdb->get_charset_collate()};";

        dbDelta($sql);
    }

    private function createAnalyticsTable(string $table_name): void
    {
        $users_table = $this->wpdb->users;
        $tokens_table = $this->getTableName('jwt_auth_tokens');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            event_status TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=success, 2=failure',
            failure_reason TEXT NULL,
            user_id BIGINT UNSIGNED NULL,
            token_id BIGINT UNSIGNED NULL,
            token_family VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            country_code CHAR(2) NULL,
            request_path VARCHAR(255) NULL,
            request_method VARCHAR(10) NULL,
            response_time INT UNSIGNED NULL,
            blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            event_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_timestamp_idx (event_timestamp),
            KEY event_type_idx (event_type),
            KEY event_status_idx (event_status),
            KEY event_type_status_idx (event_type, event_status),
            KEY analytics_lookup_idx (event_timestamp, blog_id, event_type),
            KEY analytics_status_idx (event_timestamp, blog_id, event_status),
            KEY analytics_country_idx (event_timestamp, blog_id, country_code),
            KEY analytics_user_idx (event_timestamp, blog_id, user_id),
            KEY user_lookup_idx (user_id, event_timestamp),
            KEY token_lookup_idx (token_id, event_timestamp),
            KEY blog_event_idx (blog_id, event_type, event_timestamp),
            KEY ip_timestamp_idx (ip_address, event_timestamp),
            CONSTRAINT {$table_name}_user FOREIGN KEY (user_id) REFERENCES {$users_table} (ID) ON DELETE SET NULL,
            CONSTRAINT {$table_name}_token FOREIGN KEY (token_id) REFERENCES {$tokens_table} (id) ON DELETE SET NULL
        ) {$this->wpdb->get_charset_collate()};";

        dbDelta($sql);
    }

    private function createAnalyticsSummaryTable(string $table_name): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            summary_date DATE NOT NULL,
            summary_type TINYINT UNSIGNED NOT NULL COMMENT '1=token_stats, 2=analytics',
            blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            metrics JSON NOT NULL COMMENT 'For token_stats: total_tokens, user_count, active_tokens, revoked_tokens, expired_tokens. For analytics: successful_auths, failed_auths, avg_response_time, etc.',
            metadata JSON NULL COMMENT 'Additional metadata like top_paths, user_agents, ip_addresses, etc.',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_daily_summary_idx (summary_date, summary_type, blog_id),
            KEY summary_lookup_idx (summary_type, summary_date),
            KEY blog_summary_idx (blog_id, summary_type, summary_date)
        ) {$this->wpdb->get_charset_collate()};";

        dbDelta($sql);
    }

    public function down(): void
    {
        // Drop tables in reverse order to respect foreign key constraints
        $tables = [
            JWT_AUTH_PRO_ANALYTICS_SUMMARY_TABLE,
            JWT_AUTH_PRO_ANALYTICS_TABLE,
            JWT_AUTH_PRO_TOKENS_TABLE,
        ];

        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $sql = "DROP TABLE IF EXISTS {$table_name}";
            $this->wpdb->query($sql);
        }

        delete_option('jwt_auth_db_version');
    }
}
