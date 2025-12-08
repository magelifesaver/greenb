<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Traits;

use Tmeister\JWTAuthPro\Helpers\JWT;

trait HasAlgorithm
{
    private function getAlgorithm(): string|false
    {
        // Get the algorithm from the settings
        $algorithm = $this->settingsService->getSetting('token_settings', 'signing_algorithm');

        // Honor the developer's choice of algorithm
        $algorithm = apply_filters('jwt_auth_algorithm', $algorithm);

        // Return the algorithm if it's supported or not
        return JWT::isSupportedAlgorithm($algorithm) ? $algorithm : false;
    }
}
