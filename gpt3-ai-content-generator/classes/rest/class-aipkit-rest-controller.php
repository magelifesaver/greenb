<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/rest/class-aipkit-rest-controller.php
// Status: MODIFIED
// I have updated this controller to instantiate and register the new logs handler and its REST endpoint.

namespace WPAICG\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
// Import the new handler classes
use WPAICG\REST\Handlers\AIPKit_REST_Text_Handler;
use WPAICG\REST\Handlers\AIPKit_REST_Image_Handler;
use WPAICG\REST\Handlers\AIPKit_REST_Embeddings_Handler;
use WPAICG\REST\Handlers\AIPKit_REST_Chat_Handler;
use WPAICG\REST\Handlers\AIPKit_REST_Vector_Store_Handler;
use WPAICG\REST\Handlers\AIPKit_REST_Base_Handler; // For permission callback
use WPAICG\REST\Handlers\AIPKit_REST_Chatbot_Embed_Handler; // NEW: Embed handler
use WPAICG\REST\Handlers\AIPKit_REST_Logs_Handler; // NEW: Logs handler


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * REST API Controller for AIPKit Public Interactions.
 * Registers routes and delegates handling to specific handler classes.
 */
class AIPKit_REST_Controller extends WP_REST_Controller
{
    protected $namespace = 'aipkit/v1';
    protected $rest_base_generate = 'generate';
    protected $rest_base_images = 'images/generate';
    protected $rest_base_embeddings = 'embeddings';
    protected $rest_base_chat = 'chat';
    protected $rest_base_vectors = 'vector-stores';
    protected $rest_base_chatbot_embed = 'chatbots'; // NEW
    protected $rest_base_logs = 'logs'; // NEW

    private $text_handler;
    private $image_handler;
    private $embeddings_handler;
    private $chat_handler;
    private $vector_store_handler;
    private $chatbot_embed_handler; // NEW
    private $logs_handler; // NEW
    private $base_handler; // For permission check

    public function __construct()
    {
        $this->namespace = 'aipkit/v1';
        $this->rest_base_generate = 'generate';
        $this->rest_base_images = 'images/generate';
        $this->rest_base_embeddings = 'embeddings';
        $this->rest_base_chat = 'chat';
        $this->rest_base_vectors = 'vector-stores';
        $this->rest_base_chatbot_embed = 'chatbots'; // NEW
        $this->rest_base_logs = 'logs'; // NEW

        // Instantiate handlers
        if (class_exists(AIPKit_REST_Text_Handler::class)) {
            $this->text_handler = new AIPKit_REST_Text_Handler();
        }

        if (class_exists(AIPKit_REST_Image_Handler::class)) {
            $this->image_handler = new AIPKit_REST_Image_Handler();
        }

        if (class_exists(AIPKit_REST_Embeddings_Handler::class)) {
            $this->embeddings_handler = new AIPKit_REST_Embeddings_Handler();
        }
        
        if (class_exists(AIPKit_REST_Chat_Handler::class)) {
            $this->chat_handler = new AIPKit_REST_Chat_Handler();
        }

        if (class_exists(AIPKit_REST_Vector_Store_Handler::class)) {
            $this->vector_store_handler = new AIPKit_REST_Vector_Store_Handler();
        }
        
        // NEW: Instantiate embed handler
        if (class_exists(AIPKit_REST_Chatbot_Embed_Handler::class)) {
            $this->chatbot_embed_handler = new AIPKit_REST_Chatbot_Embed_Handler();
        }

        // NEW: Instantiate logs handler
        if (class_exists(AIPKit_REST_Logs_Handler::class)) {
            $this->logs_handler = new AIPKit_REST_Logs_Handler();
        }

        if ($this->text_handler) {
            $this->base_handler = $this->text_handler;
        }

    }

    /**
     * Register text, image, embeddings, and chat generation routes.
     */
    public function register_routes()
    {
        if (!$this->text_handler || !$this->image_handler || !$this->embeddings_handler || !$this->chat_handler || !$this->vector_store_handler || !$this->base_handler || !$this->chatbot_embed_handler || !$this->logs_handler) {
            return;
        }
        
        // NEW: Register Chatbot Embed Config route
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_chatbot_embed . '/(?P<bot_id>\d+)/embed-config',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this->chatbot_embed_handler, 'handle_request'),
                'permission_callback' => '__return_true', // Publicly accessible
                'args'                => $this->chatbot_embed_handler->get_endpoint_args(),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_generate,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this->text_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->text_handler->get_endpoint_args(),
                ),
                'schema' => array($this->text_handler, 'get_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_images,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this->image_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->image_handler->get_endpoint_args(),
                ),
                'schema' => array($this->image_handler, 'get_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_embeddings,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this->embeddings_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->embeddings_handler->get_endpoint_args(),
                ),
                'schema' => array($this->embeddings_handler, 'get_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_chat . '/(?P<bot_id>\d+)/message',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this->chat_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->chat_handler->get_endpoint_args(),
                ),
                'schema' => array($this->chat_handler, 'get_item_schema'),
            )
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_vectors . '/upsert',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this->vector_store_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->vector_store_handler->get_endpoint_args(),
                ),
                'schema' => array($this->vector_store_handler, 'get_item_schema'),
            )
        );

        // NEW: Register Logs route
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base_logs,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this->logs_handler, 'handle_request'),
                    'permission_callback' => array($this->base_handler, 'check_permissions'),
                    'args'                => $this->logs_handler->get_endpoint_args(),
                ),
                'schema' => array($this->logs_handler, 'get_item_schema'),
            )
        );
    }

}