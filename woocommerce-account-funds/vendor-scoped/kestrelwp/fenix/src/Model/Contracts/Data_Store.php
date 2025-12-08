<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
/**
 * Contract for model data stores.
 *
 * @since 1.0.0
 */
interface Data_Store
{
    /**
     * Gets the store name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_name(): string;
    /**
     * Creates a new record of the model in database.
     *
     * @since 1.0.0
     *
     * @param Model $model created model with an ID
     */
    public function create(Model &$model): Model;
    /**
     * Reads a record of the model from the database.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return void
     */
    public function read(Model &$model): void;
    /**
     * Updates a record of the model in the database.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model updated model
     */
    public function update(Model &$model): Model;
    /**
     * Deletes a record of the model from the database.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return bool result
     */
    public function delete(Model &$model): bool;
    /**
     * Checks if a record of the model exists in the database.
     *
     * @since 1.1.0
     *
     * @param Model $model
     * @return bool
     */
    public function exists(Model $model): bool;
    /**
     * Returns an array of model records from the database.
     *
     * @TODO in a future major release, consider changing the returned object to a proper query object (that perhaps extends Collection class to keep the current API)
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     * @return Collection<int, Model>|Collection<string, Model>
     */
    public function query(array $args = []): Collection;
    /**
     * Reads the metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return void
     */
    public function read_meta_data(Model &$model): void;
    /**
     * Saves all the metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model
     */
    public function save_meta_data(Model &$model): Model;
    /**
     * Saves a single metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param string $meta_key
     * @param mixed $meta_value
     * @return Model
     */
    public function save_meta(Model &$model, string $meta_key, $meta_value): Model;
    /**
     * Deletes all metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return bool
     */
    public function delete_meta_data(Model &$model): bool;
    /**
     * Deletes a single metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param string $meta
     * @return bool
     */
    public function delete_meta(Model &$model, string $meta): bool;
}
