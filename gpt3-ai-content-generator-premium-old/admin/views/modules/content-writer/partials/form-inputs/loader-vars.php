<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs/loader-vars.php
// Status: MODIFIED
/**
 * Partial: Content Writer Form Inputs - Shared Variable Loader
 * Defines variables used across multiple form input partials.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\AIPKit_Providers;
use WPAICG\aipkit_dashboard;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;

// Variable definitions
$is_pro = aipkit_dashboard::is_pro_plan();
$default_provider_config = AIPKit_Providers::get_default_provider_config();
$default_provider = strtolower($default_provider_config['provider'] ?? 'openai');
$deepseek_addon_active = aipkit_dashboard::is_addon_active('deepseek');
$ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();
$default_temperature = $ai_parameters['temperature'] ?? 1.0;
$default_max_tokens = $ai_parameters['max_completion_tokens'] ?? 4000;

$providers_for_select = ['OpenAI', 'OpenRouter', 'Google', 'Azure'];
if ($deepseek_addon_active) {
    $providers_for_select[] = 'DeepSeek';
}
if ($is_pro && aipkit_dashboard::is_addon_active('ollama')) {
    $providers_for_select[] = 'Ollama';
}

$available_post_types = get_post_types(['public' => true], 'objects');
unset($available_post_types['attachment']);

$current_user_id = get_current_user_id();
// Minimal safeguard: avoid loading thousands of users which can freeze the UI.
// Load up to a small cap and ensure current user is present.
$__aipkit_user_list_cap = 200;
$users_for_author = get_users([
    'orderby' => 'display_name',
    'order'   => 'ASC',
    'fields'  => ['ID', 'display_name'],
    'number'  => $__aipkit_user_list_cap,
]);
if ($current_user_id) {
    $has_current_user = false;
    foreach ($users_for_author as $u) {
        if ((int) $u->ID === (int) $current_user_id) { $has_current_user = true; break; }
    }
    if (!$has_current_user) {
        $u = get_user_by('id', $current_user_id);
        if ($u && isset($u->ID)) {
            $users_for_author[] = (object) [
                'ID' => (int) $u->ID,
                'display_name' => (string) $u->display_name,
            ];
        }
    }
}

$post_statuses = [
    'draft' => __('Draft', 'gpt3-ai-content-generator'),
    'publish' => __('Publish', 'gpt3-ai-content-generator'),
    'pending' => __('Pending Review', 'gpt3-ai-content-generator'),
    'private' => __('Private', 'gpt3-ai-content-generator'),
];

$wp_categories = get_categories(['hide_empty' => false]);
$task_frequencies = [
    'one-time' => __('One-time', 'gpt3-ai-content-generator'),
    'aipkit_five_minutes' => __('Every 5 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_fifteen_minutes' => __('Every 15 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_thirty_minutes' => __('Every 30 Minutes', 'gpt3-ai-content-generator'),
    'hourly' => __('Hourly', 'gpt3-ai-content-generator'),
    'twicedaily' => __('Twice Daily', 'gpt3-ai-content-generator'),
    'daily' => __('Daily', 'gpt3-ai-content-generator'),
    'weekly' => __('Weekly', 'gpt3-ai-content-generator'),
];

// --- MODIFIED: Ensure gsheets verification is loaded in lib mode ---
if ($is_pro && !function_exists('\WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser::verify_access')) {
    $gsheets_parser_path = WPAICG_LIB_DIR . 'content-writer/class-aipkit-google-sheets-parser.php';
    if (file_exists($gsheets_parser_path)) {
        require_once $gsheets_parser_path;
    }
}

// --- Load Vector Store Data for UI ---
$openai_vector_stores = [];
$pinecone_indexes = [];
$qdrant_collections = [];
$openai_embedding_models = [];
$google_embedding_models = [];
$azure_embedding_models = [];

if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
    $pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
    $qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
}
if (class_exists(AIPKit_Providers::class)) {
    $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
    $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
    $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
}
// --- End Load Vector Store Data ---
