<?php

namespace WPAICG\Chat\Ajax;

use WPAICG\Chat\Admin\Ajax\BaseAjaxHandler;
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\AIPKit_Providers;
use WPAICG\Utils\AIPKit_Identifier_Utils;
use WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor;
use WPAICG\Vector\PostProcessor\Pinecone\PineconePostProcessor;
use WPAICG\Vector\PostProcessor\Qdrant\QdrantPostProcessor;
use WPAICG\Core\AIPKit_AI_Caller;
use Exception;
use WP_Error;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler for Chatbot Content Indexing functionality.
 * REFACTORED: Manages a stoppable, chained background process for analyzing, creating, and
 * populating vector stores with site content.
 */
class AIPKit_Chatbot_Index_Content_Ajax_Handler extends BaseAjaxHandler
{
    public const INDEXING_PROCESS_ID_META_KEY = '_aipkit_indexing_process_id';
    public const PROCESS_TRANSIENT_PREFIX = 'aipkit_indexing_process_';
    public const STOP_SIGNAL_TRANSIENT_PREFIX = 'aipkit_indexing_stop_';

    private $bot_storage;
    private $vector_store_manager;
    private $openai_post_processor;
    private $pinecone_post_processor;
    private $qdrant_post_processor;
    private $ai_caller;

