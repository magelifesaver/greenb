<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_analysis_results.php
// Status: MODIFIED
// I have added a new section to display the old WooCommerce custom prompts for easy copying.

/**
 * Partial: Migration Tool - Analysis Results Dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $analysis_results, $category_statuses, $migration_steps, $aipkit_migration_status, $aipkit_migration_last_error
?>
<p style="margin-top: 15px;"><?php esc_html_e('The analysis is complete. Review the items found below and choose an action for each category. You can migrate data to the new format or permanently delete the old data.', 'gpt3-ai-content-generator'); ?></p>
<p style="font-weight: bold;"><?php esc_html_e('Note: Deleting old data is a permanent action and cannot be undone.', 'gpt3-ai-content-generator'); ?></p>
<?php if (str_starts_with($aipkit_migration_status, 'failed_')) : ?>
    <div class="notice notice-error" style="margin-top: 15px;">
        <p><strong><?php esc_html_e('Migration Failed!', 'gpt3-ai-content-generator'); ?></strong></p>
        <p><?php esc_html_e('An error occurred during the migration process.', 'gpt3-ai-content-generator'); ?></p>
        <?php if (!empty($aipkit_migration_last_error)): ?>
            <p><strong><?php esc_html_e('Error details:', 'gpt3-ai-content-generator'); ?></strong><br><code style="white-space: pre-wrap;"><?php echo esc_html($aipkit_migration_last_error); ?></code></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="aipkit_data-table" style="margin-top: 20px;">
    <table>
        <thead>
            <tr>
                <th><?php esc_html_e('Data Category', 'gpt3-ai-content-generator'); ?></th>
                <th><?php esc_html_e('Analysis Details', 'gpt3-ai-content-generator'); ?></th>
                <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                <th><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
            </tr>
        </thead>
        <tbody id="aipkit_migration_dashboard_tbody">
            <?php foreach ($migration_steps as $category_key => $step_info):
                $category_result = (isset($analysis_results[$category_key]) && is_array($analysis_results[$category_key])) ? $analysis_results[$category_key] : ['summary' => __('Analysis data missing.', 'gpt3-ai-content-generator'), 'details' => [], 'count' => 0];
                $analysis_summary = $category_result['summary'] ?? __('No data found.', 'gpt3-ai-content-generator');
                $analysis_details_array = $category_result['details'] ?? [];
                $status = $category_statuses[$category_key] ?? 'pending';
                $status_text = match($status) {
                    'pending' => __('Pending', 'gpt3-ai-content-generator'),
                    'migrated' => __('Migrated', 'gpt3-ai-content-generator'),
                    'deleted' => __('Data Deleted', 'gpt3-ai-content-generator'),
                    'failed' => __('Failed', 'gpt3-ai-content-generator'),
                    'in_progress' => __('In Progress...', 'gpt3-ai-content-generator'),
                    default => ucfirst($status),
                };
                $status_class = match($status) {
                    'pending' => 'aipkit_status-info',
                    'migrated', 'deleted' => 'aipkit_status-success',
                    'failed' => 'aipkit_status-warning',
                    'in_progress' => '',
                    default => '',
                };
                $is_in_progress = ($status === 'in_progress');
                $has_data = !empty($category_result['count']);
                $buttons_disabled = $is_in_progress || !$has_data;
                ?>
            <tr id="aipkit_migration_row_<?php echo esc_attr($category_key); ?>" data-category="<?php echo esc_attr($category_key); ?>">
                <td><strong><?php echo esc_html($step_info['label']); ?></strong></td>
                <td>
                    <?php echo esc_html($analysis_summary); ?>
                    <?php if (!empty($analysis_details_array)): ?>
                        <ul style="font-size: 11px; color: #666; margin-top: 5px; max-height: 80px; overflow-y: auto; background: #f9f9f9; padding: 5px; border-radius: 3px;">
                            <?php foreach ($analysis_details_array as $detail): ?>
                                <li><?php echo esc_html($detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="aipkit_status-tag <?php echo esc_attr($status_class); ?>" id="aipkit_migration_status_<?php echo esc_attr($category_key); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                    <span class="aipkit_spinner" style="display:none;"></span>
                </td>
                <td class="aipkit_migration_actions">
                    <button class="aipkit_btn aipkit_btn-primary aipkit_btn-small aipkit_migrate_category_btn" data-category="<?php echo esc_attr($category_key); ?>" <?php disabled($buttons_disabled); ?>>
                        <?php esc_html_e('Migrate', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button class="aipkit_btn aipkit_btn-danger aipkit_btn-small aipkit_delete_category_btn" data-category="<?php echo esc_attr($category_key); ?>" <?php disabled($buttons_disabled); ?>>
                        <?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$custom_prompts_data = $analysis_results['custom_prompts'] ?? [];
if (!empty($custom_prompts_data['prompts']) && is_array($custom_prompts_data['prompts'])):
    ?>
<div class="aipkit_migration_prompts_section" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
    <h3 style="margin-top: 0;"><?php esc_html_e('Your Old Custom Prompts', 'gpt3-ai-content-generator'); ?></h3>
    <p><?php esc_html_e('Your old custom prompts could not be migrated automatically. You can copy them from here and paste them into the new templates in the Content Writer or AutoGPT modules.', 'gpt3-ai-content-generator'); ?></p>
    <?php foreach ($custom_prompts_data['prompts'] as $prompt_key => $prompt_data): ?>
    <div class="aipkit_form-group" style="margin-top: 15px;">
        <label class="aipkit_form-label" for="aipkit_migrated_prompt_<?php echo esc_attr($prompt_key); ?>"><strong><?php echo esc_html($prompt_data['label']); ?></strong></label>
        <textarea id="aipkit_migrated_prompt_<?php echo esc_attr($prompt_key); ?>" class="large-text" rows="6" readonly><?php echo esc_textarea($prompt_data['value']); ?></textarea>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
    $integration_data = $analysis_results['integration_data'] ?? [];
if (!empty($integration_data['integrations']) && is_array($integration_data['integrations'])):
    ?>
<div class="aipkit_migration_integrations_section" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
    <h3 style="margin-top: 0;"><?php esc_html_e('Your Old Integration Data', 'gpt3-ai-content-generator'); ?></h3>
    <p><?php esc_html_e('Your old Google Sheets and RSS feed data could not be migrated automatically. You can copy the data below and re-enter it into the "Automate" module to create new content writing tasks.', 'gpt3-ai-content-generator'); ?></p>
    <?php foreach ($integration_data['integrations'] as $int_key => $int_data): ?>
    <div class="aipkit_form-group" style="margin-top: 15px;">
        <label class="aipkit_form-label" for="aipkit_migrated_integration_<?php echo esc_attr($int_key); ?>"><strong><?php echo esc_html($int_data['label']); ?></strong></label>
        <textarea id="aipkit_migrated_integration_<?php echo esc_attr($int_key); ?>" class="large-text" rows="<?php echo ($int_data['is_json'] ?? false) ? '8' : '4'; ?>" readonly><?php echo esc_textarea($int_data['value']); ?></textarea>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
    $woocommerce_prompts_data = $analysis_results['woocommerce_prompts'] ?? [];
if (!empty($woocommerce_prompts_data['prompts']) && is_array($woocommerce_prompts_data['prompts'])):
    ?>
<div class="aipkit_migration_prompts_section" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
    <h3 style="margin-top: 0;"><?php esc_html_e('Your Old WooCommerce Custom Prompts', 'gpt3-ai-content-generator'); ?></h3>
    <p><?php esc_html_e('Your old custom WooCommerce prompts could not be migrated automatically. You can copy them from here and paste them into the new templates in the Content Writer module.', 'gpt3-ai-content-generator'); ?></p>
    <?php foreach ($woocommerce_prompts_data['prompts'] as $prompt_key => $prompt_data): ?>
    <div class="aipkit_form-group" style="margin-top: 15px;">
        <label class="aipkit_form-label" for="aipkit_migrated_wc_prompt_<?php echo esc_attr($prompt_key); ?>"><strong><?php echo esc_html($prompt_data['label']); ?></strong></label>
        <textarea id="aipkit_migrated_wc_prompt_<?php echo esc_attr($prompt_key); ?>" class="large-text" rows="6" readonly><?php echo esc_textarea($prompt_data['value']); ?></textarea>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>


<!-- Footer Actions -->
<div class="aipkit_migration_actions_footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: right;">
    <button id="aipkit_analyze_again_btn" class="aipkit_btn aipkit_btn-secondary">
        <span class="dashicons dashicons-update-alt"></span>
        <span class="aipkit_btn-text"><?php esc_html_e('Analyze Again', 'gpt3-ai-content-generator'); ?></span>
        <span class="aipkit_spinner" style="display:none;"></span>
    </button>
    <button id="aipkit_migrate_start_fresh_btn" class="aipkit_btn aipkit_btn-danger" style="margin-left: 10px;">
        <span class="aipkit_btn-text"><?php esc_html_e('Start Fresh & Ignore All Old Data', 'gpt3-ai-content-generator'); ?></span>
    </button>
</div>
<p class="aipkit_form-help" style="text-align: right;"><?php esc_html_e('Use "Start Fresh" to ignore all legacy data. This action cannot be undone.', 'gpt3-ai-content-generator'); ?></p>