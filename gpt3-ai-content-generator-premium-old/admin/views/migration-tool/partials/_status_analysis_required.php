<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_analysis_required.php
// Status: NEW

/**
 * Partial: Migration Tool - Status Analysis Required
 * This view prompts the user to start the data analysis process.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $aipkit_old_plugin_version
?>
<div class="notice notice-warning" style="margin-top: 15px; padding-bottom: 15px;">
    <p><strong><?php esc_html_e('Action Required: Previous Data Detected', 'gpt3-ai-content-generator'); ?></strong></p>
    <p>
        <?php 
        /* translators: %s is the old plugin version */
        echo esc_html(sprintf(__('AI Power has detected data from a previous version (%s). The new interactive migration tool needs to analyze this data before you can migrate or delete it.', 'gpt3-ai-content-generator'), esc_html($aipkit_old_plugin_version)));?>
    </p>
    <p style="color: red; font-weight: bold;">
        <?php esc_html_e('CRITICAL: It is STRONGLY recommended to back up your WordPress database before proceeding with any data operation.', 'gpt3-ai-content-generator'); ?>
    </p>
</div>

<div class="aipkit_migration_options" style="margin-top: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
    <div class="aipkit_migration_option_card" style="flex: 1; min-width: 300px; border: 1px solid var(--aipkit_container-border); border-radius: 4px; padding: 20px; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <h4><?php esc_html_e('Option 1: Analyze & Migrate', 'gpt3-ai-content-generator'); ?></h4>
        <p><?php esc_html_e('Scan your old data to see a detailed report. From there, you can choose to migrate or delete each data category individually.', 'gpt3-ai-content-generator'); ?></p>
        <button id="aipkit_analyze_data_btn" class="aipkit_btn aipkit_btn-primary">
            <span class="aipkit_btn-text"><?php esc_html_e('Analyze Legacy Data', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>

    <div class="aipkit_migration_option_card" style="flex: 1; min-width: 300px; border: 1px solid var(--aipkit_container-border); border-radius: 4px; padding: 20px; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <h4><?php esc_html_e('Option 2: Start Fresh', 'gpt3-ai-content-generator'); ?></h4>
        <p><?php esc_html_e('Choose this to ignore old data and start with default settings. You can delete the old data later from the migration dashboard if you change your mind.', 'gpt3-ai-content-generator'); ?></p>
        <button id="aipkit_migrate_start_fresh_btn" class="aipkit_btn aipkit_btn-secondary">
            <span class="aipkit_btn-text"><?php esc_html_e('Start Fresh', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
    </div>
</div>