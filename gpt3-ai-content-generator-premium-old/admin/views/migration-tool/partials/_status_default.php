<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_default.php
// Status: NEW FILE

/**
 * Partial: Migration Tool - Default/Unknown Status
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $aipkit_migration_status
?>
<p style="margin-top: 15px;">
    <?php esc_html_e('Current migration status: ', 'gpt3-ai-content-generator'); ?>
    <strong><?php echo esc_html($aipkit_migration_status); ?></strong>.
    <?php esc_html_e('If this seems incorrect, please contact support.', 'gpt3-ai-content-generator'); ?>
</p>