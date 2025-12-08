<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Contracts\Menu_Item;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Menu;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Admin handler for WordPress screens.
 *
 * @since 1.2.0
 */
final class Screens
{
    use Is_Handler;
    /** @var array<string, Menu_Item> */
    private static array $menu_items = [];
    /**
     * Constructor.
     *
     * @since 1.2.0
     *
     * @param WordPress_Plugin $plugin
     */
    public function __construct(WordPress_Plugin $plugin)
    {
        self::$plugin = $plugin;
        self::add_action('admin_menu', [$this, 'register_screens']);
    }
    /**
     * Registers WordPress admin screens.
     *
     * @since 1.2.0
     *
     * @return void
     */
    private function register_screens(): void
    {
        foreach (self::$menu_items as $item) {
            if ($item->get_parent_id()) {
                $this->register_submenu_screen($item);
            } else {
                $this->register_main_screen($item);
                if ($item instanceof Menu) {
                    foreach ($item->get_submenu_items() as $submenu_item) {
                        if ($submenu_item->is_registered()) {
                            continue;
                        }
                        $this->register_submenu_screen($submenu_item);
                    }
                }
            }
        }
    }
    /**
     * Registers a main screen.
     *
     * @since 1.2.0
     *
     * @param Menu_Item $item
     * @return void
     */
    private function register_main_screen(Menu_Item $item): void
    {
        add_menu_page($item->get_page_title(), $item->get_menu_title(), $item->get_capability(), $item->get_id(), $item->get_callback(), $item->get_menu_icon(), $item->get_position());
        $item->is_registered(\true);
    }
    /**
     * Registers a submenu screen.
     *
     * @since 1.2.0
     *
     * @param Menu_Item $item
     * @return void
     */
    private function register_submenu_screen(Menu_Item $item): void
    {
        add_submenu_page($item->get_parent_id(), $item->get_page_title(), $item->get_menu_title(), $item->get_capability(), $item->get_id(), $item->get_callback(), $item->get_position());
        $item->is_registered(\true);
    }
    /**
     * Builds an internal key identifier for a screen.
     *
     * In WordPress the first submenu item may share the ID with the parent as the slug and so it's not reliable as a key.
     *
     * @since 1.2.0
     *
     * @param Menu_Item $item
     * @return string
     */
    private static function key(Menu_Item $item): string
    {
        return get_class($item) . '-' . $item->get_id();
    }
    /**
     * Finds a screen by its ID.
     *
     * @since 1.2.0
     *
     * @param string $item_id
     * @param bool $is_parent whether the returned item should be a parent
     * @return Menu_Item|null
     */
    public static function find(string $item_id, bool $is_parent = \false): ?Menu_Item
    {
        foreach (self::$menu_items as $item) {
            if ($is_parent && $item->get_parent_id()) {
                continue;
            }
            if ($item_id === $item->get_id()) {
                return $item;
            }
        }
        return null;
    }
    /**
     * Adds a screen to the plugin admin.
     *
     * @since 1.2.0
     *
     * @param Menu_Item $item
     * @return void
     */
    public static function register(Menu_Item $item): void
    {
        self::$menu_items[self::key($item)] = $item;
    }
    /**
     * Removes a screen from the plugin admin.
     *
     * @since 1.2.0
     *
     * @param Menu_Item $item
     * @return void
     */
    public static function deregister(Menu_Item $item): void
    {
        if ($item->is_registered()) {
            if ($parent = $item->get_parent()) {
                remove_submenu_page($parent->get_id(), $item->get_id());
            } else {
                remove_menu_page($item->get_id());
            }
        }
        unset(self::$menu_items[self::key($item)]);
        $item->is_registered(\false);
    }
}
