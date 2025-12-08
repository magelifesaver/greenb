<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/comment-reply/process-comment-reply-item.php
// Status: MODIFIED
// I have added the logic to include the `reasoning_effort` parameter for compatible OpenAI models.

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\CommentReply;

use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

// Load helper files
require_once __DIR__ . '/build-reply-prompt.php';
require_once __DIR__ . '/insert-comment-reply.php';

/**
 * Processes a single "community_reply_comments" queue item.
 *
 * @param array $item The queue item from the database.
 * @param array $item_config The decoded item_config from the queue item.
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function process_comment_reply_item_logic(array $item, array $item_config): array
{
    $comment_id = absint($item['target_identifier']);
    $original_comment = get_comment($comment_id);

    if (!$original_comment) {
        return ['status' => 'error', 'message' => "Original comment #{$comment_id} not found."];
    }
    $post = get_post($original_comment->comment_post_ID);
    if (!$post) {
        return ['status' => 'error', 'message' => "Parent post for comment #{$comment_id} not found."];
    }

    // Keyword filtering
    $include_keywords_str = $item_config['include_keywords'] ?? '';
    $exclude_keywords_str = $item_config['exclude_keywords'] ?? '';
    if (!empty($include_keywords_str) || !empty($exclude_keywords_str)) {
        $comment_content_lower = strtolower($original_comment->comment_content);
        if (!empty($exclude_keywords_str)) {
            $exclude_keywords = array_map('trim', explode(',', strtolower($exclude_keywords_str)));
            foreach ($exclude_keywords as $keyword) {
                if (str_contains($comment_content_lower, $keyword)) {
                    return ['status' => 'success', 'message' => "Skipped: Comment #{$comment_id} contained exclude keyword '{$keyword}'."];
                }
            }
        }
        if (!empty($include_keywords_str)) {
            $include_keywords = array_map('trim', explode(',', strtolower($include_keywords_str)));
            $found_include = false;
            foreach ($include_keywords as $keyword) {
                if (str_contains($comment_content_lower, $keyword)) {
                    $found_include = true;
                    break;
                }
            }
            if (!$found_include) {
                return ['status' => 'success', 'message' => "Skipped: Comment #{$comment_id} did not contain any include keywords."];
            }
        }
    }

    // Build Prompt
    $prompt_template = $item_config['custom_content_prompt'] ?? 'Reply to this comment: {comment_content}';
    $final_prompt = build_reply_prompt_logic($prompt_template, $original_comment, $post);

    // Call AI
    $ai_caller = new AIPKit_AI_Caller();
    $ai_params_override = ['temperature' => floatval($item_config['ai_temperature'] ?? 1), 'max_completion_tokens' => intval($item_config['content_max_tokens'] ?? 4000)];
    if (($item_config['ai_provider'] ?? '') === 'OpenAI' && isset($item_config['reasoning_effort']) && !empty($item_config['reasoning_effort'])) {
        $model_lower = strtolower($item_config['ai_model'] ?? '');
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
            $ai_params_override['reasoning'] = ['effort' => sanitize_key($item_config['reasoning_effort'])];
        }
    }
    $system_instruction = 'You are a helpful community manager replying to comments on a blog.';
    $ai_result = $ai_caller->make_standard_call($item_config['ai_provider'], $item_config['ai_model'], [['role' => 'user', 'content' => $final_prompt]], $ai_params_override, $system_instruction);

    if (is_wp_error($ai_result)) {
        return ['status' => 'error', 'message' => 'AI call failed: ' . $ai_result->get_error_message()];
    }
    $reply_content = $ai_result['content'] ?? '';
    if (empty($reply_content)) {
        return ['status' => 'error', 'message' => 'AI returned an empty reply.'];
    }

    // Insert Reply
    $reply_result = insert_comment_reply_logic($reply_content, $original_comment, array_merge($item_config, ['task_id' => $item['task_id']]));
    if (is_wp_error($reply_result)) {
        return ['status' => 'error', 'message' => 'Failed to insert comment reply: ' . $reply_result->get_error_message()];
    }
    $new_comment_id = $reply_result;

    // Log the interaction
    $log_storage = new LogStorage();
    $log_data = [
        'bot_id' => null,
        'user_id' => $original_comment->user_id ?: null,
        'session_id' => null,
        'conversation_uuid' => 'comment-reply-' . $new_comment_id,
        'module' => 'community_reply_comments',
        'is_guest' => empty($original_comment->user_id),
        'role' => 'system',
        'ip_address' => AIPKit_IP_Anonymization::maybe_anonymize($original_comment->comment_author_IP),
        'message_role' => 'bot',
        'message_content' => "Automated reply to comment #{$comment_id}.",
        'timestamp' => time(),
        'ai_provider' => $item_config['ai_provider'],
        'ai_model' => $item_config['ai_model'],
        'usage' => $ai_result['usage'] ?? null,
        'request_payload' => ['item_config' => $item_config, 'prompt' => $final_prompt],
        'response_data' => ['original_comment_id' => $comment_id, 'reply_comment_id' => $new_comment_id, 'reply_content' => $reply_content]
    ];
    $log_storage->log_message($log_data);

    return ['status' => 'success', 'message' => "Replied to comment #{$comment_id} with new comment #{$new_comment_id}."];
}