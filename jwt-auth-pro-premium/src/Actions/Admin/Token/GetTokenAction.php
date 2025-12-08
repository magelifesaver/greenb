<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\Token;

use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class GetTokenAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(int $tokenId): array|WP_Error
    {
        $token = $this->tokenService->getToken($tokenId);

        if ($token === null) {
            return new WP_Error(
                'token_not_found',
                'Token not found',
                ['status' => 404]
            );
        }

        $tokenArray = $token->jsonSerialize();
        $tokenArray = apply_filters('jwt_auth_get_token', $tokenArray, $tokenId);

        return [
            'success' => true,
            'data' => $tokenArray,
            'message' => 'Token retrieved successfully',
        ];
    }
}
