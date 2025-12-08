<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Exception;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\DTO\TokenDTO;
use WP_Error;
use wpdb;

class TokenService
{
    private const CACHE_GROUP = 'jwt_auth_tokens';
    private wpdb $wpdb;

    public function __construct(
        private readonly TokenRepository $repository,
        private readonly SettingsService $settingsService,
    ) {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function listTokens(array $filters = []): array|WP_Error
    {
        try {
            $validatedFilters = $this->validateFilters($filters);
            if (is_wp_error($validatedFilters)) {
                return $validatedFilters;
            }

            $validatedFilters['page'] ??= 1;
            $validatedFilters['per_page'] ??= 10;

            $result = $this->repository->findAll($validatedFilters);

            // Convert array items to TokenDTO objects
            $result['items'] = array_map(fn($item) => TokenDTO::fromObject($item), $result['items']);

            return apply_filters('jwt_auth_token_list', $result, $filters);
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_token_list_error',
                'Failed to retrieve tokens.',
                ['status' => 500]
            );
        }
    }

    public function getToken(int $id): ?TokenDTO
    {
        return $this->repository->findById($id);
    }

    public function revokeToken(int $id): bool|WP_Error
    {
        try {
            $token = $this->getToken($id);
            if ($token === null) {
                return new WP_Error(
                    'jwt_auth_token_not_found',
                    'Token not found.',
                    ['status' => 404]
                );
            }

            $result = $this->repository->update($id, [
                'revoked_at' => current_time('mysql', true),
            ]);

            if (!$result) {
                return new WP_Error(
                    'jwt_auth_revoke_failed',
                    'Failed to revoke token.',
                    ['status' => 500]
                );
            }

            wp_cache_delete("token_{$id}", self::CACHE_GROUP);
            wp_cache_delete('token_metrics', self::CACHE_GROUP);
            do_action('jwt_auth_token_revoked', $id, $token);

            return true;
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_token_revocation_error',
                'Failed to revoke token.',
                ['status' => 500]
            );
        }
    }

    public function bulkRevoke(array $ids): array|WP_Error
    {
        try {
            $results = [
                'success' => [],
                'failed'  => [],
            ];

            foreach ($ids as $id) {
                $result = $this->revokeToken($id);
                if (is_wp_error($result)) {
                    $results['failed'][] = $id;
                } else {
                    $results['success'][] = $id;
                }
            }

            if (!empty($results['success'])) {
                wp_cache_delete('token_metrics', self::CACHE_GROUP);
            }

            return $results;
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_bulk_revocation_error',
                'Failed to perform bulk token revocation.',
                ['status' => 500]
            );
        }
    }

    private function validateFilters(array $filters): array|WP_Error
    {
        $allowedFilters   = ['user_id', 'status', 'created_at', 'expires_at', 'page', 'per_page', 'username'];
        $sanitizedFilters = [];

        foreach ($filters as $key => $value) {
            if (!in_array($key, $allowedFilters, true)) {
                continue;
            }

            switch ($key) {
                case 'per_page':
                    $value = absint($value);
                    if ($value < 1 || $value > 100) {
                        $value = 10; // Default to 10 if out of range
                    }
                    $sanitizedFilters[$key] = $value;
                    break;
                case 'page':
                    $value = absint($value);
                    if ($value < 1) {
                        $value = 1;
                    }
                    $sanitizedFilters[$key] = $value;
                    break;
                case 'user_id':
                    $sanitizedFilters[$key] = absint($value);
                    break;
                case 'username':
                    if (empty($value)) {
                        break;
                    }
                    $sanitizedFilters[$key] = sanitize_text_field($value);
                    break;
                case 'status':
                    if (!in_array($value, ['active', 'expired', 'revoked'], true)) {
                        return new WP_Error(
                            'jwt_auth_invalid_status',
                            'Invalid status filter.',
                            ['status' => 400]
                        );
                    }
                    $sanitizedFilters[$key] = sanitize_text_field($value);
                    break;
                case 'created_at':
                case 'expires_at':
                    if (!strtotime($value)) {
                        return new WP_Error(
                            'jwt_auth_invalid_date',
                            'Invalid date format.',
                            ['status' => 400]
                        );
                    }
                    $sanitizedFilters[$key] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitizedFilters;
    }

    public function bulkDelete(array $tokenIds): bool|WP_Error
    {
        try {
            foreach ($tokenIds as $tokenId) {
                $result = $this->delete((int) $tokenId);
                if (is_wp_error($result)) {
                    return $result;
                }
            }

            return true;
        } catch (Exception $e) {
            return new WP_Error(
                'bulk_delete_failed',
                'Failed to delete tokens',
                ['status' => 500]
            );
        }
    }

    public function delete(int $id): bool|WP_Error
    {
        try {
            $token = $this->getToken($id);
            if ($token === null) {
                return new WP_Error(
                    'jwt_auth_token_not_found',
                    'Token not found.',
                    ['status' => 404]
                );
            }

            $result = $this->repository->delete($id);
            if ($result) {
                wp_cache_delete("token_{$id}", self::CACHE_GROUP);
                wp_cache_delete('token_metrics', self::CACHE_GROUP);
                do_action('jwt_auth_token_deleted', $id, $token);
            }

            return $result;
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_token_deletion_error',
                'Failed to delete token.',
                ['status' => 500]
            );
        }
    }

    public function getTokenDetails(int $tokenId): array
    {
        $details = $this->repository->getTokenDetails($tokenId);

        // Convert to array if it's an object
        if (is_object($details)) {
            $details = (array) $details;
        }

        // Convert main token to TokenDTO
        if (isset($details['token'])) {
            $details['token'] = TokenDTO::fromObject($details['token']);
        }

        return $details;
    }

    public function getRelatedTokens(int $tokenId, int $page = 1, int $perPage = 10): array
    {
        $result = $this->repository->getRelatedTokens($tokenId, $page, $perPage);

        if (isset($result['items'])) {
            $result['items'] = array_map(
                fn($token) => TokenDTO::fromObject($token),
                (array) $result['items']
            );
        }

        return apply_filters('jwt_auth_related_tokens', $result, $tokenId);
    }

    public function getTokenUsageHistory(int $tokenId, int $page = 1, int $perPage = 10): array
    {
        $result = $this->repository->getTokenUsageHistory($tokenId, $page, $perPage);

        if (isset($result['items'])) {
            $result['items'] = array_map(function ($usage) {
                return [
                    'id' => (int) $usage['id'],
                    'event_type' => $usage['event_type'],
                    'event_status' => (int) $usage['event_status'],
                    'failure_reason' => $usage['failure_reason'],
                    'user_id' => $usage['user_id'] ? (int) $usage['user_id'] : null,
                    'token_id' => $usage['token_id'] ? (int) $usage['token_id'] : null,
                    'token_family' => $usage['token_family'],
                    'ip_address' => $usage['ip_address'],
                    'user_agent' => $usage['user_agent'],
                    'country_code' => $usage['country_code'],
                    'request_path' => $usage['request_path'],
                    'request_method' => $usage['request_method'],
                    'response_time' => $usage['response_time'] ? (int) $usage['response_time'] : null,
                    'blog_id' => (int) $usage['blog_id'],
                    'timestamp' => wp_date(
                        get_option('date_format') . ' ' . get_option('time_format'),
                        strtotime($usage['timestamp'])
                    ),
                ];
            }, $result['items']);
        }

        return apply_filters('jwt_auth_token_usage_history', $result, $tokenId);
    }

    /**
     * @throws Exception
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        $table = $this->wpdb->prefix . 'jwt_auth_tokens';

        $result = $this->wpdb->update(
            $table,
            [
                'revoked_at' => current_time('mysql', true),
            ],
            ['user_id' => $userId],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            throw new Exception($this->wpdb->last_error);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function deleteAllUserTokens(int $userId): bool
    {
        $table = $this->wpdb->prefix . 'jwt_auth_tokens';

        $result = $this->wpdb->delete(
            $table,
            ['user_id' => $userId],
            ['%d']
        );

        if ($result === false) {
            throw new Exception($this->wpdb->last_error);
        }

        return true;
    }
}
