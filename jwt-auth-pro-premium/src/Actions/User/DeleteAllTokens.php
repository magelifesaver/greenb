<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\User;

use Exception;
use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class DeleteAllTokens
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(int $userId): WP_Error|bool
    {
        $shouldDelete = apply_filters("jwt_auth_pro_delete_tokens", true, $userId);

        if (!$shouldDelete) {
            return true;
        }

        try {
            $this->tokenService->deleteAllUserTokens($userId);
            return true;
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_pro_delete_tokens_failed',
                'Failed to delete user tokens.',
                ['status' => 500]
            );
        }
    }
}
