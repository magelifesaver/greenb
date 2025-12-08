<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\User;

use Exception;
use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class RevokeAllTokens
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(int $userId, string $reason): WP_Error|bool
    {
        $shouldRevoke = apply_filters("jwt_auth_pro_revoke_tokens_on_{$reason}", true, $userId);

        if (!$shouldRevoke) {
            return true;
        }

        try {
            $this->tokenService->revokeAllUserTokens($userId);
            return true;
        } catch (Exception $e) {
            return new WP_Error(
                'jwt_auth_pro_revoke_tokens_failed',
                sprintf('Failed to revoke user tokens after %s.', $reason),
                ['status' => 500]
            );
        }
    }
}
