<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/class-aipkit-models-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax;

use WPAICG\AIPKit_Providers;
use WPAICG\Core\AIPKit_Models_API;
use WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\Images\AIPKit_Image_Provider_Strategy_Factory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for syncing AI models from providers.
 * Also handles syncing TTS voices AND models, and Vector Store indexes/collections.
 */
class ModelsAjaxHandler extends BaseDashboardAjaxHandler
{
    private $vector_store_manager;
    private $vector_store_registry;

    public function __construct()
    {
        // Vector store dependencies
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();
        }

        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $registry_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-registry.php';
            if (file_exists($registry_path)) {
                require_once $registry_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $this->vector_store_registry = new \WPAICG\Vector\AIPKit_Vector_Store_Registry();
        }

        // General dependencies for logic within this handler
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            }
        }
        if (!class_exists(\WPAICG\Core\AIPKit_Models_API::class)) {
            $models_api_path = WPAICG_PLUGIN_DIR . 'classes/core/models_api.php';
            if (file_exists($models_api_path)) {
                require_once $models_api_path;
            }
        }
        if (!class_exists(\WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory::class)) {
            $tts_factory_path = WPAICG_PLUGIN_DIR . 'classes/speech/class-aipkit-tts-provider-strategy-factory.php';
            if (file_exists($tts_factory_path)) {
                require_once $tts_factory_path;
            }
        }
    }

    /**
     * AJAX callback to sync models or voices from the selected provider.
     */
    public function ajax_sync_models()
    {
        $permission_check = $this->check_module_access_permissions('settings');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_module_access_permissions().
        $provider = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        $valid_providers = ['OpenAI', 'OpenRouter', 'Google', 'Azure', 'DeepSeek', 'ElevenLabs', 'ElevenLabsModels', 'PineconeIndexes', 'QdrantCollections', 'Replicate', 'Ollama'];
        if (!in_array($provider, $valid_providers, true)) {
            wp_send_json_error(['message' => __('Invalid provider selection.', 'gpt3-ai-content-generator')]);
            return;
        }

        $provider_data_key = $provider;
        if ($provider === 'ElevenLabsModels') {
            $provider_data_key = 'ElevenLabs';
        } elseif ($provider === 'PineconeIndexes') {
            $provider_data_key = 'Pinecone';
        } elseif ($provider === 'QdrantCollections') {
            $provider_data_key = 'Qdrant';
        }

        $provData = AIPKit_Providers::get_provider_data($provider_data_key);

        // Remap Azure 'endpoint' to 'azure_endpoint' for consistency with AI_Caller and strategy expectations.
        $api_params = [
            'api_key'                 => $provData['api_key'] ?? '',
            'base_url'                => $provData['base_url'] ?? '',
            'url'                     => $provData['url'] ?? '', // For Qdrant
            'api_version'             => $provData['api_version'] ?? '',
            'api_version_authoring'   => $provData['api_version_authoring'] ?? '2023-03-15-preview',
            'api_version_inference'   => $provData['api_version_inference'] ?? '2024-02-01',
            'azure_endpoint'          => ($provider === 'Azure' || $provider_data_key === 'Azure') ? ($provData['endpoint'] ?? '') : '',
        ];


        if (empty($api_params['api_key']) && in_array($provider, ['OpenAI', 'Azure', 'DeepSeek', 'ElevenLabs', 'ElevenLabsModels', 'PineconeIndexes', 'QdrantCollections', 'Replicate'])) {
            /* translators: %s: The provider name that was attempted to be used for model sync. */
            wp_send_json_error(['message' => sprintf(__('%s API key is required.', 'gpt3-ai-content-generator'), $provider_data_key)]);
            return;
        }
        if ($provider === 'QdrantCollections' && empty($api_params['url'])) {
            wp_send_json_error(['message' => __('Qdrant URL is required to sync collections.', 'gpt3-ai-content-generator')]);
            return;
        }

        $result = null;

        switch ($provider) {
            case 'ElevenLabs':
                $strategy = AIPKit_TTS_Provider_Strategy_Factory::get_strategy('ElevenLabs');
                $result = is_wp_error($strategy) ? $strategy : $strategy->get_voices($api_params);
                break;
            case 'ElevenLabsModels':
                $strategy = AIPKit_TTS_Provider_Strategy_Factory::get_strategy('ElevenLabs');
                $result = (is_wp_error($strategy) || !method_exists($strategy, 'get_models'))
                    ? new WP_Error('tts_model_sync_not_supported', 'TTS Model sync not supported for ElevenLabs.')
                    : $strategy->get_models($api_params);
                break;
            case 'PineconeIndexes':
                if (!$this->vector_store_manager) {
                    $result = new WP_Error('vsm_missing', 'Vector Store Manager not available.');
                    break;
                }
                $result = $this->vector_store_manager->list_all_indexes('Pinecone', $api_params);
                break;
            case 'QdrantCollections':
                if (!$this->vector_store_manager) {
                    $result = new WP_Error('vsm_missing', 'Vector Store Manager not available.');
                    break;
                }
                $result = $this->vector_store_manager->list_all_indexes('Qdrant', $api_params);
                break;
            case 'Replicate':
                if (!class_exists(\WPAICG\Images\AIPKit_Image_Provider_Strategy_Factory::class)) {
                    $factory_path = WPAICG_PLUGIN_DIR . 'classes/images/class-aipkit-image-provider-strategy-factory.php';
                    if (file_exists($factory_path)) {
                        require_once $factory_path;
                    }
                }
                $strategy = \WPAICG\Images\AIPKit_Image_Provider_Strategy_Factory::get_strategy('Replicate');
                $result = is_wp_error($strategy) ? $strategy : $strategy->get_models($api_params);
                break;
            default: // Handles OpenAI, OpenRouter, Google, Azure, DeepSeek
                $result = AIPKit_Models_API::get_models($provider, $api_params);
                break;
        }

        if (is_wp_error($result)) {
            $error_data = $result->get_error_data();
            $status_code = isset($error_data['status']) ? (int)$error_data['status'] : 500;
            wp_send_json_error(['message' => $result->get_error_message()], $status_code);
            return;
        }

        $option_map = [
            'OpenAI' => 'aipkit_openai_model_list', 'OpenRouter' => 'aipkit_openrouter_model_list',
            'Google' => 'aipkit_google_model_list', 'Azure' => 'aipkit_azure_deployment_list', 'AzureImage' => 'aipkit_azure_image_model_list', 'DeepSeek' => 'aipkit_deepseek_model_list', 'ElevenLabs' => 'aipkit_elevenlabs_voice_list',
            'ElevenLabsModels' => 'aipkit_elevenlabs_model_list',
            'PineconeIndexes' => 'aipkit_pinecone_index_list',
            'QdrantCollections' => 'aipkit_qdrant_collection_list',
            'Replicate' => 'aipkit_replicate_model_list',
            'AzureEmbedding' => 'aipkit_azure_embedding_model_list',
            'Ollama' => 'aipkit_ollama_model_list',
        ];

        $option_name = $option_map[$provider] ?? null;
        $response_models = $result; // Default to raw result

        if ($option_name) {
            $value_to_save = $result;
            // OpenAI and Google have multiple model types, split them here
            if ($provider === 'OpenAI') {
                $chat_models = [];
                $tts_models = [];
                $stt_models = [];
                $embedding_models = [];
                foreach ($result as $model) {
                    $id_lower = strtolower($model['id']);
                    if (strpos($id_lower, 'tts-') === 0) {
                        $tts_models[] = $model;
                    } elseif (strpos($id_lower, 'whisper') !== false) {
                        $stt_models[] = $model;
                    } elseif (strpos($id_lower, 'embedding') !== false) {
                        $embedding_models[] = $model;
                    } else {
                        $chat_models[] = $model;
                    }
                }
                $value_to_save = AIPKit_Models_API::group_openai_models($chat_models);
                $response_models = $value_to_save; // Set response to the grouped models
                update_option('aipkit_openai_tts_model_list', $tts_models, 'no');
                update_option('aipkit_openai_stt_model_list', $stt_models, 'no');
                update_option('aipkit_openai_embedding_model_list', $embedding_models, 'no');
            } elseif ($provider === 'Google') {
                $chat_models = [];
                $image_models = [];
                $video_models = [];
                $embedding_models = [];
                foreach ($result as $model) {
                    // Prefer capability-based detection using supportedGenerationMethods from Google API
                    $methods = [];
                    if (isset($model['supportedGenerationMethods']) && is_array($model['supportedGenerationMethods'])) {
                        // Normalize to lowercase for safe comparison
                        $methods = array_map('strtolower', $model['supportedGenerationMethods']);
                    }

                    $id = $model['id'] ?? '';
                    $id_lower = strtolower($id);
                    $is_embedding = in_array('embedcontent', $methods, true);
                    $is_image = in_array('predict', $methods, true)
                        // Include Gemini image-generation models that use generateContent
                        || (strpos($id_lower, 'gemini') !== false && strpos($id_lower, 'image-generation') !== false);
                    $is_video = in_array('predictlongrunning', $methods, true)
                        // Heuristic fallback: Veo or other video-prefixed names
                        || (strpos($id_lower, 'veo') !== false);

                    if ($is_embedding) {
                        $embedding_models[] = $model;
                    } elseif ($is_video) {
                        $video_models[] = $model;
                    } elseif ($is_image) {
                        $image_models[] = $model;
                    } else {
                        $chat_models[] = $model;
                    }
                }
                $value_to_save = $chat_models;
                $response_models = $value_to_save; // Set response to just the chat models
                update_option('aipkit_google_embedding_model_list', $embedding_models, 'no');
                update_option('aipkit_google_image_model_list', $image_models, 'no');
                update_option('aipkit_google_video_model_list', $video_models, 'no');
            } elseif ($provider === 'Ollama') {
                $chat_models = [];
                $embedding_models = [];
                foreach ($result as $model) {
                    $id_lower = strtolower($model['id']);
                    if (strpos($id_lower, 'embed') !== false) {
                        $embedding_models[] = $model;
                    } else {
                        $chat_models[] = $model;
                    }
                }
                $value_to_save = $chat_models;
                $response_models = $value_to_save; // Set response to just the chat models
                update_option('aipkit_ollama_embedding_model_list', $embedding_models, 'no');
            } elseif ($provider === 'Azure') {
                $chat_deployments = [];
                $image_deployments = [];
                $embedding_deployments = [];
                if (is_array($result)) {
                    foreach ($result as $deployment) {
                        $model_name = strtolower($deployment['name'] ?? '');
                        if (strpos($model_name, 'dall-e') !== false) {
                            $image_deployments[] = $deployment;
                        } elseif (strpos($model_name, 'embedding') !== false) {
                            $embedding_deployments[] = $deployment;
                        } else {
                            $chat_deployments[] = $deployment;
                        }
                    }
                }
                update_option('aipkit_azure_image_model_list', $image_deployments, 'no');
                update_option('aipkit_azure_embedding_model_list', $embedding_deployments, 'no');
                $value_to_save = $chat_deployments;
                
                // Return grouped models for dashboard display
                $grouped_models = [];
                if (!empty($chat_deployments)) {
                    $grouped_models['Chat Models'] = $chat_deployments;
                }
                if (!empty($embedding_deployments)) {
                    $grouped_models['Embedding Models'] = $embedding_deployments;
                }
                if (!empty($image_deployments)) {
                    $grouped_models['Image Models'] = $image_deployments;
                }
                $response_models = $grouped_models;
            } elseif ($provider === 'PineconeIndexes' && $this->vector_store_registry) {
                // Enrich with describe results to capture total_vector_count
                $pinecone_config = [
                    'api_key' => $api_params['api_key'] ?? ''
                ];
                $enriched = [];
                if ($this->vector_store_manager && is_array($value_to_save)) {
                    foreach ($value_to_save as $idx) {
                        $name = $idx['name'] ?? $idx['id'] ?? null;
                        if (!$name) continue;
                        $details = $this->vector_store_manager->describe_single_index('Pinecone', $name, $pinecone_config);
                        $enriched[] = is_wp_error($details) ? $idx : array_merge($idx, $details);
                    }
                }
                if (!empty($enriched)) {
                    $value_to_save = $enriched;
                }
                $this->vector_store_registry->update_registered_stores_for_provider('Pinecone', $value_to_save);
            } elseif ($provider === 'QdrantCollections' && $this->vector_store_registry) {
                // Enrich with describe results to capture vectors_count
                $qdrant_config = [
                    'url' => $api_params['url'] ?? '',
                    'api_key' => $api_params['api_key'] ?? ''
                ];
                $enriched = [];
                if ($this->vector_store_manager && is_array($value_to_save)) {
                    foreach ($value_to_save as $col) {
                        $name = $col['name'] ?? $col['id'] ?? null;
                        if (!$name) continue;
                        $details = $this->vector_store_manager->describe_single_index('Qdrant', $name, $qdrant_config);
                        $enriched[] = is_wp_error($details) ? $col : array_merge($col, $details);
                    }
                }
                if (!empty($enriched)) {
                    $value_to_save = $enriched;
                }
                $this->vector_store_registry->update_registered_stores_for_provider('Qdrant', $value_to_save);
            }
            update_option($option_name, $value_to_save, 'no');
        }

        AIPKit_Providers::clear_model_caches();
        /* translators: %s: The provider name that was synced. */
        wp_send_json_success(['message' => sprintf(__('%s synced successfully.', 'gpt3-ai-content-generator'), $provider_data_key), 'models'  => $response_models]);
    }
}
