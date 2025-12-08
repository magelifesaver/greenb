<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_failed.php
// Status: NEW FILE

/**
 * Partial: Migration Tool - Status Failed
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $aipkit_migration_last_error
?>
<div class="notice notice-error" style="margin-top: 15px;">
    <p><strong><?php esc_html_e('Migration Failed!', 'gpt3-ai-content-generator'); ?></strong></p>
    <p><?php esc_html_e('An error occurred during the migration process.', 'gpt3-ai-content-generator'); ?></p>
    <?php if (!empty($aipkit_migration_last_error)): ?>
        <p><strong><?php esc_html_e('Error details:', 'gpt3-ai-content-generator'); ?></strong><br><code style="white-space: pre-wrap;"><?php echo esc_html($aipkit_migration_last_error); ?></code></p>
    <?php endif; ?>
    <p><?php esc_html_e('Please check your server error logs for more details. You may need to restore from your backup or contact support.', 'gpt3-ai-content-generator'); ?></p>
    <button id="aipkit_retry_failed_step_btn" class="aipkit_btn aipkit_btn-secondary" style="margin-top:10px;">
        <span class="aipkit_btn-text"><?php esc_html_e('Retry Failed Step', 'gpt3-ai-content-generator'); ?></span>
        <span class="aipkit_spinner" style="display:none;"></span>
    </button>
</div>