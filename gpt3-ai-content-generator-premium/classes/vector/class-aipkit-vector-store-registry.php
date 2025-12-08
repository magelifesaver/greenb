<?php

namespace WPAICG\Vector;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Vector_Store_Registry
 *
 * Manages a WordPress option to store a list/cache of vector stores
 * from different providers (OpenAI, Pinecone, Qdrant).
 */
class AIPKit_Vector_Store_Registry {

    const OPTION_NAME = 'aipkit_vector_stores_registry';

    /**
     * Gets all registered vector stores from the option.
     *
     * @return array An associative array where keys are provider names
     *               (e.g., 'OpenAI', 'Pinecone') and values are arrays of store objects.
     *               Example: ['OpenAI' => [['id' => 'vs_1', 'name' => 'Store 1'], ...]]
     */
    public static function get_all_registered_stores(): array {
        return get_option(self::OPTION_NAME, []);
    }

    /**
     * Gets registered vector stores for a specific provider.
     *
     * @param string $provider The provider name (e.g., 'OpenAI', 'Pinecone').
     * @return array An array of store objects for the given provider.
     */
    public static function get_registered_stores_by_provider(string $provider): array {
        $all_stores = self::get_all_registered_stores();
        return $all_stores[$provider] ?? [];
    }

    /**
     * Updates the list of registered stores for a specific provider.
     * This will overwrite any existing stores for that provider.
     *
     * @param string $provider The provider name.
     * @param array $stores_list An array of store objects (e.g., from API response).
     *                           Each object should ideally have at least 'id' and 'name'.
     *                           For Pinecone, 'id' will be the 'name' of the index.
     */
    public static function update_registered_stores_for_provider(string $provider, array $stores_list): void {
        $all_stores = self::get_all_registered_stores();
        $existing = $all_stores[$provider] ?? [];
        $by_id = [];
        foreach ($existing as $store) {
            if (isset($store['id'])) {
                $by_id[$store['id']] = $store;
            }
        }

        $normalized_stores = [];
        foreach ($stores_list as $store_data) {
            if (!is_array($store_data)) {
                continue;
            }
            // Ensure 'id' exists. For Pinecone, 'name' is the primary ID.
            if (!isset($store_data['id']) && isset($store_data['name']) && $provider === 'Pinecone') {
                $store_data['id'] = $store_data['name'];
            }
            if (!isset($store_data['id'])) {
                continue;
            }

            $incoming = $store_data;
            $incoming['provider'] = $provider;

            // Merge with existing by id to preserve fields like counts if incoming lacks them
            $id = $incoming['id'];
            if (isset($by_id[$id]) && is_array($by_id[$id])) {
                // Existing first, then incoming to allow incoming to overwrite when present
                $merged = array_merge($by_id[$id], $incoming);
                $normalized_stores[] = $merged;
            } else {
                $normalized_stores[] = $incoming;
            }
        }

        $all_stores[$provider] = $normalized_stores;
        update_option(self::OPTION_NAME, $all_stores, false); // Autoload 'no'
    }

    /**
     * Adds or updates a single registered store for a specific provider.
     *
     * @param string $provider The provider name.
     * @param array $store_data The store data object (must include 'id', or 'name' for Pinecone).
     */
    public static function add_registered_store(string $provider, array $store_data): void {
        // Ensure 'id' exists. For Pinecone, 'name' is the primary ID.
        if (!isset($store_data['id']) && isset($store_data['name']) && $provider === 'Pinecone') {
            $store_data['id'] = $store_data['name'];
        }
        if (!isset($store_data['id'])) {
            return;
        }

        $all_stores = self::get_all_registered_stores();
        if (!isset($all_stores[$provider]) || !is_array($all_stores[$provider])) {
            $all_stores[$provider] = [];
        }

        $store_id = $store_data['id'];
        $found = false;
        foreach ($all_stores[$provider] as $key => $existing_store) {
            if (isset($existing_store['id']) && $existing_store['id'] === $store_id) {
                $all_stores[$provider][$key] = array_merge($existing_store, $store_data, ['provider' => $provider]); // Ensure provider is set
                $found = true;
                break;
            }
        }

        if (!$found) {
            $store_data['provider'] = $provider; // Ensure provider is set
            $all_stores[$provider][] = $store_data;
        }

        update_option(self::OPTION_NAME, $all_stores, false);
    }

    /**
     * Removes a single registered store for a specific provider by its ID.
     * For Pinecone, $store_id is the index name.
     *
     * @param string $provider The provider name.
     * @param string $store_id The ID of the store to remove.
     */
    public static function remove_registered_store(string $provider, string $store_id): void {
        $all_stores = self::get_all_registered_stores();
        if (!isset($all_stores[$provider]) || !is_array($all_stores[$provider])) {
            return; // No stores for this provider
        }

        $updated_provider_stores = [];
        $changed = false;
        foreach ($all_stores[$provider] as $store) {
            if (isset($store['id']) && $store['id'] === $store_id) {
                $changed = true; // Mark that a store was found and will be removed
            } else {
                $updated_provider_stores[] = $store;
            }
        }

        if ($changed) {
            if (empty($updated_provider_stores)) {
                unset($all_stores[$provider]); // Remove provider key if no stores left
            } else {
                $all_stores[$provider] = $updated_provider_stores;
            }
            update_option(self::OPTION_NAME, $all_stores, false);
        }
    }

    /**
     * Clears all registered stores for a specific provider or all providers.
     *
     * @param string|null $provider If null, clears all stores. Otherwise, clears for the specified provider.
     */
    public static function clear_registered_stores(?string $provider = null): void {
        if ($provider === null) {
            delete_option(self::OPTION_NAME);
        } else {
            $all_stores = self::get_all_registered_stores();
            if (isset($all_stores[$provider])) {
                unset($all_stores[$provider]);
                update_option(self::OPTION_NAME, $all_stores, false);
            }
        }
    }
}