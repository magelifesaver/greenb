<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-form-config-comment-reply.php
// Status: NEW FILE

/**
 * Partial: Community Engagement Automated Task - Comment Reply Configuration
 * This acts as the main content pane for the "Filters" step in the wizard.
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent: $all_selectable_post_types, $is_pro
?>
<div id="aipkit_task_config_comment_reply_main" class="aipkit_task_config_section">
    <?php
    // The directory "community-engagement" should be created in /partials/
    // as per the logical separation of task categories.
    $comment_reply_settings_partial = __DIR__ . '/community-engagement/comment-reply-settings.php';
if (file_exists($comment_reply_settings_partial)) {
    include $comment_reply_settings_partial;
} else {
    echo '<p>Error: Comment Reply Settings UI partial is missing.</p>';
}
?>
</div>