<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-wp-ai-content-generator.php
// Status: MODIFIED
// I have added a call to the database table setup function within the update check, ensuring schema changes are applied automatically when the plugin version is updated.

namespace WPAICG;

// --- Load Core Helper Classes FIRST ---
require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-dependency-loader.php';
require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-hook-manager.php';
require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-module-initializer.php';
require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-shared-assets-manager.php';
// --- END Load Core Helper Classes FIRST ---

// --- Use statements for NEW Core Helper Classes ---
use WPAICG\Includes\AIPKit_Dependency_Loader;
use WPAICG\Includes\AIPKit_Hook_Manager;
use WPAICG\Includes\AIPKit_Module_Initializer;
use WPAICG\Includes\AIPKit_Shared_Assets_Manager;

// --- END NEW ---

// --- Core Plugin Includes ---
require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator-activator.php';
require_once WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_role_manager.php'; // Needed for update check

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The core plugin class. Bootstrapper.
 */
class WP_AI_Content_Generator
{
    private static $instance = null;
    private $version;
    private $plugin_name;
    public const DB_VERSION_OPTION = 'aipkit_plugin_version'; // Option to store current DB version

    public static function get_instance(): WP_AI_Content_Generator
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';
        $this->plugin_name = 'gpt3-ai-content-generator';
    }

    /**
     * Run the plugin setup.
     * Load dependencies, define hooks, initialize modules, and ensure DB tables exist.
     */
    public function run()
    {
        // Load all dependencies using the new loader class
        AIPKit_Dependency_Loader::load();

        // Register shared assets (moved to a separate manager, called on init)
        add_action('init', [$this, 'register_shared_assets'], 0);

        // Check for plugin updates (version change)
        add_action('init', [$this, 'check_for_updates'], 10);

        // Define hooks using the new hook manager
        AIPKit_Hook_Manager::register_hooks($this->version);

        // Initialize modules using the new module initializer
        AIPKit_Module_Initializer::init($this->version);
    }

    /**
     * Register shared assets via the SharedAssetsManager.
     * Hooked to 'init' with priority 0.
     */
    public function register_shared_assets()
    {
        AIPKit_Shared_Assets_Manager::register($this->version);
    }

    /**
     * Check for plugin updates (e.g., version change) and run necessary routines.
     * Now runs on 'init' action hook, after i18n is loaded.
     */
    public function check_for_updates()
    {
        $current_version = $this->version;
        $saved_version = get_option(self::DB_VERSION_OPTION);

        // --- NEW: Check if any tables are missing as a fallback for incomplete activations/updates ---
        $tables_are_missing = $this->are_plugin_tables_missing();
        // --- END NEW ---

        if (version_compare((string)$saved_version, $current_version, '<') || $tables_are_missing) { // MODIFIED to include table check

            // --- ADDED: Clear caches first to ensure users get new assets ---
            $this->clear_external_caches();
            // --- END ADDED ---

            // Run DB table setup on version change to apply any schema updates.
            WP_AI_Content_Generator_Activator::setup_tables_for_blog();

            // Ensure Role Manager Permissions are Updated/Initialized
            if (class_exists('\\WPAICG\\AIPKit_Role_Manager')) {
                \WPAICG\AIPKit_Role_Manager::update_permissions_on_activation();
            }

            // Ensure Default Chatbot exists
            if (class_exists('\\WPAICG\\Chat\\Storage\\DefaultBotSetup')) {
                \WPAICG\Chat\Storage\DefaultBotSetup::ensure_default_chatbot();
            }

            // Ensure Default Content Writer Template exists
            if (class_exists('\\WPAICG\\ContentWriter\\AIPKit_Content_Writer_Template_Manager')) {
                \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager::ensure_default_template_exists();
            }

            // Ensure Default AI Forms exist
            if (class_exists('\\WPAICG\\AIForms\\Admin\\AIPKit_AI_Form_Defaults')) {
                \WPAICG\AIForms\Admin\AIPKit_AI_Form_Defaults::ensure_default_forms_exist();
            }

            // Ensure Cron Jobs are scheduled
            if (class_exists('\\WPAICG\\Core\\TokenManager\\AIPKit_Token_Manager')) {
                \WPAICG\Core\TokenManager\AIPKit_Token_Manager::schedule_token_reset_event();
            }
            if (class_exists('\\WPAICG\\Core\\Stream\\Cache\\AIPKit_SSE_Message_Cache')) {
                \WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache::schedule_cleanup_event();
            }
            if (class_exists('\\WPAICG\\AutoGPT\\AIPKit_Automated_Task_Cron')) {
                \WPAICG\AutoGPT\AIPKit_Automated_Task_Cron::init();
            }

            // Update the stored version
            update_option(self::DB_VERSION_OPTION, $current_version, 'no'); // Use autoload 'no'
        }
    }

    /**
     * NEW: Helper function to check if any of our custom tables are missing.
     * This adds robustness to the update process.
     * @return bool True if one or more tables are missing.
     */
    private function are_plugin_tables_missing(): bool
    {
        global $wpdb;
        $required_tables = [
            'aipkit_chat_logs',
            'aipkit_guest_token_usage',
            'aipkit_sse_message_cache',
            'aipkit_vector_data_source',
            'aipkit_automated_tasks',
            'aipkit_automated_task_queue',
            'aipkit_content_writer_templates',
            'aipkit_rss_history'
        ];

        foreach ($required_tables as $table_suffix) {
            $table_name = $wpdb->prefix . $table_suffix;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Necessary check for table existence during plugin initialization/update.
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
                return true; // Found a missing table
            }
        }
        return false;
    }

    /**
     * NEW: Clears caches from popular caching plugins.
     * This helps prevent issues with outdated assets after a plugin update.
     */
    private function clear_external_caches()
    {
        if (false === apply_filters('aipkit_auto_clear_caches_on_update', true)) {
            return;
        }

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // W3 Total Cache
        // if (function_exists('w3tc_flush_all')) {
        //     w3tc_flush_all();
        //     error_log('AIPKIT DEBUG: W3 Total Cache cleared.');
        // }

        // WP Super Cache
        // if (function_exists('wp_cache_clear_cache')) {
        //     wp_cache_clear_cache();
        //     error_log('AIPKIT DEBUG: WP Super Cache cleared.');
        // }

        // LiteSpeed Cache
        // if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
        //     \LiteSpeed_Cache_API::purge_all();
        //     error_log('AIPKIT DEBUG: LiteSpeed Cache cleared via API.');
        // } elseif (has_action('litespeed_purge_all')) {
        //     do_action('litespeed_purge_all');
        //     error_log('AIPKIT DEBUG: LiteSpeed Cache cleared via action.');
        // }

        // WP Fastest Cache
        // if (function_exists('wpfc_clear_all_cache')) {
        //     wpfc_clear_all_cache(true); // true for silent mode
        //     error_log('AIPKIT DEBUG: WP Fastest Cache cleared.');
        // }

        // SG Optimizer (SiteGround)
        // if (function_exists('sg_cachepress_purge_cache')) {
        //     sg_cachepress_purge_cache();
        //     error_log('AIPKIT DEBUG: SG Optimizer cache cleared.');
        // }

        // Hummingbird
        // if (class_exists('\Hummingbird\Core\Modules\Caching\Page') && method_exists('\Hummingbird\Core\Modules\Caching\Page', 'clear_cache')) {
        //     \Hummingbird\Core\Modules\Caching\Page::clear_cache();
        //     error_log('AIPKIT DEBUG: Hummingbird cache cleared.');
        // }

        // Autoptimize
        // if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
        //     \autoptimizeCache::clearall();
        //     error_log('AIPKIT DEBUG: Autoptimize cache cleared.');
        // }

        // WP Engine
        // if (class_exists('WpeCommon')) {
        //     if (method_exists('WpeCommon', 'purge_memcached')) {
        //         \WpeCommon::purge_memcached();
        //         error_log('AIPKIT DEBUG: WP Engine memcached purged.');
        //     }
        //     if (method_exists('WpeCommon', 'purge_varnish_cache')) {
        //         \WpeCommon::purge_varnish_cache();
        //         error_log('AIPKIT DEBUG: WP Engine Varnish cache purged.');
        //     }
        // }

        // Clear WordPress's core object cache
        wp_cache_flush();
    }


    public function get_plugin_name(): string
    {
        return $this->plugin_name;
    }
    public function get_version(): string
    {
        return $this->version;
    }

} // End class
