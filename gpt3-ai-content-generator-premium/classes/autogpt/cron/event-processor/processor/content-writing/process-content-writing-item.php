<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-writing/process-content-writing-item.php
// Status: MODIFIED
// I have added a call to the new `update_post_slug_for_seo` function after all other post data has been saved.

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentWriting;

use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Summarizer;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Meta_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Keyword_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Excerpt_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Tags_Prompt_Builder; // ADDED
use WPAICG\ContentWriter\AIPKit_Content_Writer_Image_Handler;
use WP_Error;
use WPAICG\Chat\Storage\LogStorage;

if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once __DIR__ . '/generate-title-helper.php';
require_once __DIR__ . '/build-content-prompt.php';
require_once __DIR__ . '/generate-post-helper.php';
require_once __DIR__ . '/insert-post.php';

// Dependencies for new logic
if (!class_exists(AIPKit_Content_Writer_Summarizer::class)) {
    $summarizer_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/prompt/class-aipkit-content-writer-summarizer.php';
    if (file_exists($summarizer_path)) {
        require_once $summarizer_path;
    }
}
if (!class_exists(AIPKit_Content_Writer_Meta_Prompt_Builder::class)) {
    $meta_builder_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/prompt/class-aipkit-content-writer-meta-prompt-builder.php';
    if (file_exists($meta_builder_path)) {
        require_once $meta_builder_path;
    }
}
if (!class_exists(AIPKit_Content_Writer_Keyword_Prompt_Builder::class)) {
    $keyword_builder_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/prompt/class-aipkit-content-writer-keyword-prompt-builder.php';
    if (file_exists($keyword_builder_path)) {
        require_once $keyword_builder_path;
    }
}
if (!class_exists(AIPKit_Content_Writer_Excerpt_Prompt_Builder::class)) {
    $excerpt_builder_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/prompt/class-aipkit-content-writer-excerpt-prompt-builder.php';
    if (file_exists($excerpt_builder_path)) {
        require_once $excerpt_builder_path;
    }
}
// --- ADDED: Load Tags Prompt Builder ---
if (!class_exists(AIPKit_Content_Writer_Tags_Prompt_Builder::class)) {
    $tags_builder_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/prompt/class-aipkit-content-writer-tags-prompt-builder.php';
    if (file_exists($tags_builder_path)) {
        require_once $tags_builder_path;
    }
}
// --- END ADDED ---
if (!class_exists(AIPKit_Content_Writer_Image_Handler::class)) {
    $image_handler_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/class-aipkit-content-writer-image-handler.php';
    if (file_exists($image_handler_path)) {
        require_once $image_handler_path;
    }
}

// --- ADDED: Load set_post_tags_logic function ---
if (!function_exists('\WPAICG\ContentWriter\Ajax\Actions\SavePost\set_post_tags_logic')) {
    $set_tags_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/ajax/actions/save-post/set-post-tags.php';
    if (file_exists($set_tags_path)) {
        require_once $set_tags_path;
    }
}
// --- END ADDED ---

// --- ADDED: Load SEO Helper for slug generation ---
if (!class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
    $seo_helper_path = WPAICG_PLUGIN_DIR . 'classes/seo/seo-helper.php';
    if (file_exists($seo_helper_path)) {
        require_once $seo_helper_path;
    }
}
// --- END ADDED ---


