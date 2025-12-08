<?php
// File: classes/core/token-manager/helpers/GetUserLimitLogic.php

namespace WPAICG\Core\TokenManager\Helpers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to determine the token limit for a logged-in user based on settings.
 *
 * @param array $settings The module-specific settings array (must contain 'token_limit_mode', 'token_user_limit', 'token_role_limits').
 * @param int $user_id The ID of the user.
 * @return int|null The token limit (int > 0), 0 if disabled, or null if unlimited.
 */
function GetUserLimitLogic(array $settings, int $user_id): ?int {
    $limit_mode = $settings['token_limit_mode'] ?? 'general';

    if ($limit_mode === 'general') {
        $limit_raw = $settings['token_user_limit'] ?? null;
        if ($limit_raw === '' || $limit_raw === null) return null; // Unlimited
        return ($limit_raw === '0' || $limit_raw === 0) ? 0 : absint($limit_raw);
    } else { // Role-based
        $user_data = get_userdata($user_id);
        $user_roles = $user_data ? (array) $user_data->roles : [];
        $role_limits_raw = $settings['token_role_limits'] ?? [];
        $role_limits = is_string($role_limits_raw) ? json_decode($role_limits_raw, true) : (is_array($role_limits_raw) ? $role_limits_raw : []);
        if (!is_array($role_limits)) $role_limits = [];

        if (empty($user_roles) || empty($role_limits)) {
            return null; // No roles or no role limits defined, treat as unlimited
        }

        $highest_limit = -1; // -1 indicates no specific limit found yet
        foreach ($user_roles as $role) {
            if (isset($role_limits[$role])) {
                $role_limit_value_raw = $role_limits[$role];
                if ($role_limit_value_raw === '' || $role_limit_value_raw === null) {
                    $highest_limit = null; // Explicitly unlimited for this role, overrides others
                    break;
                }
                if ($role_limit_value_raw === '0' || $role_limit_value_raw === 0) {
                    $highest_limit = max($highest_limit, 0); // Found a "disabled" (0) limit
                } elseif (ctype_digit((string)$role_limit_value_raw)) {
                    $highest_limit = max($highest_limit, (int)$role_limit_value_raw);
                }
            }
        }
        return ($highest_limit === -1) ? null : $highest_limit; // If -1, means no limit found, so unlimited
    }
}