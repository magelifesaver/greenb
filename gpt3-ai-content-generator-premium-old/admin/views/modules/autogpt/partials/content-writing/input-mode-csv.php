<?php
/**
 * Partial: Content Writing Automated Task - CSV Input Mode
 * UPDATED: Replaced textarea with a file input for direct CSV uploads.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div id="aipkit_task_cw_input_mode_csv" class="aipkit_task_cw_input_mode_section">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_task_cw_csv_file_input"><?php esc_html_e('Upload CSV File', 'gpt3-ai-content-generator'); ?></label>
        <input type="file" id="aipkit_task_cw_csv_file_input" name="csv_file_input" class="aipkit_csv_file_input" accept=".csv, text/csv">
        <div id="aipkit_task_cw_csv_analysis_results" class="aipkit_form-help aipkit_csv_analysis_results" style="margin-top: 5px;"></div>

        <?php // This hidden textarea will be populated by JS with the parsed CSV data (pipe-separated) ?>
        <textarea name="content_title" id="aipkit_task_cw_csv_data_holder" class="aipkit_form-input aipkit_csv_data_holder" style="display: none;" readonly></textarea>

        <p class="aipkit_form-help">
            <?php esc_html_e('The first column is used as the {topic}. Subsequent columns are used for {keywords}, category ID, author login, and post type slug.', 'gpt3-ai-content-generator'); ?>
        </p>
        <p class="aipkit_form-help">
            <a href="https://docs.google.com/spreadsheets/d/1WOnO_UKkbRCoyjRxQnDDTy0i-RsnrY_MDKD3Ks09JJk/edit?usp=sharing" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Click here to download a sample CSV file.', 'gpt3-ai-content-generator'); ?>
            </a>
        </p>
    </div>
</div>