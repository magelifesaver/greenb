<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\Token;

use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class RevokeTokenAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(int $tokenId): array|WP_Error
    {
        $token = $this->tokenService->getToken($tokenId);

        if (!$token) {
            return new WP_Error(
                'token_not_found',
                'Token not found',
                ['status' => 404]
            );
        }

        $result = $this->tokenService->revokeToken($tokenId);

        if (!$result) {
            return new WP_Error(
                'revoke_failed',
                'Failed to revoke token',
                ['status' => 500]
            );
        }

        return [
            'id' => $tokenId,
            'revoked' => true,
        ];
    }
}
