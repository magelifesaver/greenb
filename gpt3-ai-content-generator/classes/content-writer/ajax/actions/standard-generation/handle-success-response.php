<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/standard-generation/handle-success-response.php
// Status: MODIFIED
// I have updated this file to also generate and include the excerpt in the success response.

namespace WPAICG\ContentWriter\Ajax\Actions\StandardGeneration;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Standard_Generation_Action;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Summarizer;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Meta_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Keyword_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Excerpt_Prompt_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Tags_Prompt_Builder; // ADDED
use WPAICG\ContentWriter\AIPKit_Content_Writer_Image_Handler; // Added for images
use WP_Error;
use WPAICG\Chat\Storage\LogStorage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles a successful response from the AI call by logging it and sending a JSON success response.
 * MODIFIED: Now generates and includes SEO meta description and focus keyword if requested.
 * MODIFIED: Now generates and includes AI images if requested.
 * REFACTORED: Re-ordered SEO generation to prioritize focus keyword.
 *
 * @param AIPKit_Content_Writer_Standard_Generation_Action $handler The handler instance.
 * @param array $result The successful result array from the AI call.
 * @param array $validated_params The validated parameters from the request.
 * @param string $conversation_uuid The UUID of this interaction.
 * @return void
 */
function handle_success_response_logic(AIPKit_Content_Writer_Standard_Generation_Action $handler, array $result, array $validated_params, string $conversation_uuid): void
{
    $content = $result['content'] ?? '';
    $usage = $result['usage'] ?? null;
    $request_payload_log = $result['request_payload_log'] ?? null;
    $meta_description = null;
    $focus_keyword = null;
    $excerpt = null;
    $tags = null;

    // Log main content generation
    if ($handler->log_storage) {
        $handler->log_storage->log_message([
            'bot_id'            => null,
            'user_id'           => get_current_user_id(),
            'session_id'        => null,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'content_writer',
            'is_guest'          => 0,
            'role'              => implode(', ', wp_get_current_user()->roles),
            'ip_address'        => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
            'message_role'      => 'bot',
            'message_content'   => $content,
            'timestamp'         => time(),
            'ai_provider'       => $validated_params['provider'],
            'ai_model'          => $validated_params['model'],
            'usage'             => $usage,
            'request_payload'   => $request_payload_log,
        ]);
    }

    $final_title = $validated_params['content_title'] ?? '';
    $user_provided_keywords = !empty($validated_params['inline_keywords']) ? $validated_params['inline_keywords'] : ($validated_params['content_keywords'] ?? '');

    // --- START REFACTORED SEO LOGIC ---
    $keywords_for_prompts = $user_provided_keywords;

    // 1. Generate Focus Keyword FIRST if needed.
    $generate_keyword = ($validated_params['generate_focus_keyword'] ?? '0') === '1';
    if ($generate_keyword && empty($user_provided_keywords) && !empty($content)) {
        $content_summary_for_kw = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Summarizer::summarize($content);
        $keyword_user_prompt = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Keyword_Prompt_Builder::build($final_title, $content_summary_for_kw, 'custom', $validated_params['custom_keyword_prompt']);
        $keyword_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 1];
        $keyword_result = $handler->get_ai_caller()->make_standard_call(
            $validated_params['provider'],
            $validated_params['model'],
            [['role' => 'user', 'content' => $keyword_user_prompt]],
            $keyword_ai_params,
            'You are an SEO expert. Your task is to provide the single best focus keyword for a piece of content.'
        );
        if (!is_wp_error($keyword_result) && !empty($keyword_result['content'])) {
            $focus_keyword = trim(str_replace(['"', "'", '.'], '', $keyword_result['content']));
            $keywords_for_prompts = $focus_keyword; // Use this new keyword for other SEO prompts
            // Log keyword step
            if ($handler->log_storage) {
                $base = [
                    'bot_id' => null,
                    'user_id' => get_current_user_id(),
                    'session_id' => null,
                    'conversation_uuid' => $conversation_uuid,
                    'module' => 'content_writer',
                    'is_guest' => 0,
                    'role' => implode(', ', wp_get_current_user()->roles),
                    'ip_address' => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
                    'timestamp' => time(),
                    'ai_provider' => $validated_params['provider'],
                    'ai_model' => $validated_params['model'],
                ];
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'user',
                    'message_content' => 'Generate Focus Keyword',
                    'request_payload' => [
                        'title' => $final_title,
                        'custom_keyword_prompt' => $validated_params['custom_keyword_prompt'] ?? null,
                    ],
                ]));
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'bot',
                    'message_content' => $focus_keyword,
                    'usage' => $keyword_result['usage'] ?? null,
                    'request_payload' => [
                        'payload_sent' => [
                            'messages' => [['role' => 'user', 'content' => $keyword_user_prompt]],
                            'ai_params' => $keyword_ai_params,
                        ],
                    ],
                ]));
            }
        }
    } elseif ($generate_keyword) {
        $focus_keyword = explode(',', $user_provided_keywords)[0];
    }

    $content_summary = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Summarizer::summarize($content);

    // 2. Generate Excerpt
    if (($validated_params['generate_excerpt'] ?? '0') === '1' && !empty($content)) {
        $excerpt_user_prompt = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Excerpt_Prompt_Builder::build($final_title, $content_summary, $keywords_for_prompts, 'custom', $validated_params['custom_excerpt_prompt']);
        $excerpt_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 1];
        $excerpt_result = $handler->get_ai_caller()->make_standard_call($validated_params['provider'], $validated_params['model'], [['role' => 'user', 'content' => $excerpt_user_prompt]], $excerpt_ai_params);
        if (!is_wp_error($excerpt_result) && !empty($excerpt_result['content'])) {
            $excerpt = trim(str_replace(['"', "'"], '', $excerpt_result['content']));
            if ($handler->log_storage) {
                $base = [
                    'bot_id' => null,
                    'user_id' => get_current_user_id(),
                    'session_id' => null,
                    'conversation_uuid' => $conversation_uuid,
                    'module' => 'content_writer',
                    'is_guest' => 0,
                    'role' => implode(', ', wp_get_current_user()->roles),
                    'ip_address' => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
                    'timestamp' => time(),
                    'ai_provider' => $validated_params['provider'],
                    'ai_model' => $validated_params['model'],
                ];
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'user',
                    'message_content' => 'Generate Excerpt',
                    'request_payload' => [
                        'title' => $final_title,
                        'keywords' => $keywords_for_prompts,
                        'custom_excerpt_prompt' => $validated_params['custom_excerpt_prompt'] ?? null,
                    ],
                ]));
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'bot',
                    'message_content' => $excerpt,
                    'usage' => $excerpt_result['usage'] ?? null,
                    'request_payload' => [
                        'payload_sent' => [
                            'messages' => [['role' => 'user', 'content' => $excerpt_user_prompt]],
                            'ai_params' => $excerpt_ai_params,
                        ],
                    ],
                ]));
            }
        }
    }

    // 3. Generate Tags
    if (($validated_params['generate_tags'] ?? '0') === '1' && !empty($content)) {
        $tags_user_prompt = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Tags_Prompt_Builder::build($final_title, $content_summary, $keywords_for_prompts, 'custom', $validated_params['custom_tags_prompt']);
        $tags_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 0.5];
        $tags_result = $handler->get_ai_caller()->make_standard_call($validated_params['provider'], $validated_params['model'], [['role' => 'user', 'content' => $tags_user_prompt]], $tags_ai_params);
        if (!is_wp_error($tags_result) && !empty($tags_result['content'])) {
            $tags = trim(str_replace(['"', "'"], '', $tags_result['content']));
            if ($handler->log_storage) {
                $base = [
                    'bot_id' => null,
                    'user_id' => get_current_user_id(),
                    'session_id' => null,
                    'conversation_uuid' => $conversation_uuid,
                    'module' => 'content_writer',
                    'is_guest' => 0,
                    'role' => implode(', ', wp_get_current_user()->roles),
                    'ip_address' => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
                    'timestamp' => time(),
                    'ai_provider' => $validated_params['provider'],
                    'ai_model' => $validated_params['model'],
                ];
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'user',
                    'message_content' => 'Generate Tags',
                    'request_payload' => [
                        'title' => $final_title,
                        'keywords' => $keywords_for_prompts,
                        'custom_tags_prompt' => $validated_params['custom_tags_prompt'] ?? null,
                    ],
                ]));
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'bot',
                    'message_content' => $tags,
                    'usage' => $tags_result['usage'] ?? null,
                    'request_payload' => [
                        'payload_sent' => [
                            'messages' => [['role' => 'user', 'content' => $tags_user_prompt]],
                            'ai_params' => $tags_ai_params,
                        ],
                    ],
                ]));
            }
        }
    }

    // 4. Generate Meta Description
    if (($validated_params['generate_meta_description'] ?? '0') === '1' && !empty($content)) {
        $meta_user_prompt = \WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Meta_Prompt_Builder::build($final_title, $content_summary, $keywords_for_prompts, 'custom', $validated_params['custom_meta_prompt']);
        $meta_ai_params = ['max_completion_tokens' => 4000, 'temperature' => 1];
        $meta_result = $handler->get_ai_caller()->make_standard_call($validated_params['provider'], $validated_params['model'], [['role' => 'user', 'content' => $meta_user_prompt]], $meta_ai_params);
        if (!is_wp_error($meta_result) && !empty($meta_result['content'])) {
            $meta_description = trim(str_replace(['"', "'"], '', $meta_result['content']));
            if ($handler->log_storage) {
                $base = [
                    'bot_id' => null,
                    'user_id' => get_current_user_id(),
                    'session_id' => null,
                    'conversation_uuid' => $conversation_uuid,
                    'module' => 'content_writer',
                    'is_guest' => 0,
                    'role' => implode(', ', wp_get_current_user()->roles),
                    'ip_address' => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
                    'timestamp' => time(),
                    'ai_provider' => $validated_params['provider'],
                    'ai_model' => $validated_params['model'],
                ];
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'user',
                    'message_content' => 'Generate Meta Description',
                    'request_payload' => [
                        'title' => $final_title,
                        'keywords' => $keywords_for_prompts,
                        'custom_meta_prompt' => $validated_params['custom_meta_prompt'] ?? null,
                    ],
                ]));
                $handler->log_storage->log_message(array_merge($base, [
                    'message_role' => 'bot',
                    'message_content' => $meta_description,
                    'usage' => $meta_result['usage'] ?? null,
                    'request_payload' => [
                        'payload_sent' => [
                            'messages' => [['role' => 'user', 'content' => $meta_user_prompt]],
                            'ai_params' => $meta_ai_params,
                        ],
                    ],
                ]));
            }
        }
    }
    // --- END REFACTORED SEO LOGIC ---

    wp_send_json_success([
        'content' => $content,
        'usage' => $usage,
        'meta_description' => $meta_description,
        'focus_keyword' => $focus_keyword,
        'excerpt' => $excerpt,
    'tags' => $tags,
    'conversation_uuid' => $conversation_uuid,
        'image_data' => null // Non-streaming doesn't generate images for now.
    ]);
}
