<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/bootstrap.php
// Status: MODIFIED

namespace WPAICG\Vector\Providers;

use WPAICG\Vector\AIPKit_Vector_Base_Provider_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;
use CURLFile; // Make sure this is available or handled if not

// Ensure the function files are loaded.
// It's often better to use an autoloader, but for this specific refactor:
require_once __DIR__ . '/_request.php';
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/create-index-if-not-exists.php';
require_once __DIR__ . '/delete-index.php';
require_once __DIR__ . '/delete-openai-file.php';
require_once __DIR__ . '/delete-vectors.php';
require_once __DIR__ . '/describe-index.php';
require_once __DIR__ . '/get-mime-type-from-filename.php';
require_once __DIR__ . '/list-indexes.php';
require_once __DIR__ . '/list-vector-store-files.php';
require_once __DIR__ . '/query-vectors.php';
require_once __DIR__ . '/retrieve-file-batch.php';
require_once __DIR__ . '/upload-file.php';
require_once __DIR__ . '/upsert-vectors.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * OpenAI Vector Store Provider Strategy (Modularized).
 * Interacts with OpenAI's Vector Store API.
 */
class AIPKit_Vector_OpenAI_Strategy extends AIPKit_Vector_Base_Provider_Strategy {

    // Properties are now protected to be accessible by the namespaced functions via $this
    protected $api_key;
    protected $base_url = 'https://api.openai.com';
    protected $api_version = 'v1';
    protected static $static_mime_type_map; // Renamed to avoid potential conflicts

    public function __construct() {
        // parent::__construct(); // REMOVED: Cannot call constructor of abstract parent
        if (empty(self::$static_mime_type_map)) {
            self::$static_mime_type_map = require __DIR__ . '/mime-map.php';
        }
        // Ensure OpenAIUrlBuilder is loaded
        if (!class_exists(OpenAIUrlBuilder::class)) {
            $url_builder_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/openai/OpenAIUrlBuilder.php';
            if (file_exists($url_builder_path)) {
                require_once $url_builder_path;
            }
        }
    }

    /**
     * Makes an HTTP request to the OpenAI API.
     * This method is internal to the strategy and called by other public methods.
     *
     * @param string $method HTTP method (GET, POST, DELETE).
     * @param string $url Full request URL.
     * @param array $body Request body for POST requests or multipart data for file uploads.
     * @param bool $is_file_upload True if this is a multipart/form-data file upload.
     * @return array|WP_Error Decoded JSON response or WP_Error.
     */
    protected function _request(string $method, string $url, array $body = [], bool $is_file_upload = false): array|WP_Error {
        // The actual logic is in _request.php, called via the namespaced function
        return \WPAICG\Vector\Providers\OpenAI\Methods\_request_logic($this, $method, $url, $body, $is_file_upload);
    }

    public function connect(array $config): bool|WP_Error {
        // The main logic of setting properties is done here, the _logic file might do a test call.
        if (empty($config['api_key'])) {
            return new WP_Error('missing_api_key', __('OpenAI API Key is required for connection.', 'gpt3-ai-content-generator'));
        }
        $this->api_key = $config['api_key'];
        $this->base_url = $config['base_url'] ?? $this->base_url;
        $this->api_version = $config['api_version'] ?? $this->api_version;
        $this->is_connected = true; // Mark as connected before trying a test call in logic file

        return \WPAICG\Vector\Providers\OpenAI\Methods\connect_logic($this, $config);
    }

    public function create_index_if_not_exists(string $index_name, array $index_config): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\create_index_if_not_exists_logic($this, $index_name, $index_config);
    }

    public function upsert_vectors(string $index_name, array $vectors): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\upsert_vectors_logic($this, $index_name, $vectors);
    }

    public function query_vectors(string $index_name, array $query_vector, int $top_k, array $filter = []): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\query_vectors_logic($this, $index_name, $query_vector, $top_k, $filter);
    }

    public function delete_vectors(string $index_name, array $vector_ids): bool|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\delete_vectors_logic($this, $index_name, $vector_ids);
    }

    public function delete_index(string $index_name): bool|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\delete_index_logic($this, $index_name);
    }

    public function list_indexes(?int $limit = 20, ?string $order = 'desc', ?string $after = null, ?string $before = null): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\list_indexes_logic($this, $limit, $order, $after, $before);
    }

    public function describe_index(string $index_name): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\describe_index_logic($this, $index_name);
    }

    public function retrieve_file_batch(string $vector_store_id, string $batch_id): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\retrieve_file_batch_logic($this, $vector_store_id, $batch_id);
    }

    public function list_vector_store_files(string $vector_store_id, array $query_params = []): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\list_vector_store_files_logic($this, $vector_store_id, $query_params);
    }

    public function upload_file_for_vector_store(string $file_path, string $original_filename, string $purpose = 'assistants_file'): array|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\upload_file_for_vector_store_logic($this, $file_path, $original_filename, $purpose);
    }

    public function delete_openai_file_object(string $file_id): bool|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\delete_openai_file_object_logic($this, $file_id);
    }

    /**
     * Determines the MIME type from the filename extension.
     * This method is internal to the strategy.
     *
     * @param string $filename The filename.
     * @return string|WP_Error The MIME type string or WP_Error if not determinable/supported.
     */
    protected function get_mime_type_from_filename(string $filename): string|WP_Error {
        return \WPAICG\Vector\Providers\OpenAI\Methods\get_mime_type_from_filename_logic($this, $filename);
    }

    // Getter for static mime map for use in get_mime_type_from_filename_logic
    public static function get_static_mime_type_map(): array {
        if (empty(self::$static_mime_type_map)) {
            self::$static_mime_type_map = require __DIR__ . '/mime-map.php';
        }
        return self::$static_mime_type_map;
    }
    
    // Getters for protected properties if needed by externalized functions (if not passing $this context fully)
    public function get_api_key(): ?string { return $this->api_key; }
    public function get_base_url(): string { return $this->base_url; }
    public function get_api_version(): string { return $this->api_version; }
    public function get_is_connected_status(): bool { return $this->is_connected; }

    // Public wrappers for protected base class methods, if needed by external functions
    public function decode_json_public_wrapper(string $json_string, string $context): array|WP_Error {
        return parent::decode_json($json_string, $context);
    }
    public function parse_error_response_public_wrapper($response_body, int $status_code, string $context): string {
        return parent::parse_error_response($response_body, $status_code, $context);
    }
}