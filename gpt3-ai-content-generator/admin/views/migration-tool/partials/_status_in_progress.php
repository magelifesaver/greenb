<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_in_progress.php
// Status: NEW FILE

/**
 * Partial: Migration Tool - Status In Progress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $aipkit_migration_status
?>
<div class="notice notice-info" style="margin-top: 15px;">
    <p><?php esc_html_e('Migration is currently in progress or has been initiated. Please do not close this window or navigate away unless the process completes or fails.', 'gpt3-ai-content-generator'); ?></p>
    <p><strong><?php esc_html_e('Current status:', 'gpt3-ai-content-generator'); ?></strong> <span id="aipkit_migration_current_status_display"><?php echo esc_html($aipkit_migration_status); ?></span></p>
</div>
<div id="aipkit_migration_progress_bar_container" style="width: 100%; background-color: #e0e0e0; border-radius: 4px; margin-top: 15px; display:none;">
    <div id="aipkit_migration_progress_bar" style="width: 0%; height: 20px; background-color: var(--aipkit_brand-primary); border-radius: 4px; text-align: center; color: white; line-height: 20px;">0%</div>
</div>