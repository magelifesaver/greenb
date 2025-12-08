<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\Data_Stores;

defined('ABSPATH') or die;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\User as User_Model;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use WP_Error;
use WP_User;
use WP_User_Query;
/**
 * A data store for WordPress users.
 *
 * @since 1.0.0
 */
class User implements Data_Store
{
    use Is_Singleton;
    /** @var string internal storage name */
    protected const NAME = 'user';
    /**
     * Gets the name of the user data store.
     *
     * @return string
     */
    public static function get_name(): string
    {
        return static::NAME;
    }
    /**
     * Creates a record of the model in the database as a user.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return Model
     */
    public function create(Model &$model): Model
    {
        return $this->upsert($model, 'insert');
    }
    /**
     * Reads a record of the model from the database as a user.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return void
     */
    public function read(Model &$model): void
    {
        if ($model->is_new()) {
            return;
        }
        $user = get_user_by('ID', $model->get_id());
        if (!$user instanceof WP_User) {
            return;
        }
        foreach ($this->get_model_data($user) as $property => $value) {
            $model->{'set_' . $property}($value);
        }
    }
    /**
     * Updates a record of the model in the database as a user.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return Model
     */
    public function update(Model &$model): Model
    {
        return $this->upsert($model, 'update');
    }
    /**
     * Inserts or updates a user in the database.
     *
     * @see \wp_insert_user()
     * @see \wp_update_user()
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @param "insert"|"update" $action
     * @return Model
     */
    private function upsert(User_Model &$model, string $action): Model
    {
        $method = 'wp_' . $action . '_user';
        $user_data = $this->get_user_data($model, $action);
        if ('update' === $action) {
            $user_data['ID'] = $model->get_id();
        } else {
            unset($user_data['ID']);
        }
        /** @var int|WP_Error $result */
        $result = $method($user_data);
        if ($result === 0 || $result instanceof WP_Error) {
            Logger::alert(sprintf('Could not %1$s %2$s: %3$s', $action, $model::MODEL_NAME, $result instanceof WP_Error ? $result->get_error_message() : 'unknown server error.'));
            return $model;
        }
        $model->set_id($result);
        return $model;
    }
    /**
     * Deletes a record of the model from the database as a user.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return bool
     */
    public function delete(Model &$model): bool
    {
        if ($model->is_new()) {
            return \false;
        }
        $success = wp_delete_user($model->get_id());
        if ($success) {
            $model->set_id(0);
        }
        return $success;
    }
    /**
     * Gets the model data from a WordPress user.
     *
     * @since 1.0.0
     *
     * @param WP_User $user
     * @return array<string, mixed>
     */
    public function get_model_data(WP_User $user): array
    {
        return ['id' => $user->ID, 'email' => $user->user_email, 'handle' => $user->user_login, 'given_name' => $user->first_name, 'family_name' => $user->last_name, 'nickname' => $user->nickname, 'display_name' => $user->display_name, 'biography' => $user->description, 'url' => $user->user_url, 'locale' => $user->locale, 'created_at' => gmdate('c', strtotime($user->user_registered ?: 'now'))];
    }
    /**
     * Gets the WordPress user data from a model.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @param "insert"|"update" $action
     * @return array<string, mixed>
     */
    public function get_user_data(Model $model, string $action): array
    {
        $data = ['display_name' => $model->get_display_name(), 'first_name' => $model->get_given_name(), 'last_name' => $model->get_family_name(), 'nickname' => $model->get_nickname(), 'user_email' => $model->get_email(), 'user_login' => $model->get_handle(), 'description' => $model->get_biography(), 'user_url' => $model->get_url(), 'locale' => $model->get_locale(), 'user_registered' => $model->get_created_at()];
        if ('update' === $action) {
            $data['ID'] = $model->get_id();
        }
        return $data;
    }
    /**
     * Checks if a user exists in the database.
     *
     * @since 1.1.0
     *
     * @param User_Model $model
     * @return bool
     */
    public function exists(Model $model): bool
    {
        return username_exists($model->get_handle()) || email_exists($model->get_email());
    }
    /**
     * Queries the database for users.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     * @return Collection<int, User_Model>
     */
    public function query(array $args = []): Collection
    {
        $args['fields'] = 'all';
        $query_args = $this->parse_query_args($args);
        $wp_users = new WP_User_Query($this->parse_query_args($query_args));
        $users = [];
        foreach ($wp_users->get_results() as $wp_user) {
            $users[$wp_user->ID] = User_Model::seed($this->get_model_data($wp_user));
        }
        $total_items = intval($wp_users->get_total());
        $items_per_page = intval($wp_users->get('number') ?: $total_items);
        $current_page = intval($wp_users->get('paged') ?: 1);
        $total_pages = intval($items_per_page ? ceil($total_items / $items_per_page) : 0);
        return Collection::create($users)->set_pageable(\true)->set_total_items($total_items)->set_items_per_page($items_per_page)->set_current_page($current_page)->set_total_pages($total_pages);
    }
    /**
     * Parses the query arguments for the user.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function parse_query_args(array $args): array
    {
        $query_args = wp_parse_args($args, ['count_total' => \true]);
        if (isset($args['id']) && is_numeric($args['id'])) {
            $query_args['include'] = array_map('intval', (array) $args['id']);
            $query_args['number'] = count($query_args['include']);
            unset($query_args['id']);
        } elseif (isset($args['email'])) {
            $query_args['search'] = is_array($args['email']) ? implode(',', array_map('sanitize_email', $args['email'])) : sanitize_email((string) $args['email']);
            $query_args['search_columns'] = ['user_email'];
            $query_args['number'] = count((array) $args['email']);
            unset($query_args['email']);
        } elseif (isset($args['handle'])) {
            $query_args['login_in'] = (array) $args['handle'];
            $query_args['number'] = count($query_args['login_in']);
            unset($query_args['handle']);
        } elseif (isset($args['search'])) {
            $query_args['search'] = sanitize_text_field($args['search']);
            if (isset($args['search_where'])) {
                $query_args['search_columns'] = (array) $args['search_where'];
                unset($query_args['search_where']);
            }
        }
        if (isset($args['id']) && is_array($args['id'])) {
            $query_args['include'] = array_map('intval', $args['id']);
            $query_args['number'] = count($query_args['include']);
            unset($query_args['id']);
        }
        if (isset($args['limit'])) {
            $query_args['number'] = intval($args['limit']);
            unset($query_args['limit']);
        }
        if (isset($args['page'])) {
            $query_args['paged'] = intval($args['page']);
            unset($query_args['page']);
        }
        return $query_args;
    }
    /**
     * Reads the user's metadata from the database.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return void
     */
    public function read_meta_data(Model &$model): void
    {
        $model_meta_data = [];
        if ($model->is_new()) {
            $user_meta_data = [];
        } else {
            $user_meta_data = get_user_meta($model->get_id()) ?: [];
        }
        foreach ($user_meta_data as $key => $value) {
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
     * Saves all the metadata of the model to the database as user meta.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
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
                // @phpstan-ignore-next-line
                $this->delete_meta($model, $key);
            } else {
                update_user_meta($model->get_id(), $key, $value);
            }
        }
        return $model;
    }
    /**
     * Saves a single metadata of the user model to the database.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @param string $meta_key
     * @param scalar $meta_value
     * @return Model
     */
    public function save_meta(Model &$model, string $meta_key, $meta_value): Model
    {
        if ($model->is_new()) {
            return $model;
        }
        update_user_meta($model->get_id(), $meta_key, $meta_value);
        return $model;
    }
    /**
     * Deletes all the user's metadata from the database.
     *
     * @since 1.0.0
     *
     * @param User_Model $model
     * @return bool
     */
    public function delete_meta_data(Model &$model): bool
    {
        global $wpdb;
        if ($model->is_new()) {
            return \false;
        }
        return (bool) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE user_id = %d", $model->get_id()));
    }
    /**
     * Deletes a single metadata of the user model from the database.
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
        return delete_user_meta($model->get_id(), $meta);
    }
}
