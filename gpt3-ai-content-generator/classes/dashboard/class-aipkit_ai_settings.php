<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/class-aipkit_ai_settings.php
// REVISED FILE - Moved AJAX handlers to separate classes
// UPDATED: Removed image_triggers from default security settings and initialization.
// UPDATED: Removed 'buffer' from default AI params and saving logic.

namespace WPAICG;

use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation;
use WPAICG\Lib\Addons\AIPKit_Consent_Compliance;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle AI Settings definitions, initialization, and retrieval.
 * AJAX saving and model sync logic has been moved to dedicated handler classes.
 */
if (!class_exists('\\WPAICG\\AIPKIT_AI_Settings')) {
    class AIPKIT_AI_Settings {

        // Option name for security settings (Banned Words/IPs, OpenAI Mod, Consent)
        const SECURITY_OPTION_NAME = 'aipkit_security';

        // Default advanced parameters for AI generation.
        public static $default_ai_params = array(
            'max_completion_tokens' => 4000, 'temperature' => 1.0,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        );

        // Default API Keys structure.
        // Moved to public static.
        public static $default_api_keys = array(
            'public_api_key' => '',
        );

        // Default Security structure (including Consent).
        // Moved to public static.
        // UPDATED: Removed image_triggers
        public static $default_security_settings = array(
            'bannedwords' => ['words' => '', 'message' => ''],
            'bannedips' => ['ips' => '', 'message' => ''],
            'consent' => ['title' => '', 'message' => '', 'button' => ''],
            'openai_moderation_enabled' => '0', 'openai_moderation_message' => '',
        );

        /**
         * Initializes settings checks.
         * AJAX hooks are now registered in DashboardInitializer.
         */
        public static function init() {
            // Ensure Google Settings Handler is loaded
            $google_settings_handler_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/google/GoogleSettingsHandler.php';
            if (!class_exists(GoogleSettingsHandler::class) && file_exists($google_settings_handler_path)) {
                 require_once $google_settings_handler_path;
            }

            // Initialize Google safety settings via the handler if available
            if (class_exists(GoogleSettingsHandler::class) && method_exists(GoogleSettingsHandler::class, 'check_and_init_safety_settings')) {
                GoogleSettingsHandler::check_and_init_safety_settings();
            }
            // Initialize core settings
            self::check_and_init_ai_parameters();
            self::check_and_init_api_keys();
            self::check_and_init_security_settings();
        }

        /** Ensure ai_parameters exist in the options array. */
        private static function check_and_init_ai_parameters() {
            $opts = get_option('aipkit_options', array());
            if (!isset($opts['ai_parameters']) || !is_array($opts['ai_parameters'])) {
                $opts['ai_parameters'] = self::$default_ai_params;
                update_option('aipkit_options', $opts, 'no');
            } else {
                // Ensure all default keys exist and remove obsolete ones
                $final_params = [];
                foreach (self::$default_ai_params as $key => $default_value) {
                    $final_params[$key] = $opts['ai_parameters'][$key] ?? $default_value;
                }
                if ($final_params !== $opts['ai_parameters']) {
                    $opts['ai_parameters'] = $final_params;
                    update_option('aipkit_options', $opts, 'no');
                }
            }
        }

        /** Ensure api_keys exist in the options array. */
        private static function check_and_init_api_keys() {
             $opts = get_option('aipkit_options', array());
             if (!isset($opts['api_keys']) || !is_array($opts['api_keys'])) {
                 $opts['api_keys'] = self::$default_api_keys;
                 update_option('aipkit_options', $opts, 'no');
             } else {
                 $merged = array_merge(self::$default_api_keys, $opts['api_keys']);
                 if ($merged !== $opts['api_keys']) {
                     $opts['api_keys'] = $merged;
                     update_option('aipkit_options', $opts, 'no');
                 }
             }
        }

        /** Ensure security settings exist in their dedicated option. */
        private static function check_and_init_security_settings() {
            $security_opts = get_option(self::SECURITY_OPTION_NAME);
            $changed = false;

            // Availability checks
            if (!class_exists('\WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation')) {
                $mod_path = WPAICG_PLUGIN_DIR . 'lib/addons/class-aipkit-openai-moderation.php';
                if (file_exists($mod_path)) require_once $mod_path;
            }
            if (!class_exists('\WPAICG\Lib\Addons\AIPKit_Consent_Compliance')) {
                 $con_path = WPAICG_PLUGIN_DIR . 'lib/addons/class-aipkit-consent-compliance.php';
                 if (file_exists($con_path)) require_once $con_path;
            }
            if (!class_exists('\WPAICG\aipkit_dashboard')) {
                 $dash_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
                 if (file_exists($dash_path)) require_once $dash_path;
            }


            $openai_mod_addon_helper_exists = class_exists('\WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation');
            $consent_addon_helper_exists = class_exists('\WPAICG\Lib\Addons\AIPKit_Consent_Compliance');
            $dashboard_exists = class_exists('\WPAICG\aipkit_dashboard');

            $is_pro = $dashboard_exists && aipkit_dashboard::is_pro_plan();
            $openai_mod_addon_active = $openai_mod_addon_helper_exists && $dashboard_exists && $is_pro && aipkit_dashboard::is_addon_active(AIPKit_OpenAI_Moderation::ADDON_KEY);
            $consent_addon_active = $consent_addon_helper_exists && $dashboard_exists && $is_pro && aipkit_dashboard::is_addon_active(AIPKit_Consent_Compliance::ADDON_KEY);

            if ($security_opts === false || !is_array($security_opts)) {
                $security_opts = self::$default_security_settings; $changed = true;
            } else {
                 // Ensure Banned Words/IPs sections exist and have all sub-keys
                 foreach (['bannedwords', 'bannedips'] as $key) {
                     if (!isset($security_opts[$key]) || !is_array($security_opts[$key])) {
                        $security_opts[$key] = self::$default_security_settings[$key] ?? []; $changed = true;
                     } elseif(isset(self::$default_security_settings[$key])) {
                         $merged_sub = array_merge(self::$default_security_settings[$key], $security_opts[$key]);
                         if ($merged_sub !== $security_opts[$key]) { $security_opts[$key] = $merged_sub; $changed = true; }
                     }
                 }
                // Ensure OpenAI Moderation keys exist if addon is active and pro
                if ($openai_mod_addon_active) {
                    if (!isset($security_opts['openai_moderation_enabled'])) { $security_opts['openai_moderation_enabled'] = self::$default_security_settings['openai_moderation_enabled']; $changed = true; }
                    if (!isset($security_opts['openai_moderation_message'])) { $security_opts['openai_moderation_message'] = self::$default_security_settings['openai_moderation_message']; $changed = true; }
                }
                 // Ensure Consent keys exist if addon is active and pro
                 if ($consent_addon_active) {
                    if (!isset($security_opts['consent']) || !is_array($security_opts['consent'])) {
                        $security_opts['consent'] = self::$default_security_settings['consent']; $changed = true;
                    } else {
                        $merged_consent = array_merge(self::$default_security_settings['consent'], $security_opts['consent']);
                        if($merged_consent !== $security_opts['consent']) { $security_opts['consent'] = $merged_consent; $changed = true;}
                    }
                 }
            }

            // Remove keys if not applicable (e.g. addon disabled or not pro)
            if (!$openai_mod_addon_active) {
                if (isset($security_opts['openai_moderation_enabled'])) { unset($security_opts['openai_moderation_enabled']); $changed = true; }
                if (isset($security_opts['openai_moderation_message'])) { unset($security_opts['openai_moderation_message']); $changed = true; }
            }
            if (!$consent_addon_active) {
                 if (isset($security_opts['consent'])) { unset($security_opts['consent']); $changed = true; }
            }

            if ($changed) { update_option(self::SECURITY_OPTION_NAME, $security_opts, 'no'); }
        }

        /** Retrieve advanced AI parameters. */
        public static function get_ai_parameters(): array {
            $opts = get_option('aipkit_options', array());
            self::check_and_init_ai_parameters(); // Ensure initialized
            $opts = get_option('aipkit_options', array()); // Re-fetch
            return $opts['ai_parameters'] ?? self::$default_ai_params;
        }

        /** Retrieve API Keys. */
        public static function get_api_keys(): array {
             $opts = get_option('aipkit_options', array());
             self::check_and_init_api_keys(); // Ensure initialized
             $opts = get_option('aipkit_options', array()); // Re-fetch
             return $opts['api_keys'] ?? self::$default_api_keys;
        }

        /** Retrieve Security Settings. */
        public static function get_security_settings(): array {
             self::check_and_init_security_settings(); // Ensure initialized
             $security_opts = get_option(self::SECURITY_OPTION_NAME, self::$default_security_settings);
            return $security_opts;
        }
    } // End class

    AIPKIT_AI_Settings::init();
}