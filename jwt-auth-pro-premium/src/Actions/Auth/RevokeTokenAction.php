<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Auth;

use Tmeister\JWTAuthPro\Actions\Analytics\TrackTokenEvent;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\Enums\EventStatus;
use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class RevokeTokenAction
{
    public function __construct(
        private readonly TokenRepository $tokenRepository,
        private readonly TokenService $tokenService,
    ) {}

    public function execute(string $token): array|WP_Error
    {
        $startTime = microtime(true);

        // Validate token first
        $validation = (new ValidateTokenAction())->execute($token);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Find token by hash
        $tokenHash = wp_hash($token);
        $tokenRecord = $this->tokenRepository->findByHash($tokenHash);

        if (!$tokenRecord) {
            return new WP_Error(
                'jwt_auth_token_not_found',
                'Token not found',
                ['status' => 404]
            );
        }

        // Check if already revoked
        if (!is_null($tokenRecord->revoked_at)) {
            (new TrackTokenEvent())->execute([
                'event_type' => 'token_revocation',
                'event_status' => EventStatus::FAILURE,
                'failure_reason' => 'already_revoked',
                'user_id' => $tokenRecord->user_id,
                'token_id' => $tokenRecord->id,
                'response_time' => microtime(true) - $startTime,
            ]);

            return new WP_Error(
                'jwt_auth_token_already_revoked',
                'Token has already been revoked',
                ['status' => 403]
            );
        }

        // Revoke the token
        $result = $this->tokenService->revokeToken($tokenRecord->id);

        if (is_wp_error($result)) {
            (new TrackTokenEvent())->execute([
                'event_type' => 'token_revocation',
                'event_status' => EventStatus::FAILURE,
                'failure_reason' => $result->get_error_code(),
                'user_id' => $tokenRecord->user_id,
                'token_id' => $tokenRecord->id,
                'response_time' => microtime(true) - $startTime,
            ]);

            return $result;
        }

        // Track successful revocation
        (new TrackTokenEvent())->execute([
            'event_type' => 'token_revocation',
            'event_status' => EventStatus::SUCCESS,
            'user_id' => $tokenRecord->user_id,
            'token_id' => $tokenRecord->id,
            'token_family' => $tokenRecord->token_family,
            'response_time' => microtime(true) - $startTime,
        ]);

        do_action('jwt_auth_token_revoked', $tokenRecord->id, $tokenRecord);

        return [
            'code' => 'jwt_auth_token_revoked',
            'data' => [
                'status' => 200,
                'revoked_at' => current_time('mysql', true),
            ],
        ];
    }
}
