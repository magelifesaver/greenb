<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Base model object.
 *
 * @since 1.0.0
 *
 * @method int|string|null get_id()
 * @method array<string, mixed> get_defaults()
 * @method $this set_id( int|string|null $id )
 */
abstract class Model
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string the model name */
    public const MODEL_NAME = '';
    /** @var int|string|null model ID */
    protected $id = null;
    /** @var array<string, mixed> */
    protected array $meta_data = [];
    /** @var array<string, mixed> default property values */
    protected array $defaults = [];
    /** @var bool whether the metadata has been read */
    protected bool $meta_data_read = \false;
    /**
     * Model constructor.
     *
     * @see Model::find() to get a new instance
     * @see Model::find_many() to get many instances
     * @see Model::seed() to build a new instance
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|int|static|string|null $source
     */
    protected function __construct($source = null)
    {
        $this->set_defaults();
        if (is_int($source) || is_string($source)) {
            $this->id = $source;
            static::get_data_store()->read($this);
        } elseif (is_array($source)) {
            $this->set_properties($source);
        } elseif ($source instanceof Model) {
            $this->set_properties($source->to_array());
        }
        $this->to_array_excluded_properties = ['defaults', 'meta_data', 'meta_data_read'];
    }
    /**
     * Stores the object ID when serializing.
     *
     * @since 1.0.0
     *
     * return array<int>
     */
    public function __sleep()
    {
        return [$this->id];
    }
    /**
     * Re-runs the constructor with the object ID when re-initializing.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        $this->__construct($this->id);
    }
    /**
     * Sets the default data to the model.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    protected function set_defaults(): Model
    {
        $this->set_properties($this->defaults);
        return $this;
    }
    /**
     * Determines if the object is new.
     *
     * @since
     *
     * @return bool
     */
    public function is_new(): bool
    {
        return empty($this->id);
    }
    /**
     * Gets an instance of the data store for this model.
     *
     * @since 1.0.0
     *
     * @return Data_Store
     */
    abstract protected static function get_data_store(): Data_Store;
    /**
     * Seeds a new instance of the model
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $args optional parameters to pass to the constructor
     * @return static
     */
    public static function seed(?array $args = null): Model
    {
        // @phpstan-ignore-next-line
        return new static($args);
    }
    /**
     * Gets an instance of the current model.
     *
     * @since 1.0.0
     *
     * @param mixed $identifier
     * @return $this|null
     */
    abstract public static function find($identifier): ?Model;
    /**
     * Gets many instances of the current model.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     * @return Collection<int|string, Model>
     */
    abstract public static function find_many(array $args = []): Collection;
    /**
     * Saves the model.
     *
     * @since 1.0.0
     *
     * @param bool $save_meta_data
     * @return static
     */
    public function save(bool $save_meta_data = \true): Model
    {
        if ($this->is_new()) {
            $model = static::get_data_store()->create($this);
            $action = 'created';
        } else {
            $model = static::get_data_store()->update($this);
            $action = 'updated';
        }
        if ($save_meta_data) {
            $this->meta_data_read = \true;
            $model->save_meta_data();
        }
        /**
         * Fires after the model is saved.
         *
         * @since 1.0.0
         *
         * @param Model $model the saved model
         */
        do_action(static::plugin()->hook(static::MODEL_NAME . '_' . $action), $model);
        return $model;
        // @phpstan-ignore-line
    }
    /**
     * Deletes the model.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function delete(): bool
    {
        $deleted = static::get_data_store()->delete($this);
        if ($deleted) {
            $defaults = $this->get_defaults();
            $default_id = $defaults['id'] ?? null;
            $this->set_id($default_id);
            /**
             * Fires after the model is deleted.
             *
             * @since 1.0.0
             *
             * @param Model $model the deleted model
             */
            do_action(static::plugin()->hook(static::MODEL_NAME . '_deleted'), $this);
        }
        return $deleted;
    }
    /**
     * Gets the model metadata.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_meta_data(): array
    {
        if (!$this->meta_data_read && !$this->is_new()) {
            static::get_data_store()->read_meta_data($this);
        }
        return $this->meta_data;
    }
    /**
     * Sets the model metadata.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $meta_data
     * @return $this
     */
    public function set_meta_data(array $meta_data = []): Model
    {
        $this->meta_data = $meta_data;
        $this->meta_data_read = \true;
        return $this;
    }
    /**
     * Returns a meta value from the model metadata.
     *
     * @since 1.0.0
     *
     * @param string $meta_key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get_meta(string $meta_key, $default = null)
    {
        $meta_data = $this->get_meta_data();
        return $meta_data[$meta_key] ?? $default;
    }
    /**
     * Saves all the metadata of the model.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function save_meta_data(): Model
    {
        // ensure the metadata has been read
        if (empty($this->meta_data)) {
            $this->get_meta_data();
        }
        return static::get_data_store()->save_meta_data($this);
        // @phpstan-ignore-line
    }
    /**
     * Saves a single metadata of the model.
     *
     * @since 1.0.0
     *
     * @param string $meta_key
     * @return $this
     */
    public function save_meta(string $meta_key): Model
    {
        if (!array_key_exists($meta_key, $this->meta_data)) {
            return $this;
        }
        return static::get_data_store()->save_meta($this, $meta_key, $this->meta_data[$meta_key]);
        // @phpstan-ignore-line
    }
    /**
     * Sets a meta value to the model metadata.
     *
     * @since 1.0.0
     *
     * @param string $meta_key
     * @param mixed $meta_value
     * @return $this
     */
    public function set_meta(string $meta_key, $meta_value): Model
    {
        $this->meta_data[$meta_key] = $meta_value;
        return $this;
    }
    /**
     * Deletes all metadata of the model.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function delete_meta_data(): bool
    {
        return static::get_data_store()->delete_meta_data($this);
    }
    /**
     * Deletes a meta value from the model metadata.
     *
     * @since 1.0.0
     *
     * @param string $meta_key
     * @return $this
     */
    public function delete_meta(string $meta_key): Model
    {
        $this->meta_data[$meta_key] = null;
        static::get_data_store()->delete_meta($this, $meta_key);
        return $this;
    }
}
