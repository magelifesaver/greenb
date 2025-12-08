<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers;

use Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Framework;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;
defined('ABSPATH') or exit;
/**
 * Helper for handling MIME types.
 *
 * @since 1.1.0
 *
 * @method static self application()
 * @method static self audio()
 * @method static self image()
 * @method static self text()
 * @method static self video()
 */
final class Mime_Types
{
    /** @var string|null */
    private ?string $type_or_category;
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $data = null;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param string|null $type_or_category
     */
    private function __construct(?string $type_or_category = null)
    {
        $this->type_or_category = $type_or_category ? trim($type_or_category) : null;
    }
    /**
     * Gets the mime types data.
     *
     * The data uses a set obtained from @link https://github.com/jshttp/mime-db/blob/master/db.json
     *
     * @since 1.1.0
     *
     * @return array<string, array<string, mixed>>
     */
    private static function data(): array
    {
        if (is_array(self::$data)) {
            return self::$data;
        }
        $asset = dirname(dirname(Framework::absolute_file_path())) . '/resources/mime-types.json';
        if (!is_readable($asset)) {
            Logger::critical('MIME types data file is not readable.');
            return [];
        }
        // phpcs:ignore
        self::$data = json_decode(file_get_contents($asset) ?: '{}', \true);
        if (empty(self::$data)) {
            Logger::critical('MIME types data file is empty or invalid.');
        }
        return self::$data;
    }
    /**
     * Returns an instance for all MIME types.
     *
     * @since 1.1.0
     *
     * @return self
     */
    public static function all(): self
    {
        return new self();
    }
    /**
     * Returns an instance for a specific MIME type or category.
     *
     * @since 1.1.0
     *
     * @param string $mime_type
     * @return self
     */
    public static function from_type_or_category(string $mime_type): self
    {
        return new self($mime_type);
    }
    /**
     * Returns a MIME type instance from a file extension.
     *
     * @since 1.1.0
     *
     * @param string $extension
     * @return self
     */
    public static function from_extension(string $extension): self
    {
        foreach (self::data() as $mime_type => $mime_data) {
            if (isset($mime_data['extensions']) && in_array($extension, (array) $mime_data['extensions'], \true)) {
                return new self($mime_type);
            }
        }
        return new self('application/octet-stream');
    }
    /**
     * Returns a MIME type instance from a file name.
     *
     * @since 1.1.0
     *
     * @param string $filename
     * @return self
     */
    public static function from_filename(string $filename): self
    {
        $extension = pathinfo($filename, \PATHINFO_EXTENSION);
        return self::from_extension($extension);
    }
    /**
     * Returns a list of allowed MIME types for upload.
     *
     * @return string[]
     */
    public function allowed(): array
    {
        $current_mime_types = $this->list();
        $allowed_by_wordpress = array_values(get_allowed_mime_types());
        return array_values(array_intersect($current_mime_types, $allowed_by_wordpress));
    }
    /**
     * Returns a list of MIME types.
     *
     * @since 1.1.0
     *
     * @return string[] list of MIME types
     */
    public function list(): array
    {
        if (null === $this->type_or_category) {
            return array_keys(self::data());
        }
        $mime_types = [];
        foreach (array_keys(self::data()) as $identifier) {
            if ($this->type_or_category === $identifier || $this->type_or_category === (explode('/', $identifier)[0] ?: null)) {
                $mime_types[] = $identifier;
            }
        }
        return $mime_types;
    }
    /**
     * Returns the extensions for a MIME type.
     *
     * @since 1.1.0
     *
     * @return string[]
     */
    public function extensions(): array
    {
        $mime_data = self::data();
        $mime_types = $this->list();
        $extensions = [];
        foreach ($mime_data as $mime_type => $data) {
            if (isset($data['extensions']) && in_array($mime_type, $mime_types, \true)) {
                $extensions[] = (array) $data['extensions'];
            }
        }
        return array_merge(...$extensions);
    }
    /**
     * Returns the type for the current instance.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function type(): string
    {
        $types = $this->list();
        if (empty($types) || count($types) > 1) {
            return 'application/octet-stream';
        }
        return current($types);
    }
    /**
     * Determines if the current MIME type is allowed in uploads.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_allowed(): bool
    {
        return count(array_intersect($this->list(), $this->allowed())) > 0;
    }
    /**
     * Adds shortcuts to common MIME type categories.
     *
     * @since 1.1.0
     *
     * @param string $method_name method name
     * @param array<mixed> $arguments
     * @return static
     * @throws Error
     */
    public static function __callStatic(string $method_name, array $arguments)
    {
        switch (strtolower($method_name)) {
            case 'application':
                return new self('application');
            case 'audio':
                return new self('audio');
            case 'image':
                return new self('image');
            case 'text':
                return new self('text');
            case 'video':
                return new self('video');
            default:
                if (method_exists(__CLASS__, $method_name)) {
                    // phpcs:ignore
                    throw new Error('Call to private method ' . __CLASS__ . '::' . $method_name);
                }
                // phpcs:ignore
                throw new Error('Call to undefined method ' . __CLASS__ . '::' . $method_name);
        }
    }
}
