<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress\Media;

defined('ABSPATH') or die;
/**
 * Object representation of a WordPress avatar.
 *
 * @since 1.1.0
 */
final class Avatar
{
    /** @var int */
    private int $user_id;
    /** @var array<string, mixed> */
    private array $data;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param positive-int $user_id
     * @param array<string, mixed> $data
     */
    private function __construct(int $user_id, array $data)
    {
        $this->user_id = $user_id;
        $this->data = wp_parse_args($data, ['size' => 0, 'height' => 0, 'width' => 0, 'default' => 'blank', 'rating' => 'G', 'scheme' => 'https', 'processed_args' => null, 'extra_attr' => '']);
    }
    /**
     * Gets the user ID.
     *
     * @since 1.1.0
     *
     * @return int
     */
    public function get_user_id(): int
    {
        return $this->user_id;
    }
    /**
     * Gets the size of the avatar in pixels.
     *
     * @since 1.1.0
     *
     * @return int
     */
    public function get_size(): int
    {
        $size = $this->data['size'];
        return max(0, is_numeric($size) ? (int) $size : 0);
    }
    /**
     * Gets the URL of the avatar picture.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_url(): string
    {
        if (empty($this->data['url'])) {
            $this->data['url'] = get_avatar_url($this->user_id, $this->data) ?: '';
        }
        return $this->data['url'];
    }
    /**
     * Returns an {@see Avatar} instance for the given user in the given size.
     *
     * @since 1.1.0
     *
     * @param int|string $size size in pixels or a registered image size name (defaults to 96 pixel, WordPress default)
     * @param array<string, mixed> $args optional arguments
     * @return self
     */
    public function with_size($size = 96, array $args = []): self
    {
        if (is_string($size)) {
            $image_sizes = Image_Size::get_image_sizes();
            if (isset($image_sizes[$size])) {
                $size = max($image_sizes[$size]['width'], $image_sizes[$size]['height']);
            }
        }
        if (!is_int($size)) {
            $size = is_numeric($size) ? (int) $size : 2048;
            // Gravatar max size
        }
        $args['size'] = $size;
        return new self($this->user_id, get_avatar_data($this->user_id, $args) ?: []);
        // @phpstan-ignore-line
    }
    /**
     * Returns an {@see Avatar} instance for the given user.
     *
     * @since 1.1.0
     *
     * @param positive-int $user_id
     * @param array<string, mixed> $args optional arguments
     * @return self
     */
    public static function for(int $user_id, array $args = []): self
    {
        return new self($user_id, get_avatar_data($user_id, $args) ?: []);
        // @phpstan-ignore-line
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
        return ['user_id' => $this->get_user_id(), 'size' => $this->get_size(), 'url' => $this->get_url()];
    }
}
