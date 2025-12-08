<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/settings-advanced-integrations.php
// Status: MODIFIED
// I have removed the direct includes for Pinecone and Qdrant as their settings are now in the combined vector-databases.php partial.

/**
 * Partial: Integrations Settings
 * This file acts as a router, including different integration partials based on active addons.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Providers;

// Variables passed from parent (settings/index.php) are used by the included partials.
$voice_playback_addon_active = aipkit_dashboard::is_addon_active('voice_playback');
$vector_databases_addon_active = aipkit_dashboard::is_addon_active('vector_databases');
$stock_images_addon_active = aipkit_dashboard::is_addon_active('stock_images');
$replicate_addon_active = aipkit_dashboard::is_addon_active('replicate');
$post_enhancer_addon_active = aipkit_dashboard::is_addon_active('ai_post_enhancer');
$semantic_search_addon_active = aipkit_dashboard::is_addon_active('semantic_search');
$whatsapp_addon_active = aipkit_dashboard::is_addon_active('whatsapp');

$integrations_tab_visible = $voice_playback_addon_active || $vector_databases_addon_active || $stock_images_addon_active || $replicate_addon_active || $post_enhancer_addon_active || $semantic_search_addon_active || $whatsapp_addon_active;


if (!$integrations_tab_visible) {
    echo '<div class="aipkit_settings-tab-content-inner-padding"><p>' . esc_html__('No active integrations to configure.', 'gpt3-ai-content-generator') . '</p></div>';
    return;
}

// Prepare variables needed by the included partials
$current_elevenlabs_api_key = $elevenlabs_data['api_key'] ?? '';
$current_elevenlabs_default_voice = $elevenlabs_data['voice_id'] ?? '';
$current_elevenlabs_default_model = $elevenlabs_data['model_id'] ?? '';

$current_pinecone_api_key = $pinecone_data['api_key'] ?? '';
$current_pinecone_default_index = $pinecone_data['default_index'] ?? '';
$pinecone_index_list = AIPKit_Providers::get_pinecone_indexes();


$current_qdrant_api_key = $qdrant_data['api_key'] ?? '';
$current_qdrant_url = $qdrant_data['url'] ?? '';
$current_qdrant_default_collection = $qdrant_data['default_collection'] ?? '';
$qdrant_collection_list = AIPKit_Providers::get_qdrant_collections(); // Add this line for the new partial
$qdrant_defaults = AIPKit_Providers::get_provider_defaults('Qdrant'); // Add this line for the new partial


$pexels_data = AIPKit_Providers::get_provider_data('Pexels');
$current_pexels_api_key = $pexels_data['api_key'] ?? '';

$pixabay_data = AIPKit_Providers::get_provider_data('Pixabay');
$current_pixabay_api_key = $pixabay_data['api_key'] ?? '';

$replicate_data = AIPKit_Providers::get_provider_data('Replicate');
$current_replicate_api_key = $replicate_data['api_key'] ?? '';

$aipkit_options = get_option('aipkit_options', []);
$enhancer_editor_integration_enabled = $aipkit_options['enhancer_settings']['editor_integration'] ?? '1';
$enhancer_default_insert_position = $aipkit_options['enhancer_settings']['default_insert_position'] ?? 'replace';

?>
<div class="aipkit_settings-tab-content-inner-padding">
    <div class="aipkit_accordion-group">

        <?php if ($post_enhancer_addon_active) : ?>
            <?php include __DIR__ . '/integrations/ai-assistant.php'; ?>
        <?php endif; ?>

        <?php if ($semantic_search_addon_active) : ?>
            <?php include __DIR__ . '/integrations/semantic-search.php'; ?>
        <?php endif; ?>

        <?php if ($replicate_addon_active) : ?>
            <?php include __DIR__ . '/integrations/replicate.php'; ?>
        <?php endif; ?>

        <?php if ($stock_images_addon_active) : ?>
            <?php include __DIR__ . '/integrations/stock-images.php'; ?>
        <?php endif; ?>

        <?php if ($voice_playback_addon_active) : ?>
            <?php include __DIR__ . '/integrations/elevenlabs.php'; ?>
        <?php endif; ?>

        <?php if ($vector_databases_addon_active) : ?>
            <?php include __DIR__ . '/integrations/vector-databases.php'; ?>
        <?php endif; ?>

        <?php if ($whatsapp_addon_active) : ?>
            <?php // Include Pro lib partial for WhatsApp settings ?>
            <?php $wa_partial = WPAICG_LIB_DIR . 'views/settings/partials/whatsapp.php'; if (file_exists($wa_partial)) { include $wa_partial; } ?>
        <?php endif; ?>

    </div>
</div>
