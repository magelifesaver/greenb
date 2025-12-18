<?php
/**
 * Partial: Content Writer Form - Generation Options
 * Conditionally displayed when 'single' generation mode is selected.
 */
if (!defined('ABSPATH')) exit;
?>
<div id="aipkit_cw_single_content_options">
     <div class="aipkit_accordion">
        <div class="aipkit_accordion-header">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
            <?php esc_html_e('Generation Options', 'gpt3-ai-content-generator'); ?>
        </div>
        <div class="aipkit_accordion-content">
            <div class="aipkit_form-group">
                <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_content_writer_stream_mode">
                    <input type="checkbox" id="aipkit_content_writer_stream_mode" name="stream_mode" value="1" checked>
                    <?php esc_html_e('Stream Mode (Real-time Generation)', 'gpt3-ai-content-generator'); ?>
                </label>
                <p class="aipkit_form-help"><?php esc_html_e('Uncheck to generate content in a single request (shows progress bar).', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>
</div>