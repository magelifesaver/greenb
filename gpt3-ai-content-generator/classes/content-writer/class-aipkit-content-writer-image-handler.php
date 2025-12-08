<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/class-aipkit-content-writer-image-handler.php

namespace WPAICG\ContentWriter;

use WPAICG\Images\AIPKit_Image_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class AIPKit_Content_Writer_Image_Handler
{
    private $image_manager;
    private $pexels_image_cache = []; // MODIFIED: Added instance property for Pexels caching

    public function __construct()
    {
        if (!class_exists(AIPKit_Image_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/images/class-aipkit-image-manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            } else {
                $this->image_manager = null;
                return;
            }
        }
        $this->image_manager = new AIPKit_Image_Manager();
    }

    public function generate_and_prepare_images(array $settings, string $final_title, string $final_keywords, ?string $original_topic = null): array|WP_Error
    {
        if (!$this->image_manager) {
            return new WP_Error('image_manager_missing', 'Image Manager dependency is not available.');
        }

        $generate_in_content = ($settings['generate_images_enabled'] ?? '0') === '1';
        $image_count = absint($settings['image_count'] ?? 1);
        $image_provider = strtolower($settings['image_provider'] ?? 'openai');
        $generate_featured = ($settings['generate_featured_image'] ?? '0') === '1';

        $main_image_prompt_template = $settings['image_prompt'] ?? '{topic}';
        $featured_image_prompt_template = !empty($settings['featured_image_prompt']) ? $settings['featured_image_prompt'] : $main_image_prompt_template;

        // Replace placeholders for AI-specific prompts
        $replacements = ['{topic}' => $final_title, '{keywords}' => $final_keywords];
        $ai_main_prompt = str_replace(array_keys($replacements), array_values($replacements), $main_image_prompt_template);
        $ai_featured_prompt = str_replace(array_keys($replacements), array_values($replacements), $featured_image_prompt_template);

        // --- MODIFICATION: "Keyword-First" search strategy for Pexels/Pixabay ---
        $stock_image_topic = !empty(trim($final_keywords)) ? trim($final_keywords) : (!empty($original_topic) ? $original_topic : $final_title);
        // --- END MODIFICATION ---
        $prompt_for_main_images = ($image_provider === 'pexels' || $image_provider === 'pixabay') ? $stock_image_topic : $ai_main_prompt;
        $prompt_for_featured_image = !empty($featured_image_prompt_template)
                                     ? (($image_provider === 'pexels' || $image_provider === 'pixabay') ? $stock_image_topic : $ai_featured_prompt)
                                     : $prompt_for_main_images; // Fallback to main prompt if featured is empty

        $final_image_data = [
            'in_content_images' => [],
            'featured_image_id' => null,
            'placement_settings' => [
                'placement' => $settings['image_placement'] ?? 'after_first_h2',
                'param_x' => absint($settings['image_placement_param_x'] ?? 2)
            ]
        ];

        $current_user_id = get_current_user_id() ?: 1;

        if ($image_provider === 'pexels' || $image_provider === 'pixabay') {
            $num_to_fetch = 0;
            if ($generate_in_content) {
                $num_to_fetch += $image_count;
            }
            if ($generate_featured) {
                $num_to_fetch++;
            }

            if ($num_to_fetch > 0) {
                $generation_options = ['n' => $num_to_fetch];
                if ($image_provider === 'pexels') {
                    $generation_options['provider'] = 'pexels';
                    $generation_options['orientation'] = $settings['pexels_orientation'] ?? 'none';
                    $generation_options['size'] = $settings['pexels_size'] ?? 'none';
                    $generation_options['color'] = $settings['pexels_color'] ?? '';
                } elseif ($image_provider === 'pixabay') {
                    $generation_options['provider'] = 'pixabay';
                    $generation_options['orientation'] = $settings['pixabay_orientation'] ?? 'all';
                    $generation_options['image_type'] = $settings['pixabay_image_type'] ?? 'all';
                    $generation_options['category'] = $settings['pixabay_category'] ?? '';
                }


                $stock_result = $this->image_manager->generate_image($prompt_for_main_images, $generation_options, $current_user_id);
                if (!is_wp_error($stock_result) && !empty($stock_result['images'])) {
                    $this->pexels_image_cache = $stock_result['images']; // Reusing this cache variable name for simplicity
                } else {
                    $error_msg = is_wp_error($stock_result) ? $stock_result->get_error_message() : "No images returned from {$image_provider} API.";
                }
            }

            // Populate in-content images from cache
            if ($generate_in_content && $image_count > 0) {
                $final_image_data['in_content_images'] = array_splice($this->pexels_image_cache, 0, $image_count);
            }

            // Populate featured image from the *remaining* cache
            if ($generate_featured) {
                $featured_image_data = array_shift($this->pexels_image_cache);
                if ($featured_image_data && !empty($featured_image_data['attachment_id'])) {
                    $final_image_data['featured_image_id'] = $featured_image_data['attachment_id'];
                }
            }
            return $final_image_data; // Return early for Pexels/Pixabay
        }


        // --- Original AI Generation Logic (for OpenAI, Google, etc.) ---
        // Main image generation
        if ($generate_in_content && $image_count > 0 && !empty($prompt_for_main_images)) {
            $image_model = $settings['image_model'] ?? 'gpt-image-1';
            $generation_options = [
                'provider' => strtolower($settings['image_provider'] ?? 'openai'),
                'model' => $image_model,
                'size' => '1024x1024',
                'response_format' => 'url',
                'user' => 'cw_user_' . $current_user_id,
                'quality' => 'standard',
                'style' => 'vivid'
            ];

            // Models that only support returning one image per request
            $models_with_n_equals_1 = ['dall-e-3', 'gpt-image-1'];
            if (strpos($image_model, 'gemini') !== false && strpos($image_model, 'image-generation') !== false) {
                $models_with_n_equals_1[] = $image_model; // handle all Gemini image-generation variants
            }

            if (in_array($image_model, $models_with_n_equals_1, true)) {
                for ($i = 0; $i < $image_count; $i++) {
                    $generation_options['n'] = 1;
                    $result = $this->image_manager->generate_image($prompt_for_main_images, $generation_options, $current_user_id);
                    if (!is_wp_error($result) && !empty($result['images'])) {
                        $final_image_data['in_content_images'][] = $result['images'][0];
                    } else {
                        $error_msg = is_wp_error($result) ? $result->get_error_message() : 'No images returned from API.';
                        // Log the error for debugging bulk mode issues
                        error_log("AIPKit Image Generation Error (Image #" . ($i + 1) . "): " . $error_msg . " | Model: " . $image_model . " | Provider: " . ($generation_options['provider'] ?? 'unknown'));
                    }
                }
            } else { // Models that support n > 1
                $max_n = 10;
                if (($settings['image_provider'] ?? 'openai') === 'google' && $image_model === 'imagen-3.0-generate-002') {
                    $max_n = 4;
                }
                $generation_options['n'] = min($image_count, $max_n);

                $result = $this->image_manager->generate_image($prompt_for_main_images, $generation_options, $current_user_id);
                if (!is_wp_error($result) && !empty($result['images'])) {
                    $final_image_data['in_content_images'] = array_merge($final_image_data['in_content_images'], $result['images']);
                } else {
                    $error_msg = is_wp_error($result) ? $result->get_error_message() : 'No images returned from API.';
                    // Log the error for debugging bulk mode issues
                    error_log("AIPKit Image Generation Error (Batch): " . $error_msg . " | Model: " . $image_model . " | Provider: " . ($generation_options['provider'] ?? 'unknown') . " | Count: " . $image_count);
                }
            }
        }

        // Featured image generation
        if ($generate_featured && !empty($prompt_for_featured_image)) {
            // Note: The original logic already had a separate call for the featured image for AI providers,
            // which is correct behavior as it might use a different prompt.
            $generation_options = [];
            if ($image_provider === 'pexels' || $image_provider === 'pixabay') { // This logic is now handled above, but as a fallback
                $generation_options = [
                    'provider' => $image_provider,
                    'orientation' => $settings[$image_provider.'_orientation'] ?? 'none',
                    'n' => 1
                ];
                if ($image_provider === 'pexels') {
                    $generation_options['size'] = $settings['pexels_size'] ?? 'none';
                    $generation_options['color'] = $settings['pexels_color'] ?? '';
                } else { // Pixabay
                    $generation_options['image_type'] = $settings['pixabay_image_type'] ?? 'all';
                    $generation_options['category'] = $settings['pixabay_category'] ?? '';
                }
            } else { // AI Generation
                $generation_options = [
                    'provider' => strtolower($settings['image_provider'] ?? 'openai'),
                    'model' => $settings['image_model'] ?? 'gpt-image-1',
                    'n' => 1,
                    'size' => '1024x1024',
                    'response_format' => 'url',
                    'user' => 'cw_user_featured_' . $current_user_id,
                    'quality' => 'hd',
                    'style' => 'vivid'
                ];
            }

            $result = $this->image_manager->generate_image($prompt_for_featured_image, $generation_options, $current_user_id);

            if (!is_wp_error($result) && !empty($result['images'][0]['attachment_id'])) {
                $final_image_data['featured_image_id'] = $result['images'][0]['attachment_id'];
            } else {
                $error_msg = is_wp_error($result) ? $result->get_error_message() : 'No featured image attachment ID returned.';
                // Log the error for debugging
                error_log("AIPKit Featured Image Generation Error: " . $error_msg . " | Model: " . ($generation_options['model'] ?? 'unknown') . " | Provider: " . ($generation_options['provider'] ?? 'unknown'));
            }
        }

        return $final_image_data;
    }
}
