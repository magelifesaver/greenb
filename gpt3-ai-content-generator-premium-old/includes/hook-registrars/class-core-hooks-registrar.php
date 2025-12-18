<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/hook-registrars/class-core-hooks-registrar.php
// Status: NEW FILE

namespace WPAICG\Includes\HookRegistrars;

use WPAICG\WP_AI_Content_Generator_i18n;
use WPAICG\Public\WP_AI_Content_Generator_Public;
use WPAICG\Shortcodes\AIPKit_Shortcodes_Manager;
use WPAICG\PostEnhancer\Core as PostEnhancerCore;
use WPAICG\Speech\AIPKit_Speech_Manager;
use WPAICG\STT\AIPKit_STT_Manager;
use WPAICG\Images\AIPKit_Image_Manager;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Registers core functionality hooks (i18n, public, shortcodes, and non-AJAX module inits).
 */
class Core_Hooks_Registrar {

    public static function register(
        WP_AI_Content_Generator_i18n $i18n,
        WP_AI_Content_Generator_Public $public,
        AIPKit_Shortcodes_Manager $shortcodes,
        PostEnhancerCore $post_enhancer,
        ?AIPKit_Speech_Manager $speech_manager, // Nullable if class might not exist
        ?AIPKit_STT_Manager $stt_manager,       // Nullable
        ?AIPKit_Image_Manager $image_manager     // Nullable
    ) {
        add_action('init', [$i18n, 'init_hooks'], 0);
        $public->init_hooks();
        $shortcodes->init_hooks();
        $post_enhancer->init_hooks();

        if ($speech_manager && method_exists($speech_manager, 'init_hooks')) {
            $speech_manager->init_hooks();
        }
        if ($stt_manager && method_exists($stt_manager, 'init_hooks')) {
            $stt_manager->init_hooks();
        }
        if ($image_manager && method_exists($image_manager, 'init_hooks')) {
            $image_manager->init_hooks();
        }
        
        // --- ADDED: Register custom cron schedules ---
        add_filter('cron_schedules', [__CLASS__, 'add_custom_cron_schedules']);
        // --- END ADDED ---
    }
    
    /**
     * Adds custom cron schedules for more frequent task automation.
     * WP Cron's actual execution depends on site traffic. These define intervals, not exact run times.
     * @param array $schedules The existing cron schedules.
     * @return array The modified schedules.
     */
    public static function add_custom_cron_schedules($schedules) {
        $schedules['aipkit_five_minutes'] = [
            'interval' => 300, // 5 * 60 seconds
            'display'  => __('Every 5 Minutes', 'gpt3-ai-content-generator')
        ];
        $schedules['aipkit_fifteen_minutes'] = [
            'interval' => 900, // 15 * 60 seconds
            'display'  => __('Every 15 Minutes', 'gpt3-ai-content-generator')
        ];
        $schedules['aipkit_thirty_minutes'] = [
            'interval' => 1800, // 30 * 60 seconds
            'display'  => __('Every 30 Minutes', 'gpt3-ai-content-generator')
        ];
        // Ensure weekly is present as some plugins/themes might remove it.
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 604800,
                'display'  => __('Once Weekly', 'gpt3-ai-content-generator')
            ];
        }
        return $schedules;
    }
}