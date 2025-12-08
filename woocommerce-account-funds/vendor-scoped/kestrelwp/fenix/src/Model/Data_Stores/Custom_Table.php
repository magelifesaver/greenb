<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Data_Stores;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database\Table;
/**
 * Custom table data store.
 *
 * @since 1.6.0
 */
abstract class Custom_Table implements Data_Store
{
    use Is_Singleton;
    /** @var string internal storage name */
    protected const NAME = '';
    /**
     * Gets the name of the post type used as data store.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function get_name(): string
    {
        return static::NAME;
    }
    /**
     * Returns the table object for the data store.
     *
     * @since 1.6.0
     *
     * @return Table
     */
    abstract protected function table(): Table;
    /**
     * Creates a record in the database table for the model.
     *
     * @since 1.6.0
     *
     * @param Model $model
     * @return Model
     */
    public function create(Model &$model): Model
    {
        $id = $this->table()->insert($model->to_array());
        if ($id) {
            $model->set_id($id);
        }
        return $model;
    }
    /**
     * Reads a record from the database table and sets its properties to the model.
     *
     * This assumes that the model will use the primary key of the table as its ID.
     * Concrete implementations of this data store may override this method, also to adjust the properties mapped to the model.
     *
     * @since 1.6.0
     *
     * @param Model $model
     * @return void
     */
    public function read(Model &$model): void
    {
        $id = $model->get_id();
        if (!$id) {
            return;
        }
        $result = $this->table()->get_row('WHERE ' . ($this->table()->get_primary_key() ?: 'id') . ' = %d', $id);
        if (!$result) {
            return;
        }
        $model->set_properties((array) $result);
    }
    /**
     * Updates a record in the database table.
     *
     * This assumes that the model will use the primary key of the table as its ID.
     * Concrete implementations of this data store may override this method.
     *
     * @since 1.6.0
     *
     * @param Model $model
     * @return Model
     */
    public function update(Model &$model): Model
    {
        $id = $model->get_id();
        if (!$id) {
            return $model;
        }
        $this->table()->update($model->to_array(), [], [$this->table()->get_primary_key() ?: 'id' => $id], ['%d']);
        return $model;
    }
    /**
     * Deletes a record from the database table.
     *
     * This assumes that the model will use the primary key of the table as its ID.
     * Concrete implementations of this data store may override this method.
     *
     * @since 1.6.0
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model &$model): bool
    {
        $id = $model->get_id();
        if (!$id) {
            return \false;
        }
        $result = $this->table()->delete([$this->table()->get_primary_key() ?: 'id' => $id], ['%d']);
        if (!$result) {
            return \false;
        }
        return \true;
    }
    /**
     * Determines if a record exists in the database table.
     *
     * This assumes that the model will use the primary key of the table as its ID.
     * Concrete implementations of this data store may override this method.
     *
     * @since 1.6.0
     *
     * @param Model $model
     * @return bool
     */
    public function exists(Model $model): bool
    {
        $id = $model->get_id();
        if (!$id) {
            return \false;
        }
        $result = $this->table()->get_row('WHERE ' . ($this->table()->get_primary_key() ?: 'id') . ' = %d', $id);
        return !empty($result);
    }
    /**
     * Reads the metadata of the model.
     *
     * @since 1.6.0
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
     * @since 1.6.0
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
     * @since 1.6.0
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
     * @since 1.6.0
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
     * @since 1.6.0
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
