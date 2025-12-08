<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks;

defined('ABSPATH') or exit;
use WP_Block;
/**
 * Base block class for dynamic blocks.
 *
 * @since 1.1.0
 */
abstract class Dynamic_Block extends Block
{
    /**
     * Provides the arguments used when registering the block.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    protected function get_arguments(): array
    {
        return array_merge(parent::get_arguments(), ['render_callback' => [static::class, 'render']]);
    }
    /**
     * Renders the block output.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $attributes
     * @param string $content
     * @param WP_Block|null $block
     * @return string
     */
    abstract public static function render(array $attributes = [], string $content = '', ?WP_Block $block = null): string;
}
