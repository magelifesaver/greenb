<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Contracts\Menu_Item;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Traits\Is_Menu_Item;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
defined('ABSPATH') or exit;
/**
 * A top-level menu page that can be used when creating a container for {@see Screen} items.
 *
 * @since 1.2.0
 */
final class Menu implements Menu_Item
{
    use Is_Handler;
    use Is_Menu_Item;
    /** @var array<string, Screen> */
    protected array $submenu_items = [];
    /**
     * Menu constructor.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @param string $title
     */
    protected function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->menu_title = $title;
        $this->callback = '__return_null';
        // by default top-level menu items do not have a callback, should use a submenu item instead
        $this->to_array_excluded_properties += ['callable', 'plugin'];
    }
    /**
     * Adds a sub menu screen item to the menu.
     *
     * @since 1.2.0
     *
     * @param class-string<Screen>|Screen $screen
     * @return $this
     */
    public function add_submenu_item($screen): self
    {
        // @phpstan-ignore-next-line sanity check
        if (!is_a($screen, Screen::class, \true)) {
            _doing_it_wrong(__METHOD__, 'Screen to add as a submenu item must be class that extends ' . Screen::class . '.', '');
            return $this;
        }
        if (is_string($screen)) {
            $screen = $screen::instance();
        }
        $screen->set_parent_id($this->get_id());
        $this->submenu_items[$screen->get_id()] = $screen;
        return $this;
    }
    /**
     * Removes a sub menu screen item from the menu.
     *
     * @since 1.2.0
     *
     * @param mixed|Screen|string $item
     * @return $this
     */
    public function remove_submenu_item($item): self
    {
        if ($item instanceof Screen) {
            $item = $item->get_id();
        } elseif (!is_string($item)) {
            return $this;
        }
        unset($this->submenu_items[$item]);
        return $this;
    }
    /**
     * Returns a sub menu screen item by its ID.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @return Screen|null
     */
    public function get_submenu_item(string $id): ?Screen
    {
        return $this->submenu_items[$id] ?? null;
    }
    /**
     * Returns all sub menu screen items.
     *
     * @since 1.2.0
     *
     * @return Screen[]
     */
    public function get_submenu_items(): array
    {
        return $this->submenu_items;
    }
    /**
     * Sets all sub menu screen items.
     *
     * @since 1.2.0
     *
     * @param class-string<Screen>[]|Screen[] $screens
     * @return self
     */
    public function set_submenu_items(array $screens): self
    {
        foreach ($screens as $screen) {
            $this->add_submenu_item($screen);
        }
        return $this;
    }
}
