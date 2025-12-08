<?php

namespace Tmeister\JWTAuthPro\Actions\Generators;

use Exception;
use Tmeister\JWTAuthPro\Helpers\JWT;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Traits\HasAlgorithm;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;
use WP_User;

class GenerateTokenAction
{
    use HasAlgorithm;
    use HasRequestIP;

    private SettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    public function execute(string $username, string $password, ?WP_User $user = null): array|WP_Error
    {
        try {
            if (empty($this->getSecretPrivateKey())) {
                return $this->createError('jwt_auth_bad_config');
            }

            if (!$user) {
                $user = wp_authenticate($username, $password);
            }

            $user = apply_filters('jwt_auth_before_authenticate', $user);

            if (is_wp_error($user)) {
                return $this->createError('jwt_auth_failed', $user->get_error_message());
            }

            // Generate token
            $token        = $this->createTokenPayload($user);
            $encodedToken = $this->encodeToken($token);

            if (is_wp_error($encodedToken)) {
                return $encodedToken;
            }

            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

            $rawTokenData = apply_filters('jwt_auth_jwt_token_data_before_update', [
                'hash'         => wp_hash($encodedToken),
                'user_id'      => $user->ID,
                'issued_at'    => date('Y-m-d H:i:s', $token['iat']),
                'expires_at'   => date('Y-m-d H:i:s', $token['exp']),
                'metadata'     => json_encode(['user_agent' => $userAgent]),
                'user_agent'   => $userAgent,
                'ip_address'   => $this->getRequestIP() ?? null,
                'token_family' => bin2hex(random_bytes(16)),
            ]);

            return [
                'token'        => $encodedToken,
                'user'         => $user,
                'rawTokenData' => $rawTokenData,
            ];
        } catch (Exception $e) {
            return $this->createError('jwt_auth_failed', $e->getMessage());
        }
    }

    private function createTokenPayload($user): array
    {
        $issuedAt = apply_filters('jwt_auth_issued_at', time());
        $expiration = $this->settingsService->getSetting('token_settings', 'jwt_expiration');

        $token    = [
            'iss'  => apply_filters('jwt_auth_issuer', get_bloginfo('url')),
            'iat'  => $issuedAt,
            'nbf'  => apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt),
            'exp'  => apply_filters('jwt_auth_expire', $issuedAt + $expiration, $issuedAt),
            'data' => [
                'user' => apply_filters('jwt_auth_token_user_data', [
                    'id' => $user->data->ID,
                ], $user),
            ],
        ];

        return apply_filters('jwt_auth_token_before_sign', $token, $user);
    }

    private function encodeToken(array $token): string|WP_Error
    {
        try {
            $algorithm = $this->getAlgorithm();
            if (!$algorithm) {
                return $this->createError('jwt_auth_unsupported_algorithm');
            }

            $secretPrivateKey = $this->getSecretPrivateKey();
            return JWT::encode($token, $secretPrivateKey, $algorithm);
        } catch (Exception $e) {
            return $this->createError('jwt_auth_encode_failed', $e->getMessage());
        }
    }

    private function getSecretPrivateKey(): ?string
    {
        return apply_filters('jwt_auth_secret_private_key', defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : null);
    }

    private function createError(string $code, string $message = ''): WP_Error
    {
        $messages = apply_filters('jwt_auth_error_messages', [
            'jwt_auth_bad_config'            => 'JWT is not configured properly, please contact the admin',
            'jwt_auth_unsupported_algorithm' => 'Algorithm not supported',
            'jwt_auth_failed'                => $message ?: 'Invalid credentials.',
            'jwt_auth_encode_failed'         => $message ?: 'Error encoding token.',
        ]);

        return new WP_Error($code, $messages[$code], ['status' => 403]);
    }
}
