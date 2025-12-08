<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs.php
// Status: MODIFIED
/**
 * Partial: Content Writer Form Inputs
 * This is the left-hand column of the Content Writer UI.
 * It loads shared variables and includes all the smaller configuration partials.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load shared variables used by the partials
require_once __DIR__ . '/form-inputs/loader-vars.php';

?>
<!-- Accordion Group for all settings -->
<div class="aipkit_accordion-group">
    <?php
    // Include templates as the first accordion in the group.
    include __DIR__ . '/template-controls.php';

    // Include each modularized accordion section in the desired order
    include __DIR__ . '/form-inputs/ai-settings.php';
    include __DIR__ . '/form-inputs/seo-settings.php';
    include __DIR__ . '/form-inputs/image-settings.php';
    include __DIR__ . '/form-inputs/vector-settings.php';
    include __DIR__ . '/form-inputs/post-settings.php';
    ?>
</div> <!-- End accordion group -->