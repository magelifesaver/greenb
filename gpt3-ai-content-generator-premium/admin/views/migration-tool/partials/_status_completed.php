<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_completed.php
// Status: NEW FILE

/**
 * Partial: Migration Tool - Status Completed
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="notice notice-success is-dismissible" style="margin-top: 15px;">
    <p><?php esc_html_e('Migration completed successfully!', 'gpt3-ai-content-generator'); ?></p>
    <?php // TODO: Add option for cleanup here if desired in the future. For now, the task is just to refactor. ?>
    <?php /*
    <p>
        <button id="aipkit_cleanup_old_data_btn" class="aipkit_btn aipkit_btn-secondary">
            <?php esc_html_e('Optional: Clean Up Old Data', 'gpt3-ai-content-generator'); ?>
        </button>
    </p>
    <p class="aipkit_form-help">
        <?php esc_html_e('This will attempt to delete old database tables and options from previous plugin versions. Ensure you have a backup before proceeding.', 'gpt3-ai-content-generator'); ?>
    </p>
    */ ?>
</div>