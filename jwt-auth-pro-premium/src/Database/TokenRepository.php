<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Database;

use DateTime;
use DateTimeZone;
use Exception;
use Tmeister\JWTAuthPro\DTO\TokenDTO;
use Tmeister\JWTAuthPro\Traits\HasTableName;
use WP_Error;
use wpdb;

class TokenRepository
{
    use HasTableName;

    private string $table;
    private string $analyticsTable;
    private wpdb $wpdb;
    private array $allowedColumns = [
        'hash',
        'user_id',
        'issued_at',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'refresh_token_hash',
        'refresh_token_expires_at',
        'token_family',
        'metadata',
        'user_agent',
        'ip_address',
        'blog_id',
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->getTableName(JWT_AUTH_PRO_TOKENS_TABLE);
        $this->analyticsTable = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_TABLE);
    }

    public function findByHash(string $hash): ?TokenDTO
    {
        $token = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE hash = %s',
                $this->table,
                $hash
            )
        );

        return $token ? TokenDTO::fromObject($token) : null;
    }

    public function findByUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE user_id = %d ORDER BY issued_at DESC LIMIT %d OFFSET %d',
                $this->table,
                $userId,
                $perPage,
                $offset
            )
        );

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE user_id = %d',
                $this->table,
                $userId
            )
        );

        return [
            'items' => array_map(fn($item) => TokenDTO::fromObject($item), $items),
            'total' => $total,
        ];
    }

    public function create(array $data): int|WP_Error
    {
        $sanitizedData = array_intersect_key($data, array_flip($this->allowedColumns));

        if (empty($sanitizedData)) {
            return new WP_Error(
                'token_creation_failed',
                'No valid columns provided for token creation'
            );
        }

        $inserted = $this->wpdb->insert($this->table, $sanitizedData);

        if (!$inserted) {
            return new WP_Error(
                'token_creation_failed',
                'Failed to create token'
            );
        }

        return $this->wpdb->insert_id;
    }

    public function update(int $id, array $data): bool|WP_Error
    {
        $sanitizedData = array_intersect_key($data, array_flip($this->allowedColumns));

        if (empty($sanitizedData)) {
            return new WP_Error(
                'token_update_failed',
                'No valid columns provided for update'
            );
        }

        $formats = [];
        foreach ($sanitizedData as $value) {
            $formats[] = is_int($value) ? '%d' : '%s';
        }

        $updated = $this->wpdb->update(
            $this->table,
            $sanitizedData,
            ['id' => $id],
            $formats,
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error(
                'token_update_failed',
                'Failed to update token'
            );
        }

        return true;
    }

    public function revoke(int $id): bool|WP_Error
    {
        return $this->update($id, ['revoked_at' => gmdate('Y-m-d H:i:s')]);
    }

    public function delete(int $id): bool|WP_Error
    {
        // Start transaction
        $this->wpdb->query('START TRANSACTION');

        try {
            // Get token family if exists
            $token = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    'SELECT token_family FROM %i WHERE id = %d',
                    $this->table,
                    $id
                )
            );

            if (!$token) {
                throw new Exception('Token not found');
            }

            // Delete analytics data
            $analytics_table = $this->getTableName(JWT_AUTH_PRO_ANALYTICS_TABLE);
            $this->wpdb->delete(
                $analytics_table,
                ['token_id' => $id],
                ['%d']
            );

            // If token has a family, delete all related tokens and their analytics data
            if ($token->token_family) {
                // Get all related token IDs
                $related_token_ids = $this->wpdb->get_col(
                    $this->wpdb->prepare(
                        'SELECT id FROM %i WHERE token_family = %s AND id != %d',
                        $this->table,
                        $token->token_family,
                        $id
                    )
                );

                // Delete analytics data for related tokens
                if (!empty($related_token_ids)) {
                    $placeholders = implode(',', array_fill(0, count($related_token_ids), '%d'));
                    $this->wpdb->query(
                        $this->wpdb->prepare(
                            "DELETE FROM {$analytics_table} WHERE token_id IN ($placeholders)",
                            ...$related_token_ids
                        )
                    );

                    // Delete related tokens
                    $this->wpdb->query(
                        $this->wpdb->prepare(
                            'DELETE FROM %i WHERE id IN (' . $placeholders . ')',
                            $this->table,
                            ...$related_token_ids
                        )
                    );
                }
            }

            // Delete the main token
            $deleted = $this->wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );

            if ($deleted === false) {
                throw new Exception('Failed to delete token');
            }

            // Commit transaction
            $this->wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $this->wpdb->query('ROLLBACK');
            return new WP_Error(
                'token_deletion_failed',
                $e->getMessage()
            );
        }
    }

    public function getActiveTokens(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE revoked_at IS NULL AND expires_at > UTC_TIMESTAMP()
                ORDER BY last_used_at DESC LIMIT %d OFFSET %d',
                $this->table,
                $perPage,
                $offset
            )
        );

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE revoked_at IS NULL AND expires_at > UTC_TIMESTAMP()',
                $this->table
            )
        );

        return [
            'items' => array_map(fn($item) => TokenDTO::fromObject($item), $items),
            'total' => $total,
        ];
    }

    public function getRevokedTokens(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE revoked_at IS NOT NULL
                ORDER BY issued_at DESC LIMIT %d OFFSET %d',
                $this->table,
                $perPage,
                $offset
            )
        );

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE revoked_at IS NOT NULL',
                $this->table
            )
        );

        return [
            'items' => array_map(fn($item) => TokenDTO::fromObject($item), $items),
            'total' => $total,
        ];
    }

    public function getExpiredTokens(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE revoked_at IS NULL AND expires_at <= UTC_TIMESTAMP()
                ORDER BY expires_at DESC LIMIT %d OFFSET %d',
                $this->table,
                $perPage,
                $offset
            )
        );

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE revoked_at IS NULL AND expires_at <= UTC_TIMESTAMP()',
                $this->table
            )
        );

        return [
            'items' => array_map(fn($item) => TokenDTO::fromObject($item), $items),
            'total' => $total,
        ];
    }

    public function findAll(array $filters = []): array
    {
        $where = [];
        $prepare = [$this->table]; // Start with table name

        if (isset($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $prepare[] = $filters['user_id'];
        }

        if (!empty($filters['username'])) {
            $where[] = 'user_id IN (SELECT ID FROM %i WHERE user_login LIKE %s OR display_name LIKE %s)';
            array_push(
                $prepare,
                $this->wpdb->users,
                '%' . $this->wpdb->esc_like($filters['username']) . '%',
                '%' . $this->wpdb->esc_like($filters['username']) . '%'
            );
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $where[] = '(revoked_at IS NULL AND expires_at > UTC_TIMESTAMP())';
                    break;
                case 'expired':
                    $where[] = '(revoked_at IS NULL AND expires_at <= UTC_TIMESTAMP())';
                    break;
                case 'revoked':
                    $where[] = 'revoked_at IS NOT NULL';
                    break;
            }
        }

        if (isset($filters['created_at'])) {
            $date = new DateTime($filters['created_at'], new DateTimeZone('UTC'));
            $start = $date->format('Y-m-d 00:00:00');
            $end = $date->format('Y-m-d 23:59:59');
            $where[] = 'issued_at >= TIMESTAMP(%s) AND issued_at <= TIMESTAMP(%s)';
            array_push($prepare, $start, $end);
        }

        if (isset($filters['expires_at'])) {
            $date = new DateTime($filters['expires_at'], new DateTimeZone('UTC'));
            $start = $date->format('Y-m-d 00:00:00');
            $end = $date->format('Y-m-d 23:59:59');
            $where[] = 'expires_at >= TIMESTAMP(%s) AND expires_at <= TIMESTAMP(%s)';
            array_push($prepare, $start, $end);
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count for pagination
        $countQuery = 'SELECT COUNT(*) FROM %i ' . $whereClause;
        $totalItems = (int) $this->wpdb->get_var($this->wpdb->prepare($countQuery, $prepare));

        // Handle pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 10;
        $offset = ($page - 1) * $perPage;

        // Add pagination parameters
        $query = 'SELECT * FROM %i ' . $whereClause . ' ORDER BY issued_at DESC LIMIT %d OFFSET %d';
        array_push($prepare, $perPage, $offset);

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $prepare),
            ARRAY_A
        );

        return [
            'items' => $items,
            'pagination' => [
                'total' => $totalItems,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($totalItems / $perPage),
            ],
        ];
    }

    public function findById(int $id): ?TokenDTO
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE id = %d',
                $this->table,
                $id
            ),
            ARRAY_A
        );

        return $result ? TokenDTO::fromObject($result) : null;
    }

    public function findByRefreshToken(string $refreshTokenHash): ?TokenDTO
    {
        $token = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT * FROM %i
                WHERE refresh_token_hash = %s
                AND refresh_token_expires_at > UTC_TIMESTAMP()
                AND revoked_at IS NULL',
                $this->table,
                $refreshTokenHash
            ),
            ARRAY_A
        );

        return $token ? TokenDTO::fromObject($token) : null;
    }

    public function findByTokenFamily(string $tokenFamily): array
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT * FROM %i WHERE token_family = %s AND revoked_at IS NULL',
                $this->table,
                $tokenFamily
            ),
            ARRAY_A
        ) ?: [];

        return array_map(fn($item) => TokenDTO::fromObject($item), $results);
    }

    public function updateLastUsedAt(int $tokenId): bool
    {
        $updated = $this->wpdb->update(
            $this->table,
            ['last_used_at' => gmdate('Y-m-d H:i:s')],
            ['id' => $tokenId],
            ['%s'],
            ['%d']
        );

        return $updated !== false;
    }

    public function getTokenDetails(int $id): array
    {
        $token = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT t.*, u.user_nicename, u.user_url as user_link
                FROM %i t
                LEFT JOIN %i u ON t.user_id = u.ID
                WHERE t.id = %d',
                $this->table,
                $this->wpdb->users,
                $id
            )
        );

        if (!$token) {
            return [];
        }

        return [
            'token' => $token,
            'has_related_tokens' => (bool) $token->token_family,
        ];
    }

    public function getRelatedTokens(int $id, int $page = 1, int $perPage = 10): array
    {
        $token = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT token_family FROM %i WHERE id = %d',
                $this->table,
                $id
            )
        );

        if (!$token || !$token->token_family) {
            return [
                'items' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => 0,
                ],
            ];
        }

        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT t.*, u.user_nicename, u.user_url as user_link
                FROM %i t
                LEFT JOIN %i u ON t.user_id = u.ID
                WHERE t.token_family = %s AND t.id != %d
                ORDER BY t.issued_at DESC
                LIMIT %d OFFSET %d',
                $this->table,
                $this->wpdb->users,
                $token->token_family,
                $id,
                $perPage,
                $offset
            )
        );

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*)
                FROM %i
                WHERE token_family = %s AND id != %d',
                $this->table,
                $token->token_family,
                $id
            )
        );

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }

    public function getTokenUsageHistory(int $tokenId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT a.*
                FROM %i a
                WHERE a.token_id = %d
                ORDER BY a.event_timestamp DESC
                LIMIT %d OFFSET %d',
                $this->analyticsTable,
                $tokenId,
                $perPage,
                $offset
            ),
            ARRAY_A
        ) ?: [];

        $total = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*)
                FROM %i
                WHERE token_id = %d',
                $this->analyticsTable,
                $tokenId
            )
        );

        return [
            'items' => array_map(function ($event) {
                return [
                    'id' => (int) $event['id'],
                    'event_type' => $event['event_type'],
                    'event_status' => (int) $event['event_status'],
                    'failure_reason' => $event['failure_reason'],
                    'user_id' => $event['user_id'] ? (int) $event['user_id'] : null,
                    'token_id' => $event['token_id'] ? (int) $event['token_id'] : null,
                    'token_family' => $event['token_family'],
                    'ip_address' => $event['ip_address'],
                    'user_agent' => $event['user_agent'],
                    'country_code' => $event['country_code'],
                    'request_path' => $event['request_path'],
                    'request_method' => $event['request_method'],
                    'response_time' => $event['response_time'] ? (int) $event['response_time'] : null,
                    'blog_id' => (int) $event['blog_id'],
                    'timestamp' => $event['event_timestamp'],
                ];
            }, $items),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }
}
