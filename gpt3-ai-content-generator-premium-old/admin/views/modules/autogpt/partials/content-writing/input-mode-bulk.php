<?php
/**
 * Partial: Content Writing Automated Task - Bulk Input Mode
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div id="aipkit_task_cw_input_mode_bulk" class="aipkit_task_cw_input_mode_section">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_task_cw_content_title_bulk"><?php esc_html_e('Topic (one per line)', 'gpt3-ai-content-generator'); ?></label>
        <textarea id="aipkit_task_cw_content_title_bulk" name="content_title_bulk" class="aipkit_form-input" rows="6" placeholder="<?php esc_attr_e("e.g., Topic | keywords | category_id | author | post_type\nHow to bake a cake | frosting, flour | 15 | mary | post\nAbout our services | web design | 12 | john | page", 'gpt3-ai-content-generator'); ?>"></textarea>
    </div>
</div>