<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs/image-settings.php
// Status: MODIFIED

/**
* Partial: Content Writer Form - AI Image Settings
* @since 2.1
*/
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\aipkit_dashboard; // Added for addon status check

$stock_images_addon_active = aipkit_dashboard::is_addon_active('stock_images');
$replicate_addon_active = aipkit_dashboard::is_addon_active('replicate');

?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Images', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label"><?php esc_html_e('Image Generation', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipkit_checkbox-group">
                <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_cw_generate_images_enabled">
                    <input type="checkbox" id="aipkit_cw_generate_images_enabled" name="generate_images_enabled" class="aipkit_toggle_switch aipkit_cw_image_enable_toggle aipkit_autosave_trigger" value="1">
                    <?php esc_html_e('In-Content Images', 'gpt3-ai-content-generator'); ?>
                </label>
                <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_cw_generate_featured_image">
                    <input type="checkbox" id="aipkit_cw_generate_featured_image" name="generate_featured_image" class="aipkit_toggle_switch aipkit_autosave_trigger" value="1">
                    <?php esc_html_e('Featured Image', 'gpt3-ai-content-generator'); ?>
                </label>
            </div>
        </div>

        <div class="aipkit_cw_image_settings_container" style="display:none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--aipkit_container-border);">
            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_image_provider"><?php esc_html_e('Image Provider', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_image_provider" name="image_provider" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="openai" selected>OpenAI</option>
                        <option value="google">Google</option>
                        <option value="azure">Azure</option>
                        <option value="replicate" <?php disabled(!$replicate_addon_active); ?>>
                            <?php esc_html_e('Replicate', 'gpt3-ai-content-generator'); ?>
                             <?php if (!$replicate_addon_active): ?>
                                <?php esc_html_e('(Addon Disabled)', 'gpt3-ai-content-generator'); ?>
                            <?php endif; ?>
                        </option>
                        <option value="pexels" <?php disabled(!$stock_images_addon_active); ?>>
                            <?php esc_html_e('Pexels', 'gpt3-ai-content-generator'); ?>
                            <?php if (!$stock_images_addon_active): ?>
                                <?php esc_html_e('(Addon Disabled)', 'gpt3-ai-content-generator'); ?>
                            <?php endif; ?>
                        </option>
                        <option value="pixabay" <?php disabled(!$stock_images_addon_active); ?>>
                            <?php esc_html_e('Pixabay', 'gpt3-ai-content-generator'); ?>
                            <?php if (!$stock_images_addon_active): ?>
                                <?php esc_html_e('(Addon Disabled)', 'gpt3-ai-content-generator'); ?>
                            <?php endif; ?>
                        </option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col" id="aipkit_cw_image_model_group">
                    <label class="aipkit_form-label" for="aipkit_cw_image_model"><?php esc_html_e('Image Model', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_image_model" name="image_model" class="aipkit_form-input aipkit_autosave_trigger">
                        <?php // Populated by JS?>
                    </select>
                </div>
            </div>

            <div class="aipkit_form-row" id="aipkit_cw_pexels_options" style="display: none;">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pexels_orientation"><?php esc_html_e('Orientation', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pexels_orientation" name="pexels_orientation" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="none"><?php esc_html_e('None', 'gpt3-ai-content-generator'); ?></option>
                        <option value="landscape"><?php esc_html_e('Landscape', 'gpt3-ai-content-generator'); ?></option>
                        <option value="portrait"><?php esc_html_e('Portrait', 'gpt3-ai-content-generator'); ?></option>
                        <option value="square"><?php esc_html_e('Square', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pexels_size"><?php esc_html_e('Size', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pexels_size" name="pexels_size" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="none"><?php esc_html_e('None', 'gpt3-ai-content-generator'); ?></option>
                        <option value="large"><?php esc_html_e('Large', 'gpt3-ai-content-generator'); ?></option>
                        <option value="medium"><?php esc_html_e('Medium', 'gpt3-ai-content-generator'); ?></option>
                        <option value="small"><?php esc_html_e('Small', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pexels_color"><?php esc_html_e('Color', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pexels_color" name="pexels_color" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value=""><?php esc_html_e('Any Color', 'gpt3-ai-content-generator'); ?></option>
                        <option value="red"><?php esc_html_e('Red', 'gpt3-ai-content-generator'); ?></option>
                        <option value="orange"><?php esc_html_e('Orange', 'gpt3-ai-content-generator'); ?></option>
                        <option value="yellow"><?php esc_html_e('Yellow', 'gpt3-ai-content-generator'); ?></option>
                        <option value="green"><?php esc_html_e('Green', 'gpt3-ai-content-generator'); ?></option>
                        <option value="turquoise"><?php esc_html_e('Turquoise', 'gpt3-ai-content-generator'); ?></option>
                        <option value="blue"><?php esc_html_e('Blue', 'gpt3-ai-content-generator'); ?></option>
                        <option value="violet"><?php esc_html_e('Violet', 'gpt3-ai-content-generator'); ?></option>
                        <option value="pink"><?php esc_html_e('Pink', 'gpt3-ai-content-generator'); ?></option>
                        <option value="brown"><?php esc_html_e('Brown', 'gpt3-ai-content-generator'); ?></option>
                        <option value="black"><?php esc_html_e('Black', 'gpt3-ai-content-generator'); ?></option>
                        <option value="gray"><?php esc_html_e('Gray', 'gpt3-ai-content-generator'); ?></option>
                        <option value="white"><?php esc_html_e('White', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
            </div>

            <div class="aipkit_form-row" id="aipkit_cw_pixabay_options" style="display: none;">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pixabay_orientation"><?php esc_html_e('Orientation', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pixabay_orientation" name="pixabay_orientation" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="all"><?php esc_html_e('All', 'gpt3-ai-content-generator'); ?></option>
                        <option value="horizontal"><?php esc_html_e('Horizontal', 'gpt3-ai-content-generator'); ?></option>
                        <option value="vertical"><?php esc_html_e('Vertical', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pixabay_image_type"><?php esc_html_e('Image Type', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pixabay_image_type" name="pixabay_image_type" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="all"><?php esc_html_e('All', 'gpt3-ai-content-generator'); ?></option>
                        <option value="photo"><?php esc_html_e('Photo', 'gpt3-ai-content-generator'); ?></option>
                        <option value="illustration"><?php esc_html_e('Illustration', 'gpt3-ai-content-generator'); ?></option>
                        <option value="vector"><?php esc_html_e('Vector', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_pixabay_category"><?php esc_html_e('Category', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_pixabay_category" name="pixabay_category" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value=""><?php esc_html_e('Any Category', 'gpt3-ai-content-generator'); ?></option>
                        <?php
                        $pixabay_categories = ['backgrounds', 'fashion', 'nature', 'science', 'education', 'feelings', 'health', 'people', 'religion', 'places', 'animals', 'industry', 'computer', 'food', 'sports', 'transportation', 'travel', 'buildings', 'business', 'music'];
foreach ($pixabay_categories as $cat) {
    echo '<option value="' . esc_attr($cat) . '">' . esc_html(ucfirst($cat)) . '</option>';
}
?>
                    </select>
                </div>
            </div>

            <div class="aipkit_form-group">
                <label class="aipkit_form-label" for="aipkit_cw_image_prompt"><?php esc_html_e('Image Prompt', 'gpt3-ai-content-generator'); ?></label>
                <textarea id="aipkit_cw_image_prompt" name="image_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6" placeholder="<?php esc_attr_e('e.g., A photo of a freshly baked chocolate cake on a wooden table.', 'gpt3-ai-content-generator'); ?>"></textarea>
                <p class="aipkit_form-help"><?php
                    $text = __('Use placeholders: {topic}, {keywords}, {excerpt}, {post_title}.', 'gpt3-ai-content-generator');
$html = preg_replace_callback(
    '/(\{[a-zA-Z0-9_]+\})/',
    function ($matches) {
        return sprintf(
            '<code class="aipkit-placeholder" title="%s">%s</code>',
            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
            esc_html($matches[0])
        );
    },
    $text
);
echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
?></p>
            </div>

            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_image_count"><?php esc_html_e('Images', 'gpt3-ai-content-generator'); ?></label>
                    <input type="number" id="aipkit_cw_image_count" name="image_count" class="aipkit_form-input aipkit_autosave_trigger" value="1" min="1" max="10">
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_image_placement"><?php esc_html_e('Placement', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_image_placement" name="image_placement" class="aipkit_form-input aipkit_cw_image_placement_select aipkit_autosave_trigger">
                        <option value="after_first_h2"><?php esc_html_e('After 1st H2 Heading', 'gpt3-ai-content-generator'); ?></option>
                        <option value="after_first_h3"><?php esc_html_e('After 1st H3 Heading', 'gpt3-ai-content-generator'); ?></option>
                        <option value="after_every_x_h2"><?php esc_html_e('After every X H2 headings', 'gpt3-ai-content-generator'); ?></option>
                        <option value="after_every_x_h3"><?php esc_html_e('After every X H3 headings', 'gpt3-ai-content-generator'); ?></option>
                        <option value="after_every_x_p"><?php esc_html_e('After every X paragraphs', 'gpt3-ai-content-generator'); ?></option>
                        <option value="at_end"><?php esc_html_e('End of Content', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col aipkit_cw_image_placement_x_field" style="display: none;">
                    <label class="aipkit_form-label" for="aipkit_cw_image_placement_param_x"><?php esc_html_e('X Value', 'gpt3-ai-content-generator'); ?></label>
                    <input type="number" id="aipkit_cw_image_placement_param_x" name="image_placement_param_x" class="aipkit_form-input aipkit_autosave_trigger" value="2" min="1">
                </div>
            </div>
            
            <div class="aipkit_form-row">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_image_size"><?php esc_html_e('Size', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_image_size" name="image_size" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="large" selected><?php esc_html_e('Large', 'gpt3-ai-content-generator'); ?></option>
                        <option value="medium"><?php esc_html_e('Medium', 'gpt3-ai-content-generator'); ?></option>
                        <option value="thumbnail"><?php esc_html_e('Thumbnail', 'gpt3-ai-content-generator'); ?></option>
                        <option value="full"><?php esc_html_e('Full Size', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_cw_image_alignment"><?php esc_html_e('Alignment', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_cw_image_alignment" name="image_alignment" class="aipkit_form-input aipkit_autosave_trigger">
                        <option value="none" selected><?php esc_html_e('None', 'gpt3-ai-content-generator'); ?></option>
                        <option value="left"><?php esc_html_e('Left', 'gpt3-ai-content-generator'); ?></option>
                        <option value="center"><?php esc_html_e('Center', 'gpt3-ai-content-generator'); ?></option>
                        <option value="right"><?php esc_html_e('Right', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <hr class="aipkit_hr">
        <div class="aipkit_form-group aipkit_cw_featured_image_prompt_field" style="display:none;">
            <label class="aipkit_form-label" for="aipkit_cw_featured_image_prompt"><?php esc_html_e('Featured Image Prompt', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_cw_featured_image_prompt" name="featured_image_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="2" placeholder="<?php esc_attr_e('Leave blank to use the main image prompt.', 'gpt3-ai-content-generator'); ?>"></textarea>
            <p class="aipkit_form-help"><?php
$text = __('Use placeholders: {topic}, {keywords}.', 'gpt3-ai-content-generator');
$html = preg_replace_callback(
    '/(\{[a-zA-Z0-9_]+\})/',
    function ($matches) {
        return sprintf(
            '<code class="aipkit-placeholder" title="%s">%s</code>',
            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
            esc_html($matches[0])
        );
    },
    $text
);
echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
?></p>
        </div>
    </div>
</div>