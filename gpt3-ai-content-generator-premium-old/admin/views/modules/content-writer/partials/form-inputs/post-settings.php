<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs/post-settings.php
// Status: MODIFIED

/**
 * Partial: Content Writer Form - Post Settings
 */
if (!defined('ABSPATH')) {
    exit;
}
// Variables from loader-vars.php: $available_post_types, $users_for_author, $current_user_id, $wp_categories, $post_statuses
?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Post', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_content_writer_post_type">Type</label>
                <select id="aipkit_content_writer_post_type" name="post_type" class="aipkit_form-input">
                    <?php foreach ($available_post_types as $pt_slug => $pt_obj): ?>
                        <option value="<?php echo esc_attr($pt_slug); ?>" <?php selected($pt_slug, 'post'); ?>><?php echo esc_html($pt_obj->label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_content_writer_post_author">Author</label>
                <select id="aipkit_content_writer_post_author" name="post_author" class="aipkit_form-input">
                    <?php foreach ($users_for_author as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($user->ID, $current_user_id); ?>><?php echo esc_html($user->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_content_writer_categories">Categories</label>
            <select id="aipkit_content_writer_categories" name="post_categories[]" class="aipkit_form-input" multiple size="3" style="height: auto;">
                <?php foreach ($wp_categories as $category): ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aipkit_form-row">
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="aipkit_content_writer_post_status">Status</label>
                <select id="aipkit_content_writer_post_status" name="post_status" class="aipkit_form-input">
                    <?php foreach ($post_statuses as $status_val => $status_label): ?>
                        <option value="<?php echo esc_attr($status_val); ?>" <?php selected($status_val, 'draft'); ?>><?php echo esc_html($status_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="aipkit_form-group aipkit_form-col aipkit_cw_schedule_date_group" style="display: none;">
                <label class="aipkit_form-label" for="aipkit_content_writer_post_schedule_date">Schedule Date</label>
                <input type="date" id="aipkit_content_writer_post_schedule_date" name="post_schedule_date" class="aipkit_form-input">
            </div>
             <div class="aipkit_form-group aipkit_form-col aipkit_cw_schedule_time_group" style="display: none;">
                <label class="aipkit_form-label" for="aipkit_content_writer_post_schedule_time">Schedule Time</label>
                <input type="time" id="aipkit_content_writer_post_schedule_time" name="post_schedule_time" class="aipkit_form-input">
            </div>
        </div>
        <div id="aipkit_cw_schedule_options_wrapper" class="aipkit_schedule_options_wrapper" style="display:none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--aipkit_container-border);">
            <div class="aipkit_form-group">
                <label class="aipkit_form-label"><?php esc_html_e('Publishing Schedule', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipkit_radio-group">
                    <label class="aipkit_radio-label">
                        <input type="radio" name="schedule_mode" value="immediate" checked>
                        <?php esc_html_e('Publish Immediately', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <label class="aipkit_radio-label">
                        <input type="radio" name="schedule_mode" value="smart">
                        <?php esc_html_e('Smart Schedule', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <label class="aipkit_radio-label aipkit_schedule_from_input_option">
                        <input type="radio" name="schedule_mode" value="from_input">
                        <?php esc_html_e('Use Dates from Input', 'gpt3-ai-content-generator'); ?>
                    </label>
                </div>
            </div>
            <div id="aipkit_cw_smart_schedule_fields" class="aipkit_smart_schedule_fields" style="display: none;">
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_cw_smart_schedule_start_datetime"><?php esc_html_e('Start Date/Time', 'gpt3-ai-content-generator'); ?></label>
                        <input type="datetime-local" id="aipkit_cw_smart_schedule_start_datetime" name="smart_schedule_start_datetime" class="aipkit_form-input">
                    </div>
                </div>
                <div class="aipkit_form-row">
                    <div class="aipkit_form-group aipkit_form-col">
                         <label class="aipkit_form-label" for="aipkit_cw_smart_schedule_interval_value"><?php esc_html_e('Publish one post every', 'gpt3-ai-content-generator'); ?></label>
                        <input type="number" id="aipkit_cw_smart_schedule_interval_value" name="smart_schedule_interval_value" value="1" min="1" class="aipkit_form-input" style="width: 70px; text-align: center;">
                    </div>
                     <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="aipkit_cw_smart_schedule_interval_unit"> </label>
                        <select id="aipkit_cw_smart_schedule_interval_unit" name="smart_schedule_interval_unit" class="aipkit_form-input">
                            <option value="hours"><?php esc_html_e('Hours', 'gpt3-ai-content-generator'); ?></option>
                            <option value="days"><?php esc_html_e('Days', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
             <p class="aipkit_form-help aipkit_schedule_from_input_help" style="display: none;">
                <?php esc_html_e('Append | YYYY-MM-DD HH:MM (or :SS) to each line (Bulk/CSV/URL) or use the Google Sheets schedule column. Also accepted: YYYY/MM/DD HH:MM, MM/DD/YYYY HH:MM, DD/MM/YYYY HH:MM, ISO 8601. Site timezone assumed unless offset/Z supplied.', 'gpt3-ai-content-generator'); ?>
            </p>
        </div>
    </div>
</div>