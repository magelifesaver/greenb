<?php
// File: includes/utils/class-aipkit-admin-header-action-buttons.php
// Purpose: Shared utility to inject action buttons (e.g., Content Assistant, Index) next to the page title on list screens.

namespace WPAICG\Utils;

if (!defined('ABSPATH')) { exit; }

/**
 * Class AIPKit_Admin_Header_Action_Buttons
 * Provides a single injection point so multiple modules can register header buttons
 * without duplicating MutationObserver / placement logic.
 */
class AIPKit_Admin_Header_Action_Buttons {

    /** @var array<string, array{label:string, id:string, capability?:string, class?:string}> */
    private static $registered = [];
    private static $hook_added = false;

    /**
     * Register a button for header injection.
     */
    public static function register_button(string $id, string $label, array $args = []): void {
        if (isset(self::$registered[$id])) return;
        $defaults = [
            'id' => $id,
            'label' => $label,
            'capability' => null,
            'class' => 'page-title-action'
        ];
        self::$registered[$id] = array_merge($defaults, $args);
        self::ensure_hook();
    }

    private static function ensure_hook(): void {
        if (self::$hook_added) return;
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
        self::$hook_added = true;
    }

    /**
     * Enqueue assets + localize data for list screens.
     */
    public static function enqueue(): void {
        if (empty(self::$registered)) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->base !== 'edit') return; // Only list screens

        // Filter by capability
        $buttons = array_filter(self::$registered, function($btn){
            return empty($btn['capability']) || current_user_can($btn['capability']);
        });
        if (empty($buttons)) return;

        $export = [];
        foreach ($buttons as $btn) {
            $export[] = [
                'id' => $btn['id'],
                'label' => $btn['label'],
                'class' => $btn['class'] ?? 'page-title-action'
            ];
        }

        $version = self::file_ver('dist/css/admin-header-action-buttons.bundle.css', 'dist/js/admin-header-action-buttons.bundle.js');
        $css_handle = 'aipkit-header-buttons';
        $js_handle  = 'aipkit-header-buttons';

        // Enqueue built (dist) assets so raw source files need not be shipped.
        wp_enqueue_style(
            $css_handle,
            WPAICG_PLUGIN_URL . 'dist/css/admin-header-action-buttons.bundle.css',
            [],
            $version
        );

        wp_enqueue_script(
            $js_handle,
            WPAICG_PLUGIN_URL . 'dist/js/admin-header-action-buttons.bundle.js',
            [],
            $version,
            true
        );

        // Provide data
        wp_add_inline_script(
            $js_handle,
            'window.aipkitHeaderButtons = ' . wp_json_encode($export) . ';',
            'before'
        );
    }

    private static function file_ver(string $css_rel, string $js_rel): string {
        $base = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR : plugin_dir_path(__FILE__) . '../../';
        $css_ts = @filemtime($base . ltrim($css_rel, '/')) ?: 0;
        $js_ts  = @filemtime($base . ltrim($js_rel, '/')) ?: 0;
        $best = max($css_ts, $js_ts);
        if ($best > 0) return (string)$best;
        return defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }
}
