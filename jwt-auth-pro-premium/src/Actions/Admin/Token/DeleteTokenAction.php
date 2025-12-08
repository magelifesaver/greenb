<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\Token;

use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class DeleteTokenAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(int $tokenId): bool|WP_Error
    {
        $token = $this->tokenService->getToken($tokenId);

        if ($token === null) {
            return new WP_Error(
                'token_not_found',
                'Token not found',
                ['status' => 404]
            );
        }

        $result = $this->tokenService->delete($tokenId);

        if (!$result) {
            return new WP_Error(
                'delete_failed',
                'Failed to delete token',
                ['status' => 500]
            );
        }

        return true;
    }
}
