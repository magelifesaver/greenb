<?php

namespace Tmeister\JWTAuthPro\Services;

use stdClass;
use Tmeister\JWTAuthPro\Actions\Auth\AuthenticateRequestAction;
use Tmeister\JWTAuthPro\Actions\Auth\RevokeTokenAction;
use Tmeister\JWTAuthPro\Actions\Auth\TokenRefreshRequestHandler;
use Tmeister\JWTAuthPro\Actions\Auth\TokenRequestHandler;
use Tmeister\JWTAuthPro\Actions\Auth\ValidateTokenAction;
use Tmeister\JWTAuthPro\Database\TokenRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class AuthenticationService
{
    private TokenRepository $tokenRepository;
    private TokenService $tokenService;

    public function __construct(
        private readonly AuthenticateRequestAction $authenticateAction,
        private readonly SettingsService $settingsService,
    ) {
        $this->tokenRepository = new TokenRepository();
        $this->tokenService = new TokenService($this->tokenRepository, $this->settingsService);
    }

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('rest_api_init', [$this, 'addCorsSupport']);
        add_filter('determine_current_user', [$this, 'authenticateRequest']);
        add_filter('rest_pre_dispatch', [$this, 'handleRateLimitError'], 10, 3);
        add_filter('rest_post_dispatch', [$this, 'addRateLimitHeadersToCors'], 10, 3);
    }

    public function registerRoutes(): void
    {
        register_rest_route('jwt-auth/v1', 'token', [
            'methods' => 'POST',
            'callback' => [$this, 'generateToken'],
            'permission_callback' => [$this, 'verifyProAccess'],
            'args' => [
                'username' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'password' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('jwt-auth/v1', 'token/validate', [
            'methods' => 'POST',
            'callback' => [$this, 'validateToken'],
            'permission_callback' => [$this, 'verifyProAccess'],
        ]);

        register_rest_route('jwt-auth/v1', 'token/refresh', [
            'methods' => 'POST',
            'callback' => [$this, 'refreshToken'],
            'permission_callback' => [$this, 'verifyProAccess'],
            'args' => [
                'refresh_token' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('jwt-auth/v1', 'token/revoke', [
            'methods' => 'POST',
            'callback' => [$this, 'revokeToken'],
            'permission_callback' => [$this, 'verifyProAccess'],
        ]);
    }

    public function verifyProAccess(): bool|WP_Error
    {
        // Check testing mode first for CI/CD environments
        if (LicenseService::isTestingMode()) {
            return true;
        }

        $license = LicenseService::getInstance()->getFreemius();

        if ($license->can_use_premium_code()) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            'Please activate your JWT Auth Pro license.',
            ['status' => rest_authorization_required_code()]
        );
    }

    public function generateToken(WP_REST_Request $request): array|WP_Error
    {
        return (new TokenRequestHandler())->execute(
            $request->get_param('username'),
            $request->get_param('password')
        );
    }

    public function validateToken(WP_REST_Request $request): WP_Error|stdClass
    {
        $token = $this->extractToken($request);

        if (is_wp_error($token)) {
            return $token;
        }

        $tokenValidation = (new ValidateTokenAction())->execute($token);

        if (is_wp_error($tokenValidation)) {
            return $tokenValidation;
        }

        return (object) [
            'code' => 'jwt_auth_valid_token',
            'data' => ['status' => 200],
        ];
    }

    public function refreshToken(WP_REST_Request $request): array|WP_Error
    {
        $refreshToken = sanitize_text_field($request->get_param('refresh_token'));

        return (new TokenRefreshRequestHandler())->execute($refreshToken);
    }

    public function revokeToken(WP_REST_Request $request): array|WP_Error
    {
        // Extract token from Authorization header
        $token = $this->extractToken($request);

        if (is_wp_error($token)) {
            return $token;
        }

        // Delegate to the RevokeTokenAction
        return (new RevokeTokenAction($this->tokenRepository, $this->tokenService))->execute($token);
    }

    public function authenticateRequest($user)
    {
        return $this->authenticateAction->execute($user);
    }

    public function addCorsSupport(): void
    {
        $enableCors = apply_filters(
            'jwt_auth_token_enable_cors',
            defined('JWT_AUTH_CORS_ENABLE')
            ? constant('JWT_AUTH_CORS_ENABLE')
            : $this->settingsService->getSetting('token_settings', 'enable_cors')
        );

        if (!$enableCors) {
            return;
        }

        $headers = apply_filters(
            'jwt_auth_cors_allow_headers',
            'Access-Control-Allow-Headers, Content-Type, Authorization'
        );

        header(sprintf('Access-Control-Allow-Headers: %s', $headers));
    }

    private function extractToken(WP_REST_Request $request): string|WP_Error
    {
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header) {
            return new WP_Error(
                'jwt_auth_no_auth_header',
                'Authorization header not found.',
                ['status' => 403]
            );
        }

        $token = str_replace('Bearer ', '', $auth_header);

        if (empty(trim($token))) {
            return new WP_Error(
                'jwt_auth_bad_auth_header',
                'Authorization header malformed.',
                ['status' => 403]
            );
        }

        return $token;
    }

    /**
     * Handle rate limit errors and return proper REST response
     * @param mixed $result
     * @param mixed $server
     * @param mixed $request
     */
    public function handleRateLimitError($result, $server, $request): mixed
    {
        if ($this->authenticateAction->hasError()) {
            $error = $this->authenticateAction->getError();
            if ($error && $error->get_error_code() === 'jwt_auth_rate_limited') {
                $data = $error->get_error_data();
                $response = new WP_REST_Response([
                    'code' => 'jwt_auth_rate_limited',
                    'message' => $error->get_error_message(),
                    'data' => ['status' => 429],
                ], 429);

                if (isset($data['retry_after'])) {
                    $response->header('Retry-After', $data['retry_after']);
                }

                return $response;
            }
        }

        return $result;
    }

    /**
     * Add rate limit headers to CORS expose headers
     * @param mixed $response
     * @param mixed $server
     * @param mixed $request
     */
    public function addRateLimitHeadersToCors($response, $server, $request)
    {
        // Only add headers if CORS is enabled
        $enableCors = apply_filters(
            'jwt_auth_token_enable_cors',
            defined('JWT_AUTH_CORS_ENABLE')
            ? constant('JWT_AUTH_CORS_ENABLE')
            : $this->settingsService->getSetting('token_settings', 'enable_cors')
        );

        if (!$enableCors) {
            return $response;
        }

        // Only add headers if JWT token is present in the request
        $authHeader = $request->get_header('Authorization');
        if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
            return $response;
        }

        // Get existing expose headers
        $existingHeaders = $response->get_headers();
        $exposeHeaders = $existingHeaders['Access-Control-Expose-Headers'] ?? '';

        // Get rate limit headers to expose
        $rateLimitHeaders = apply_filters(
            'jwt_auth_cors_expose_headers',
            'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, Retry-After'
        );

        // Combine existing headers with rate limit headers
        if (!empty($exposeHeaders)) {
            $combinedHeaders = $exposeHeaders . ', ' . $rateLimitHeaders;
        } else {
            $combinedHeaders = $rateLimitHeaders;
        }

        // Set the combined headers
        $response->header('Access-Control-Expose-Headers', $combinedHeaders);

        return $response;
    }
}
