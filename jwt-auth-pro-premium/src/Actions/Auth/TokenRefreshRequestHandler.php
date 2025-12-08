<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Auth;

use Tmeister\JWTAuthPro\Actions\Analytics\TrackTokenEvent;
use Tmeister\JWTAuthPro\Actions\Generators\GenerateRefreshTokenAction;
use Tmeister\JWTAuthPro\Actions\Generators\GenerateTokenAction;
use Tmeister\JWTAuthPro\Database\RateLimitRepository;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\DTO\TokenDTO;
use Tmeister\JWTAuthPro\Enums\EventStatus;
use Tmeister\JWTAuthPro\Services\RateLimitService;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class TokenRefreshRequestHandler
{
    use HasRequestIP;

    private TokenRepository $tokenRepository;
    private RateLimitService $rateLimitService;

    public function __construct()
    {
        $this->tokenRepository = new TokenRepository();
        $this->rateLimitService = new RateLimitService(
            new RateLimitRepository(),
            new SettingsService()
        );
    }

    public function execute(string $refreshToken): array|WP_Error
    {
        // Check rate limit
        $rateLimitResult = $this->rateLimitService->isRateLimited($this->getRequestIP());

        if (is_wp_error($rateLimitResult)) {
            return $rateLimitResult;
        }

        $startTime = microtime(true);
        $validatedToken = $this->validateRefreshToken($refreshToken);

        if ($validatedToken instanceof WP_Error) {
            return $validatedToken;
        }

        // Generate new JWT token and refresh token
        $jwt              = (new GenerateTokenAction())->execute('', '', $validatedToken['user']);
        $refreshTokenData = (new GenerateRefreshTokenAction())->execute($validatedToken['user']->ID);

        // Revoke the current token
        $this->tokenRepository->revoke($validatedToken['token_id']);

        // Store the new token in the database
        $fullTokenData = array_merge($jwt['rawTokenData'], $refreshTokenData);
        // Add the token family to the new token
        $fullTokenData['token_family'] = $validatedToken['token_family'];

        $this->tokenRepository->create($fullTokenData);

        // Calculate token expiration
        $tokenExpiredAt = strtotime($jwt['rawTokenData']['expires_at']) - time();

        $response = [
            'access_token'  => $jwt['token'],
            'expires_in'    => $tokenExpiredAt,
            'token_type'    => 'Bearer',
            'refresh_token' => $refreshTokenData['refresh_token_hash'],
        ];

        $finalResponse = apply_filters('jwt_auth_token_before_dispatch', $response, $validatedToken['user']);

        (new TrackTokenEvent())->execute([
            'event_type' => 'token_refresh',
            'user_id' => $fullTokenData['user_id'],
            'token_id' => $validatedToken['token_id'],
            'token_family' => $fullTokenData['token_family'],
            'response_time' => microtime(true) - $startTime,
        ]);

        do_action('jwt_auth_token_refreshed', $response['access_token'], $validatedToken['user']);

        // Add rate limit headers to the response
        $this->rateLimitService->addRateLimitHeaders();

        return $finalResponse;
    }

    private function validateRefreshToken(string $refreshToken): array|WP_Error
    {
        /** @var TokenDTO|null $token */
        $token = $this->tokenRepository->findByRefreshToken($refreshToken);

        if ($token === null) {
            return $this->createError('jwt_auth_invalid_refresh_token');
        }

        // Verify if the token is not expired
        if (strtotime($token->refresh_token_expires_at) < time()) {
            return $this->createError('jwt_auth_expired_refresh_token');
        }

        // Verify if the user still exists
        $user = get_user_by('ID', $token->user_id);

        if (!$user) {
            return $this->createError('jwt_auth_user_not_found');
        }

        return [
            'user'         => $user,
            'token_id'     => (int) $token->id,
            'token_family' => $token->token_family,
        ];
    }

    private function createError(string $code): WP_Error
    {
        $messages = apply_filters('jwt_auth_error_messages', [
            'jwt_auth_invalid_refresh_token' => 'Invalid refresh token.',
            'jwt_auth_refresh_failed'        => 'Failed to refresh token.',
            'jwt_auth_user_not_found'        => 'User not found.',
            'jwt_auth_expired_refresh_token' => 'Expired refresh token.',
        ]);

        $status = apply_filters('jwt_auth_error_status', 403, $code);

        (new TrackTokenEvent())->execute([
            'event_type' => 'token_refresh',
            'event_status' => EventStatus::FAILURE,
            'failure_reason' => $code,
        ]);

        return new WP_Error($code, $messages[$code], ['status' => $status]);
    }
}
