<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/class-aipkit_role_manager.php
// Status: MODIFIED
// I have added 'semantic_search' to the list of manageable modules.

namespace WPAICG;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Role_Manager
 *
 * Handles getting roles, modules, saving/getting role-based permissions, checking access,
 * and updating permissions on activation.
 * UPDATED: update_permissions_on_activation() now migrates old wpaicg_ capabilities to new module permissions.
 */
class AIPKit_Role_Manager
{
    public const OPTION_NAME = 'aipkit_role_permissions';
    private static $permission_cache = [];

    /**
     * Register AJAX actions for saving role permissions.
     */
    public static function init()
    {
        add_action('wp_ajax_aipkit_save_role_permissions', [__CLASS__, 'ajax_save_role_permissions']);
    }

    /**
     * Get the list of modules that require permission management.
     * @return array ['module_slug' => 'Module Name', ...]
     */
    public static function get_manageable_modules(): array
    {
        return [
            // Dashboard Modules
            'chatbot'               => __('Chatbot (Admin)', 'gpt3-ai-content-generator'),
            'content-writer'        => __('Content Writer', 'gpt3-ai-content-generator'),
            'autogpt'               => __('AutoGPT (General Access)', 'gpt3-ai-content-generator'),
            'autogpt_auto_indexer'  => __('Auto Content Indexing (AutoGPT)', 'gpt3-ai-content-generator'),
            'ai-forms'              => __('AI Forms', 'gpt3-ai-content-generator'),
            'image-generator'       => __('Image Generator', 'gpt3-ai-content-generator'),
            'ai-training'           => __('AI Training', 'gpt3-ai-content-generator'),
            'user-credits'          => __('User Credits', 'gpt3-ai-content-generator'),
            'settings'              => __('Settings (Global)', 'gpt3-ai-content-generator'),
            'addons'                => __('Add-ons (Admin)', 'gpt3-ai-content-generator'),
            'logs'                  => __('Logs (Admin)', 'gpt3-ai-content-generator'),
            // Frontend / Non-Dashboard Modules
            'token_usage_shortcode' => __('Token Usage Shortcode (Frontend)', 'gpt3-ai-content-generator'),
            'ai_post_enhancer'      => __('Content Assistant (Admin Edit Screen)', 'gpt3-ai-content-generator'),
            'vector_content_indexer' => __('Vector Content Indexer (Post Screen)', 'gpt3-ai-content-generator'),
        ];
    }

