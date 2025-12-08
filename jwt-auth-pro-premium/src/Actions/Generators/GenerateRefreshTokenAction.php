<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Generators;

use Tmeister\JWTAuthPro\Helpers\RefreshTokenGenerator;
use Tmeister\JWTAuthPro\Services\SettingsService;
use WP_Error;

class GenerateRefreshTokenAction
{
    private SettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    public function execute(int $userId): array|WP_Error
    {
        $refreshToken     = RefreshTokenGenerator::generate();
        $refreshTokenHash = wp_hash($refreshToken);

        $expirationTime   = $this->settingsService->getSetting('token_settings', 'refresh_token_expiration');
        $expirationTime   = apply_filters('jwt_auth_refresh_token_expiration', $expirationTime);

        $refreshToken     = [
            'refresh_token_hash'       => $refreshTokenHash,
            'refresh_token_expires_at' => date('Y-m-d H:i:s', time() + $expirationTime),
        ];

        return apply_filters('jwt_auth_refresh_token_data_before_update', $refreshToken, $userId);
    }
}
