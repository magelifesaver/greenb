<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/utils/class-log-status-renderer.php

namespace WPAICG\Chat\Utils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Utility class for rendering log pruning cron status HTML.
 * 
 * This class centralizes the generation of cron status display HTML
 * to avoid code duplication between the settings view and AJAX handlers.
 */
class LogStatusRenderer
{
    /**
     * Renders the cron status panel HTML.
     *
     * @return string The HTML content for the cron status panel
     */
    public static function render_cron_status_panel(): string
    {
        // Get saved settings
        $log_settings = get_option('aipkit_log_settings', [
            'enable_pruning' => false,
            'retention_period_days' => 90
        ]);

        $enable_pruning = (bool)($log_settings['enable_pruning'] ?? false);

        // Get cron status information
        $cron_hook = 'aipkit_prune_logs_cron'; // From LogCronManager::HOOK_NAME
        $next_scheduled = wp_next_scheduled($cron_hook);
        $is_cron_active = $next_scheduled !== false;

        // Get last run time
        $last_run_option = get_option('aipkit_log_pruning_last_run', '');
        $last_run_time = $last_run_option ? $last_run_option : __('Never', 'gpt3-ai-content-generator');

        // Generate and return the HTML
        ob_start();
        ?>
        <div class="aipkit_setting_container aipkit_cron_status_panel">
            <span class="aipkit_chip_label"><?php esc_html_e('Status:', 'gpt3-ai-content-generator'); ?></span>
            <div class="aipkit_status_content">
                <div class="aipkit_status_indicator <?php echo ($enable_pruning && $is_cron_active) ? 'active' : 'inactive'; ?>"></div>
                <span class="aipkit_status_text <?php echo ($enable_pruning && $is_cron_active) ? 'active' : 'inactive'; ?>">
                    <?php 
                    if ($enable_pruning && $is_cron_active) {
                        esc_html_e('Scheduled', 'gpt3-ai-content-generator');
                    } elseif ($enable_pruning && !$is_cron_active) {
                        esc_html_e('Not Scheduled', 'gpt3-ai-content-generator');
                    } else {
                        esc_html_e('Disabled', 'gpt3-ai-content-generator');
                    }
                    ?>
                </span>
                <span class="aipkit_status_separator">•</span>
                <span class="aipkit_last_run_icon" title="<?php 
                    if ($last_run_time && $last_run_time !== __('Never', 'gpt3-ai-content-generator')) {
                        echo esc_attr(__('Last Clean Up: ', 'gpt3-ai-content-generator') . wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_run_time)));
                    } else {
                        echo esc_attr(__('Last Clean Up: Never', 'gpt3-ai-content-generator'));
                    }
                ?>">
                    <span class="dashicons dashicons-clock"></span>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
