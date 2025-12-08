<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\Data_Stores;

defined('ABSPATH') or die;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * User meta data store.
 *
 * @since 1.1.0
 */
abstract class User_Meta implements Data_Store
{
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string internal storage name */
    protected const NAME = '';
    /**
     * Gets the name of the meta used as data store.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_name(): string
    {
        return static::plugin()->key(static::NAME);
    }
    /**
     * Reads the metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return void
     */
    public function read_meta_data(Model &$model): void
    {
        // by default, metadata is not handled in this data store but implementations of this data store can change this
    }
    /**
     * Saves all the metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model
     */
    public function save_meta_data(Model &$model): Model
    {
        // by default, metadata is not handled in this data store but implementations of this data store can change this
        return $model;
    }
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
    public function save_meta(Model &$model, string $meta_key, $meta_value): Model
    {
        // by default, metadata is not handled in this data store but implementations of this data store can change this
        return $model;
    }
    /**
     * Deletes all metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return bool
     */
    public function delete_meta_data(Model &$model): bool
    {
        // by default, metadata is not handled in this data store but implementations of this data store can change this
        return \false;
    }
    /**
     * Deletes a single metadata of the model.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param string $meta
     * @return bool
     */
    public function delete_meta(Model &$model, string $meta): bool
    {
        // by default, metadata is not handled in this data store but implementations of this data store can change this
        return \false;
    }
}
