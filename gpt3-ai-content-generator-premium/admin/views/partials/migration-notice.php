<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/partials/migration-notice.php
// Status: NEW FILE

/**
 * Partial: Migration Notification Banner
 * Informs users with old data about the new migration tool.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables passed from parent (dashboard.php):
// $migration_tool_url
?>
<div id="aipkit_migration_notice" class="aipkit_notification_bar aipkit_notification_bar--warning">
    <div class="aipkit_notification_bar__icon">
        <span class="dashicons dashicons-info"></span>
    </div>
    <div class="aipkit_notification_bar__content">
        <p>
            <strong><?php esc_html_e('Action Required: Legacy Data Detected', 'gpt3-ai-content-generator'); ?></strong><br>
            <?php esc_html_e('You have data from an older version. You can import your previous data or dismiss this notice to start fresh with a clean installation.', 'gpt3-ai-content-generator'); ?>
        </p>
    </div>
    <div class="aipkit_notification_bar__actions">
        <a href="<?php echo esc_url($migration_tool_url); ?>" class="aipkit_btn aipkit_btn-primary"><?php esc_html_e('Go to Migration Tool', 'gpt3-ai-content-generator'); ?></a>
        <button type="button" id="aipkit_dismiss_migration_notice_btn" class="aipkit_btn aipkit_btn-secondary"><?php esc_html_e('Dont Show Again', 'gpt3-ai-content-generator'); ?></button>
        <button type="button" class="aipkit_notification_bar__close" title="<?php esc_attr_e('Dismiss for now', 'gpt3-ai-content-generator'); ?>">×</button>
    </div>
</div>