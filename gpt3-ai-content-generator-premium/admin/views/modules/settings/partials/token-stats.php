<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/token-stats.php
// UPDATED FILE - Corrected method name call

/**
 * Partial: Token Usage Statistics
 *
 * Displays the stats overview cards and chart container for the AI Settings page.
 * UPDATED: Displays actual stats data fetched from AIPKit_Stats.
 * UPDATED: Changed chart placeholder to a chart container div.
 * REVISED: Displays stats for overall usage, not just chat. Shows most used module.
 * FIXED: Corrected the method name called to get the most used module.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\Stats\AIPKit_Stats; // Use renamed stats class

// Variables passed from parent settings/index.php:
// $stats_error_message (string|null)
// $stats_data (array|WP_Error) - contains ['days_period', 'total_tokens', 'total_interactions', 'avg_tokens_per_interaction', 'module_counts']

$days_period = 30; // Default
$total_tokens_used = 0;
$total_interactions = 0;
$avg_tokens_per_interaction = 0;
$most_used_module = __('N/A', 'gpt3-ai-content-generator');

if (is_array($stats_data)) {
    $days_period = $stats_data['days_period'] ?? 30;
    $total_tokens_used = $stats_data['total_tokens'] ?? 0;
    $total_interactions = $stats_data['total_interactions'] ?? 0;
    $avg_tokens_per_interaction = $stats_data['avg_tokens_per_interaction'] ?? 0;
    $module_counts = $stats_data['module_counts'] ?? [];

    // Instantiate the calculator to use its helper method
    // (Ensure class exists - should be loaded by parent)
    if (class_exists('\\WPAICG\\Stats\\AIPKit_Stats')) {
         $stats_calculator = new AIPKit_Stats();
         // *** FIXED: Call the correct method name ***
         $most_used_module_key = $stats_calculator->get_most_used_module($module_counts);
         // *** END FIX ***
         $most_used_module = $most_used_module_key ?: __('(No usage)', 'gpt3-ai-content-generator');
    } else {
         $most_used_module = __('Error', 'gpt3-ai-content-generator'); // Indicate error if class missing
    }

} elseif (is_wp_error($stats_data)) {
     // Error message handled below
     $stats_error_message = $stats_data->get_error_message();
}

?>
<div class="aipkit_settings_column aipkit_settings_column-right aipkit_sub_container">
    <div class="aipkit_sub_container_header">
        <div class="aipkit_sub_container_title"><?php echo esc_html__('Usage Overview', 'gpt3-ai-content-generator'); // Changed Title ?></div>
    </div>
    <div class="aipkit_sub_container_body">
        <?php if ($stats_error_message): ?>
            <div class="aipkit_notice aipkit_notice-warning">
            <?php
                // translators: %s is the error message explaining why stats could not be calculated
                echo '<p>' . esc_html( sprintf( __('Error calculating stats: %s', 'gpt3-ai-content-generator'), $stats_error_message ) ) . '</p>';
                ?>
            </div>
        <?php else: ?>
            <?php if (empty($stats_notice_message)) : ?>
            <div class="aipkit_form-row" style="align-items:center; justify-content: space-between; margin-bottom: 10px;">
                <p class="aipkit_form-help" style="margin: 0;">
                <?php
                // translators: %d is the number of days used in the statistics range
                echo esc_html( sprintf( __('Total token usage statistics across all modules for the last %d days.', 'gpt3-ai-content-generator'), $days_period ) );
                ?>
                <?php if ($total_interactions > 10000): ?>
                    <em><?php esc_html_e('(Stats calculation might be slow due to high volume)', 'gpt3-ai-content-generator'); ?></em>
                <?php endif; ?>
                </p>
                <div class="aipkit_form-group" style="display:flex; gap:6px; align-items:center;">
                    <select id="aipkit_stats_period_select" class="aipkit_select">
                        <?php $period_options = [3, 7, 14, 30, 90]; $current = (int) ($days_period ?: 3); foreach ($period_options as $opt): ?>
                            <option value="<?php echo (int) $opt; ?>" <?php selected($current, $opt); ?>><?php echo esc_html( sprintf(_n('%d day', '%d days', $opt, 'gpt3-ai-content-generator'), $opt) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($stats_notice_message)) : ?>
                <div class="aipkit_notice aipkit_notice-info">
                    <?php echo esc_html($stats_notice_message); ?>
                    <a href="<?php echo esc_url('https://docs.aipower.org/docs/logs#auto-delete-logs-pruning'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Read more', 'gpt3-ai-content-generator'); ?></a>
                </div>
            <?php else: ?>

            <div class="aipkit_stats_overview_grid">
                 <!-- Stat Card: Total Tokens -->
                <div class="aipkit_stats_overview_card">
                    <div class="aipkit_stats_overview_label"><?php esc_html_e('Total Tokens Used', 'gpt3-ai-content-generator'); ?></div>
                    <div class="aipkit_stats_overview_value"><?php echo ($total_tokens_used !== null) ? esc_html(number_format_i18n((int)$total_tokens_used)) : esc_html__('N/A', 'gpt3-ai-content-generator'); ?></div>
                     <div class="aipkit_stats_overview_details"><?php 
                     // translators: %d is the number of days used in the stats period
                     echo esc_html(sprintf(__('(Last %d days)', 'gpt3-ai-content-generator'), $days_period)); 
                     ?>
                     </div>
                </div>
                <!-- Stat Card: Avg Tokens / Interaction -->
                <div class="aipkit_stats_overview_card">
                    <div class="aipkit_stats_overview_label"><?php esc_html_e('Avg Tokens / Interaction', 'gpt3-ai-content-generator'); // Changed label ?></div>
                    <div class="aipkit_stats_overview_value"><?php echo ($avg_tokens_per_interaction !== null) ? esc_html(number_format_i18n((int)$avg_tokens_per_interaction)) : esc_html__('N/A', 'gpt3-ai-content-generator'); ?></div>
                     <div class="aipkit_stats_overview_details"><?php echo ($avg_tokens_per_interaction !== null) ? esc_html__('(Estimate)', 'gpt3-ai-content-generator') : esc_html__('(Not available for large datasets)', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <!-- Stat Card: Most Used Module -->
                <div class="aipkit_stats_overview_card">
                    <div class="aipkit_stats_overview_label"><?php esc_html_e('Most Used Module', 'gpt3-ai-content-generator'); // Changed label ?></div>
                    <div class="aipkit_stats_overview_value"><?php echo esc_html($most_used_module); ?></div> <?php // Uses the corrected variable ?>
                    <div class="aipkit_stats_overview_details">
                    <?php 
                        // translators: %d is the number of days used in the stats period
                        echo esc_html(sprintf(__('(By interactions, last %d days)', 'gpt3-ai-content-generator'), $days_period)); 
                    ?>
                    </div>
                </div>
            </div>

            <!-- Chart Container -->
            <div id="aipkit_token_usage_chart_container" class="aipkit_token_usage_chart_container" data-default-days="<?php echo (int) ($days_period ?: 3); ?>">
                <!-- Chart will be rendered here by JS -->
                <div class="aipkit_chart_loading_placeholder">
                     <span class="aipkit_spinner" style="display:inline-block;"></span>
                     <?php esc_html_e('Loading chart data...', 'gpt3-ai-content-generator'); ?>
                 </div>
                 <div class="aipkit_chart_error_placeholder" style="display: none;"></div>
                 <div class="aipkit_chart_nodata_placeholder" style="display: none;"></div>
            </div>
            <?php endif; ?>
        <?php endif; // end error check ?>
     </div><!-- / .aipkit_sub_container_body -->
</div><!-- / Right Column -->