/**
 * Orchestrates the entire process of generating a single piece of content from a queue item.
 *
 * @param array $item_config The configuration for the specific queue item.
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function process_content_writing_item_logic(array $item_config): array
{
    // --- START: Abuse Prevention ---
    $generation_mode = $item_config['cw_generation_mode'] ?? 'single';
    if (in_array($generation_mode, ['rss', 'gsheets', 'url'])) { // 'url' is also Pro
        if (!class_exists('\WPAICG\aipkit_dashboard') || !\WPAICG\aipkit_dashboard::is_pro_plan()) {
            $error_message = __('License is not active.', 'gpt3-ai-content-generator');
            return ['status' => 'error', 'message' => $error_message];
        }
    }
    // --- END: Abuse Prevention ---

    $ai_caller = new AIPKit_AI_Caller();

    // 1. Generate Title (if needed)
    $title_result = generate_title_logic($item_config, $ai_caller);
    if (is_wp_error($title_result)) {
        return ['status' => 'error', 'message' => $title_result->get_error_message()];
    }
    $final_title = $title_result['title'];
    $title_usage = $title_result['usage'] ?? null;

    // 2. Build Prompts
    $config_for_prompt = array_merge($item_config, ['content_title' => $final_title]);
    $prompts = build_content_prompts_logic($config_for_prompt);

    // 3. Generate Post Content
    $content_result = generate_post_logic($prompts, $item_config, $ai_caller);
    if (is_wp_error($content_result)) {
        return ['status' => 'error', 'message' => $content_result->get_error_message()];
    }
    $generated_content = $content_result['content'];
    $content_usage = $content_result['usage'] ?? null;

    // 4. Generate Images
    $image_data = null;
    $image_usage = null;
    if ((($item_config['generate_images_enabled'] ?? '0') === '1' || ($item_config['generate_featured_image'] ?? '0') === '1') && class_exists(AIPKit_Content_Writer_Image_Handler::class)) {
        $image_handler = new AIPKit_Content_Writer_Image_Handler();
        $keywords_for_images = !empty($item_config['inline_keywords']) ? $item_config['inline_keywords'] : ($item_config['content_keywords'] ?? '');
        $image_result = $image_handler->generate_and_prepare_images($item_config, $final_title, $keywords_for_images, $item_config['content_title']);

        if (is_wp_error($image_result)) {
            // Don't stop the whole process, just log the error and continue without images.
            $error_details = $image_result->get_error_message();
            $error_code = $image_result->get_error_code();
            error_log("AIPKit AutoGPT Image Generation Failed: [{$error_code}] {$error_details} | Title: {$final_title} | Provider: " . ($item_config['image_provider'] ?? 'unknown') . " | Model: " . ($item_config['image_model'] ?? 'unknown'));
        } else {
            $image_data = $image_result;
            // The image handler does not currently return usage data.
            // $image_usage = $image_result['usage'] ?? null; // This line would be correct if handler returned usage
        }
    }

    // 5. Generate SEO Data
    $meta_description = null;
    $focus_keyword = null;
    $excerpt = null;
    $tags = null; // ADDED
    $meta_usage = null;
    $keyword_usage = null;
    $excerpt_usage = null;
    $tags_usage = null; // ADDED

    $generate_meta = (isset($item_config['generate_meta_description']) && $item_config['generate_meta_description'] === '1');
    $generate_keyword = (isset($item_config['generate_focus_keyword']) && $item_config['generate_focus_keyword'] === '1');
    $generate_excerpt = (isset($item_config['generate_excerpt']) && $item_config['generate_excerpt'] === '1');
    $generate_tags = (isset($item_config['generate_tags']) && $item_config['generate_tags'] === '1'); // ADDED
    $prompt_mode = $item_config['prompt_mode'] ?? 'custom'; // For AutoGPT, we assume prompts are always custom
    $should_generate_seo = ($generate_meta || $generate_keyword || $generate_excerpt || $generate_tags) && !empty($generated_content); // ADDED tags to condition

    if ($should_generate_seo) {
        $content_summary = AIPKit_Content_Writer_Summarizer::summarize($generated_content);
        $final_keywords = !empty($item_config['inline_keywords']) ? $item_config['inline_keywords'] : ($item_config['content_keywords'] ?? '');

        if ($generate_keyword && empty($final_keywords)) { // Only generate if not provided
            $custom_keyword_prompt = $item_config['custom_keyword_prompt'] ?? null;
            $keyword_user_prompt = AIPKit_Content_Writer_Keyword_Prompt_Builder::build($final_title, $content_summary, $prompt_mode, $custom_keyword_prompt);
            $keyword_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 0.2];
            $keyword_result = $ai_caller->make_standard_call($item_config['ai_provider'], $item_config['ai_model'], [['role' => 'user', 'content' => $keyword_user_prompt]], $keyword_ai_params, 'You are an SEO expert. Your task is to provide the single best focus keyword for a piece of content.');
            if (!is_wp_error($keyword_result) && !empty($keyword_result['content'])) {
                $focus_keyword = trim(str_replace(['"', "'", '.'], '', $keyword_result['content']));
                $final_keywords = $focus_keyword; // Use this new keyword for other SEO prompts
                $keyword_usage = $keyword_result['usage'] ?? null;
            }
        } elseif (!empty($final_keywords)) {
            $focus_keyword = explode(',', $final_keywords)[0]; // Use first provided keyword as focus keyword
        }

        if ($generate_excerpt && class_exists(AIPKit_Content_Writer_Excerpt_Prompt_Builder::class)) {
            $custom_excerpt_prompt = $item_config['custom_excerpt_prompt'] ?? null;
            $excerpt_user_prompt = AIPKit_Content_Writer_Excerpt_Prompt_Builder::build($final_title, $content_summary, $final_keywords, $prompt_mode, $custom_excerpt_prompt);
            $excerpt_system_instruction = 'You are an expert copywriter. Your task is to provide an engaging excerpt for a piece of content.';
            $excerpt_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 1];

            $excerpt_result = $ai_caller->make_standard_call(
                $item_config['ai_provider'],
                $item_config['ai_model'],
                [['role' => 'user', 'content' => $excerpt_user_prompt]],
                $excerpt_ai_params,
                $excerpt_system_instruction
            );
            if (!is_wp_error($excerpt_result) && !empty($excerpt_result['content'])) {
                $excerpt = trim(str_replace(['"', "'"], '', $excerpt_result['content']));
                $excerpt_usage = $excerpt_result['usage'] ?? null;
            }
        }

        // --- ADDED: Tags Generation Logic ---
        if ($generate_tags && class_exists(AIPKit_Content_Writer_Tags_Prompt_Builder::class)) {
            $custom_tags_prompt = $item_config['custom_tags_prompt'] ?? null;
            $tags_user_prompt = AIPKit_Content_Writer_Tags_Prompt_Builder::build($final_title, $content_summary, $final_keywords, $prompt_mode, $custom_tags_prompt);
            $tags_system_instruction = 'You are an SEO expert. Your task is to provide a comma-separated list of relevant tags for a piece of content.';
            $tags_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 0.5];

            $tags_result = $ai_caller->make_standard_call(
                $item_config['ai_provider'],
                $item_config['ai_model'],
                [['role' => 'user', 'content' => $tags_user_prompt]],
                $tags_ai_params,
                $tags_system_instruction
            );
            if (!is_wp_error($tags_result) && !empty($tags_result['content'])) {
                $tags = trim(str_replace(['"', "'"], '', $tags_result['content']));
                $tags_usage = $tags_result['usage'] ?? null;
            }
        }
        // --- END ADDED ---

        if ($generate_meta && class_exists(AIPKit_Content_Writer_Meta_Prompt_Builder::class)) {
            $custom_meta_prompt = $item_config['custom_meta_prompt'] ?? null;
            $meta_user_prompt = AIPKit_Content_Writer_Meta_Prompt_Builder::build($final_title, $content_summary, $final_keywords, $prompt_mode, $custom_meta_prompt);
            $meta_system_instruction = 'You are an SEO expert specializing in writing meta descriptions.';
            $meta_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 1];

            $meta_result = $ai_caller->make_standard_call(
                $item_config['ai_provider'],
                $item_config['ai_model'],
                [['role' => 'user', 'content' => $meta_user_prompt]],
                $meta_ai_params,
                $meta_system_instruction
            );

            if (!is_wp_error($meta_result) && !empty($meta_result['content'])) {
                $meta_description = trim(str_replace(['"', "'"], '', $meta_result['content']));
                $meta_usage = $meta_result['usage'] ?? null;
            }
        }
    }
    // --- END Generate SEO Data ---

    // 6. Insert Post
    $scheduled_gmt_time = $item_config['scheduled_gmt_time'] ?? null;
    $insert_result = insert_post_logic($final_title, $generated_content, $item_config, $meta_description, $focus_keyword, $image_data, $excerpt, $scheduled_gmt_time);
    if (is_wp_error($insert_result)) {
        return ['status' => 'error', 'message' => $insert_result->get_error_message()];
    }
    $new_post_id = $insert_result;

    // --- ADDED: Log processed RSS item GUID to history ---
    if (isset($item_config['rss_item_guid']) && !empty($item_config['rss_item_guid']) && isset($item_config['task_id'])) {
        global $wpdb;
        $history_table_name = $wpdb->prefix . 'aipkit_rss_history';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct insert to a custom table. Caches will be invalidated.
        $wpdb->insert(
            $history_table_name,
            [
                'task_id'   => absint($item_config['task_id']),
                'item_guid' => $item_config['rss_item_guid'],
            ],
            ['%d', '%s']
        );
    }
    // --- END ADDED ---

    // --- MODIFIED: Save Tags using the new helper ---
    if (!empty($tags) && class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_tags($new_post_id, $tags);
    }
    // --- END MODIFICATION ---


    // --- NEW: Update Slug based on checkbox ---
    if (isset($item_config['generate_seo_slug']) && $item_config['generate_seo_slug'] === '1' && class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_post_slug_for_seo($new_post_id);
    }
    // --- END NEW ---

    // --- NEW: Update Google Sheet Status ---
    if (isset($item_config['cw_generation_mode']) && $item_config['cw_generation_mode'] === 'gsheets' &&
        isset($item_config['gsheets_row_index']) && isset($item_config['gsheets_sheet_id'])) {
        if (class_exists('\WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser')) {
            try {
                $credentials_array = $item_config['gsheets_credentials'] ?? [];
                if (!empty($credentials_array)) {
                    $sheets_parser = new \WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser($credentials_array);
                    $status_to_write = 'Processed on ' . current_time('mysql');
                    $sheets_parser->update_row_status(
                        $item_config['gsheets_sheet_id'],
                        $item_config['gsheets_row_index'],
                        $status_to_write
                    );
                }
            } catch (\Exception $e) {
                // Don't fail the whole post generation for this. Just log the error.
            }
        }
    }
    // --- END NEW ---

    // 7. Log the generated content
    $total_usage = ['input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'provider_raw' => []];
    $all_usages = array_filter([$title_usage, $content_usage, $meta_usage, $keyword_usage, $image_usage, $excerpt_usage, $tags_usage]); // ADDED tags_usage
    foreach ($all_usages as $usage) {
        if (is_array($usage)) {
            $total_usage['input_tokens'] += (int)($usage['input_tokens'] ?? 0);
            $total_usage['output_tokens'] += (int)($usage['output_tokens'] ?? 0);
            $total_usage['total_tokens'] += (int)($usage['total_tokens'] ?? 0);
            if (isset($usage['provider_raw'])) {
                $total_usage['provider_raw'][] = $usage['provider_raw'];
            }
        }
    }
    if (class_exists(LogStorage::class)) {
        $log_storage = new LogStorage();
        $post_author_id = $item_config['post_author'] ?? 1;
        $author_data = get_userdata($post_author_id);
        $log_data = [
            'bot_id'            => null,
            'user_id'           => $post_author_id,
            'session_id'        => null,
            'conversation_uuid' => wp_generate_uuid4(),
            'module'            => 'content_writer_automation',
            'is_guest'          => 0,
            'role'              => $author_data ? implode(', ', $author_data->roles) : null,
            'ip_address'        => null,
            'message_role'      => 'bot',
            'message_content'   => "Automated Post Generated: " . esc_html($final_title),
            'timestamp'         => time(),
            'ai_provider'       => $item_config['ai_provider'],
            'ai_model'          => $item_config['ai_model'],
            'usage'             => $total_usage,
            'request_payload'   => ['item_config' => $item_config, 'prompts' => $prompts],
            'response_data'     => ['post_id' => $new_post_id, 'title' => $final_title, 'meta' => $meta_description, 'keyword' => $focus_keyword, 'excerpt' => $excerpt, 'tags' => $tags] // ADDED tags
        ];
        $log_storage->log_message($log_data);
    }

    return ['status' => 'success', 'message' => 'Content generated and post created (ID: ' . $new_post_id . ').'];
}