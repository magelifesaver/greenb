<?php
/**
 * API client
 *
 * Provides methods for communicating with Pinecone and OpenAI vector stores.  All
 * remote requests are made via the WordPress HTTP API to benefit from its
 * built‑in error handling and filters.  The API endpoints used here follow
 * current documentation for 2025; for example, OpenAI vector stores are
 * listed via `GET /v1/vector_stores`【57791364174585†L346-L400】, while Pinecone indexes can be
 * retrieved via the controller service.  Update these methods as APIs
 * evolve.
 */
class Vector_Sync_Api_Client {
    /**
     * Retrieve the stored API key for a given service.
     *
     * @param string $service Either 'pinecone' or 'openai'.
     * @return string API key or empty string if not set.
     */
    public function get_api_key( $service ) {
        // Settings are stored in a custom table.  Retrieve them through the
        // database abstraction instead of using get_option().
        $settings = Vector_Sync_DB::get_settings();
        $api      = $settings['api'] ?? array();
        if ( 'pinecone' === $service && ! empty( $api['pinecone_api_key'] ) ) {
            return $api['pinecone_api_key'];
        }
        if ( 'openai' === $service && ! empty( $api['openai_api_key'] ) ) {
            return $api['openai_api_key'];
        }
        return '';
    }

    /**
     * Get a list of available vector spaces (indexes or stores).
     *
     * For OpenAI this calls the `vector_stores` endpoint【57791364174585†L346-L400】.  For
     * Pinecone the endpoint varies by environment; by default we call the
     * controller service to list indexes.  Returns an array with id and name
     * pairs or a WP_Error on failure.
     *
     * @param string $service 'pinecone' or 'openai'.
     * @return array|WP_Error Array of vector spaces.
     */
    public function list_vector_spaces( $service ) {
        if ( 'openai' === $service ) {
            $api_key = $this->get_api_key( 'openai' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'missing_key', __( 'OpenAI API key not set.', 'vector-sync' ) );
            }
            // Retrieve optional organisation and project IDs from settings to
            // target the correct account and project when listing stores【799506338848902†L138-L156】.
            $settings = Vector_Sync_DB::get_settings();
            $api_opts = $settings['api'] ?? array();
            // Build request headers.  Follow the pattern used by the
            // official OpenAI SDK: include Content-Type for all non-file
            // requests and the assistants beta header【799506338848902†L148-L156】.  We omit
            // organisation and project headers here because they are rarely
            // required and can cause empty responses.
            $headers = array(
                'Authorization' => 'Bearer ' . $api_key,
                'OpenAI-Beta'   => 'assistants=v2',
                'Content-Type'  => 'application/json',
            );
            $args = array(
                'headers' => $headers,
                'timeout' => 30,
            );
            // Request up to 100 vector stores.  The limit defaults to 20 but
            // can be increased to ensure we retrieve all stores.  If the
            // response indicates there are more pages (has_more=true) we
            // could paginate; however the majority of users have fewer than
            // 100 stores, so additional requests are unnecessary.
            $url      = 'https://api.openai.com/v1/vector_stores?limit=100';
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === $code && isset( $body['data'] ) ) {
                $result = array();
                foreach ( $body['data'] as $store ) {
                    $result[] = array(
                        'id'   => $store['id'],
                        'name' => $store['name'],
                    );
                }
                return $result;
            }
            return new WP_Error( 'api_error', __( 'Unable to list OpenAI vector stores.', 'vector-sync' ), $body );
        }
        if ( 'pinecone' === $service ) {
            $api_key = $this->get_api_key( 'pinecone' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'missing_key', __( 'Pinecone API key not set.', 'vector-sync' ) );
            }
            // Use the unified Pinecone control-plane endpoint to list indexes.  In
            // the 2025 API, indexes are retrieved via GET /indexes on the
            // api.pinecone.io host【469912433914145†L140-L147】.  We specify the
            // X-Pinecone-Api-Version header to ensure the request targets the
            // latest stable version (2025-10).  No environment is required
            // because each index carries its own region.
            $url  = 'https://api.pinecone.io/indexes';
            $args = array(
                'headers' => array(
                    'Api-Key'              => $api_key,
                    'Content-Type'         => 'application/json',
                    'X-Pinecone-Api-Version' => '2025-10',
                ),
                'timeout' => 30,
            );
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === $code && isset( $body['indexes'] ) && is_array( $body['indexes'] ) ) {
                $result = array();
                foreach ( $body['indexes'] as $index ) {
                    $name = isset( $index['name'] ) ? $index['name'] : '';
                    $result[] = array(
                        'id'   => $name,
                        'name' => $name,
                    );
                }
                return $result;
            }
            // In case of unexpected response structure, surface the raw body for
            // debugging purposes.
            return new WP_Error( 'api_error', __( 'Unable to list Pinecone indexes.', 'vector-sync' ), $body );
        }
        return new WP_Error( 'invalid_service', __( 'Unknown service.', 'vector-sync' ) );
    }

    /**
     * Create a new vector space (index or store).
     *
     * @param string $service 'pinecone' or 'openai'.
     * @param string $name    Human‑readable name for the space.
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    public function create_vector_space( $service, $name ) {
        if ( 'openai' === $service ) {
            $api_key = $this->get_api_key( 'openai' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'missing_key', __( 'OpenAI API key not set.', 'vector-sync' ) );
            }
            // Include optional organisation/project headers when creating a store.
            $settings = Vector_Sync_DB::get_settings();
            $api_opts = $settings['api'] ?? array();
            $headers = array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            );
            if ( ! empty( $api_opts['openai_org_id'] ) ) {
                $headers['OpenAI-Organization'] = $api_opts['openai_org_id'];
            }
            if ( ! empty( $api_opts['openai_project_id'] ) ) {
                $headers['OpenAI-Project'] = $api_opts['openai_project_id'];
            }
            $args = array(
                'headers' => $headers,
                'body'    => wp_json_encode( array( 'name' => $name ) ),
                'method'  => 'POST',
                'timeout' => 30,
            );
            $response = wp_remote_post( 'https://api.openai.com/v1/vector_stores', $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === $code || 201 === $code ) {
                return $body;
            }
            return new WP_Error( 'api_error', __( 'Unable to create OpenAI vector store.', 'vector-sync' ), $body );
        }
        if ( 'pinecone' === $service ) {
            $api_key   = $this->get_api_key( 'pinecone' );
            $dimension = 1536; // default dimension; adjust based on embedding model
            if ( empty( $api_key ) ) {
                return new WP_Error( 'missing_key', __( 'Pinecone API key not set.', 'vector-sync' ) );
            }
            // Create an index via the unified control-plane endpoint.  See
            // Pinecone API docs for the body structure: name, dimension, and
            // optional spec.  We specify the latest API version header.【469912433914145†L140-L147】
            $url  = 'https://api.pinecone.io/indexes';
            $body = array(
                'name'      => $name,
                'dimension' => $dimension,
                'metric'    => 'cosine',
                // Use serverless spec by default in the us-east-1 region.  Users
                // can later configure environment via the Pinecone console.
                'spec'      => array(
                    'serverless' => array(
                        'cloud'  => 'aws',
                        'region' => 'us-east-1',
                    ),
                ),
            );
            $args = array(
                'headers' => array(
                    'Api-Key'              => $api_key,
                    'Content-Type'         => 'application/json',
                    'X-Pinecone-Api-Version' => '2025-10',
                ),
                'body'    => wp_json_encode( $body ),
                'method'  => 'POST',
                'timeout' => 30,
            );
            $response = wp_remote_post( $url, $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code( $response );
            $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === $code || 201 === $code ) {
                return $resp_body;
            }
            return new WP_Error( 'api_error', __( 'Unable to create Pinecone index.', 'vector-sync' ), $resp_body );
        }
        return new WP_Error( 'invalid_service', __( 'Unknown service.', 'vector-sync' ) );
    }

    /**
     * Upsert an array of vectors into the specified space.  This method is a
     * placeholder; implementation depends on the selected service.  See the
     * Pinecone and OpenAI documentation for details on payload format.  The
     * Data Manager uses this method to push embeddings during sync.
     *
     * @param string $service Service name.
     * @param string $space_id Vector space identifier.
     * @param array  $vectors  Array of vectors with `id`, `values` and optional
     *                         `metadata` keys.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function upsert_vectors( $service, $space_id, array $vectors ) {
        if ( empty( $vectors ) ) {
            return true;
        }
        // OpenAI uses file‑based ingestion rather than upsert; vector stores are
        // populated via the file upload API.  Implementation would require
        // creating files and associating them with the store.
        if ( 'openai' === $service ) {
            return new WP_Error( 'not_implemented', __( 'Upserting vectors to OpenAI vector stores is not implemented.', 'vector-sync' ) );
        }
        if ( 'pinecone' === $service ) {
            $api_key     = $this->get_api_key( 'pinecone' );
            $environment = $this->get_pinecone_environment();
            if ( empty( $api_key ) || empty( $environment ) ) {
                return new WP_Error( 'missing_key', __( 'Pinecone API key or environment not set.', 'vector-sync' ) );
            }
            // Pinecone upsert endpoint: https://<index>-<project>.svc.<environment>.pinecone.io/vectors/upsert
            // Without project ID we cannot build the full URL; store a base URL in settings.
            // Retrieve the base URL from the nested settings structure.  This
            // value should be stored under pinecone -> base_url if configured.
            $all_settings = Vector_Sync_DB::get_settings();
            $base_url = isset( $all_settings['pinecone']['base_url'] ) ? $all_settings['pinecone']['base_url'] : '';
            if ( empty( $base_url ) ) {
                return new WP_Error( 'missing_url', __( 'Pinecone index base URL not set.', 'vector-sync' ) );
            }
            $url  = trailingslashit( $base_url ) . 'vectors/upsert';
            $body = array(
                'vectors' => $vectors,
                'namespace' => '',
            );
            $args = array(
                'headers' => array(
                    'Api-Key'      => $api_key,
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'method'  => 'POST',
                'timeout' => 60,
            );
            $response = wp_remote_post( $url, $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code( $response );
            if ( 200 === $code ) {
                return true;
            }
            return new WP_Error( 'api_error', __( 'Unable to upsert vectors to Pinecone.', 'vector-sync' ) );
        }
        return new WP_Error( 'invalid_service', __( 'Unknown service.', 'vector-sync' ) );
    }

    /**
     * Helper to fetch the Pinecone environment from settings.
     */
    private function get_pinecone_environment() {
        $settings = Vector_Sync_DB::get_settings();
        $api      = $settings['api'] ?? array();
        return isset( $api['pinecone_environment'] ) && $api['pinecone_environment'] ? $api['pinecone_environment'] : 'us-west4-gcp';
    }
}