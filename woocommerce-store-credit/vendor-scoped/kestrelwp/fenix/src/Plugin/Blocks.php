<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks\Block;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks\Dynamic_Block;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use WP_Block_Editor_Context;
/**
 * Blocks handler for the WordPress block editor.
 *
 * @since 1.1.0
 */
abstract class Blocks
{
    use Is_Handler;
    /** @var class-string<Block|Dynamic_Block>[] blocks to be registered */
    protected array $blocks = [];
    /**
     * Blocks handler constructor.
     *
     * @since 1.1.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        static::add_action('init', [$this, 'register_blocks']);
        static::add_action('enqueue_block_assets', [$this, 'register_block_assets']);
        static::add_action('enqueue_block_editor_assets', [$this, 'register_block_editor_assets']);
        static::add_filter('block_categories_all', [$this, 'register_block_categories'], 9, 2);
    }
    /**
     * Registers the blocks.
     *
     * @since 1.1.0
     *
     * @return void
     */
    protected function register_blocks(): void
    {
        foreach ($this->blocks as $block) {
            // @phpstan-ignore-next-line sanity check
            if (!is_string($block)) {
                _doing_it_wrong(__METHOD__, 'Invalid block. A block must be a valid class that extends ' . Block::class . '.', '');
                continue;
            }
            // @phpstan-ignore-next-line sanity check
            if (!is_a($block, Block::class, \true)) {
                _doing_it_wrong(__METHOD__, esc_html(sprintf('Cannot load block. %1$s must be a valid class that extends %2$s.', $block, Block::class)), '');
                continue;
            }
            $block::instance()->register();
        }
    }
    /**
     * Registers block assets shared by multiple blocks handled by the same plugin.
     *
     * These assets are meant for the front end where the block is displayed, not the block editor.
     *
     * @since 1.1.0
     *
     * @return void
     */
    protected function register_block_assets(): void
    {
        // stub method
    }
    /**
     * Registers block assets shared by multiple blocks handled by the same plugin.
     *
     * These assets are meant for the block editor.
     *
     * @since 1.1.0
     *
     * @return void
     */
    protected function register_block_editor_assets(): void
    {
        // stub method
    }
    /**
     * Registers block categories.
     *
     * @since 1.1.0
     *
     * @param array<int, array<string, string>>|mixed $categories
     * @param mixed|WP_Block_Editor_Context|null $context
     * @return array<int, array<string, string>>|mixed
     */
    protected function register_block_categories($categories, $context = null)
    {
        // phpcs:ignore
        // stub method
        return $categories;
    }
}
