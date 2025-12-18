<?php
/**
 * Partial: Chatbot Custom Theme Settings (Main Orchestrator)
 *
 * Single-file Custom Theme Settings (consolidated)
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager;

// Variables available from parent script (accordion-appearance.php):
// $bot_id, $bot_settings

$custom_theme_defaults = BotSettingsManager::get_custom_theme_defaults();

// Helper function to get saved value or default
$get_cts_val = function($key) use ($bot_settings, $custom_theme_defaults) {
    $custom_settings = $bot_settings['custom_theme_settings'] ?? [];
    return $custom_settings[$key] ?? ($custom_theme_defaults[$key] ?? '');
};

// Helper to escape attribute values from the custom theme settings
$esc_cts_val_attr = function($key) use ($get_cts_val) {
    return esc_attr($get_cts_val($key));
};

// Font families array, used by general-appearance-settings.php
$font_families = [
    'System' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
    'Arial' => 'Arial, Helvetica, sans-serif',
    'Verdana' => 'Verdana, Geneva, sans-serif',
    'Tahoma' => 'Tahoma, Geneva, sans-serif',
    'Trebuchet MS' => '"Trebuchet MS", Helvetica, sans-serif',
    '"Times New Roman", Times, serif',
    'Georgia' => 'Georgia, serif',
    'Garamond' => 'Garamond, serif',
    '"Courier New", Courier, monospace',
    '"Brush Script MT", cursive',
];

?>
<div
    class="aipkit_custom_theme_settings_container"
    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_custom_theme_settings_container"
    data-defaults="<?php echo esc_attr(wp_json_encode($custom_theme_defaults)); ?>"
>
    <!-- Segmented controls for compact navigation inside modal -->
    <div class="aipkit_segmented_controls" role="tablist" aria-label="<?php esc_attr_e('Custom Theme Sections', 'gpt3-ai-content-generator'); ?>">
        <button type="button" class="aipkit_segmented_btn is-active" data-segment="general" role="tab" aria-selected="true"><?php esc_html_e('General', 'gpt3-ai-content-generator'); ?></button>
        <button type="button" class="aipkit_segmented_btn" data-segment="colors" role="tab" aria-selected="false"><?php esc_html_e('Colors', 'gpt3-ai-content-generator'); ?></button>
    </div>

    <div class="aipkit_segmented_container">
        <!-- General (General + Layout + Header) -->
        <section class="aipkit_settings_section is-active-segment" data-segment="general">
            <div class="aipkit_settings_section-body">
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Typography', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid">
                      <!-- Font Family -->
                      <div class="aipkit_form-group aipkit_form-col">
                          <label class="aipkit_form-label" for="cts_font_family_<?php echo esc_attr($bot_id); ?>">
                              <?php esc_html_e('Font Family', 'gpt3-ai-content-generator'); ?>
                          </label>
                          <select id="cts_font_family_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[font_family]" class="aipkit_form-input">
                              <?php foreach($font_families as $name => $stack): ?>
                                  <option value="<?php echo esc_attr($stack); ?>" <?php selected($get_cts_val('font_family'), $stack); ?>>
                                      <?php echo esc_html(is_string($name) ? $name : $stack); ?>
                                  </option>
                              <?php endforeach; ?>
                              <option value="inherit" <?php selected($get_cts_val('font_family'), 'inherit'); ?>>
                                  <?php esc_html_e('Inherit from Page', 'gpt3-ai-content-generator'); ?>
                              </option>
                          </select>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Typography & Layout (compact, aligned) -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Layout', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid">
                    <!-- Bubble Border Radius -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_bubble_border_radius_<?php echo esc_attr($bot_id); ?>">
                            <?php esc_html_e('Bubble Border Radius (px)', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <div class="aipkit_slider_wrapper">
                            <input
                                type="range"
                                id="cts_bubble_border_radius_<?php echo esc_attr($bot_id); ?>"
                                name="custom_theme_settings[bubble_border_radius]"
                                class="aipkit_form-input aipkit_range_slider"
                                value="<?php echo $esc_cts_val_attr('bubble_border_radius'); // phpcs:ignore ?>"
                                min="0" max="50" step="1"
                            />
                            <span id="cts_bubble_border_radius_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <div class="aipkit_form-help">
                            <?php esc_html_e('Controls the roundness of message bubbles.', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>

                    <!-- Inline Max Width -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_max_width_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Inline Max Width (px)', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="cts_container_max_width_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_max_width]" class="aipkit_form-input aipkit_range_slider" value="<?php echo esc_attr($esc_cts_val_attr('container_max_width')); ?>" min="200" max="1200" step="10">
                            <span id="cts_container_max_width_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <p class="aipkit_form-help"><?php esc_html_e('Max width for inline chat (e.g., 650).', 'gpt3-ai-content-generator'); ?></p>
                    </div>

                    <!-- Popup Width -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_popup_width_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Popup Width (px)', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="cts_popup_width_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[popup_width]" class="aipkit_form-input aipkit_range_slider" value="<?php echo esc_attr($esc_cts_val_attr('popup_width')); ?>" min="200" max="1000" step="10">
                            <span id="cts_popup_width_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <p class="aipkit_form-help"><?php esc_html_e('Width for popup on desktop (e.g., 400).', 'gpt3-ai-content-generator'); ?></p>
                    </div>

                    <!-- Initial Height -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_height_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Initial Height (px)', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="cts_container_height_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_height]" class="aipkit_form-input aipkit_range_slider" value="<?php echo esc_attr($esc_cts_val_attr('container_height')); ?>" min="100" max="1000" step="10">
                            <span id="cts_container_height_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <p class="aipkit_form-help"><?php esc_html_e('Preferred starting height (e.g., 450).', 'gpt3-ai-content-generator'); ?></p>
                    </div>

                    <!-- Min Height -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_min_height_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Min Height (px)', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="cts_container_min_height_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_min_height]" class="aipkit_form-input aipkit_range_slider" value="<?php echo esc_attr($esc_cts_val_attr('container_min_height')); ?>" min="50" max="800" step="10">
                            <span id="cts_container_min_height_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <p class="aipkit_form-help"><?php esc_html_e('Minimum height (e.g., 250).', 'gpt3-ai-content-generator'); ?></p>
                    </div>

                    <!-- Max Height -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_max_height_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Max Height (Viewport %)', 'gpt3-ai-content-generator'); ?></label>
                        <div class="aipkit_slider_wrapper">
                            <input type="range" id="cts_container_max_height_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_max_height]" class="aipkit_form-input aipkit_range_slider" value="<?php echo esc_attr($esc_cts_val_attr('container_max_height')); ?>" min="10" max="100" step="1" data-suffix="%">
                            <span id="cts_container_max_height_<?php echo esc_attr($bot_id); ?>_value" class="aipkit_slider_value"></span>
                        </div>
                        <p class="aipkit_form-help"><?php esc_html_e('Max height as % of window', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    </div>
                  </div>
                </div>
            </div>
        </section>

        <!-- Colors -->
        <section class="aipkit_settings_section" data-segment="colors">
            <div class="aipkit_settings_section-body">
                <!-- Global Colors -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Global Colors', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid_color_picker">
                    <!-- Messages Area Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_messages_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Messages Area', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_messages_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[messages_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('messages_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background of the scrollable conversation area.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Container Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Container', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_container_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('container_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Overall chat widget background.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Container Text -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Container Text', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_container_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('container_text_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Default text color used across the chat area.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Container Border -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_container_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Container Border', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_container_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[container_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('container_border_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Outline/border color of the chat container.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Header Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_header_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Header', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_header_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[header_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('header_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Top bar background in the chat header.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Header Text & Icons -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_header_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Header Icons', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_header_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[header_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('header_text_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Title and icons color in the header.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Header Border -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_header_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Header Border', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_header_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[header_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('header_border_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Divider/outline below the header bar.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    </div>
                  </div>
                </div>

                <!-- Bubble & Footer Colors (combined in one row) -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Bubble & Footer', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid_color_picker">
                    <!-- Bot Bubble Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_bot_bubble_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Bot Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_bot_bubble_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[bot_bubble_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('bot_bubble_bg_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background for assistant/bot message bubbles.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Bot Bubble Text -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_bot_bubble_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Bot Text', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_bot_bubble_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[bot_bubble_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('bot_bubble_text_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Text color inside bot message bubbles.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- User Bubble Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_user_bubble_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('User Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_user_bubble_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[user_bubble_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('user_bubble_bg_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background for your message bubbles.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- User Bubble Text -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_user_bubble_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('User Text', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_user_bubble_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[user_bubble_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('user_bubble_text_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Text color inside your message bubbles.', 'gpt3-ai-content-generator'); ?></div>
                    </div>

                    <!-- Footer Background -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_footer_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Footer', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_footer_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[footer_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('footer_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Footer bar background below the input.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Footer Text -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_footer_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Footer Text', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_footer_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[footer_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('footer_text_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Text color used in the footer area.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <!-- Footer Border -->
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_footer_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Footer Border', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_footer_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[footer_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('footer_border_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Divider/outline above the footer area.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    </div>
                  </div>
                </div>

                <!-- Input Area -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Input Area', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid_color_picker">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_input_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Input Bar', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_input_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[input_area_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('input_area_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background of the bottom input bar.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_input_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Input Text', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_input_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[input_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('input_text_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Text color inside the input field.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_input_wrapper_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Textarea', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_input_wrapper_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[input_wrapper_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('input_wrapper_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background of the textarea field.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_input_wrapper_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Textarea Border', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_input_wrapper_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[input_wrapper_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('input_wrapper_border_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Border color of the textarea field.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_send_button_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Send Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_send_button_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[send_button_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('send_button_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background of the send button.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_send_button_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Send Icon', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_send_button_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[send_button_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('send_button_text_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Icon color of the send button.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    </div>
                  </div>
                </div>

                <!-- Action & Utility Buttons -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Action Buttons', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid_color_picker">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_action_button_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_action_button_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[action_button_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('action_button_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Default background for action buttons.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_action_button_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Icon/Text Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_action_button_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[action_button_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('action_button_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Default color for action buttons.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_action_button_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Border Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_action_button_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[action_button_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('action_button_border_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Border color for action buttons.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_action_button_hover_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Hover Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_action_button_hover_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[action_button_hover_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('action_button_hover_bg_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background on hover for action buttons.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_action_button_hover_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Hover Icon/Text Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_action_button_hover_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[action_button_hover_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo $esc_cts_val_attr('action_button_hover_color'); // phpcs:ignore ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Icon/text color on hover for action buttons.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    </div>
                  </div>
                </div>

                <!-- Sidebar (if enabled) -->
                <div class="aipkit_settings_subsection">
                  <div class="aipkit_settings_subsection-header">
                    <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Conversation Sidebar', 'gpt3-ai-content-generator'); ?></h5>
                  </div>
                  <div class="aipkit_settings_subsection-body">
                    <div class="aipkit_settings_grid_color_picker">
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_sidebar_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Background', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_sidebar_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[sidebar_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('sidebar_bg_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Conversation list sidebar background.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_sidebar_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Text Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_sidebar_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[sidebar_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('sidebar_text_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Default text color in the sidebar.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_sidebar_border_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Border Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_sidebar_border_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[sidebar_border_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('sidebar_border_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Border/divider color for the sidebar.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_sidebar_active_bg_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Active Item', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_sidebar_active_bg_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[sidebar_active_bg_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('sidebar_active_bg_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Background for the selected/active item.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    <div class="aipkit_form-group aipkit_form-col">
                        <label class="aipkit_form-label" for="cts_sidebar_active_text_color_<?php echo esc_attr($bot_id); ?>"><?php esc_html_e('Active Item Text Color', 'gpt3-ai-content-generator'); ?></label>
                        <input type="color" id="cts_sidebar_active_text_color_<?php echo esc_attr($bot_id); ?>" name="custom_theme_settings[sidebar_active_text_color]" class="aipkit_form-input aipkit_color_picker_input" value="<?php echo esc_attr($esc_cts_val_attr('sidebar_active_text_color')); ?>">
                        <div class="aipkit_form-help"><?php esc_html_e('Text color for the selected/active item.', 'gpt3-ai-content-generator'); ?></div>
                    </div>
                    </div>
                  </div>
                </div>

                
            </div>
        </section>
    </div>
</div>
