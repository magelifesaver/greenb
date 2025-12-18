<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/index.php
// Status: MODIFIED

/**
 * AIPKit AutoGPT Module - Main View
 * UPDATED: Re-architected into a three-column layout with a central tabbed input panel and action bar.
 * MODIFIED: Moved template controls to the left column and status indicators to the right column.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers; // For AI models
use WPAICG\AIPKIT_AI_Settings; // For AI parameters
use WPAICG\aipkit_dashboard; // For addon status

// --- Variable Definitions for Partials ---
$post_types_args = ['public' => true];
$all_post_types = get_post_types($post_types_args, 'objects');
$all_selectable_post_types = array_filter($all_post_types, function ($pt_obj) {
    return $pt_obj->name !== 'attachment';
});

$openai_vector_stores = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
}
$pinecone_indexes = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
}
$qdrant_collections = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
}
$openai_embedding_models = [];
$google_embedding_models = [];
if (class_exists(AIPKit_Providers::class)) {
    $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
    $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
}


$task_categories = [
    '' => __('-- Select Category --', 'gpt3-ai-content-generator'),
    'content_creation' => __('Create New Content', 'gpt3-ai-content-generator'),
    'content_enhancement' => __('Update Existing Content', 'gpt3-ai-content-generator'),
    'knowledge_base' => __('Knowledge Base', 'gpt3-ai-content-generator'),
    'community_engagement' => __('Engagement', 'gpt3-ai-content-generator'),
];
$frequencies = [
    'one-time' => __('One-time', 'gpt3-ai-content-generator'),
    'aipkit_five_minutes' => __('Every 5 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_fifteen_minutes' => __('Every 15 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_thirty_minutes' => __('Every 30 Minutes', 'gpt3-ai-content-generator'),
    'hourly' => __('Hourly', 'gpt3-ai-content-generator'),
    'twicedaily' => __('Twice Daily', 'gpt3-ai-content-generator'),
    'daily' => __('Daily', 'gpt3-ai-content-generator'),
    'weekly' => __('Weekly', 'gpt3-ai-content-generator'),
];

$is_pro = aipkit_dashboard::is_pro_plan(); // Define is_pro for partials

// For Content Writing Task Type
$cw_providers_for_select = ['OpenAI', 'OpenRouter', 'Google', 'Azure'];
if (aipkit_dashboard::is_addon_active('deepseek')) {
    $cw_providers_for_select[] = 'DeepSeek';
}
if ($is_pro && aipkit_dashboard::is_addon_active('ollama')) {
    $cw_providers_for_select[] = 'Ollama';
}
$cw_ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();
$cw_default_temperature = $cw_ai_parameters['temperature'] ?? 1.0;
$cw_default_max_tokens = $cw_ai_parameters['max_completion_tokens'] ?? 4000;
$cw_available_post_types = get_post_types(['public' => true], 'objects');
unset($cw_available_post_types['attachment']);
$cw_current_user_id = get_current_user_id();
// Minimal safeguard: avoid loading thousands of users into selects.
// Load up to a small, reasonable cap and ensure current user is always present.
$__aipkit_user_list_cap = 200;
$cw_users_for_author = get_users([
    'orderby' => 'display_name',
    'order'   => 'ASC',
    'fields'  => ['ID', 'display_name'],
    'number'  => $__aipkit_user_list_cap,
]);
if ($cw_current_user_id) {
    $has_current_user = false;
    foreach ($cw_users_for_author as $u) {
        if ((int) $u->ID === (int) $cw_current_user_id) { $has_current_user = true; break; }
    }
    if (!$has_current_user) {
        $u = get_user_by('id', $cw_current_user_id);
        if ($u && isset($u->ID)) {
            $cw_users_for_author[] = (object) [
                'ID' => (int) $u->ID,
                'display_name' => (string) $u->display_name,
            ];
        }
    }
}
$cw_post_statuses = [
    'draft' => __('Draft', 'gpt3-ai-content-generator'),
    'publish' => __('Publish', 'gpt3-ai-content-generator'),
    'pending' => __('Pending Review', 'gpt3-ai-content-generator'),
    'private' => __('Private', 'gpt3-ai-content-generator'),
];
$cw_wp_categories = get_categories(['hide_empty' => false]);

$aipkit_task_statuses_for_select = [ // This was used for the task status dropdown
    'active' => __('Active', 'gpt3-ai-content-generator'),
    'paused' => __('Paused', 'gpt3-ai-content-generator'),
];
// --- End Variable Definitions ---

?>
<div class="aipkit_container aipkit_module_autogpt" id="aipkit_autogpt_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('Automate', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_container-actions">
            <button id="aipkit_add_new_task_btn" class="aipkit_btn aipkit_btn-primary">
                <span class="dashicons dashicons-plus-alt2" style="margin-top:0px;"></span>
                <?php esc_html_e('Add New Task', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
    </div>

    <?php include __DIR__ . '/partials/task-automation-ui.php'; // Include the main UI partial?>
</div>
