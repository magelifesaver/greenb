<?php
/**
 * Partial: AI Training - Knowledge Base Detail View
 * This is the container for showing the details of a single knowledge base.
 * Content is populated by JavaScript when a card is clicked.
 */
if (!defined('ABSPATH')) {
    exit;
}
// Variables needed for the included provider UIs will be available
// as they are defined in the parent `vector-store.php`.
$initial_active_provider = 'openai'; // A default, JS will control the actual visibility
?>
<div class="aipkit_kb_detail_view_container">
    <div class="aipkit_kb_detail_header">
        <button type="button" id="aipkit_kb_back_to_list_btn" class="aipkit_btn aipkit_btn-secondary">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Back', 'gpt3-ai-content-generator'); ?>
        </button>
        <h3 id="aipkit_kb_detail_title"><!-- Title will be set by JS --></h3>
        <div class="aipkit_actions_menu" style="margin-left: auto;">
            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_actions_toggle_btn" title="<?php esc_attr_e('Actions', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-ellipsis"></span>
            </button>
            <div class="aipkit_actions_dropdown_menu" style="display: none;">
                <button type="button" class="aipkit_dropdown-item-btn" id="aipkit_kb_detail_search_btn">
                    <span class="dashicons dashicons-search"></span> <?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?>
                </button>
                <button type="button" class="aipkit_dropdown-item-btn aipkit_dropdown-item--danger" id="aipkit_kb_detail_delete_btn">
                    <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
        </div>
    </div>
    <div class="aipkit_kb_detail_body">
        <!-- FIXED: Removed "_details" suffix from container ID -->
        <div id="aipkit_vector_provider_management_blocks_container">
            <!-- FIXED: Removed "_details" suffix from child div IDs -->
            <div class="aipkit_vector_provider_management_block" id="aipkit_vector_openai_management_ui" data-provider="openai" style="display: <?php echo $initial_active_provider === 'openai' ? 'block' : 'none'; ?>;">
                <?php include __DIR__ . '/vector-store/provider-ui/vector-store-openai.php'; ?>
            </div>
            <div class="aipkit_vector_provider_management_block" id="aipkit_vector_pinecone_management_ui" data-provider="pinecone" style="display: <?php echo $initial_active_provider === 'pinecone' ? 'block' : 'none'; ?>;">
                <?php include __DIR__ . '/vector-store/provider-ui/vector-store-pinecone.php'; ?>
            </div>
            <div class="aipkit_vector_provider_management_block" id="aipkit_vector_qdrant_management_ui" data-provider="qdrant" style="display: <?php echo $initial_active_provider === 'qdrant' ? 'block' : 'none'; ?>;">
                <?php include __DIR__ . '/vector-store/provider-ui/vector-store-qdrant.php'; ?>
            </div>
        </div>
    </div>
</div>