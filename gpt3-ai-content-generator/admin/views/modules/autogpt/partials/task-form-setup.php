<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-form-setup.php
// Status: MODIFIED
/**
 * Partial: Automated Task Form - Task Setup Section
 * UPDATED: Replaced single Task Type dropdown with a two-step Category/Type selection.
 */

if (!defined('ABSPATH')) {
    exit;
}
// Variables from parent: $task_categories, $frequencies, $aipkit_task_statuses_for_select, etc.
?>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_automated_task_name"><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipkit_automated_task_name" name="task_name" class="aipkit_form-input" required>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_automated_task_category"><?php esc_html_e('Category', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_automated_task_category" name="task_category" class="aipkit_form-input">
            <?php foreach ($task_categories as $cat_key => $cat_label) : ?>
                <option value="<?php echo esc_attr($cat_key); ?>"><?php echo esc_html($cat_label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_automated_task_type"><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_automated_task_type" name="task_type" class="aipkit_form-input" disabled>
            <option value=""><?php esc_html_e('-- Select a category first --', 'gpt3-ai-content-generator'); ?></option>
        </select>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_automated_task_frequency"><?php esc_html_e('Frequency', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_automated_task_frequency" name="task_frequency" class="aipkit_form-input">
            <?php foreach ($frequencies as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($value, 'daily'); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_automated_task_status"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_automated_task_status" name="task_status" class="aipkit_form-input">
            <?php foreach ($aipkit_task_statuses_for_select as $status_value => $status_label): ?>
                <option value="<?php echo esc_attr($status_value); ?>" <?php selected($status_value, 'active'); ?>><?php echo esc_html($status_label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<!-- NEW: Wrapper for Content Indexing source settings -->
<div id="aipkit_task_ci_source_wrapper" class="aipkit_task_config_section" style="display: none; border-top: 1px dashed var(--aipkit_container-border); margin-top: 15px; padding-top: 15px;">
    <?php include __DIR__ . '/content-indexing/source-settings.php'; ?>
</div>
<!-- Wrapper for Content Writing input modes -->
<div id="aipkit_task_cw_input_modes_wrapper" class="aipkit_task_config_section" style="display: none; border-top: 1px dashed var(--aipkit_container-border); margin-top: 15px; padding-top: 15px;">
    <?php // This hidden input will be set by JS based on the selected task_type?>
    <input type="hidden" name="cw_generation_mode" id="aipkit_task_cw_generation_mode" value="">

    <!-- Input sections for different modes -->
    <?php include __DIR__ . '/content-writing/input-mode-bulk.php'; ?>
    <?php include __DIR__ . '/content-writing/input-mode-csv.php'; ?>

    <?php // Pro Feature: RSS Mode?>
    <div id="aipkit_task_cw_input_mode_rss" class="aipkit_task_cw_input_mode_section" style="display:none;">
        <?php
        $shared_rss_partial = WPAICG_LIB_DIR . 'views/shared/content-writing/input-mode-rss.php';
if (file_exists($shared_rss_partial)) {
    include $shared_rss_partial;
} else {
    echo '<p>This is a Pro feature. Please upgrade to access the RSS feature.</p>';
}
?>
    </div>

    <?php // Pro Feature: URL Mode?>
    <div id="aipkit_task_cw_input_mode_url" class="aipkit_task_cw_input_mode_section" style="display:none;">
        <?php
$shared_url_partial = WPAICG_LIB_DIR . 'views/shared/content-writing/input-mode-url.php';
if (file_exists($shared_url_partial)) {
    include $shared_url_partial;
} else {
    echo '<p>This is a Pro feature. Please upgrade to access the URL feature.</p>';
}
?>
    </div>

    <?php // Pro Feature: Google Sheets Mode?>
    <div id="aipkit_task_cw_input_mode_gsheets" class="aipkit_task_cw_input_mode_section" style="display:none;">
        <?php
$shared_gsheets_partial = WPAICG_LIB_DIR . 'views/shared/content-writing/input-mode-gsheets.php';
if (file_exists($shared_gsheets_partial)) {
    $prefix = 'aipkit_task_cw';
    include $shared_gsheets_partial;
} else {
    echo '<p>This is a Pro feature. Please upgrade to access the Google Sheets feature.</p>';
}
?>
    </div>
</div>
<!-- Wrapper for Comment Reply source settings -->
<div id="aipkit_task_cc_source_wrapper" class="aipkit_task_config_section" style="display: none; border-top: 1px dashed var(--aipkit_container-border); margin-top: 15px; padding-top: 15px;">
    <div class="aipkit_form-row">
        <?php
$comment_reply_source_partial = __DIR__ . '/community-engagement/source-settings.php';
if (file_exists($comment_reply_source_partial)) {
    include $comment_reply_source_partial;
} else {
    echo '<p>Error: Comment Reply Source Settings UI partial is missing.</p>';
}
?>
    </div>
</div>
<!-- Wrapper for Content Enhancement source settings -->
<div id="aipkit_task_ce_content_selection_wrapper" class="aipkit_task_config_section" style="display: none; border-top: 1px dashed var(--aipkit_container-border); margin-top: 15px; padding-top: 15px;">
    <?php
    $content_enhancement_source_partial = __DIR__ . '/content-enhancement/source-settings.php';
if (file_exists($content_enhancement_source_partial)) {
    include $content_enhancement_source_partial;
} else {
    echo '<p>Error: Content Enhancement Source Settings UI partial is missing.</p>';
}
?>
</div>