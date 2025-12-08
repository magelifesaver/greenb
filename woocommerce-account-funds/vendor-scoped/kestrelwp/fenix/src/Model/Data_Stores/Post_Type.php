<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Data_Stores;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use WP_Error;
use WP_Post;
/**
 * Post type data store.
 *
 * @since 1.0.0
 */
abstract class Post_Type implements Data_Store
{
    use Is_Singleton;
    /** @var string internal storage name */
    protected const NAME = '';
    /**
     * Gets the name of the post type used as data store.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_name(): string
    {
        return static::NAME;
    }
    /**
     * Creates a new record of the model in database as a post type.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model
     */
    public function create(Model &$model): Model
    {
        return $this->upsert($model, 'insert');
    }
    /**
     * Reads a record of the model from the database as a post type.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return void
     */
    public function read(Model &$model): void
    {
        if ($model->is_new()) {
            return;
        }
        $post = get_post($model->get_id());
        if (!$post instanceof WP_Post || $post->post_type !== static::get_name()) {
            return;
        }
        foreach ($this->get_model_data($post) as $property => $value) {
            $model->{'set_' . $property}($value);
        }
        $model->set_id($post->ID);
    }
    /**
     * Updates a record of the model in the database as a post type.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model
     */
    public function update(Model &$model): Model
    {
        return $this->upsert($model, 'update');
    }
    /**
     * Creates or updates a record of the model in the database as a post type.
     *
     * @see \wp_insert_post()
     * @see \wp_update_post()
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param "insert"|"update" $action
     * @return Model
     */
    protected function upsert(Model &$model, string $action): Model
    {
        $method = 'wp_' . $action . '_post';
        $post_data = $this->get_post_data($model, $action);
        $post_data['post_type'] = static::get_name();
        if ('update' === $action) {
            $post_data['ID'] = $model->get_id();
        } else {
            unset($post_data['ID']);
        }
        /** @var int|WP_Error $result */
        $result = $method($post_data, \true);
        if ($result === 0 || $result instanceof WP_Error) {
            Logger::alert(sprintf('Could not %1$s %2$s: %3$s', $action, $model::MODEL_NAME, $result instanceof WP_Error ? $result->get_error_message() : 'unknown server error.'));
            return $model;
        }
        $model->set_id($result);
        return $model;
    }
    /**
     * Deletes a record of the model from the database as a post type.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model &$model): bool
    {
        if ($model->is_new()) {
            return \false;
        }
        $result = wp_delete_post($model->get_id(), \true);
        $success = null !== $result && \false !== $result;
        if ($success) {
            $model->set_id(0);
        }
        return $success;
    }
    /**
     * Gets the post data from the model.
     *
     * This method should map the main model properties to the post data array.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param "insert"|"update"|null $context
     * @return array<string, mixed>
     */
    abstract protected function get_post_data(Model $model, ?string $context = null): array;
    /**
     * Gets the model properties from the post.
     *
     * This method should map the post properties to the model properties.
     *
     * @param WP_Post $post
     * @return array<string, mixed>
     */
    abstract protected function get_model_data(WP_Post $post): array;
    /**
     * Determines if a record of the model exists in the database as a post type.
     *
     * @since 1.1.0
     *
     * @param Model $model
     * @return bool
     */
    public function exists(Model $model): bool
    {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s LIMIT 1", $model->get_id(), static::get_name()));
    }
    /**
     * Reads the metadata of the model from the database from postmeta.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return void
     */
    public function read_meta_data(Model &$model): void
    {
        $model_meta_data = [];
        if ($model->is_new()) {
            $post_meta_data = [];
        } else {
            $post_meta_data = get_post_meta($model->get_id()) ?: [];
        }
        foreach ($post_meta_data as $key => $value) {
            if (is_array($value)) {
                if (count($value) === 1) {
                    $model_meta_data[$key] = maybe_unserialize(current($value));
                } else {
                    $model_meta_data[$key] = array_map('maybe_unserialize', $value);
                }
            } else {
                $model_meta_data[$key] = maybe_unserialize($value);
            }
        }
        $model->set_meta_data($model_meta_data);
    }
    /**
     * Saves all the metadata of the model to the database as postmeta.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return Model
     */
    public function save_meta_data(Model &$model): Model
    {
        if ($model->is_new()) {
            return $model;
        }
        $meta_data = $model->get_meta_data();
        foreach ($meta_data as $key => $value) {
            if (null === $value) {
                $this->delete_meta($model, $key);
            } else {
                update_post_meta($model->get_id(), $key, $value);
            }
        }
        return $model;
    }
    /**
     * Saves a single metadata of the model to the database as postmeta.
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
        if ($model->is_new()) {
            return $model;
        }
        update_post_meta($model->get_id(), $meta_key, $meta_value);
        return $model;
    }
    /**
     * Deletes all metadata of the model from the database as postmeta.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @return bool
     */
    public function delete_meta_data(Model &$model): bool
    {
        if ($model->is_new()) {
            return \false;
        }
        return delete_meta($model->get_id());
    }
    /**
     * Deletes a single metadata of the model from the database as postmeta.
     *
     * @since 1.0.0
     *
     * @param Model $model
     * @param string $meta
     * @return bool
     */
    public function delete_meta(Model &$model, string $meta): bool
    {
        if ($model->is_new()) {
            return \false;
        }
        return delete_post_meta($model->get_id(), $meta);
    }
}
