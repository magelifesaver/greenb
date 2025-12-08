<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/class-aipkit_dashboard_initializer.php
// Status: MODIFIED

namespace WPAICG\Dashboard;

use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\TokenManager\Constants\CronHookConstant;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Chat\Admin\Ajax\UserCreditsAjaxHandler;
use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\Dashboard\Ajax\SettingsAjaxHandler;
use WPAICG\Dashboard\Ajax\ModelsAjaxHandler;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\WP_AI_Content_Generator_Activator;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initializes the AIPKit Dashboard.
 * - Registers the admin menu page.
 * - Includes necessary dashboard component files for backend logic.
 * - Registers hooks for dashboard-specific AJAX actions and cron jobs.
 */
class Initializer
{
    private $version;
    private $role_manager;

    public static function init($version)
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self($version);
        }
        return $instance;
    }

    private function __construct($version)
    {
        $this->version = $version;

        // --- Load Core Provider Strategies FIRST (needed by Settings AJAX) ---
        // Note: These are loaded by the main plugin loader.

        // --- Load Dashboard Component Logic ---
        require_once __DIR__ . '/class-aipkit_dashboard.php';
        require_once __DIR__ . '/class-aipkit_ai_settings.php';
        require_once __DIR__ . '/class-aipkit_providers.php';
        require_once __DIR__ . '/class-aipkit_stats.php';
        require_once __DIR__ . '/class-aipkit_role_manager.php';

        // --- Load AJAX Handlers for Dashboard Actions ---
        // BaseDashboardAjaxHandler is loaded by Base_Ajax_Handlers_Loader
        // SettingsAjaxHandler and ModelsAjaxHandler are now loaded by Base_Ajax_Handlers_Loader

        $user_credits_handler_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/user_credits_ajax_handler.php';
        if (file_exists($user_credits_handler_path)) {
            require_once $user_credits_handler_path;
        }

        // GoogleSettingsHandler (and its AJAX logic file) is loaded by ProviderDependenciesLoader.

        $this->role_manager = new AIPKit_Role_Manager();

        $this->register_hooks();
    }

    private function register_hooks()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // --- ADDED: Hook for admin_init tasks ---
        add_action('admin_init', [$this, 'run_admin_init_tasks']);
        // --- END ADDED ---

        // --- MODIFIED: Corrected Token Manager usage ---
        if (class_exists('\\WPAICG\\Core\\TokenManager\\AIPKit_Token_Manager')) {
            $token_manager = new \WPAICG\Core\TokenManager\AIPKit_Token_Manager();
            if (!has_action(\WPAICG\Core\TokenManager\Constants\CronHookConstant::CRON_HOOK, [$token_manager, 'perform_token_reset'])) {
                add_action(\WPAICG\Core\TokenManager\Constants\CronHookConstant::CRON_HOOK, [$token_manager, 'perform_token_reset']);
            }
        }
        // --- END MODIFICATION ---

        if (class_exists('\\WPAICG\\AIPKit_Role_Manager') && method_exists('\\WPAICG\\AIPKit_Role_Manager', 'init')) {
            \WPAICG\AIPKit_Role_Manager::init();
        }
        // SettingsAjaxHandler and ModelsAjaxHandler are instantiated in Ajax_Hooks_Registrar
        // if (class_exists('\\WPAICG\\Dashboard\\Ajax\\BaseDashboardAjaxHandler')) { // This check is not enough, needs specific handlers
        // SettingsAjaxHandler hooks are now in Ajax_Hooks_Registrar
        // ModelsAjaxHandler hooks are now in Ajax_Hooks_Registrar

        // MODIFIED: Call static method from the bootstrapped GoogleSettingsHandler
        if (class_exists(\WPAICG\Core\Providers\Google\GoogleSettingsHandler::class) && method_exists(\WPAICG\Core\Providers\Google\GoogleSettingsHandler::class, 'ajax_sync_google_tts_voices')) {
            if (!has_action('wp_ajax_aipkit_sync_google_tts_voices', ['\WPAICG\Core\Providers\Google\GoogleSettingsHandler', 'ajax_sync_google_tts_voices'])) {
                add_action('wp_ajax_aipkit_sync_google_tts_voices', ['\WPAICG\Core\Providers\Google\GoogleSettingsHandler', 'ajax_sync_google_tts_voices']);
            }
        }

        if (class_exists('\\WPAICG\\Chat\\Admin\\Ajax\\UserCreditsAjaxHandler')) {
            $user_credits_handler = new UserCreditsAjaxHandler();
            if (!has_action('wp_ajax_aipkit_get_user_credits_data', [$user_credits_handler, 'ajax_get_user_credits_data'])) {
                add_action('wp_ajax_aipkit_get_user_credits_data', [$user_credits_handler, 'ajax_get_user_credits_data']);
            }
            // --- NEW: Register balance update AJAX action ---
            if (method_exists($user_credits_handler, 'ajax_admin_update_token_balance') && !has_action('wp_ajax_aipkit_admin_update_token_balance', [$user_credits_handler, 'ajax_admin_update_token_balance'])) {
                add_action('wp_ajax_aipkit_admin_update_token_balance', [$user_credits_handler, 'ajax_admin_update_token_balance']);
            }
            // --- END NEW ---
        }

        if (class_exists('\\WPAICG\\aipkit_dashboard') && method_exists('\\WPAICG\\aipkit_dashboard', 'init')) {
            \WPAICG\aipkit_dashboard::init();
        }
        if (class_exists('\\WPAICG\\AIPKIT_AI_Settings') && method_exists('\\WPAICG\\AIPKIT_AI_Settings', 'init')) {
            \WPAICG\AIPKIT_AI_Settings::init();
        }
    }

    // --- ADDED: Method to run tasks on admin_init ---
    public function run_admin_init_tasks()
    {
        // Run the migration check on every admin page load.
        // The check is idempotent and will exit early if not needed.
        if (class_exists(WP_AI_Content_Generator_Activator::class)) {
            WP_AI_Content_Generator_Activator::check_for_old_data_and_set_migration_status();
        }
    }
    // --- END ADDED ---

    private function get_base_menu_capability(): string
    {
        $base_capability = 'edit_posts';
        return apply_filters('aipkit_base_menu_capability', $base_capability);
    }
    public function register_admin_menu()
    {
        $menu_capability = $this->get_base_menu_capability();
        if (!current_user_can($menu_capability) && !current_user_can('manage_options') && !current_user_can('wpaicg_settings')) {
            return;
        }
        add_menu_page(__('AIP', 'gpt3-ai-content-generator'), __('AIP', 'gpt3-ai-content-generator'), $menu_capability, 'wpaicg', [$this, 'render_dashboard_page'], WPAICG_PLUGIN_URL . 'public/images/icon.png', 6);
        add_submenu_page('wpaicg', __('Dashboard', 'gpt3-ai-content-generator'), __('Dashboard', 'gpt3-ai-content-generator'), $menu_capability, 'wpaicg', [$this, 'render_dashboard_page']);
        add_submenu_page('wpaicg', __('Role Manager', 'gpt3-ai-content-generator'), __('Role Manager', 'gpt3-ai-content-generator'), 'manage_options', 'aipkit-role-manager', [$this, 'render_role_manager_page']);

        // --- MODIFIED: Conditionally register Migration Tool submenu page ---
        // The check for old data runs on admin_init, so this option should be available here.
        if (get_option(WP_AI_Content_Generator_Activator::MIGRATION_DATA_EXISTS_OPTION, false)) {
            add_submenu_page(
                'wpaicg', // Parent slug
                __('Migration Tool', 'gpt3-ai-content-generator'), // Page title
                __('Migration Tool', 'gpt3-ai-content-generator'), // Menu title
                'manage_options', // Capability
                'aipkit-migration-tool', // Menu slug
                [$this, 'render_migration_tool_page'] // Callback function
            );
        }
        // --- END MODIFICATION ---
    }
    private function can_user_access_dashboard(): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!class_exists('\\WPAICG\\AIPKit_Role_Manager')) {
            return false;
        }
        $manageable_modules = AIPKit_Role_Manager::get_manageable_modules();
        foreach (array_keys($manageable_modules) as $module_slug) {
            if (AIPKit_Role_Manager::user_can_access_module($module_slug)) {
                return true;
            }
        }
        return false;
    }
    public function render_dashboard_page()
    {
        if (!$this->can_user_access_dashboard()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'gpt3-ai-content-generator'), 403);
        }
        $dashboard_path = WPAICG_PLUGIN_DIR . 'admin/views/dashboard.php';
        if (file_exists($dashboard_path)) {
            include $dashboard_path;
        } else {
            echo '<div class="wrap"><h2>Error</h2><p>Dashboard view file not found: ' . esc_html($dashboard_path) . '</p></div>';
        }
    }
    public function render_role_manager_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'gpt3-ai-content-generator'));
        }
        $role_manager_path = WPAICG_PLUGIN_DIR . 'admin/views/modules/role-manager/index.php';
        if (file_exists($role_manager_path)) {
            echo '<div class="wrap aipkit_wrap">';
            include $role_manager_path;
            echo '</div>';
        } else {
            echo '<div class="wrap"><h2>Error</h2><p>Role Manager view file not found: ' . esc_html($role_manager_path) . '</p></div>';
        }
    }

    // --- ADDED: Callback for Migration Tool page ---
    public function render_migration_tool_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'gpt3-ai-content-generator'));
        }
        $migration_tool_path = WPAICG_PLUGIN_DIR . 'admin/views/migration-tool.php';
        if (file_exists($migration_tool_path)) {
            echo '<div class="wrap aipkit_wrap">'; // Add standard wrapper
            include $migration_tool_path;
            echo '</div>';
        } else {
            echo '<div class="wrap"><h2>Error</h2><p>Migration Tool view file not found: ' . esc_html($migration_tool_path) . '</p></div>';
        }
    }
    // --- END ADDED ---
}
