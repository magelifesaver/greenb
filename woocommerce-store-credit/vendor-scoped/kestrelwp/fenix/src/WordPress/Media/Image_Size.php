<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress\Media;

defined('ABSPATH') or die;
/**
 * Object representation of a WordPress image size.
 *
 * @since 1.1.0
 */
class Image_Size
{
    /** @var array<string, array<string, bool|int>>|null */
    private static ?array $image_sizes = null;
    /** @var string */
    private string $name;
    /**
     * Constructor.
     *
     * @param string $size_name
     */
    private function __construct(string $size_name)
    {
        $this->name = $size_name;
    }
    /**
     * Returns the image size name.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    /**
     * Returns the image size width.
     *
     * @since 1.1.0
     *
     * @return int
     */
    public function height(): int
    {
        $sizes = self::get_image_sizes();
        return $sizes[$this->name]['height'] ?: 0;
    }
    /**
     * Returns the image size width.
     *
     * @since 1.1.0
     *
     * @return int
     */
    public function width(): int
    {
        $sizes = self::get_image_sizes();
        return $sizes[$this->name]['width'] ?: 0;
    }
    /**
     * Returns whether the image size is cropped.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function cropped(): bool
    {
        $sizes = self::get_image_sizes();
        return (bool) ($sizes[$this->name]['crop'] ?: \false);
    }
    /**
     * Gets the registered image sizes.
     *
     * @since 1.1.0
     *
     * @phpstan-return array{
     *     string: array{
     *          width: int,
     *          height: int,
     *          crop: bool
     *    }
     * }
     *
     * @return array<string, array<string, bool|int>> indexed by image size name
     */
    public static function get_image_sizes(): array
    {
        global $_wp_additional_image_sizes;
        if (is_array(self::$image_sizes)) {
            return self::$image_sizes;
        }
        $parsed_image_sizes = $image_sizes = [];
        $default_image_sizes = get_intermediate_image_sizes();
        foreach ($default_image_sizes as $size) {
            $parsed_image_sizes[$size]['width'] = intval(get_option("{$size}_size_w"));
            $parsed_image_sizes[$size]['height'] = intval(get_option("{$size}_size_h"));
            $parsed_image_sizes[$size]['crop'] = in_array(get_option("{$size}_crop"), [1, '1', 'true', \true, 'yes'], \true);
        }
        if (!empty($_wp_additional_image_sizes)) {
            $parsed_image_sizes = array_merge($parsed_image_sizes, $_wp_additional_image_sizes);
        }
        foreach ($parsed_image_sizes as $size => $data) {
            if (!isset($data['width'], $data['height'], $data['crop']) || !is_numeric($data['width']) || !is_numeric($data['height'])) {
                continue;
            }
            $image_sizes[$size] = ['width' => (int) $data['width'], 'height' => (int) $data['height'], 'crop' => in_array($data['crop'], [1, '1', 'true', \true, 'yes'], \true)];
        }
        self::$image_sizes = $image_sizes;
        return $image_sizes;
    }
    /**
     * Returns an {@see Image_Size} instance for the given image size in pixels.
     *
     * @since 1.1.0
     *
     * @param int $size
     * @param string $dimension
     * @return self
     */
    public static function match_pixels(int $size, string $dimension = 'width'): self
    {
        $size_name = '';
        $min_diff = \PHP_INT_MAX;
        $height_or_width = in_array($dimension, ['height', 'width'], \true) ? $dimension : 'width';
        foreach (self::get_image_sizes() as $size_key => $size_data) {
            $dimension = $size_data[$height_or_width] ?: null;
            if (!is_numeric($dimension)) {
                continue;
            }
            if ($size === (int) $dimension) {
                return new self($size_key);
            }
            $diff = abs($size - (int) $dimension);
            if ($diff < $min_diff) {
                $min_diff = $diff;
                $size_name = $size_key;
            }
        }
        return new self($size_name);
    }
    /**
     * Converts the instance to an array.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return ['name' => $this->name(), 'width' => $this->width(), 'height' => $this->height(), 'crop' => $this->cropped()];
    }
}
