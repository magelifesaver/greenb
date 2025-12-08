<?php
/**
 * Partial: AI Training - Vector Store Main Content
 * REVISED: Now includes a collapsible "Add Content" form at the top,
 * followed by the new two-column master-detail layout.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables are needed by sub-partials
use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;

$openai_data = AIPKit_Providers::get_provider_data('OpenAI');
$openai_api_key_is_set = !empty($openai_data['api_key']);

$pinecone_data = AIPKit_Providers::get_provider_data('Pinecone');
$pinecone_api_key_is_set = !empty($pinecone_data['api_key']);

$qdrant_data = AIPKit_Providers::get_provider_data('Qdrant');
$qdrant_url_is_set = !empty($qdrant_data['url']);
$qdrant_api_key_is_set = !empty($qdrant_data['api_key']);

$initial_active_provider = 'openai';

$initial_openai_stores = [];
$pinecone_indexes = [];
$qdrant_collections = [];
$all_stores = [];

if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    // OpenAI
    $initial_openai_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
    if (is_array($initial_openai_stores)) { $all_stores = array_merge($all_stores, $initial_openai_stores); }

    // Pinecone
    $pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
    if (is_array($pinecone_indexes)) { $all_stores = array_merge($all_stores, $pinecone_indexes); }
    
    // Qdrant
    $qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
    if (is_array($qdrant_collections)) { $all_stores = array_merge($all_stores, $qdrant_collections); }
}


$openai_embedding_models_list = AIPKit_Providers::get_openai_embedding_models();
$google_embedding_models_list = AIPKit_Providers::get_google_embedding_models();

$post_types_args = ['public' => true];
$all_selectable_post_types = get_post_types($post_types_args, 'objects');
$all_selectable_post_types = array_filter($all_selectable_post_types, function ($pt_obj) {
    return $pt_obj->name !== 'attachment';
});

?>
<div class="aipkit_container-body" id="aipkit_vector_store_management_area">
    <?php include __DIR__ . '/vector-store/nonce-fields.php'; ?>
    <?php include __DIR__ . '/vector-store/data-attributes.php'; ?>

    <!-- Add Content Form -->
    <div id="aipkit_add_content_form_container" style="display:none;max-width: 750px;">
        <?php // This partial now contains the entire "Add Content" form, moved from the old left-panel.php ?>
        <?php include __DIR__ . '/vector-store/global-form/global-form-wrapper.php'; ?>
    </div>

    <!-- This is the new structure that replaces the old layout.php include -->
    <!-- View for the list of knowledge base cards -->
    <div id="aipkit_kb_card_view">
        <?php include __DIR__ . '/knowledge-base-cards.php'; ?>
    </div>

    <!-- View for the list-style knowledge base items (initially hidden, toggled via selector) -->
    <div id="aipkit_kb_list_view" style="display: none;">
        <?php include __DIR__ . '/knowledge-base-list.php'; ?>
    </div>

    <!-- View for the details of a single knowledge base (initially hidden) -->
    <div id="aipkit_kb_detail_view" style="display: none;">
        <?php include __DIR__ . '/knowledge-base-details.php'; ?>
    </div>
</div>