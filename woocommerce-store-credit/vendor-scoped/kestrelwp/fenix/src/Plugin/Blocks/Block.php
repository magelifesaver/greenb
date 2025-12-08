<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks;

defined('ABSPATH') or exit;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Hidden_Callbacks;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use WP_Block;
use WP_Block_List;
/**
 * Base block class for static blocks.
 *
 * @since 1.1.0
 */
abstract class Block
{
    use Has_Hidden_Callbacks;
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string the block name */
    protected const NAME = '';
    /**
     * Returns the block name.
     *
     * @since 1.1.0
     *
     * @return string e.g. 'plugin-name/block-name'
     */
    public static function get_name(): string
    {
        $prefix = static::plugin()->handle();
        $name = Strings::string(static::NAME)->kebab_case()->to_string();
        return "{$prefix}/{$name}";
    }
    /**
     * Gets the inner blocks from a block.
     *
     * @since 1.1.0
     *
     * @param WP_Block|null $block
     * @return WP_Block_List|null
     */
    protected static function get_inner_blocks(?WP_Block $block): ?WP_Block_List
    {
        // @phpstan-ignore-next-line sanity checks on existence of inner blocks from external code
        return $block && $block->inner_blocks instanceof WP_Block_List ? $block->inner_blocks : null;
    }
    /**
     * Determines if a block has inner blocks.
     *
     * @since 1.1.0
     *
     * @param WP_Block|null $block
     * @return bool
     */
    protected static function has_inner_blocks(?WP_Block $block): bool
    {
        return null !== static::get_inner_blocks($block);
    }
    /**
     * Returns the full path to the block assets.
     *
     * @since 1.1.0
     *
     * @param string|null $path optional path to append
     * @return string
     */
    protected function get_path(?string $path = null): string
    {
        return static::plugin()->assets_path('blocks/' . Strings::string(static::NAME)->kebab_case()->append($path ? '/' . $path : '')->to_string());
    }
    /**
     * Returns arguments used when registering the block.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    protected function get_arguments(): array
    {
        return ['name' => static::get_name()];
    }
    /**
     * Registers the block.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->should_register()) {
            return;
        }
        register_block_type($this->get_path(), $this->get_arguments());
    }
    /**
     * Determines if the block should be registered.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    protected function should_register(): bool
    {
        return is_readable($this->get_path('block.json'));
    }
    /**
     * De-registers the block.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function deregister(): void
    {
        unregister_block_type(static::get_name());
    }
    /**
     * Helper method to flatten classes and styles to be applied to the block wrapper element.
     *
     * @since 1.1.0
     *
     * @param array<"class"|"style"|"value", mixed|string> ...$additional_attributes
     * @return array<"class"|"style", string>
     */
    protected static function flatten_classes_and_styles(array ...$additional_attributes): array
    {
        $merged_attributes = ['class' => '', 'style' => ''];
        $classes_to_be_merged = $styles_to_be_merged = [];
        foreach ($additional_attributes as $attributes) {
            if (isset($attributes['class'])) {
                $classes_to_be_merged = array_merge($classes_to_be_merged, explode(' ', $attributes['class']));
            }
            if (isset($attributes['style'])) {
                $styles_to_be_merged = array_merge($styles_to_be_merged, explode(' ', $attributes['style']));
            }
        }
        $merged_attributes['class'] = trim(Arrays::array((array) $merged_attributes['class'])->merge($classes_to_be_merged)->discard_duplicates()->join(' ')->to_string());
        $merged_attributes['style'] = trim(Arrays::array((array) $merged_attributes['style'])->merge($styles_to_be_merged)->discard_duplicates()->join(' ')->to_string());
        return $merged_attributes;
    }
    /**
     * Returns the class and style for alignment properties for the block.
     *
     * @see StyleAttributesUtils::get_align_class_and_style() for a similar handling in WooCommerce Blocks
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $attributes
     * @return array<"class"|"style", string|null>
     */
    protected static function get_alignment_class_and_style(array $attributes): array
    {
        /** @var string|null $align_attribute */
        $align_attribute = $attributes['align'] ?? null;
        if ('wide' === $align_attribute) {
            return ['class' => 'alignwide', 'style' => null];
        }
        if ('full' === $align_attribute) {
            return ['class' => 'alignfull', 'style' => null];
        }
        if ('left' === $align_attribute) {
            return ['class' => 'alignleft', 'style' => null];
        }
        if ('right' === $align_attribute) {
            return ['class' => 'alignright', 'style' => null];
        }
        if ('center' === $align_attribute) {
            return ['class' => 'aligncenter', 'style' => null];
        }
        return ['class' => '', 'style' => ''];
    }
}
