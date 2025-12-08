<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Tmeister\JWTAuthPro\Actions\User\DeleteAllTokens;
use Tmeister\JWTAuthPro\Actions\User\RevokeAllTokens;
use WP_Error;
use WP_User;

class UserService
{
    public function __construct(
        private readonly RevokeAllTokens $revokeAllTokens,
        private readonly DeleteAllTokens $deleteAllTokens,
        private readonly SettingsService $settingsService,
    ) {}

    public function registerHooks(): void
    {
        // Password change hooks
        add_action('after_password_reset', [$this, 'handlePasswordChange']);
        add_action('password_reset', [$this, 'handlePasswordChange']);

        // Profile update hooks
        add_action('profile_update', [$this, 'handleProfileUpdate'], 10, 2);
        add_action('set_user_role', [$this, 'handleRoleChange']);

        // User deletion hook
        add_action('delete_user', [$this, 'handleUserDelete']);
    }

    public function handlePasswordChange(WP_User $user): void
    {
        $canRevoke = $this->settingsService->getSetting('user_settings', 'revoke_on_password_change');
        $allowAction = apply_filters('jwt_auth_revoke_tokens_on_password_change', $canRevoke);

        if (!$allowAction) {
            return;
        }

        $result = $this->revokeAllTokens->execute($user->ID, 'password_change');
        if ($result instanceof WP_Error) {
            do_action('jwt_auth_error', $result, 'password_change');
        }
    }

    public function handleProfileUpdate(int $userId, WP_User $oldUser): void
    {
        $user = get_user_by('id', $userId);

        if (!$user instanceof WP_User) {
            return;
        }

        // Check if the email has changed
        if ($user->user_email !== $oldUser->user_email) {
            $canRevokeOnEmailChange = $this->settingsService->getSetting('user_settings', 'revoke_on_email_change');
            $allowActionOnEmailChange = apply_filters('jwt_auth_revoke_tokens_on_email_change', $canRevokeOnEmailChange);

            if (!$allowActionOnEmailChange) {
                return;
            }

            $result = $this->revokeAllTokens->execute($userId, 'email_change');

            if ($result instanceof WP_Error) {
                do_action('jwt_auth_error', $result, 'email_change');
            }
        }

        // Check if the password has changed
        if ($user->user_pass !== $oldUser->user_pass) {
            $canRevokeOnPasswordChange = $this->settingsService->getSetting('user_settings', 'revoke_on_password_change');
            $allowActionOnPasswordChange = apply_filters('jwt_auth_revoke_tokens_on_password_change', $canRevokeOnPasswordChange);

            if (!$allowActionOnPasswordChange) {
                return;
            }

            $result = $this->revokeAllTokens->execute($userId, 'password_change');

            if ($result instanceof WP_Error) {
                do_action('jwt_auth_error', $result, 'password_change');
            }
        }
    }

    public function handleRoleChange(int $userId): void
    {
        $canRevoke = $this->settingsService->getSetting('user_settings', 'revoke_on_role_change');
        $allowAction = apply_filters('jwt_auth_revoke_tokens_on_role_change', $canRevoke);

        if (!$allowAction) {
            return;
        }

        $result = $this->revokeAllTokens->execute($userId, 'role_change');
        if ($result instanceof WP_Error) {
            do_action('jwt_auth_error', $result, 'role_change');
        }
    }

    public function handleUserDelete(int $userId): void
    {
        $canDelete = $this->settingsService->getSetting('user_settings', 'delete_on_user_delete');
        $allowAction = apply_filters('jwt_auth_delete_tokens_on_user_delete', $canDelete);

        if (!$allowAction) {
            return;
        }

        $result = $this->deleteAllTokens->execute($userId);
        if ($result instanceof WP_Error) {
            do_action('jwt_auth_error', $result, 'user_delete');
        }
    }
}
