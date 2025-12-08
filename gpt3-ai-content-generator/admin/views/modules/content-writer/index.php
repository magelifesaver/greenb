<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/index.php
// Status: MODIFIED
/**
 * AIPKit Content Writer Module - Main View
 * UPDATED: Re-architected into a three-column layout with a central tabbed input panel and action bar.
 * MODIFIED: Moved template controls to the left column and status indicators to the right column.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- MODIFIED: Load shared variables at the top level ---
require_once __DIR__ . '/partials/form-inputs/loader-vars.php';
// --- END MODIFICATION ---

$content_writer_nonce = wp_create_nonce('aipkit_content_writer_nonce');
$content_writer_template_nonce = wp_create_nonce('aipkit_content_writer_template_nonce');
$frontend_stream_nonce = wp_create_nonce('aipkit_frontend_chat_nonce');
?>
<div class="aipkit_container aipkit_module_content_writer" id="aipkit_content_writer_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('Content Writer', 'gpt3-ai-content-generator'); ?></div>
        <div class="aipkit_container-actions">
            <?php // Placeholder for future actions?>
        </div>
    </div>
    <div class="aipkit_container-body">
        <form id="aipkit_content_writer_form" onsubmit="return false;">
            <!-- Hidden inputs for nonces, cache keys etc. needed by JS -->
            <input type="hidden" name="_ajax_nonce" id="aipkit_content_writer_nonce" value="<?php echo esc_attr($content_writer_nonce); ?>">
            <input type="hidden" id="aipkit_content_writer_frontend_stream_nonce" value="<?php echo esc_attr($frontend_stream_nonce); ?>">
            <input type="hidden" id="aipkit_content_writer_template_nonce_field" value="<?php echo esc_attr($content_writer_template_nonce); ?>">
            <input type="hidden" name="stream_cache_key" id="aipkit_content_writer_stream_cache_key" value="">
            <input type="hidden" name="image_data" id="aipkit_cw_image_data_holder" value="">

            <div class="aipkit_content_writer_layout">
                <!-- Left Column: All inputs and settings -->
                <div class="aipkit_content_writer_column aipkit_content_writer_inputs">
                    <?php include __DIR__ . '/partials/form-inputs.php'; ?>
                </div>

                <!-- Center Column: Main generation area -->
                <div class="aipkit_content_writer_column aipkit_content_writer_output">
                    
                    <!-- NEW: Tabbed Input Panel -->
                    <?php include __DIR__ . '/partials/form-inputs/generation-mode.php'; ?>
                    
                    <!-- Main Output Area -->
                    <?php include __DIR__ . '/partials/output-area.php'; ?>
                </div>

                <!-- Right Column: Status Display -->
                <div class="aipkit_content_writer_column aipkit_content_writer_templates">
                    <!-- Status Display Container -->
                    <div id="aipkit_cw_status_display_container">
                        <div id="aipkit_content_writer_form_status"></div>
                        <?php include __DIR__ . '/partials/generation-status-indicators.php'; ?>
                        <div class="aipkit_content_writer_progress_bar_container" style="display: none;">
                            <div class="aipkit_content_writer_progress_bar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>