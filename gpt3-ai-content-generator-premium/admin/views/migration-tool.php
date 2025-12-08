<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/migration-tool.php
// Status: MODIFIED
// I have added a new step for 'Indexed Data (Knowledge Base)' to the migration tool's UI configuration.

/**
 * AIPKit Migration Tool - Admin View (Main Orchestrator)
 *
 * Provides the user interface for initiating and monitoring the data migration
 * from previous plugin versions. Now includes partials for different UI states.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\WP_AI_Content_Generator_Activator; // For migration status option constants

// --- PHP Variables Setup (remains in the main file) ---
$aipkit_migration_data_exists = get_option(WP_AI_Content_Generator_Activator::MIGRATION_DATA_EXISTS_OPTION, false);
$aipkit_migration_status = get_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION, 'not_started');
$aipkit_migration_last_error = get_option(WP_AI_Content_Generator_Activator::MIGRATION_LAST_ERROR_OPTION, '');
$aipkit_old_plugin_version = get_option(WP_AI_Content_Generator_Activator::MIGRATION_OLD_VERSION_OPTION, '1.x');
$analysis_results = get_option(WP_AI_Content_Generator_Activator::MIGRATION_ANALYSIS_RESULTS_OPTION, []);
$category_statuses = get_option(WP_AI_Content_Generator_Activator::MIGRATION_CATEGORY_STATUS_OPTION, []);


$migration_tool_nonce = wp_create_nonce('aipkit_migration_tool_action');

// This array defines the categories and their associated AJAX actions.
$migration_steps = [
    'global_settings' => [
        'label' => __('Global, Provider & API Settings', 'gpt3-ai-content-generator'),
        'action' => 'aipkit_migrate_global_settings',
        'delete_action' => 'aipkit_delete_old_global_settings',
    ],
    'chatbot_data' => [
        'label' => __('Chatbots', 'gpt3-ai-content-generator'),
        'action' => 'aipkit_migrate_chatbot_data',
        'delete_action' => 'aipkit_delete_old_chatbot_data',
    ],
    'cpt_data' => [
        'label' => __('AI Forms & Other Legacy Data', 'gpt3-ai-content-generator'),
        'action' => 'aipkit_migrate_cpt_data',
        'delete_action' => 'aipkit_delete_old_cpt_data',
    ],
    'indexed_data' => [
        'label' => __('Indexed Data (Knowledge Base)', 'gpt3-ai-content-generator'),
        'action' => 'aipkit_migrate_indexed_data',
        'delete_action' => 'aipkit_delete_old_indexed_data',
    ],
];

?>
<div class="aipkit_container aipkit_migration_tool_container" id="aipkit_migration_tool_container">
    <?php include __DIR__ . '/migration-tool/partials/_header.php'; ?>

    <div class="aipkit_container-body">
        <?php include __DIR__ . '/migration-tool/partials/_messages_area.php'; ?>

        <?php
        // --- Conditional Display of Status Blocks (REVISED) ---
        if ($aipkit_migration_status === 'analysis_required') {
            include __DIR__ . '/migration-tool/partials/_status_analysis_required.php';
        } elseif ($aipkit_migration_status === 'analysis_complete' || str_starts_with($aipkit_migration_status, 'in_progress_') || str_starts_with($aipkit_migration_status, 'failed_')) {
            include __DIR__ . '/migration-tool/partials/_status_analysis_results.php';
        } elseif ($aipkit_migration_status === 'completed') {
            include __DIR__ . '/migration-tool/partials/_status_completed.php';
        } elseif ($aipkit_migration_status === 'fresh_install_chosen') {
            include __DIR__ . '/migration-tool/partials/_status_fresh_install_chosen.php';
        } elseif (!$aipkit_migration_data_exists && $aipkit_migration_status === 'not_applicable') {
            include __DIR__ . '/migration-tool/partials/_status_not_applicable.php';
        } else {
            // Fallback for any other/unexpected status
            include __DIR__ . '/migration-tool/partials/_status_default.php';
        }
// --- End Conditional Display ---
?>
    </div>
</div>

<?php include __DIR__ . '/migration-tool/partials/_javascript.php'; ?>