<?php

namespace Tmeister\JWTAuthPro\Actions\Auth;

use Tmeister\JWTAuthPro\Actions\Analytics\TrackTokenEvent;
use Tmeister\JWTAuthPro\Actions\Generators\GenerateRefreshTokenAction;
use Tmeister\JWTAuthPro\Actions\Generators\GenerateTokenAction;
use Tmeister\JWTAuthPro\Database\RateLimitRepository;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use Tmeister\JWTAuthPro\Enums\EventStatus;
use Tmeister\JWTAuthPro\Services\RateLimitService;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class TokenRequestHandler
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

    public function execute($username, $password): WP_Error|array
    {
        // Check rate limit first
        $rateLimitResult = $this->rateLimitService->isRateLimited($this->getRequestIP());
        if (is_wp_error($rateLimitResult)) {
            return $rateLimitResult;
        }

        $startTime = microtime(true);
        $jwt = (new GenerateTokenAction())->execute($username, $password);

        if (is_wp_error($jwt)) {
            (new TrackTokenEvent())->execute([
                'event_type' => 'token_generation',
                'event_status' => EventStatus::FAILURE,
                'failure_reason' => $jwt->get_error_code(),
            ]);

            return $jwt;
        }

        $refreshToken = (new GenerateRefreshTokenAction())->execute($jwt['user']->ID);

        // Get and revoke tokens from the same family if they exist
        if (!empty($refreshToken['token_family'])) {
            $existingTokens = $this->tokenRepository->findByTokenFamily($refreshToken['token_family']);
            foreach ($existingTokens as $token) {
                $this->tokenRepository->revoke((int) $token['id']);
            }
        }

        $fullTokenData = array_merge($jwt['rawTokenData'], $refreshToken);
        $token_id = $this->tokenRepository->create($fullTokenData);

        (new TrackTokenEvent())->execute([
            'event_type' => 'token_generation',
            'user_id' => $fullTokenData['user_id'],
            'token_id' => $token_id,
            'token_family' => $fullTokenData['token_family'],
            'response_time' => microtime(true) - $startTime,
        ]);

        do_action('jwt_auth_token_generated', $jwt['token'], $jwt['user']);

        // Add rate limit headers to the response
        $this->rateLimitService->addRateLimitHeaders();

        return $this->prepareResponse($jwt, $refreshToken);
    }

    private function prepareResponse(array $jwt, $refreshToken): array
    {
        $user = $jwt['user'];
        $data = [
            'token'             => $jwt['token'],
            'user_id'           => $user->ID,
            'user_email'        => $user->data->user_email,
            'user_nicename'     => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
            'refresh_token'     => $refreshToken['refresh_token_hash'],
        ];

        return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
    }
}
