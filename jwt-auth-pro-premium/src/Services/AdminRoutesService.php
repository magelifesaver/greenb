<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Exception;
use Tmeister\JWTAuthPro\Actions\Admin\Settings\GetSettingsOptionsAction;
use Tmeister\JWTAuthPro\Actions\Admin\System\GetSystemInfoAction;
use Tmeister\JWTAuthPro\Actions\Admin\Token\DeleteTokenAction;
use Tmeister\JWTAuthPro\Actions\Admin\Token\GetTokenAction;
use Tmeister\JWTAuthPro\Actions\Admin\Token\ListTokensAction;
use Tmeister\JWTAuthPro\Actions\Admin\Token\RevokeTokenAction;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class AdminRoutesService
{
    private const NAMESPACE = 'jwt-auth/v1';
    private const REST_BASE = 'admin';

    public function __construct(
        private readonly SecurityService $securityService,
        private readonly TokenService $tokenService,
    ) {}

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'listTokens'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getToken'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'revokeToken'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'deleteToken'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/(?P<id>[\d]+)/details',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getTokenDetails'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/(?P<id>[\d]+)/related',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getRelatedTokens'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                        'page' => [
                            'required' => false,
                            'default' => 1,
                            'type' => 'integer',
                            'minimum' => 1,
                        ],
                        'per_page' => [
                            'required' => false,
                            'default' => 10,
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/(?P<id>[\d]+)/usage-history',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getTokenUsageHistory'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                    'args'                => [
                        'id' => [
                            'required' => true,
                        ],
                        'page' => [
                            'required' => false,
                            'default' => 1,
                            'type' => 'integer',
                            'minimum' => 1,
                        ],
                        'per_page' => [
                            'required' => false,
                            'default' => 10,
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/bulk',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'bulkTokens'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/tokens/metrics',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getTokenMetrics'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/settings/options',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getSettingsOptions'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/system/info',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getSystemInfo'],
                    'permission_callback' => [$this, 'validateAdminAccess'],
                ],
            ]
        );
    }

    public function validateAdminAccess(): bool
    {
        $result = $this->securityService->validateAdminAccess();

        if (is_wp_error($result)) {
            return false;
        }

        $this->securityService->addSecurityHeaders();

        return true;
    }

    public function listTokens(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $action = new ListTokensAction($this->tokenService);
            $result = $action->execute($request->get_params());

            if (is_wp_error($result)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $result->get_error_message(),
                ], 400);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getToken(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $action = new GetTokenAction($this->tokenService);
        $result = $action->execute((int) $request->get_param('id'));

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    public function revokeToken(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $action = new RevokeTokenAction($this->tokenService);
        $result = $action->execute((int) $request->get_param('id'));

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Token revoked successfully',
            'data' => $result,
        ], 200);
    }

    public function deleteToken(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $action = new DeleteTokenAction($this->tokenService);
        $result = $action->execute((int) $request->get_param('id'));

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Token deleted successfully',
        ], 200);
    }

    public function getTokenDetails(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $tokenId = (int) $request->get_param('id');
            $details = $this->tokenService->getTokenDetails($tokenId);

            if (empty($details)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Token not found',
                ], 404);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $details,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRelatedTokens(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $tokenId = (int) $request->get_param('id');
            $page = (int) $request->get_param('page');
            $perPage = (int) $request->get_param('per_page');

            $result = $this->tokenService->getRelatedTokens($tokenId, $page, $perPage);

            return new WP_REST_Response([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTokenUsageHistory(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $tokenId = (int) $request->get_param('id');
            $page = (int) $request->get_param('page');
            $perPage = (int) $request->get_param('per_page');

            $result = $this->tokenService->getTokenUsageHistory($tokenId, $page, $perPage);

            return new WP_REST_Response([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSettingsOptions(): WP_REST_Response
    {
        $action = new GetSettingsOptionsAction();
        return $action->execute();
    }

    public function getSystemInfo(): WP_REST_Response
    {
        $action = new GetSystemInfoAction();
        return $action->execute();
    }
}
