<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Traits;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Contracts\Menu_Item;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Screen;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Arrayable;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
/**
 * Trait for objects that represent a WordPress admin menu item like top-level admin pages and submenu pages.
 *
 * @since 1.2.0
 *
 * @mixin Menu_Item
 * @mixin Screen
 *
 * @method void output()
 */
trait Is_Menu_Item
{
    use Is_Arrayable;
    /** @var string screen ID */
    protected string $id = '';
    /** @var string|null ID of the parent screen, if any */
    protected ?string $parent_id = null;
    /** @var string icon URL for top-level menu items */
    protected string $menu_icon = '';
    /** @var string menu title */
    protected string $menu_title = '';
    /** @var string page title when output */
    protected string $page_title = '';
    /** @var float|int|null the position in the menu order */
    protected $position = null;
    /** @var string|null capability required to access the screen */
    protected ?string $capability = null;
    /** @var callable|callable-string|null callback to render the screen */
    protected $callback = null;
    /** @var bool whether the screen in context has been registered */
    protected bool $is_registered = \false;
    /**
     * Returns the item ID.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_id(): string
    {
        return $this->id;
    }
    /**
     * Returns the parent item ID, if any.
     *
     * @since 1.2.0
     *
     * @return string|null
     */
    public function get_parent_id(): ?string
    {
        return $this->parent_id;
    }
    /**
     * Sets the parent item ID.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @return $this
     */
    public function set_parent_id(string $id): Menu_Item
    {
        $this->parent_id = $id;
        return $this;
    }
    /**
     * Returns the parent item, if any.
     *
     * @since 1.2.0
     *
     * @return Menu_Item|null
     */
    public function get_parent(): ?Menu_Item
    {
        return $this->parent_id ? Screens::find($this->parent_id, \true) : null;
    }
    /**
     * Returns the capability required to access the item.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_capability(): string
    {
        $capability = $this->capability;
        if (null === $capability) {
            if ($parent = $this->get_parent()) {
                $capability = $parent->get_capability();
            }
            if (null === $capability) {
                return self::plugin() instanceof WooCommerce_Extension ? 'manage_woocommerce' : 'manage_options';
            }
        }
        return $capability;
    }
    /**
     * Sets the capability required to access the item.
     *
     * @since 1.2.0
     *
     * @param string $capability
     * @return $this
     */
    public function set_capability(string $capability): Menu_Item
    {
        $this->capability = $capability;
        return $this;
    }
    /**
     * Returns the page title.
     *
     * When not set, it will default to the menu item title, unless this method is overridden.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_page_title(): string
    {
        return empty($this->page_title) ? $this->get_menu_title() : $this->page_title;
    }
    /**
     * Sets the page title.
     *
     * @since 1.2.0
     *
     * @param string $title
     * @return $this
     */
    public function set_page_title(string $title): Menu_Item
    {
        $this->page_title = $title;
        return $this;
    }
    /**
     * Returns the menu title.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_menu_title(): string
    {
        return $this->menu_title;
    }
    /**
     * Sets the menu title.
     *
     * @since 1.2.0
     *
     * @param string $title
     * @return $this
     */
    public function set_menu_title(string $title): Menu_Item
    {
        $this->menu_title = $title;
        return $this;
    }
    /**
     * Returns the callback to render the screen.
     *
     * @since 1.2.0
     *
     * @return callable|callable-string|null
     */
    public function get_callback()
    {
        if (null === $this->callback) {
            return fn() => $this->output();
            // default
        } elseif (is_callable($this->callback)) {
            return $this->callback;
            // override
        }
        // @phpstan-ignore-next-line
        return '__return_null';
    }
    /**
     * Sets the callback to render the screen.
     *
     * @since 1.2.0
     *
     * @param callable|callable-string|null $callback
     * @return $this
     */
    public function set_callback($callback): Menu_Item
    {
        $this->callback = $callback;
        return $this;
    }
    /**
     * Returns the icon URL for top-level menu items.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_menu_icon(): string
    {
        return $this->menu_icon;
    }
    /**
     * Sets the icon URL for top-level menu items.
     *
     * @since 1.2.0
     *
     * @param string $icon
     * @return $this
     */
    public function set_menu_icon(string $icon): Menu_Item
    {
        $this->menu_icon = $icon;
        return $this;
    }
    /**
     * Returns the position in the menu order.
     *
     * @since 1.2.0
     *
     * @return float|int|null
     */
    public function get_position()
    {
        return $this->position;
    }
    /**
     * Sets the position in the menu order.
     *
     * @since 1.2.0
     *
     * @param float|int $position
     * @return $this
     */
    public function set_position($position): Menu_Item
    {
        $this->position = $position;
        return $this;
    }
    /**
     * Determines if the item has been registered.
     *
     * @since 1.2.0
     *
     * @param bool|null $set_registered pass this flag to set the registered status
     * @return bool
     */
    public function is_registered(?bool $set_registered = null): bool
    {
        if (null !== $set_registered) {
            $this->is_registered = $set_registered;
        }
        return $this->is_registered;
    }
    /**
     * Registers the menu item.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @param string $title
     * @return $this
     */
    public static function register(string $id, string $title = ''): Menu_Item
    {
        /** @var Menu_Item $instance */
        $instance = static::instance($id, $title);
        Screens::register($instance);
        // @phpstan-ignore-next-line
        return $instance;
    }
    /**
     * Unregisters the menu item.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public static function deregister(): void
    {
        if (!static::is_loaded()) {
            return;
        }
        /** @var Menu_Item $instance */
        $instance = static::instance();
        Screens::deregister($instance);
    }
}
