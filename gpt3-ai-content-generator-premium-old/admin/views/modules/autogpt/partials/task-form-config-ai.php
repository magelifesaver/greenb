<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-form-config-ai.php
// Status: MODIFIED

/**
 * Partial: Automated Task Form - AI Configuration
 * Contains fields for selecting AI Provider, Model, and parameters.
 * Used for Content Writing tasks.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent: $cw_providers_for_select, $cw_default_temperature, $cw_default_max_tokens
?>
<div id="aipkit_task_config_ai" class="aipkit_task_config_section">
    <?php include __DIR__ . '/content-writing/model-settings.php'; ?>
</div>