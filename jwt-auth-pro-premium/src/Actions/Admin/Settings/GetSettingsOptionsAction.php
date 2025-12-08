<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\Settings;

use Tmeister\JWTAuthPro\Helpers\JWT;
use WP_REST_Response;

class GetSettingsOptionsAction
{
    public function execute(): WP_REST_Response
    {
        $analyticsRetentionOptions = apply_filters('jwt_auth_analytics_retention_options', [
            ['value' => 30, 'label' => '30 days'],
            ['value' => 90, 'label' => '90 days'],
            ['value' => 180, 'label' => '180 days'],
            ['value' => 360, 'label' => '360 days'],
            ['value' => -1, 'label' => 'Forever'],
        ]);

        $jwtExpirationOptions = apply_filters('jwt_auth_jwt_expiration_options', [
            ['value' => 7, 'label' => '7 days'],
            ['value' => 14, 'label' => '14 days'],
            ['value' => 30, 'label' => '30 days'],
            ['value' => 90, 'label' => '90 days'],
            ['value' => 180, 'label' => '180 days'],
        ]);

        $refreshTokenExpirationOptions = apply_filters('jwt_auth_refresh_token_expiration_options', [
            ['value' => 7, 'label' => '7 days'],
            ['value' => 14, 'label' => '14 days'],
            ['value' => 30, 'label' => '30 days'],
            ['value' => 90, 'label' => '90 days'],
            ['value' => 180, 'label' => '180 days'],
        ]);

        $signingAlgorithmOptions = array_map(
            fn(string $algo) => ['value' => $algo, 'label' => $algo],
            apply_filters('jwt_auth_signing_algorithm_options', JWT::getSupportedAlgorithms())
        );

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'analytics_retention' => $analyticsRetentionOptions,
                'jwt_expiration' => $jwtExpirationOptions,
                'refresh_token_expiration' => $refreshTokenExpirationOptions,
                'signing_algorithm' => $signingAlgorithmOptions,
            ],
        ], 200);
    }
}
