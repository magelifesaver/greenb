<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;

defined('ABSPATH') or die;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Framework;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Data_Stores\User as User_Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Media\Avatar;
use WP_User;
/**
 * A model for WordPress users.
 *
 * @since 1.0.0
 *
 * @method int get_id()
 * @method string get_email()
 * @method string get_given_name()
 * @method string get_family_name()
 * @method string get_display_name()
 * @method string get_handle()
 * @method string get_nickname()
 * @method string get_biography()
 * @method string get_url()
 * @method string get_created_at()
 * @method User set_id( int $value )
 * @method User set_email( string $value )
 * @method User set_given_name( string $value )
 * @method User set_family_name( string $value )
 * @method User set_display_name( string $value )
 * @method User set_handle( string $value )
 * @method User set_nickname( string $value )
 * @method User set_biography( string $value )
 * @method User set_url( string $value )
 * @method User set_created_at( string $value )
 */
class User extends Model
{
    /** @var string the internal model name */
    public const MODEL_NAME = 'user';
    /** @var string */
    protected const LOCALE_META_KEY = 'locale';
    /** @var string user email address */
    protected string $email = '';
    /** @var string login handle */
    protected string $handle = '';
    /** @var string */
    protected string $given_name = '';
    /** @var string */
    protected string $family_name = '';
    /** @var string */
    protected string $display_name = '';
    /** @var string */
    protected string $nickname = '';
    /** @var string */
    protected string $biography = '';
    /** @var string */
    protected string $url = '';
    /** @var string */
    protected string $created_at = '';
    /** @var string */
    protected string $locale = '';
    /**
     * Returns an instance of the users data store.
     *
     * @since 1.0.0
     *
     * @return User_Data_Store
     */
    protected static function get_data_store(): Data_Store
    {
        return User_Data_Store::instance();
    }
    /**
     * Returns an instance of the user model with the given identifier.
     *
     * @since 1.0.0
     *
     * @param int|mixed|string|User|WP_User $identifier
     * @return User|null
     */
    public static function find($identifier): ?Model
    {
        $user = null;
        if ($identifier instanceof WP_User) {
            $identifier = $identifier->ID;
        }
        if ($identifier instanceof User) {
            $user = $identifier;
        } elseif (is_numeric($identifier)) {
            $user = static::find_many(['id' => intval($identifier)])->one();
        } elseif (is_string($identifier)) {
            if (is_email($identifier)) {
                $user = static::find_by_email($identifier) ?: static::find_by_handle($identifier);
            } else {
                $user = static::find_by_handle($identifier);
            }
        }
        return $user instanceof User ? $user : null;
    }
    /**
     * Finds a user by their email address.
     *
     * @since 1.0.0
     *
     * @param string $email
     * @return User|null
     */
    public static function find_by_email(string $email): ?User
    {
        return static::find_many(['email' => $email])->one();
    }
    /**
     * Finds a user by their login handle.
     *
     * @since 1.0.0
     *
     * @param string $handle
     * @return User|null
     */
    public static function find_by_handle(string $handle): ?User
    {
        return static::find_many(['handle' => $handle])->one();
    }
    /**
     * Finds many users.
     *
     * @since 1.0.0
     *
     * @phpstan-param array{
     *      email?: string|list<string>,
     *      handle?: string|list<string>,
     *      id?: int|list<int>,
     *      limit?: int,
     *      meta_query?: array<int|string, array{
     *          key: string,
     *          value?: mixed,
     *          compare?: string,
     *      }>,
     *      offset?: int,
     *      page?: positive-int,
     *      search?: string,
     *      search_where?: list<string>,
     *  } $args
     *
     * @param array<string, mixed> $args
     * @return Collection<int, User>
     */
    public static function find_many(array $args = []): Collection
    {
        return static::get_data_store()->query($args);
    }
    /**
     * Gets the current user.
     *
     * @since 1.0.0
     *
     * @return User|null
     */
    public static function current(): ?User
    {
        $user_id = get_current_user_id();
        return $user_id ? static::find($user_id) : null;
    }
    /**
     * Determines if the user from the current instance is logged in.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_logged_in(): bool
    {
        $current = static::current();
        return $current && $current->get_id() === $this->get_id();
    }
    /**
     * Determines if the user is registered.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_registered(): bool
    {
        return !$this->is_new() && static::get_data_store()->exists($this);
    }
    /**
     * Gets the user's full name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_full_name(): string
    {
        $first_name = $this->get_given_name();
        $last_name = $this->get_family_name();
        if (empty($first_name) || empty($last_name)) {
            return $this->get_display_name();
        }
        return $this->format_full_name($first_name, $last_name);
    }
    /**
     * Formats the full name based on the user's locale.
     *
     * @since 1.1.0
     *
     * @param string $given_name
     * @param string $family_name
     * @return string
     */
    protected function format_full_name(string $given_name, string $family_name): string
    {
        $family_name_first_locales = ['zh_CN', 'zh_TW', 'zh_HK', 'ja_JP', 'ko_KR', 'vi_VN', 'hu_HU', 'mn_MN', 'km_KH', 'si_LK', 'ta_LK', 'ta_IN', 'my_MM'];
        if (in_array($this->get_locale(), $family_name_first_locales, \true)) {
            return trim($family_name . ' ' . $given_name);
        }
        return trim($given_name . ' ' . $family_name);
    }
    /**
     * Gets the user's locale.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_locale(): string
    {
        if (empty($this->locale)) {
            $this->locale = get_user_locale(!$this->is_new() ? $this->as_wordpress_user() : 0);
        }
        return $this->locale;
    }
    /**
     * Sets the user's locale.
     *
     * @since 1.1.0
     *
     * @param string $locale
     * @return $this
     */
    public function set_locale(string $locale): User
    {
        $this->locale = $locale;
        return $this->set_meta(static::LOCALE_META_KEY, $locale);
    }
    /**
     * Determines if the user's locale matches the given locale.
     *
     * @since 1.1.4
     *
     * @param string $locale the locale to compare, e.g. 'en_US'
     * @param bool $strict default false (only check the base language group, e.g. `en`), if true it will compare the full locale string (e.g. `en_GB`)
     * @return bool
     */
    public function uses_locale(string $locale, bool $strict = \false): bool
    {
        $user_locale = $this->get_locale();
        $same_locale = \false;
        if ($strict) {
            $same_locale = $user_locale === $locale;
        }
        return $same_locale || substr($user_locale, 0, 2) === substr($locale, 0, 2);
    }
    /**
     * Returns the WordPress user's Avatar instance.
     *
     * @since 1.1.0
     *
     * @param int|string $size size in pixels or a registered image size name
     * @param array<string, mixed> $args
     * @return Avatar
     */
    public function get_avatar($size, array $args = []): ?Avatar
    {
        if ($this->is_new()) {
            return null;
        }
        return Avatar::for($this->get_id())->with_size($size, $args);
    }
    /**
     * Gets the URL to create a new user.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public static function get_create_url(): string
    {
        return admin_url('user-new.php');
    }
    /**
     * Gets the URL to manage users.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public static function get_manage_url(): string
    {
        return admin_url('users.php');
    }
    /**
     * Gets the URL to edit this user.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_edit_url(): string
    {
        if ($this->is_new()) {
            return static::get_create_url();
        }
        return get_edit_user_link($this->get_id());
    }
    /**
     * Returns the current instance as a WordPress User object.
     *
     * @since 1.1.0
     *
     * @return WP_User
     */
    public function as_wordpress_user(): WP_User
    {
        $user = !$this->is_new() ? get_user_by('ID', $this->get_id()) : null;
        if (!$user instanceof WP_User) {
            $user = new WP_User();
        }
        $user->ID = $this->get_id() ?: 0;
        $user->first_name = $this->get_given_name() ?: '';
        $user->last_name = $this->get_family_name() ?: '';
        $user->user_email = $this->get_email() ?: '';
        $user->user_login = $this->get_handle() ?: '';
        $user->display_name = $this->get_display_name() ?: '';
        $user->nickname = $this->get_nickname() ?: '';
        $user->description = $this->get_biography() ?: '';
        $user->user_url = $this->get_url() ?: '';
        return $user;
    }
    /**
     * Converts the user model to an array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        $array = parent::to_array();
        $avatar_urls = rest_get_avatar_urls($this->get_id());
        $avatar_sizes = array_keys($avatar_urls);
        $array['full_name'] = $this->get_full_name();
        $array['avatar'] = [
            /* translators: Placeholder: %s - User display name */
            'alt' => trim(esc_html(sprintf(__('%s avatar', Framework::textdomain()), $this->get_display_name()))),
            'url' => end($avatar_urls) ?: '',
            'min_size' => $avatar_sizes ? min($avatar_sizes) : 0,
            'max_size' => $avatar_sizes ? max($avatar_sizes) : 0,
        ];
        return $array;
    }
}
