<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/image-generator/index.php
/**
 * AIPKit Image Generator Module - Admin View
 * REVISED: Moved shortcode configurator elements to be inline with the tabs, on the right.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="aipkit_container aipkit_module_image_generator" id="aipkit_image_generator_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('Image Generator', 'gpt3-ai-content-generator'); ?></div>
        <?php // Controls are now moved into the .aipkit_tabs div?>
    </div>

    <!-- Tabs & Controls Wrapper -->
    <div class="aipkit_tabs">
        <div class="aipkit_tab aipkit_active" data-tab="generator">
            <?php esc_html_e('Generator', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_tab" data-tab="image-settings">
            <?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?>
        </div>

        <!-- NEW: Wrapper for right-aligned controls within the tab bar -->
        <div class="aipkit_tabs_module_controls">
            <div class="aipkit_image_generator_top_bar"> <?php // This name might be slightly confusing now but kept for CSS continuity?>
                <div class="aipkit_top_bar_left_group">
                    <div class="aipkit_shortcode_display_top_wrapper">
                        <code id="aipkit_image_generator_shortcode_snippet" title="<?php esc_attr_e('Click to copy shortcode', 'gpt3-ai-content-generator'); ?>">
                            [aipkit_image_generator]
                        </code>
                        <button type="button" id="aipkit_image_generator_shortcode_settings_toggle" class="aipkit_icon_btn" title="<?php esc_attr_e('Configure Shortcode Options', 'gpt3-ai-content-generator'); ?>" aria-expanded="false" aria-controls="aipkit_image_generator_shortcode_configurator">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </button>
                    </div>
                </div>
                <button type="button" id="aipkit_theme_switcher_toggle_btn" class="aipkit_icon_btn" title="<?php esc_attr_e('Switch Preview Theme', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-lightbulb"></span>
                </button>
            </div>

            <!-- Shortcode Configurator (positioned absolutely relative to aipkit_tabs_module_controls) -->
            <div class="aipkit_shortcode_configurator" id="aipkit_image_generator_shortcode_configurator" style="display: none;">
                <div class="aipkit_config_section">
                    <h6 class="aipkit_config_section_title"><?php esc_html_e('UI Elements', 'gpt3-ai-content-generator'); ?></h6>
                    <div class="aipkit_config_options_grid">
                        <label class="aipkit_config_item">
                            <input type="checkbox" name="cfg_show_provider" class="aipkit_config_input" value="1" checked>
                            <span><?php esc_html_e('Show Provider Select', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <label class="aipkit_config_item">
                            <input type="checkbox" name="cfg_show_model" class="aipkit_config_input" value="1" checked>
                            <span><?php esc_html_e('Show Model Select', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <label class="aipkit_config_item">
                            <input type="checkbox" name="cfg_show_history" class="aipkit_config_input" value="1">
                            <span><?php esc_html_e('Show User History (if logged in)', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="aipkit_config_section">
                    <h6 class="aipkit_config_section_title"><?php esc_html_e('Frontend Theme', 'gpt3-ai-content-generator'); ?></h6>
                    <div class="aipkit_config_options_group"> 
                        <label class="aipkit_config_item">
                            <input type="radio" name="cfg_theme" class="aipkit_config_input" value="light">
                            <span><?php esc_html_e('Light', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <label class="aipkit_config_item">
                            <input type="radio" name="cfg_theme" class="aipkit_config_input" value="dark" checked>
                            <span><?php esc_html_e('Dark', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                        <label class="aipkit_config_item">
                            <input type="radio" name="cfg_theme" class="aipkit_config_input" value="custom">
                            <span><?php esc_html_e('Custom CSS', 'gpt3-ai-content-generator'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            <!-- End Shortcode Configurator -->
        </div> <!-- / .aipkit_tabs_module_controls -->
    </div> <!-- / .aipkit_tabs -->


    <div class="aipkit_tab_content_container">
        <!-- Generator Tab Content -->
        <div class="aipkit_tab-content aipkit_active" id="generator-content">
             <div class="aipkit_image_generator_admin_preview_wrapper">
                 <?php
                 echo do_shortcode('[aipkit_image_generator history="true"]');
?>
            </div>
        </div><!-- /#generator-content -->

        <div class="aipkit_tab-content" id="image-settings-content">
             <?php include __DIR__ . '/partials/settings-image-generator.php'; ?>
        </div>

    </div><!-- /.aipkit_tab_content_container -->
</div><!-- /.aipkit_container -->