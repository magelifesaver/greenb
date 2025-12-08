<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Contracts;

defined('ABSPATH') or exit;
/**
 * Interface for a WordPress admin menu page like a top-level menu or a submenu.
 *
 * @since 1.2.0
 */
interface Menu_Item
{
    /**
     * Returns the item ID.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_id(): string;
    /**
     * Returns the parent item ID, if any.
     *
     * @since 1.2.0
     *
     * @return string|null
     */
    public function get_parent_id(): ?string;
    /**
     * Sets the parent item ID.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @return $this
     */
    public function set_parent_id(string $id): Menu_Item;
    /**
     * Returns the parent item, if any.
     *
     * @since 1.2.0
     *
     * @return Menu_Item|null
     */
    public function get_parent(): ?Menu_Item;
    /**
     * Returns the capability required to access the screen.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_capability(): string;
    /**
     * Sets the capability required to access the screen.
     *
     * @since 1.2.0
     *
     * @param string $capability
     * @return $this
     */
    public function set_capability(string $capability): Menu_Item;
    /**
     * Returns the page title.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_page_title(): string;
    /**
     * Sets the page title.
     *
     * @since 1.2.0
     *
     * @param string $title
     * @return $this
     */
    public function set_page_title(string $title): Menu_Item;
    /**
     * Returns the menu title.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_menu_title(): string;
    /**
     * Sets the menu title.
     *
     * @since 1.2.0
     *
     * @param string $title
     * @return $this
     */
    public function set_menu_title(string $title): Menu_Item;
    /**
     * Returns the menu icon.
     *
     * @since 1.2.0
     *
     * @return string
     */
    public function get_menu_icon(): string;
    /**
     * Sets the menu icon.
     *
     * @since 1.2.0
     *
     * @param string $icon
     * @return $this
     */
    public function set_menu_icon(string $icon): Menu_Item;
    /**
     * Returns the position in the menu.
     *
     * @since 1.2.0
     *
     * @return float|int|null
     */
    public function get_position();
    /**
     * Sets the position in the menu.
     *
     * @since 1.2.0
     *
     * @param float|int $position
     * @return $this
     */
    public function set_position($position): Menu_Item;
    /**
     * Returns the callback for the screen.
     *
     * @since 1.2.0
     *
     * @return callable|callable-string|null
     */
    public function get_callback();
    /**
     * Sets the callback for the screen.
     *
     * @since 1.2.0
     *
     * @param callable|callable-string|null $callback
     * @return $this
     */
    public function set_callback($callback): Menu_Item;
    /**
     * Returns whether the screen is registered.
     *
     * @since 1.2.0
     *
     * @param bool|null $is_registered sets the registered status
     * @return bool
     */
    public function is_registered(?bool $is_registered = null): bool;
    /**
     * Registers the screen with WordPress.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @param string $title
     * @return Menu_Item
     */
    public static function register(string $id, string $title = ''): Menu_Item;
    /**
     * Unregisters the screen with WordPress.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public static function deregister();
}