    /**
     * Get all editable WordPress roles.
     * @return array ['role_slug' => ['name' => 'Role Name', ...], ...]
     */
    public static function get_editable_roles(): array
    {
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }
        return $wp_roles->get_names();
    }

    /**
     * Get the default permissions (admin only for all modules).
     * @return array ['module_slug' => ['administrator']]
     */
    private static function get_default_permissions(): array
    {
        $defaults = [];
        $modules = self::get_manageable_modules();
        foreach (array_keys($modules) as $module_slug) {
            $defaults[$module_slug] = ['administrator'];
        }
        return $defaults;
    }

    /**
     * Get the currently saved role permissions.
     * @return array ['module_slug' => ['role1', 'role2'], ...]
     */
    public static function get_role_permissions(): array
    {
        $permissions = get_option(self::OPTION_NAME);
        $modules = self::get_manageable_modules();
        $defaults = self::get_default_permissions();

        if ($permissions === false || !is_array($permissions)) {
            return $defaults;
        }

        $updated_permissions = $permissions;
        $changed = false;
        foreach (array_keys($modules) as $module_slug) {
            if (!isset($updated_permissions[$module_slug])) {
                $updated_permissions[$module_slug] = $defaults[$module_slug];
                $changed = true;
            } elseif (!is_array($updated_permissions[$module_slug]) || empty($updated_permissions[$module_slug])) {
                if (!is_array($updated_permissions[$module_slug])) {
                    $updated_permissions[$module_slug] = $defaults[$module_slug];
                    $changed = true;
                }
            }
        }

        foreach (array_keys($updated_permissions) as $saved_module_slug) {
            if (!isset($modules[$saved_module_slug])) {
                unset($updated_permissions[$saved_module_slug]);
                $changed = true;
            }
        }
        return $updated_permissions;
    }

    /**
     * Called on plugin activation or update.
     * Ensures all current manageable modules exist in the permissions option, adding defaults if missing.
     * Migrates old wpaicg_ capabilities to the new module-based permission system.
     */
    public static function update_permissions_on_activation()
    {
        $current_permissions = get_option(self::OPTION_NAME);
        $all_modules = self::get_manageable_modules();
        $default_permissions_for_new_modules = self::get_default_permissions();
        $changed = false;

        if ($current_permissions === false || !is_array($current_permissions)) {
            $current_permissions = [];
            $changed = true;
        }

        $final_permissions = $current_permissions;

        // Step 1: Ensure all current modules are present and prune obsolete ones
        foreach (array_keys($all_modules) as $module_slug) {
            if (!isset($final_permissions[$module_slug]) || !is_array($final_permissions[$module_slug])) {
                $final_permissions[$module_slug] = $default_permissions_for_new_modules[$module_slug];
                $changed = true;
            }
        }
        foreach (array_keys($final_permissions) as $saved_module_slug) {
            if (!isset($all_modules[$saved_module_slug])) {
                unset($final_permissions[$saved_module_slug]);
                $changed = true;
            }
        }

        // Step 2: Migrate old capabilities to new module permissions
        $old_to_new_cap_map = [
            'wpaicg_settings'        => 'settings',
            'wpaicg_single_content'  => 'content-writer',
            'wpaicg_bulk_content'    => 'autogpt',
            'wpaicg_chatgpt'         => 'chatbot',
            'wpaicg_imgcreator'      => 'image-generator',
            'wpaicg_embeddings_access' => 'ai-training',
            'wpaicg_finetune_access'   => 'ai-training',
            'wpaicg_chatbot_widget'  => 'chatbot',
            'wpaicg_aipower_dashboard' => 'settings',
            'wpaicg_audio_converter_access' => 'audio_converter',
            'wpaicg_chat_logs'       => 'logs',
            'wpaicg_forms_access'    => 'ai-forms',
            'wpaicg_account_access'  => 'ai_account',
            'wpaicg_post_enhancer'   => 'ai_post_enhancer',
            'wpaicg_vector_indexer'  => 'vector_content_indexer',
        ];

        $wp_roles = wp_roles();
        foreach ($wp_roles->roles as $role_slug => $role_data) {
            $role_object = $wp_roles->get_role($role_slug);
            if (!$role_object) {
                continue;
            }

            foreach ($old_to_new_cap_map as $old_cap => $new_module_slug) {
                if ($role_object->has_cap($old_cap)) {
                    if (isset($final_permissions[$new_module_slug]) && is_array($final_permissions[$new_module_slug])) {
                        if (!in_array($role_slug, $final_permissions[$new_module_slug], true)) {
                            $final_permissions[$new_module_slug][] = $role_slug;
                            $changed = true;
                        }
                    } else {
                        $final_permissions[$new_module_slug] = [$role_slug];
                        if ($role_slug !== 'administrator' && !in_array('administrator', $final_permissions[$new_module_slug])) {
                            $final_permissions[$new_module_slug][] = 'administrator';
                        }
                        $changed = true;
                    }
                }
            }
        }
        foreach (array_keys($all_modules) as $module_slug_for_admin_check) {
            if (isset($final_permissions[$module_slug_for_admin_check]) && is_array($final_permissions[$module_slug_for_admin_check])) {
                if (!in_array('administrator', $final_permissions[$module_slug_for_admin_check], true)) {
                    $final_permissions[$module_slug_for_admin_check][] = 'administrator';
                    $changed = true;
                }
            } else {
                $final_permissions[$module_slug_for_admin_check] = ['administrator'];
                $changed = true;
            }
            $final_permissions[$module_slug_for_admin_check] = array_values(array_unique($final_permissions[$module_slug_for_admin_check]));
        }


        if ($changed) {
            update_option(self::OPTION_NAME, $final_permissions, 'no');
            self::$permission_cache = []; // Clear cache
        }
    }


    /**
     * AJAX handler to save role permissions.
     */
    public static function ajax_save_role_permissions()
    {
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_key($_POST['_ajax_nonce']), 'aipkit_role_manager_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'gpt3-ai-content-generator')], 403);
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to manage roles.', 'gpt3-ai-content-generator')], 403);
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The array is sanitized inside the loop.
        $permissions_input = isset($_POST['permissions']) ? wp_unslash($_POST['permissions']) : [];
        $sanitized_permissions = [];
        $valid_modules = array_keys(self::get_manageable_modules());
        $valid_roles = array_keys(self::get_editable_roles());

        if (!is_array($permissions_input)) {
            wp_send_json_error(['message' => __('Invalid input format.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        foreach ($permissions_input as $module_slug => $allowed_roles) {
            $module_slug = sanitize_key($module_slug);
            if (!in_array($module_slug, $valid_modules)) {
                continue;
            }

            $sanitized_roles = [];
            if (is_array($allowed_roles)) {
                foreach ($allowed_roles as $role_slug => $is_allowed) {
                    $role_slug = sanitize_key($role_slug);
                    if (in_array($role_slug, $valid_roles) && $is_allowed) {
                        $sanitized_roles[] = $role_slug;
                    }
                }
            }
            if (!in_array('administrator', $sanitized_roles)) {
                $sanitized_roles[] = 'administrator';
            }
            $sanitized_permissions[$module_slug] = array_unique($sanitized_roles);
        }

        foreach ($valid_modules as $module_slug) {
            if (!isset($sanitized_permissions[$module_slug])) {
                $sanitized_permissions[$module_slug] = ['administrator'];
            }
        }

        $updated = update_option(self::OPTION_NAME, $sanitized_permissions, 'no');
        self::$permission_cache = [];

        if ($updated) {
            wp_send_json_success(['message' => __('Role permissions saved successfully.', 'gpt3-ai-content-generator')]);
        } else {
            wp_send_json_success(['message' => __('Permissions are up to date.', 'gpt3-ai-content-generator')]);
        }
    }

    /**
     * Checks if the current user has permission to access a specific module.
     * @param string $module_slug The slug of the module.
     * @return bool True if the user has access, false otherwise.
     */
    public static function user_can_access_module(string $module_slug): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        // Normalize module slug to match saved permissions keys.
        // Some callers use underscores (e.g., 'image_generator') while
        // the Role Manager stores slugs with hyphens (e.g., 'image-generator').
        $all_permissions = self::get_role_permissions();
        $normalized_slug = $module_slug;
        if (!isset($all_permissions[$normalized_slug])) {
            $alt_hyphen = str_replace('_', '-', $module_slug);
            if (isset($all_permissions[$alt_hyphen])) {
                $normalized_slug = $alt_hyphen;
            } else {
                $alt_underscore = str_replace('-', '_', $module_slug);
                if (isset($all_permissions[$alt_underscore])) {
                    $normalized_slug = $alt_underscore;
                }
            }
        }

        $user_id = get_current_user_id();
        $cache_key = $user_id . '_' . $normalized_slug;
        if (isset(self::$permission_cache[$cache_key])) {
            return self::$permission_cache[$cache_key];
        }

        $user = wp_get_current_user();
        if (!$user || !$user->ID) {
            self::$permission_cache[$cache_key] = false;
            return false;
        }
        $user_roles = (array) $user->roles;

        // Use normalized slug to fetch allowed roles
        $allowed_roles = isset($all_permissions[$normalized_slug]) && is_array($all_permissions[$normalized_slug])
                         ? $all_permissions[$normalized_slug]
                         : ['administrator'];

        $has_access = count(array_intersect($user_roles, $allowed_roles)) > 0;
        self::$permission_cache[$cache_key] = $has_access;

        return $has_access;
    }

}
