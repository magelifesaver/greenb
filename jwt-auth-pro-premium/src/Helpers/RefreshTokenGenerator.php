<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Helpers;

class RefreshTokenGenerator
{
    private const TOKEN_LENGTH = 64;

    public static function generate(): string
    {
        return apply_filters('jwt_auth_refresh_token_generation', wp_generate_password(self::TOKEN_LENGTH, false));
    }
}
