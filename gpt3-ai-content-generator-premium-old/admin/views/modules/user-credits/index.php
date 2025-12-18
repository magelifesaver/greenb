<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/user-credits/index.php
// Status: MODIFIED

/**
 * AIPKit User Credits / Token Management Module - Admin View
 *
 * Allows site owners to view and manage token usage for registered users.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Prepare nonce for potential future actions (like edit/reset)
$user_credits_nonce = wp_create_nonce('aipkit_user_credits_nonce');

?>
<div class="aipkit_container aipkit_user_credits_container" id="aipkit_user_credits_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('User Credits & Usage', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_container-actions">
            <!-- Shortcode Configurator -->
            <div class="aipkit_user_credits_shortcode_config_wrapper">
                 <div class="aipkit_shortcode_display_wrapper">
                    <code id="aipkit_token_usage_shortcode_snippet" title="<?php esc_attr_e('Click to copy shortcode', 'gpt3-ai-content-generator'); ?>">
                        [aipkit_token_usage]
                    </code>
                    <button type="button" id="aipkit_token_usage_shortcode_settings_toggle" class="aipkit_icon_btn" title="<?php esc_attr_e('Configure Shortcode', 'gpt3-ai-content-generator'); ?>" aria-expanded="false">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </button>
                </div>
                <div id="aipkit_token_usage_shortcode_configurator" class="aipkit_shortcode_configurator" style="display: none;">
                     <div class="aipkit_config_section">
                        <h6 class="aipkit_config_section_title"><?php esc_html_e('Modules to Show', 'gpt3-ai-content-generator'); ?></h6>
                        <div class="aipkit_config_options_grid">
                             <label class="aipkit_config_item">
                                <input type="checkbox" name="cfg_show_chatbot" class="aipkit_config_input" value="1" checked>
                                <span><?php esc_html_e('Chatbot', 'gpt3-ai-content-generator'); ?></span>
                            </label>
                            <label class="aipkit_config_item">
                                <input type="checkbox" name="cfg_show_aiforms" class="aipkit_config_input" value="1" checked>
                                <span><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></span>
                            </label>
                            <label class="aipkit_config_item">
                                <input type="checkbox" name="cfg_show_imagegenerator" class="aipkit_config_input" value="1" checked>
                                <span><?php esc_html_e('Image Generator', 'gpt3-ai-content-generator'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Refresh Button -->
            <button id="aipkit_refresh_credits_btn" class="aipkit_btn aipkit_btn-secondary" title="<?php esc_attr_e('Refresh Data', 'gpt3-ai-content-generator'); ?>">
                 <span class="dashicons dashicons-update-alt"></span>
            </button>
        </div>
    </div>
    <div class="aipkit_container-body">

        <!-- Stats Cards Placeholder -->
        <div class="aipkit_stats-grid" id="aipkit_user_credits_stats_grid" style="margin-bottom: 25px;">
             <div class="aipkit_stat-card">
                <div class="aipkit_stat-title"><?php esc_html_e('Total Users Tracked', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_stat-value" id="aipkit_stat_total_users">-</div>
            </div>
             <div class="aipkit_stat-card">
                <div class="aipkit_stat-title"><?php esc_html_e('Total Tokens Used', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_stat-value" id="aipkit_stat_total_tokens">-</div>
            </div>
            <div class="aipkit_stat-card">
                <div class="aipkit_stat-title"><?php esc_html_e('Average Usage / User', 'gpt3-ai-content-generator'); ?></div>
                <div class="aipkit_stat-value" id="aipkit_stat_avg_tokens">-</div>
            </div>
        </div>

        <!-- NEW: Token Usage Chart -->
        <div id="aipkit_user_credits_chart_container" class="aipkit_sub_container" style="margin-bottom: 25px;">
             <div class="aipkit_sub_container_header">
                <div class="aipkit_sub_container_title"><?php esc_html_e('Daily Token Usage Trend', 'gpt3-ai-content-generator'); ?></div>
            </div>
             <div class="aipkit_sub_container_body">
                 <p class="aipkit_form-help" style="margin-top:0; margin-bottom: 15px;">
                    <?php esc_html_e('Total token usage by all users across all modules for the last 30 days.', 'gpt3-ai-content-generator'); ?>
                </p>
                <div id="aipkit_token_usage_chart_container_credits" class="aipkit_token_usage_chart_container" style="min-height: 250px; margin-top:0;">
                    <div class="aipkit_chart_loading_placeholder">
                         <span class="aipkit_spinner" style="display:inline-block;"></span>
                         <?php esc_html_e('Loading chart data...', 'gpt3-ai-content-generator'); ?>
                     </div>
                     <div class="aipkit_chart_error_placeholder" style="display: none;"></div>
                     <div class="aipkit_chart_nodata_placeholder" style="display: none;"></div>
                </div>
             </div>
        </div>
        <!-- END: Token Usage Chart -->

        <!-- Filters Placeholder -->
         <div class="aipkit_user_credits_filters" style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid var(--aipkit_container-border);">
            <input type="text" id="aipkit_user_search" class="aipkit_form-input" placeholder="<?php esc_attr_e('Search by Username or Email...', 'gpt3-ai-content-generator'); ?>" style="max-width: 300px; display: inline-block; margin-right: 10px;">
            <button id="aipkit_user_search_btn" class="aipkit_btn aipkit_btn-secondary"><?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?></button>
            <button id="aipkit_user_reset_search_btn" class="aipkit_btn aipkit_btn-secondary" style="margin-left: 5px; display: none;"><?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?></button>
         </div>

        <!-- User Credits Table -->
        <div class="aipkit_data-table aipkit_user_credits_table_wrapper">
            <table id="aipkit_user_credits_table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'gpt3-ai-content-generator'); ?></th>
                        <th style="width: 150px;"><?php esc_html_e('Token Balance', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Periodic Tokens Used', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Usage Details', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Last Reset', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                    </tr>
                </thead>
                <tbody id="aipkit_user_credits_table_body">
                    <!-- Rows will be populated by JS -->
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">
                            <span class="aipkit_spinner" style="display:inline-block; width: 20px; height: 20px;"></span>
                            <?php esc_html_e('Loading user credits...', 'gpt3-ai-content-generator'); ?>
                        </td>
                    </tr>
                </tbody>
                <tfoot style="display: none;"> <?php // Hide footer initially?>
                    <tr>
                        <th colspan="6">
                             <div class="aipkit_pagination" id="aipkit_user_credits_pagination">
                                <!-- Pagination controls will be populated by JS -->
                            </div>
                        </th>
                    </tr>
                </tfoot>
            </table>
            <div id="aipkit_user_credits_no_results" style="display: none; text-align: center; padding: 20px; color: var(--aipkit_text-secondary);">
                <?php esc_html_e('No user token data found.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>

    </div><!-- /.aipkit_container-body -->
</div><!-- /.aipkit_container -->

<?php // Add nonce value for JS?>
<input type="hidden" id="aipkit_user_credits_nonce_field" value="<?php echo esc_attr($user_credits_nonce); ?>">