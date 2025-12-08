<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes\Metabox;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Admin handler for WordPress metaboxes.
 *
 * @since 1.2.0
 */
final class Metaboxes
{
    use Is_Handler;
    /** @var array<numeric-string, array<string, Metabox>> */
    private static array $metaboxes = [];
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
        self::add_action('admin_init', [$this, 'register_metaboxes'], \PHP_INT_MAX);
    }
    /**
     * Registers metaboxes in WordPress admin screens.
     *
     * @since 1.2.0
     *
     * @return void
     */
    private function register_metaboxes(): void
    {
        foreach (self::$metaboxes as $priority => $metabox_group) {
            add_action('add_meta_boxes', function ($screen_id = null, $object = null) use ($metabox_group) {
                foreach ($metabox_group as $metabox) {
                    if ($metabox->is_registered() || !$metabox->should_register($screen_id, $object)) {
                        continue;
                    }
                    foreach ($metabox->get_screens() as $screen) {
                        add_meta_box($metabox->get_id(), $metabox->get_title(), $metabox->get_callback(), $screen, $metabox->get_screen_context($screen), $metabox->get_screen_priority($screen), $metabox->get_callback_args($screen));
                        add_filter("postbox_classes_{$screen}_{$metabox->get_id()}", fn($classes = []) => array_unique(array_merge((array) $classes, $metabox->get_classes($screen))));
                    }
                    $metabox->is_registered(\true);
                }
            }, intval($priority ?: 10));
        }
    }
    /**
     * Adds a metabox to register.
     *
     * @since 1.2.0
     *
     * @param Metabox $metabox
     * @param int|null $priority
     * @return void
     */
    public static function register(Metabox $metabox, ?int $priority): void
    {
        $priority = strval($priority ?: 10);
        if (!isset(self::$metaboxes[$priority])) {
            self::$metaboxes[$priority] = [];
        }
        self::$metaboxes[$priority][$metabox->get_id()] = $metabox;
    }
    /**
     * Removes a metabox from the plugin admin.
     *
     * @since 1.2.0
     *
     * @param Metabox $metabox
     * @param string[]|null $screens
     * @return void
     */
    public static function deregister(Metabox $metabox, ?array $screens = null): void
    {
        if ($metabox->is_registered()) {
            if (null === $screens) {
                $screens = $metabox->get_screens();
            }
            foreach ($screens as $screen) {
                remove_meta_box($metabox->get_id(), $screen, $metabox->get_screen_context($screen));
            }
        }
        foreach (self::$metaboxes as $priority => $metabox_group) {
            if (isset($metabox_group[$metabox->get_id()])) {
                unset(self::$metaboxes[$priority][$metabox->get_id()]);
            }
        }
        $metabox->is_registered(\false);
    }
}
