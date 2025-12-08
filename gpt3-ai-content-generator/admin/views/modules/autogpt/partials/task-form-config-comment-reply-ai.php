<?php
/**
 * Partial: Automated Task Form - Comment Reply AI & Prompt Configuration
 * This is the content pane for the "AI & Prompt" step in the wizard.
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_task_config_comment_reply_ai_main" class="aipkit_task_config_section">
    <?php
    // The directory "community-engagement" should be created in /partials/
    // as per the logical separation of task categories.
    $comment_reply_ai_settings_partial = __DIR__ . '/community-engagement/ai-settings.php';
if (file_exists($comment_reply_ai_settings_partial)) {
    include $comment_reply_ai_settings_partial;
} else {
    echo '<p>Error: Comment Reply AI Settings UI partial is missing.</p>';
}
?>
</div>