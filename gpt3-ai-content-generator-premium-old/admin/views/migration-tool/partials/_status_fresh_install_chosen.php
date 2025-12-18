<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_status_fresh_install_chosen.php
// Status: NEW FILE

/**
 * Partial: Migration Tool - Status Fresh Install Chosen
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="notice notice-success is-dismissible" style="margin-top: 15px;">
    <p><?php esc_html_e('You have chosen to start fresh. The plugin will use default settings. Old data (if any) has not been migrated but remains in your database.', 'gpt3-ai-content-generator'); ?></p>
</div>