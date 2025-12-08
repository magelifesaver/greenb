<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-automation-ui.php
// Status: MODIFIED
// I have added a new step content div for the Content Enhancement Knowledge Base settings.
/**
 * Main UI for Task Automation.
 * Includes the form for creating/editing tasks and the list of existing tasks/queue.
 * Variable definitions are now in admin/views/modules/autogpt/index.php
 * REVISED: Wizard steps are now rendered by JavaScript for dynamic behavior.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_container-body">
    <!-- Add New Task / Edit Task Form (Initially Hidden) -->
    <div id="aipkit_automated_task_form_wrapper" style="display:none; max-width: 800px;margin-bottom: 20px; padding: 20px; border: 1px solid var(--aipkit_container-border); border-radius: 4px; background-color: var(--aipkit_bg-primary);">
        <div class="aipkit_task_form_wizard">
            <h3 id="aipkit_automated_task_form_title" style="text-align: center; margin-bottom: 20px;"></h3>
            <!-- Step Indicators will be rendered here by JavaScript -->
            <div class="aipkit_wizard_steps"></div>

            <form id="aipkit_automated_task_form" onsubmit="return false;">
                <input type="hidden" name="task_id" id="aipkit_automated_task_id" value="">

                <!-- Container for all possible content steps -->
                <div class="aipkit_wizard_content_container">
                    <!-- Step Content: Setup -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_form_setup">
                        <?php include __DIR__ . '/task-form-setup.php'; ?>
                    </div>
                    <!-- Step Content: AI & Prompts -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_ai">
                        <?php include __DIR__ . '/task-form-config-ai.php'; ?>
                    </div>
                    <!-- Step Content: Knowledge Base -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_knowledge_base">
                         <?php include __DIR__ . '/content-writing/knowledge-base-settings.php'; ?>
                    </div>
                    <!-- Step Content: SEO -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_seo">
                        <?php include __DIR__ . '/task-form-config-seo.php'; ?>
                    </div>
                    <!-- Step Content: Image Settings -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_form_image_settings">
                        <?php include __DIR__ . '/task-form-image-settings.php'; ?>
                    </div>
                    <!-- Step Content: Content Writing (Finish) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_content_writing">
                        <?php include __DIR__ . '/task-form-config-content-writing.php'; ?>
                    </div>
                    <!-- Step Content: Content Indexing (Finish) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_content_indexing">
                        <?php include __DIR__ . '/task-form-config-content-indexing.php'; ?>
                    </div>
                    <!-- Step Content: Comment Reply Settings (new) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_comment_reply">
                        <?php include __DIR__ . '/task-form-config-comment-reply.php'; ?>
                    </div>
                    <!-- Step Content: Comment Reply AI Settings (new) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_comment_reply_ai">
                        <?php include __DIR__ . '/task-form-config-comment-reply-ai.php'; ?>
                    </div>
                     <!-- Step Content: Content Enhancement - Knowledge Base (new) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_enhancement_knowledge_base">
                        <?php
                        $content_enhancement_kb_partial = WPAICG_LIB_DIR . 'views/modules/autogpt/partials/content-enhancement/knowledge-base-settings.php';
                        if ($is_pro && file_exists($content_enhancement_kb_partial)) {
                            include $content_enhancement_kb_partial;
                        }
                        ?>
                    </div>
                    <!-- Step Content: Content Enhancement - AI & Prompts (new) -->
                    <div class="aipkit_wizard_content_step" data-content-id="task_config_enhancement_ai_and_prompts">
                         <?php include __DIR__ . '/content-enhancement/ai-and-prompts.php'; ?>
                    </div>
                </div>


                <!-- Wizard Navigation -->
                <div class="aipkit_wizard_nav">
                    <button type="button" id="aipkit_wizard_prev_btn" class="aipkit_btn aipkit_btn-secondary" style="display: none;"><?php esc_html_e('Previous', 'gpt3-ai-content-generator'); ?></button>
                    <div id="aipkit_automated_task_form_status" class="aipkit_form-help"></div>
                    <div class="aipkit_wizard_nav_right">
                        <button type="button" id="aipkit_cancel_edit_task_btn" class="aipkit_btn aipkit_btn-secondary"><?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?></button>
                        <button type="button" id="aipkit_wizard_next_btn" class="aipkit_btn aipkit_btn-primary">
                            <span class="aipkit_btn-text"><?php esc_html_e('Next', 'gpt3-ai-content-generator'); ?></span>
                        </button>
                        <button type="submit" id="aipkit_save_task_btn" class="aipkit_btn aipkit_btn-primary" style="display: none;">
                            <span class="aipkit_btn-text"><?php esc_html_e('Save Task', 'gpt3-ai-content-generator'); ?></span>
                            <span class="aipkit_spinner" style="display:none;"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- List of Existing Tasks -->
    <?php include __DIR__ . '/task-list.php'; ?>

    <!-- Indexing Queue Viewer -->
    <hr class="aipkit_hr" style="margin-top: 30px; margin-bottom: 20px;">
    <?php include __DIR__ . '/task-queue.php'; ?>

</div>