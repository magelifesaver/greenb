<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool/partials/_javascript.php
// Status: MODIFIED

/**
 * Partial: Migration Tool - JavaScript Enqueue and Localization
 * Enqueues the dedicated JS bundle for the migration tool and localizes PHP data.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables from parent: $migration_tool_nonce, $migration_steps, $analysis_results, $category_statuses

// Enqueue the dedicated script for the migration tool
$migration_tool_js_handle = 'aipkit-migration-tool-script';
$migration_tool_js_path = WPAICG_PLUGIN_URL . 'dist/js/admin-migration-tool.bundle.js';
$migration_tool_js_version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';

wp_register_script(
    $migration_tool_js_handle,
    $migration_tool_js_path,
    ['aipkit-admin-main', 'wp-i18n'], // Depends on main admin bundle for global utils & i18n
    $migration_tool_js_version,
    true
);
wp_enqueue_script($migration_tool_js_handle);
wp_set_script_translations($migration_tool_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');


// Localize data for the migration tool script
wp_localize_script($migration_tool_js_handle, 'aipkitMigrationToolData', [
    'nonce' => $migration_tool_nonce,
    'steps' => $migration_steps,
    'analysisResults' => $analysis_results, // Pass analysis results
    'categoryStatuses' => $category_statuses, // Pass current category statuses
    'text' => [
        'confirmStartFresh' => esc_js(__('Are you sure? This will NOT import any of your old settings or data. The plugin will start with default configurations.', 'gpt3-ai-content-generator')),
        'confirmDeleteData' => esc_js(__('Are you sure you want to PERMANENTLY delete the old data for this category? This action cannot be undone.', 'gpt3-ai-content-generator')),
        'processing' => esc_js(__('Processing...', 'gpt3-ai-content-generator')),
        'analyzing' => esc_js(__('Analyzing...', 'gpt3-ai-content-generator')),
        'deleting' => esc_js(__('Deleting...', 'gpt3-ai-content-generator')),
        'migrating' => esc_js(__('Migrating...', 'gpt3-ai-content-generator')),
        'startFreshButton' => esc_js(__('Start Fresh', 'gpt3-ai-content-generator')),
        'migrationComplete' => esc_js(__('Migration completed successfully!', 'gpt3-ai-content-generator')),
        'choiceProcessedReloading' => esc_js(__('Choice processed. Reloading page...', 'gpt3-ai-content-generator')),
        'statusPending' => esc_js(__('Pending', 'gpt3-ai-content-generator')),
        'statusMigrated' => esc_js(__('Migrated', 'gpt3-ai-content-generator')),
        'statusDeleted' => esc_js(__('Data Deleted', 'gpt3-ai-content-generator')),
        'statusFailed' => esc_js(__('Failed', 'gpt3-ai-content-generator')),
        'statusInProgress' => esc_js(__('In Progress...', 'gpt3-ai-content-generator')),
        'analyzeAgain' => esc_js(__('Analyze Again', 'gpt3-ai-content-generator')), // ADDED
    ]
]);

?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.aipkit_initMigrationTool === 'function') {
            window.aipkit_initMigrationTool();
        } else {
            console.error('AIPKit Migration Tool: Main initializer function (aipkit_initMigrationTool) not found.');
            const messagesArea = document.getElementById('aipkit_migration_messages_area');
            if (messagesArea) {
                messagesArea.innerHTML = '<div class="notice notice-error"><p>Critical Error: Migration tool script failed to load.</p></div>';
            }
        }
    });
</script>