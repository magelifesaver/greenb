<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-training/partials/vector-store/data-attributes.php
// Status: NEW FILE
if (!defined('ABSPATH')) {
    exit;
}
// Variables from parent: $initial_openai_stores, $openai_embedding_models_list, $google_embedding_models_list
?>
<div id="aipkit_initial_openai_stores_data" style="display:none;" data-stores="<?php echo esc_attr(wp_json_encode($initial_openai_stores)); ?>"></div>
<div id="aipkit_openai_embedding_models_data" style="display:none;" data-models="<?php echo esc_attr(wp_json_encode($openai_embedding_models_list)); ?>"></div>
<div id="aipkit_google_embedding_models_data" style="display:none;" data-models="<?php echo esc_attr(wp_json_encode($google_embedding_models_list)); ?>"></div>