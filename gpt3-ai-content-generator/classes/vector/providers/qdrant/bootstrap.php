<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/bootstrap.php

namespace WPAICG\Vector\Providers;

use WPAICG\Vector\AIPKit_Vector_Base_Provider_Strategy;
use WP_Error;

// Ensure the function files are loaded.
$method_files = [
    '_request.php',
    'connect.php',
    'create-index-if-not-exists.php',
    'upsert-vectors.php',
    'query-vectors.php',
    'delete-vectors.php',
    'delete-index.php',
    'list-indexes.php',
    'describe-index.php',
    'upload-file-for-vector-store.php'
];

foreach ($method_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Qdrant Vector Store Provider Strategy (Modularized).
 */
class AIPKit_Vector_Qdrant_Strategy extends AIPKit_Vector_Base_Provider_Strategy {
    protected $api_key;
    protected $qdrant_url;
    // is_connected is inherited from AIPKit_Vector_Base_Provider_Strategy

    public function __construct() {
        // No specific constructor logic needed here for now
    }

    /**
     * Makes an HTTP request to the Qdrant API.
     * This method is internal to the strategy and called by other public methods.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH).
     * @param string $path API path (e.g., '/collections').
     * @param array $body Request body for POST/PUT/PATCH requests.
     * @param array $query_params Query parameters for the request.
     * @return array|WP_Error Decoded JSON response or WP_Error.
     */
    protected function _request(string $method, string $path, array $body = [], array $query_params = []): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\_request_logic($this, $method, $path, $body, $query_params);
    }

    public function connect(array $config): bool|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\connect_logic($this, $config);
    }

    public function create_index_if_not_exists(string $index_name, array $index_config): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\create_index_if_not_exists_logic($this, $index_name, $index_config);
    }

    public function upsert_vectors(string $index_name, array $vectors_data): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\upsert_vectors_logic($this, $index_name, $vectors_data);
    }

    public function query_vectors(string $index_name, array $query_vector_param, int $top_k, array $filter = []): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\query_vectors_logic($this, $index_name, $query_vector_param, $top_k, $filter);
    }

    public function delete_vectors(string $index_name, array $vector_ids_or_filter): bool|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\delete_vectors_logic($this, $index_name, $vector_ids_or_filter);
    }

    public function delete_index(string $index_name): bool|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\delete_index_logic($this, $index_name);
    }

    public function list_indexes(?int $limit = null, ?string $order = null, ?string $after = null, ?string $before = null): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\list_indexes_logic($this, $limit, $order, $after, $before);
    }

    public function describe_index(string $index_name): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\describe_index_logic($this, $index_name);
    }

    public function upload_file_for_vector_store(string $file_path, string $original_filename, string $purpose = 'user_data'): array|WP_Error {
        return \WPAICG\Vector\Providers\Qdrant\Methods\upload_file_for_vector_store_logic($this, $file_path, $original_filename, $purpose);
    }
    
    // Getters for protected properties needed by externalized functions
    public function get_api_key(): ?string { return $this->api_key; }
    public function get_qdrant_url(): ?string { return $this->qdrant_url; }
    public function get_is_connected_status(): bool { return $this->is_connected; }

    // Setters for protected properties
    public function set_api_key(?string $key): void { $this->api_key = $key; }
    public function set_qdrant_url(?string $url): void { $this->qdrant_url = $url ? rtrim($url, '/') : null; }
    public function set_is_connected_status(bool $status): void { $this->is_connected = $status; }

    // Public wrappers for protected base class methods, if needed by external functions
    public function decode_json_public_wrapper(string $json_string, string $context): array|WP_Error {
        return parent::decode_json($json_string, $context);
    }
    public function parse_error_response_public_wrapper($response_body, int $status_code, string $context): string {
        return parent::parse_error_response($response_body, $status_code, $context);
    }
}