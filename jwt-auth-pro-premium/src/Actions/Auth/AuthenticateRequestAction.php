<?php

namespace Tmeister\JWTAuthPro\Actions\Auth;

use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class AuthenticateRequestAction
{
    use HasRequestIP;

    private ?WP_Error $error = null;

    public function execute($user)
    {
        if (!$this->shouldAuthenticate()) {
            return $user;
        }

        $token = $this->extractBearerToken();
        if (!$token) {
            // save error
            $this->error = new WP_Error('jwt_auth_missing_token', 'Missing token', ['status' => 401]);
            return $user;
        }

        $validatedToken = (new ValidateTokenAction())->execute($token);

        if (is_wp_error($validatedToken)) {
            $this->error = $validatedToken;
            return $user;
        }

        return $validatedToken->data->user->id;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getError(): ?WP_Error
    {
        return $this->error;
    }

    private function shouldAuthenticate(): bool
    {
        $rest_api_slug = rest_get_url_prefix();
        $requested_url = sanitize_url($_SERVER['REQUEST_URI']);

        return defined('REST_REQUEST')
            && REST_REQUEST
            && str_contains($requested_url, $rest_api_slug)
            && !str_contains($requested_url, 'token/validate')
            && !str_contains($requested_url, 'token');
    }

    private function extractBearerToken(): ?string
    {
        $auth_header = !empty($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : false;

        if (!$auth_header) {
            $auth_header = !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) : false;
        }

        if (!$auth_header || !str_starts_with($auth_header, 'Bearer')) {
            return null;
        }

        $token = str_replace('Bearer ', '', $auth_header);

        return trim($token);
    }
}