    public function __construct()
    {
        $this->bot_storage = new BotStorage();

        // Load dependencies, ensuring they are available for the handler's methods.
        $this->vector_store_manager = class_exists('WPAICG\Vector\AIPKit_Vector_Store_Manager')
            ? new AIPKit_Vector_Store_Manager()
            : null;
        $this->openai_post_processor = class_exists('WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor')
            ? new OpenAIPostProcessor()
            : null;
        $this->pinecone_post_processor = class_exists('WPAICG\Vector\PostProcessor\Pinecone\PineconePostProcessor')
            ? new PineconePostProcessor()
            : null;
        $this->qdrant_post_processor = class_exists('WPAICG\Vector\PostProcessor\Qdrant\QdrantPostProcessor')
            ? new QdrantPostProcessor()
            : null;
        $this->ai_caller = class_exists('WPAICG\Core\AIPKit_AI_Caller')
            ? new AIPKit_AI_Caller()
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * AJAX: Check if an indexing process is already running for a bot.
     */
    public function ajax_check_indexing_status()
    {
        $permission_check = $this->check_module_access_permissions('chatbot', 'aipkit_chatbot_index_content_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $bot_id = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        $process_id = get_post_meta($bot_id, self::INDEXING_PROCESS_ID_META_KEY, true);

        if (!empty($process_id)) {
            $process_data = get_transient(self::PROCESS_TRANSIENT_PREFIX . $process_id);
            if ($process_data && in_array($process_data['status'], ['initializing', 'running'], true)) {
                wp_send_json_success(['active_process' => true, 'process_id' => $process_id]);
                return;
            }
        }

        wp_send_json_success(['active_process' => false]);
    }

    /**
     * AJAX: Analyze configuration for express setup.
     */
    public function ajax_analyze_express_setup()
    {
        $permission_check = $this->check_module_access_permissions('chatbot', 'aipkit_chatbot_index_content_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $post_data = wp_unslash($_POST);
        $bot_id = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
        $user_config = isset($post_data['config']) ? json_decode(wp_unslash($post_data['config']), true) : [];

        if (!$bot_id) {
            $this->send_wp_error(new WP_Error('invalid_bot_id', __('Invalid bot ID.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        try {
            $result = $this->_analyze_setup($bot_id, $user_config);
            wp_send_json_success($result);
        } catch (Exception $e) {
            $this->send_wp_error(new WP_Error('analysis_failed', $e->getMessage()), 500);
        }
    }

    /**
     * AJAX: Start a content indexing process.
     */
    public function ajax_start_content_indexing()
    {
        $permission_check = $this->check_module_access_permissions('chatbot', 'aipkit_chatbot_index_content_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $post_data = wp_unslash($_POST);
        $bot_id = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
        $mode = isset($post_data['mode']) ? sanitize_key($post_data['mode']) : '';
        $content_types = isset($post_data['content_types']) ? json_decode(wp_unslash($post_data['content_types']), true) : [];
        $config = isset($post_data['config']) ? json_decode(wp_unslash($post_data['config']), true) : [];

        if (!$bot_id || !in_array($mode, ['express', 'custom'], true) || empty($content_types)) {
            $this->send_wp_error(new WP_Error('invalid_params', __('Invalid parameters.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        $existing_process_id = get_post_meta($bot_id, self::INDEXING_PROCESS_ID_META_KEY, true);
        if (!empty($existing_process_id)) {
            $existing_process_data = get_transient(self::PROCESS_TRANSIENT_PREFIX . $existing_process_id);
            if ($existing_process_data && in_array($existing_process_data['status'], ['initializing', 'running'], true)) {
                $this->send_wp_error(new WP_Error('process_running', __('An indexing process is already running for this bot.', 'gpt3-ai-content-generator')), 409);
                return;
            }
        }

        try {
            $process_id = $this->_start_indexing_process($bot_id, $mode, $content_types, $config);
            wp_send_json_success(['process_id' => $process_id]);
        } catch (Exception $e) {
            $this->send_wp_error(new WP_Error('start_failed', $e->getMessage()), 500);
        }
    }

    /**
     * AJAX: Get indexing progress from a transient.
     */
    public function ajax_get_indexing_progress()
    {
        $permission_check = $this->check_module_access_permissions('chatbot', 'aipkit_chatbot_index_content_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        $post_data = wp_unslash($_POST);
        $process_id = isset($post_data['process_id']) ? sanitize_key($post_data['process_id']) : '';

        if (!$process_id) {
            $this->send_wp_error(new WP_Error('invalid_process_id', __('Invalid process ID.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        try {
            $progress = $this->_get_process_progress($process_id);
            wp_send_json_success($progress);
        } catch (Exception $e) {
            $this->send_wp_error(new WP_Error('progress_fetch_failed', $e->getMessage()), 500);
        }
    }

    /**
     * AJAX: Cancel a running indexing process.
     */
    public function ajax_cancel_content_indexing()
    {
        $permission_check = $this->check_module_access_permissions('chatbot', 'aipkit_chatbot_index_content_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $process_id = isset($_POST['process_id']) ? sanitize_key($_POST['process_id']) : '';
        if (empty($process_id)) {
            $this->send_wp_error(new WP_Error('missing_process_id', __('Process ID is required to cancel.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        $this->_cancel_indexing_process($process_id);
        wp_send_json_success(['message' => __('Indexing process cancellation requested.', 'gpt3-ai-content-generator')]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cron Job
    |--------------------------------------------------------------------------
    */

    /**
     * Static method to be hooked into WP Cron for processing.
     * @param string $process_id The unique ID of the process to execute.
     */
    public static function process_content_indexing($process_id)
    {
        $handler = new self();
        $handler->_execute_indexing_process($process_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Management
    |--------------------------------------------------------------------------
    */

    private function _start_indexing_process($bot_id, $mode, $content_types, $config)
    {
        $process_id = 'index_' . $bot_id . '_' . time() . '_' . wp_generate_uuid4();
        update_post_meta($bot_id, self::INDEXING_PROCESS_ID_META_KEY, $process_id);
        delete_transient(self::STOP_SIGNAL_TRANSIENT_PREFIX . $process_id);

        $process_data = [
            'bot_id' => $bot_id, 'mode' => $mode, 'content_types' => $content_types,
            'config' => $config, 'status' => 'initializing', 'progress' => 0,
            'current_task' => 'Starting indexing process...', 'total_posts' => 0,
            'posts_to_index' => [], // Will be populated by the first cron job
            'vector_store_id' => null, // Will be populated by the first cron job
            'indexed_posts' => 0, 'started_at' => current_time('mysql'),
            'messages' => [], 'error_message' => null,
        ];

        $this->_update_process_data($process_id, $process_data);

        // Schedule the first job in the chain.
        wp_schedule_single_event(time(), 'aipkit_process_content_indexing', [$process_id]);
        spawn_cron();

        return $process_id;
    }

    private function _get_process_progress($process_id)
    {
        $process_data = get_transient(self::PROCESS_TRANSIENT_PREFIX . $process_id);
        if ($process_data === false) {
            throw new Exception(__('Process not found or expired.', 'gpt3-ai-content-generator'));
        }

        $percentage = ($process_data['total_posts'] > 0)
            ? ($process_data['indexed_posts'] / $process_data['total_posts']) * 100
            : 0;

        return [
            'status' => $process_data['status'],
            'percentage' => round($percentage, 1),
            'current_task' => $process_data['current_task'],
            'total_posts' => $process_data['total_posts'],
            'indexed_posts' => $process_data['indexed_posts'],
            'messages' => array_slice($process_data['messages'] ?? [], -10),
            'error_message' => $process_data['error_message'] ?? ''
        ];
    }

    private function _update_process_data($process_id, $process_data)
    {
        set_transient(self::PROCESS_TRANSIENT_PREFIX . $process_id, $process_data, HOUR_IN_SECONDS * 6); // Extend transient life
    }

    private function _cancel_indexing_process($process_id)
    {
        // This is the "stop sign". The next cron job will see this and stop.
        set_transient(self::STOP_SIGNAL_TRANSIENT_PREFIX . $process_id, '1', HOUR_IN_SECONDS * 6);
    }

    private function _cleanup_indexing_process($process_id, $is_cancelled = false, $final_message = '')
    {
        $process_data = get_transient(self::PROCESS_TRANSIENT_PREFIX . $process_id);
        if (!$process_data) {
            return;
        }

        $is_error = strpos($final_message, '💥') === 0;

        // **REMOVED** the automatic deletion of the vector store on cancellation or error.
        // The partially created store will now be kept.

        // **NEW**: If the process was stopped by the user and some work was done,
        // update the bot's settings to use the partially indexed store.
        if ($is_cancelled && $process_data['indexed_posts'] > 0 && !empty($process_data['vector_store_id'])) {
            $this->_update_bot_vector_settings($process_data['bot_id'], $process_data['config'], $process_data['vector_store_id']);
            /* translators: %1$d: Number of posts indexed, %2$d: Total number of posts in the plan. */
            $final_message = sprintf(__('⏹️ Process stopped. Indexed %1$d of %2$d items. The chatbot is now configured to use the partially indexed content.', 'gpt3-ai-content-generator'), $process_data['indexed_posts'], $process_data['total_posts']);
            $process_data['messages'][] = ['text' => $final_message, 'type' => 'info', 'time' => current_time('mysql')];
        }

        if ($is_cancelled) {
            $process_data['status'] = 'cancelled';
            $process_data['current_task'] = 'Process cancelled by user.';
            $process_data['messages'][] = ['text' => "⏹️ Process cancelled by user.", 'type' => 'info', 'time' => current_time('mysql')];
        } elseif ($is_error) {
            $process_data['status'] = 'failed';
            $process_data['error_message'] = $final_message;
            $process_data['current_task'] = 'Indexing failed';
            $process_data['messages'][] = ['text' => $final_message, 'type' => 'error', 'time' => current_time('mysql')];
        } else {
            $process_data['status'] = 'completed';
            $process_data['progress'] = 100;
            $process_data['current_task'] = 'Indexing completed successfully!';
            $process_data['messages'][] = ['text' => $final_message, 'type' => 'success', 'time' => current_time('mysql')];
        }

        $this->_update_process_data($process_id, $process_data);
        delete_post_meta($process_data['bot_id'], self::INDEXING_PROCESS_ID_META_KEY);
        delete_transient(self::STOP_SIGNAL_TRANSIENT_PREFIX . $process_id);
    }


    /*
    |--------------------------------------------------------------------------
    | Setup & Analysis
    |--------------------------------------------------------------------------
    */

    private function _analyze_setup($bot_id, $user_config = [])
    {
        $config = $this->_determine_vector_config($bot_id, $user_config);
        $content_types = $this->_get_all_indexable_content_types();

        if (empty($content_types)) {
            throw new Exception(__('No indexable content types found.', 'gpt3-ai-content-generator'));
        }

        $provider_name_map = ['openai' => 'OpenAI', 'pinecone' => 'Pinecone', 'qdrant' => 'Qdrant'];
        $embedding_provider_name_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];

        $display_info = [
            'provider_name' => $provider_name_map[$config['provider']] ?? ucfirst($config['provider']),
            'provider_key' => $config['provider']
        ];

        switch ($config['provider']) {
            case 'openai':
                $display_info['store_label'] = __('Vector Store', 'gpt3-ai-content-generator');
                $display_info['store_name'] = $config['store_name'];
                break;
            case 'pinecone':
            case 'qdrant':
                $display_info['store_label'] = ($config['provider'] === 'pinecone') ? __('Index', 'gpt3-ai-content-generator') : __('Collection', 'gpt3-ai-content-generator');
                $display_info['store_name'] = $config[$config['provider'] === 'pinecone' ? 'index_name' : 'collection_name'];
                $display_info['embedding_provider'] = $embedding_provider_name_map[$config['embedding_provider']] ?? ucfirst($config['embedding_provider']);
                $display_info['embedding_model'] = $config['embedding_model'];
                break;
        }
        return ['config' => $config, 'content_types' => $content_types, 'display_info' => $display_info];
    }

    private function _determine_vector_config($bot_id, $user_config)
    {
        $config = ['provider' => '', 'embedding_provider' => '', 'embedding_model' => '', 'store_name' => '', 'index_name' => '', 'collection_name' => ''];
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        $bot_provider = $bot_settings['provider'] ?? 'OpenAI'; // Get the bot's main provider

        if (!empty($user_config['provider'])) {
            $config['provider'] = $user_config['provider'];
        } else {
            if ($bot_provider === 'OpenAI' && !empty(AIPKit_Providers::get_provider_data('OpenAI')['api_key'])) {
                $config['provider'] = 'openai';
            } else {
                if (!empty(AIPKit_Providers::get_provider_data('Pinecone')['api_key'])) {
                    $config['provider'] = 'pinecone';
                } elseif (!empty(AIPKit_Providers::get_provider_data('Qdrant')['api_key']) && !empty(AIPKit_Providers::get_provider_data('Qdrant')['url'])) {
                    $config['provider'] = 'qdrant';
                } else {
                     throw new Exception(__('No vector store provider API key found. Please configure OpenAI, Pinecone, or Qdrant in AI Settings.', 'gpt3-ai-content-generator'));
                }
            }
        }
        
        // Now determine embedding config
        $config = array_merge($config, $this->_determine_embedding_config($user_config, $bot_provider));

        $store_key = $config['provider'] === 'pinecone' ? 'index_name' : ($config['provider'] === 'qdrant' ? 'collection_name' : 'store_name');
        $generated = $user_config[$store_key] ?? $this->_generate_vector_store_name($bot_id, $config['provider']);
        if ($config['provider'] === 'pinecone') {
            // Keep Pinecone index names short & compliant (<=45 chars, lowercase, hyphens)
            $index_name = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $generated));
            $index_name = trim(preg_replace('/-+/', '-', $index_name), '-');
            if ($index_name === '' || strlen($index_name) > 45) {
                // Add a short unique suffix so repeated runs create distinct indexes.
                $suffix = substr(wp_generate_uuid4(), 0, 8); // 8 hex chars
                $index_name = 'chat-pinecone-u' . $bot_id . '-' . $suffix;
                // Extra safety: truncate if somehow exceeds 45 (shouldn't normally)
                if (strlen($index_name) > 45) {
                    $index_name = substr($index_name, 0, 45);
                }
            }
            $config[$store_key] = $index_name;
        } else {
            $config[$store_key] = $generated;
        }

        return $config;
    }

    private function _determine_embedding_config($user_config, $bot_provider) // Added $bot_provider
    {
        if (!empty($user_config['embedding_provider'])) {
            $model = $user_config['embedding_model'] ?? '';
            if(empty($model)){
                 if($user_config['embedding_provider'] === 'openai'){
                     $model = 'text-embedding-3-small';
                 }
                 elseif($user_config['embedding_provider'] === 'google'){
                     $model = 'text-embedding-004';
                 }
                 elseif($user_config['embedding_provider'] === 'azure'){
                     $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
                     $model = !empty($azure_embedding_models) ? $azure_embedding_models[0]['id'] : '';
                 }
            }
            return [
                'embedding_provider' => $user_config['embedding_provider'],
                'embedding_model' => $model
            ];
        }

        // NEW: Check if bot provider is Azure
        if ($bot_provider === 'Azure' && !empty(AIPKit_Providers::get_provider_data('Azure')['api_key'])) {
             $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
             return [
                 'embedding_provider' => 'azure',
                 'embedding_model' => !empty($azure_embedding_models) ? $azure_embedding_models[0]['id'] : ''
             ];
        }

        if (!empty(AIPKit_Providers::get_provider_data('OpenAI')['api_key'])) {
            return ['embedding_provider' => 'openai', 'embedding_model' => 'text-embedding-3-small'];
        } elseif (!empty(AIPKit_Providers::get_provider_data('Google')['api_key'])) {
            return ['embedding_provider' => 'google', 'embedding_model' => 'text-embedding-004'];
        }
        throw new Exception(__('No embedding provider API key found. Please configure OpenAI, Google, or Azure API key in AI Settings -> Providers.', 'gpt3-ai-content-generator'));
    }

    private function _get_all_indexable_content_types()
    {
        $post_type_slugs = get_post_types(['public' => true], 'names');
        unset($post_type_slugs['attachment']);
        $core_types = ['post', 'page', 'product'];
        foreach ($core_types as $type) {
            if (post_type_exists($type) && !in_array($type, $post_type_slugs, true)) {
                $post_type_slugs[] = $type;
            }
        }
        $post_type_slugs = array_values(array_unique($post_type_slugs));

        $content_types_with_counts = [];
        foreach ($post_type_slugs as $slug) {
            $counts = wp_count_posts($slug);
            $publish_count = $counts->publish ?? 0;
            if ($publish_count > 0) {
                $post_type_obj = get_post_type_object($slug);
                $content_types_with_counts[] = [
                    'slug' => $slug, 'label' => $post_type_obj ? $post_type_obj->label : ucfirst($slug),
                    'count' => $publish_count,
                ];
            }
        }
        return $content_types_with_counts;
    }

    private function _generate_vector_store_name($bot_id, $provider)
    {
        if (!class_exists(AIPKit_Identifier_Utils::class)) {
            return "chatbot-{$bot_id}-{$provider}-" . time();
        }
        $bot_post = get_post($bot_id);
        $bot_name = $bot_post ? sanitize_title($bot_post->post_title) : 'bot';
        return AIPKit_Identifier_Utils::generate_chat_context_identifier($bot_id, null, "chatbot-{$provider}-{$bot_name}");
    }

    /*
    |--------------------------------------------------------------------------
    | Indexing Execution (Cron)
    |--------------------------------------------------------------------------
    */

    private function _execute_indexing_process($process_id)
    {
        $process_data = get_transient(self::PROCESS_TRANSIENT_PREFIX . $process_id);
        if ($process_data === false) {
            return;
        }

        try {
            // Check for stop signal first
            if ('1' === get_transient(self::STOP_SIGNAL_TRANSIENT_PREFIX . $process_id)) {
                throw new Exception('Process cancelled by user.');
            }

            // Initialization on first run
            if ($process_data['status'] === 'initializing') {
                $process_data['status'] = 'running';
                $process_data['messages'][] = ['text' => '🚀 Starting indexing process...', 'type' => 'info', 'time' => current_time('mysql')];

                $vector_store_id = $this->_create_vector_store($process_data['config']);
                $process_data['vector_store_id'] = $vector_store_id;
                $label = $process_data['config']['provider'] === 'pinecone' ? __('Index', 'gpt3-ai-content-generator') : ($process_data['config']['provider'] === 'qdrant' ? __('Collection', 'gpt3-ai-content-generator') : __('Vector Store', 'gpt3-ai-content-generator'));
                $process_data['messages'][] = [
                    'text' => sprintf(__('🆕 %1$s created: %2$s', 'gpt3-ai-content-generator'), $label, $vector_store_id),
                    'type' => 'info',
                    'time' => current_time('mysql')
                ];

                $posts_to_index = $this->_get_posts_to_index($process_data['content_types']);
                $process_data['posts_to_index'] = $posts_to_index;
                $process_data['total_posts'] = count($posts_to_index);
                $process_data['current_task'] = "Found {$process_data['total_posts']} items to index.";
                $this->_update_process_data($process_id, $process_data);
            }

            // Process one item from the list
            if (!empty($process_data['posts_to_index'])) {
                $post_id_to_index = array_shift($process_data['posts_to_index']);

                try {
                    $this->_index_single_post($post_id_to_index, $process_data['config'], $process_data['vector_store_id']);
                    $process_data['indexed_posts']++;
                    $process_data['messages'][] = ['text' => "✅ Indexed: " . get_the_title($post_id_to_index), 'type' => 'success', 'time' => current_time('mysql')];
                } catch (Exception $e) {
                    $process_data['messages'][] = ['text' => "❌ Failed: " . get_the_title($post_id_to_index) . " - " . $e->getMessage(), 'type' => 'error', 'time' => current_time('mysql')];
                }

                $process_data['progress'] = ($process_data['total_posts'] > 0) ? ($process_data['indexed_posts'] / $process_data['total_posts']) * 100 : 0;
                $process_data['current_task'] = "Indexing post {$process_data['indexed_posts']} of {$process_data['total_posts']}";
                $this->_update_process_data($process_id, $process_data);
            }

            // Check if finished or reschedule
            if (empty($process_data['posts_to_index'])) {
                $this->_update_bot_vector_settings($process_data['bot_id'], $process_data['config'], $process_data['vector_store_id']);
                $label = $process_data['config']['provider'] === 'pinecone' ? __('Index', 'gpt3-ai-content-generator') : ($process_data['config']['provider'] === 'qdrant' ? __('Collection', 'gpt3-ai-content-generator') : __('Vector Store', 'gpt3-ai-content-generator'));
                $this->_cleanup_indexing_process($process_id, false, "🎉 Indexing completed! Indexed {$process_data['indexed_posts']} items into {$label}: {$process_data['vector_store_id']}.");
            } else {
                wp_schedule_single_event(time() + 3, 'aipkit_process_content_indexing', [$process_id]);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($message === 'Process cancelled by user.') {
                // Genuine cancellation
                $this->_cleanup_indexing_process($process_id, true, '⏹️ Process cancelled by user.');
            } else {
                // Treat as error, not user cancellation
                if (function_exists('error_log')) {
                    error_log('[AIPKit Indexing Error][' . $process_id . '] ' . $message);
                }
                $this->_cleanup_indexing_process($process_id, false, "💥 Indexing failed: {$message}");
            }
        }
    }

    private function _create_vector_store($config)
    {
        if (!$this->vector_store_manager) {
            throw new Exception('Vector store manager not available');
        }
        $provider_map = ['openai' => 'OpenAI', 'pinecone' => 'Pinecone', 'qdrant' => 'Qdrant'];
        $provider = $provider_map[$config['provider']] ?? $config['provider'];
        $index_name = $config[$config['provider'] === 'pinecone' ? 'index_name' : ($config['provider'] === 'qdrant' ? 'collection_name' : 'store_name')];
        $index_config = ['metadata' => ['source' => 'chatbot_express_setup']];

        if ($provider !== 'OpenAI') {
            if (!$this->ai_caller) {
                throw new Exception('AI Caller not available for embedding.');
            }
            $embedding_provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
            $normalized_embedding_provider = $embedding_provider_map[$config['embedding_provider']] ?? ucfirst($config['embedding_provider']);
            $embedding_result = $this->ai_caller->generate_embeddings($normalized_embedding_provider, 'test', ['model' => $config['embedding_model']]);
            if (is_wp_error($embedding_result)) {
                throw new Exception('Could not generate test embedding: ' . $embedding_result->get_error_message());
            }
            $dimension = isset($embedding_result['embeddings'][0]) ? count($embedding_result['embeddings'][0]) : null;
            if (!$dimension) {
                throw new Exception('Could not determine vector dimension for model: ' . $config['embedding_model']);
            }
            $index_config = ['dimension' => $dimension, 'metric' => 'cosine'];
        }

        $result = $this->vector_store_manager->create_index_if_not_exists($provider, $index_name, $index_config, AIPKit_Providers::get_provider_data($provider));
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
        return ($provider === 'OpenAI') ? ($result['id'] ?? false) : $index_name;
    }

    private function _get_posts_to_index($content_types)
    {
        $query = new WP_Query([
            'post_type' => $content_types, 'post_status' => 'publish',
            'posts_per_page' => -1, 'fields' => 'ids'
        ]);
        return $query->posts;
    }

    private function _index_single_post($post_id, $config, $vector_store_id)
    {
        $provider = $config['provider'];
        $embedding_provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $normalized_embedding_provider = isset($config['embedding_provider']) ? ($embedding_provider_map[$config['embedding_provider']] ?? ucfirst($config['embedding_provider'])) : null;

        $processor_map = [
            'openai' => $this->openai_post_processor,
            'pinecone' => $this->pinecone_post_processor,
            'qdrant' => $this->qdrant_post_processor,
        ];
        $processor = $processor_map[$provider] ?? null;
        if (!$processor) {
            throw new Exception("Post processor for {$provider} not available");
        }

        if ($provider === 'openai') {
            $result = $processor->index_single_post_to_store($post_id, $vector_store_id);
        } else {
            $result = $processor->{'index_single_post_to_' . ($provider === 'pinecone' ? 'index' : 'collection')}($post_id, $vector_store_id, $normalized_embedding_provider, $config['embedding_model']);
        }

        if ($result['status'] !== 'success') {
            throw new Exception($result['message']);
        }
        return true;
    }

    private function _update_bot_vector_settings($bot_id, $config, $vector_store_id)
    {
        update_post_meta($bot_id, '_aipkit_enable_vector_store', '1');
        update_post_meta($bot_id, '_aipkit_vector_store_provider', $config['provider']);

        switch ($config['provider']) {
            case 'openai':
                $existing_ids = json_decode(get_post_meta($bot_id, '_aipkit_openai_vector_store_ids', true) ?: '[]', true);
                if (!is_array($existing_ids)) {
                    $existing_ids = [];
                }
                $existing_ids[] = $vector_store_id;
                update_post_meta($bot_id, '_aipkit_openai_vector_store_ids', wp_json_encode(array_values(array_unique($existing_ids))));
                break;
            case 'pinecone':
                update_post_meta($bot_id, '_aipkit_pinecone_index_name', $vector_store_id);
                update_post_meta($bot_id, '_aipkit_vector_embedding_provider', $config['embedding_provider']);
                update_post_meta($bot_id, '_aipkit_vector_embedding_model', $config['embedding_model']);
                break;
            case 'qdrant':
                update_post_meta($bot_id, '_aipkit_qdrant_collection_name', $vector_store_id);
                update_post_meta($bot_id, '_aipkit_vector_embedding_provider', $config['embedding_provider']);
                update_post_meta($bot_id, '_aipkit_vector_embedding_model', $config['embedding_model']);
                break;
        }
    }
}