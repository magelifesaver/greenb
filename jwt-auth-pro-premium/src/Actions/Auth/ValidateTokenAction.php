<?php

namespace Tmeister\JWTAuthPro\Actions\Auth;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Tmeister\JWTAuthPro\Actions\Analytics\TrackTokenEvent;
use Tmeister\JWTAuthPro\Database\RateLimitRepository;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\Enums\EventStatus;
use Tmeister\JWTAuthPro\Helpers\JWT;
use Tmeister\JWTAuthPro\Helpers\Key;
use Tmeister\JWTAuthPro\Services\RateLimitService;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Traits\HasAlgorithm;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class ValidateTokenAction
{
    use HasAlgorithm;
    use HasRequestIP;

    public function __construct(
        private readonly TokenRepository $tokenRepository = new TokenRepository(),
        private readonly SettingsService|MockObject $settingsService = new SettingsService(),
        private readonly RateLimitService|MockObject $rateLimitService = new RateLimitService(
            new RateLimitRepository(),
            new SettingsService()
        ),
    ) {}

    public function execute(string $token): WP_Error|stdClass
    {
        $secretPublicKey = $this->getSecretPublicKey();
        if (empty($secretPublicKey)) {
            return $this->createError('jwt_auth_bad_config');
        }

        // Check rate limit
        $rateLimitResult = $this->rateLimitService->isRateLimited($this->getRequestIP());
        if (is_wp_error($rateLimitResult)) {
            return $rateLimitResult;
        }

        $startTime = microtime(true);

        try {
            $algorithm = $this->getAlgorithm();

            if (!$algorithm) {
                return $this->createError('jwt_auth_unsupported_algorithm');
            }

            $decoded = JWT::decode(
                $token,
                new Key($secretPublicKey, $algorithm)
            );

            if (is_wp_error($validation = $this->validateTokenData($decoded))) {
                (new TrackTokenEvent())->execute([
                    'event_type' => 'token_validation',
                    'event_status' => EventStatus::FAILURE,
                    'failure_reason' => $validation->get_error_code(),
                    'response_time' => microtime(true) - $startTime,
                ]);

                return $validation;
            }

            $tokeHash    = wp_hash($token);
            $tokenRecord = $this->tokenRepository->findByHash($tokeHash);

            if (!$tokenRecord || !is_null($tokenRecord->revoked_at)) {
                return $this->createError('jwt_auth_revoked_token', '', $tokenRecord?->id);
            }

            // Update the last used date of the token
            if (isset($tokenRecord->id)) {
                $this->tokenRepository->updateLastUsedAt($tokenRecord->id);
            }

            (new TrackTokenEvent())->execute([
                'event_type' => 'token_validation',
                'user_id' => $tokenRecord->user_id,
                'token_id' => $tokenRecord->id,
                'token_family' => $tokenRecord->token_family,
                'response_time' => microtime(true) - $startTime,
            ]);

            do_action('jwt_auth_token_validated', $decoded);

            // Add rate limit headers to the response
            $this->rateLimitService->addRateLimitHeaders();

            return $decoded;
        } catch (Exception $e) {
            return $this->createError('jwt_auth_invalid_token', $e->getMessage());
        }
    }

    private function validateTokenData($token): WP_Error|bool
    {
        if (!isset($token->iss) || $token->iss !== get_bloginfo('url')) {
            return $this->createError('jwt_auth_bad_iss', 'The iss do not match with this server');
        }

        if (!isset($token->data->user->id)) {
            return $this->createError('jwt_auth_bad_request', 'User ID not found in the token');
        }

        return true;
    }

    private function createError(string $code, string $message = '', ?int $tokenId = null): WP_Error
    {
        $messages = [
            'jwt_auth_bad_config'    => 'JWT is not configured properly',
            'jwt_auth_invalid_token' => $message ?: 'Invalid token',
            'jwt_auth_revoked_token' => 'This token has been revoked',
            'jwt_auth_bad_iss'       => $message ?: 'The iss do not match with this server',
            'jwt_auth_bad_request'   => $message ?: 'User ID not found in the token',
        ];

        // Determine event type based on the code
        $eventType = $code === 'jwt_auth_revoked_token' ? 'token_revocation' : 'token_validation';

        (new TrackTokenEvent())->execute([
            'event_type' => $eventType,
            'event_status' => EventStatus::FAILURE,
            'failure_reason' => $code,
            'token_id' => $tokenId,
        ]);

        return new WP_Error($code, $messages[$code], ['status' => 403]);
    }

    private function getSecretPublicKey(): ?string
    {
        return apply_filters('jwt_auth_secret_public_key', defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : null);
    }

    // For testing purposes
    public function getRateLimitService(): RateLimitService
    {
        return $this->rateLimitService;
    }
}
