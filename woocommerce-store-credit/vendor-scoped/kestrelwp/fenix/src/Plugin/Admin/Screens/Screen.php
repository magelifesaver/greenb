<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Contracts\Menu_Item;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Traits\Is_Menu_Item;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress;
/**
 * Object representation of a WordPress admin screen, such as a menu page or submenu page.
 *
 * @since 1.2.0
 */
abstract class Screen implements Menu_Item
{
    use Is_Handler;
    use Is_Menu_Item;
    /** @var string */
    public const ID = '';
    /** @var bool false if it should remove a thank you note in the footer for this screen */
    protected bool $should_display_footer_text = \false;
    /**
     * Screen constructor.
     *
     * @since 1.2.0
     *
     * @param string $id
     * @param string $title
     */
    protected function __construct(string $id = '', string $title = '')
    {
        $this->id = empty($id) ? static::ID : $id;
        $this->menu_title = $title;
        $this->to_array_excluded_properties += ['callable', 'should_display_footer_text'];
        add_action('admin_footer_text', function ($text) {
            $screen = WordPress\Admin::get_current_screen();
            if (!$this->should_display_footer_text && Strings::string($screen->id)->ends_with($this->id)) {
                $text = '';
            }
            return $text;
        });
    }
    /**
     * Renders the screen.
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function output(): void
    {
        // stub method
    }
}
