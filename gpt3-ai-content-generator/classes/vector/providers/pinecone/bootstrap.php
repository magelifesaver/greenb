<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/bootstrap.php
// Status: MODIFIED

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
 * Pinecone Vector Store Provider Strategy (Modularized).
 */
class AIPKit_Vector_Pinecone_Strategy extends AIPKit_Vector_Base_Provider_Strategy {
    // Properties are protected to be accessible by the namespaced functions via $this
    protected $api_key;
    protected $base_api_url = 'https://api.pinecone.io'; // Controller plane API

    public function __construct() {
        // parent::__construct(); // Cannot call constructor of abstract parent
        // No specific constructor logic needed here for now
    }

    /**
     * Makes an HTTP request to the Pinecone API.
     * This method is internal to the strategy and called by other public methods.
     *
     * @param string $method HTTP method (GET, POST, DELETE, PATCH).
     * @param string $path API path (e.g., '/indexes').
     * @param array $body Request body for POST/PATCH requests.
     * @param string|null $index_host_url Optional. If provided, this URL is used as the base instead of controller API.
     * @return array|WP_Error Decoded JSON response or WP_Error.
     */
    protected function _request(string $method, string $path, array $body = [], string $index_host_url = null): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\_request_logic($this, $method, $path, $body, $index_host_url);
    }

    public function connect(array $config): bool|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\connect_logic($this, $config);
    }

    public function create_index_if_not_exists(string $index_name, array $index_config): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\create_index_if_not_exists_logic($this, $index_name, $index_config);
    }

    public function upsert_vectors(string $index_name, array $vectors_data): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\upsert_vectors_logic($this, $index_name, $vectors_data);
    }

    public function query_vectors(string $index_name, array $query_vector_param, int $top_k, array $filter = []): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\query_vectors_logic($this, $index_name, $query_vector_param, $top_k, $filter);
    }

    public function delete_vectors(string $index_name, array $vector_ids): bool|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\delete_vectors_logic($this, $index_name, $vector_ids);
    }

    public function delete_index(string $index_name): bool|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\delete_index_logic($this, $index_name);
    }

    public function list_indexes(?int $limit = null, ?string $order = null, ?string $after = null, ?string $before = null): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\list_indexes_logic($this, $limit, $order, $after, $before);
    }

    public function describe_index(string $index_name): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\describe_index_logic($this, $index_name);
    }

    public function upload_file_for_vector_store(string $file_path, string $original_filename, string $purpose = 'user_data'): array|WP_Error {
        return \WPAICG\Vector\Providers\Pinecone\Methods\upload_file_for_vector_store_logic($this, $file_path, $original_filename, $purpose);
    }

    // Getters for protected properties if needed by externalized functions
    public function get_api_key(): ?string { return $this->api_key; }
    public function get_base_api_url(): string { return $this->base_api_url; }
    public function get_is_connected_status(): bool { return $this->is_connected; }
    public function set_is_connected_status(bool $status): void { $this->is_connected = $status; }
    public function set_api_key(string $key): void { $this->api_key = $key; }

}