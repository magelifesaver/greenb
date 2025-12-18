<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/image-generator/partials/settings-image-generator.php
// Status: MODIFIED

/**
 * Partial: Image Generator Settings Tab Content
 * Main file for the Settings tab within the Image Generator module.
 * Uses accordions to organize provider settings and common settings.
 * Includes Token Management accordion conditionally.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler; // Import the handler class
use WPAICG\aipkit_dashboard; // Import dashboard class

// Fetch current settings using the handler method
$settings_data = AIPKit_Image_Settings_Ajax_Handler::get_settings();

// Prepare nonce for saving
$settings_nonce = wp_create_nonce('aipkit_image_generator_settings_nonce');

// Check if Token Management addon is active
$is_token_management_active = aipkit_dashboard::is_addon_active('token_management');

// Check if Replicate addon is active
$is_replicate_addon_active = aipkit_dashboard::is_addon_active('replicate');

?>
<div class="aipkit_container-body aipkit_settings_container_body"> <?php // Added settings container body class?>
    <form id="aipkit_image_generator_settings_form" onsubmit="return false;" style="max-width: 700px;">
        <?php // Add nonce field for AJAX verification?>
        <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($settings_nonce); ?>">

        <div class="aipkit_accordion-group"> <?php // Wrap all accordions in a group?>
            <!-- Replicate Settings Accordion REMOVED -->

                        <!-- NEW: Token Management Accordion (Conditional) -->
             <?php if ($is_token_management_active): ?>
                <div class="aipkit_accordion">
                    <div class="aipkit_accordion-header">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php esc_html_e('Token Management', 'gpt3-ai-content-generator'); ?>
                    </div>
                    <div class="aipkit_accordion-content aipkit_active">
                        <?php include __DIR__ . '/settings-token-management.php'; ?>
                    </div>
                </div>
            <?php endif; ?>
            <!-- END NEW -->

            <!-- Replicate Settings Accordion (Conditional) -->
            <?php if ($is_replicate_addon_active): ?>
                <div class="aipkit_accordion">
                    <div class="aipkit_accordion-header">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Replicate', 'gpt3-ai-content-generator'); ?>
                    </div>
                    <div class="aipkit_accordion-content">
                        <?php include __DIR__ . '/settings-replicate.php'; ?>
                    </div>
                </div>
            <?php endif; ?>
            <!-- END Replicate Settings -->

             <!-- Common Settings Accordion -->
            <div class="aipkit_accordion">
                 <div class="aipkit_accordion-header">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php esc_html_e('Custom CSS', 'gpt3-ai-content-generator'); ?>
                </div>
                <div class="aipkit_accordion-content">
                     <?php include __DIR__ . '/settings-common.php'; ?>
                </div>
            </div>

            <!-- Frontend Display Accordion -->
            <div class="aipkit_accordion">
                 <div class="aipkit_accordion-header">
                    <span class="dashicons dashicons-desktop"></span>
                    <?php esc_html_e('Provider & Model Filtering', 'gpt3-ai-content-generator'); ?>
                </div>
                <div class="aipkit_accordion-content">
                     <?php include __DIR__ . '/settings-frontend-filtering.php'; ?>
                </div>
            </div>

        </div> <!-- /.aipkit_accordion-group -->

        <!-- Save Button -->
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--aipkit_container-border);">
             <div id="aipkit_image_settings_save_status" class="aipkit_form-help" style="min-height: 1.5em; margin-bottom: 8px;"></div> <?php // Status Message Area?>
             <button
                type="button"
                id="aipkit_save_image_settings_btn"
                class="aipkit_btn aipkit_btn-primary"
            >
                 <span class="aipkit_btn-text"><?php esc_html_e('Save Image Settings', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
        </div>

    </form>
</div><!-- / .aipkit_container-body (Settings) -->