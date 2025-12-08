<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Enum for metabox priority in WordPress.
 *
 * @since 1.2.0
 */
class Metabox_Screen_Priority
{
    use Is_Enum;
    /** @var string */
    public const DEFAULT = 'default';
    /** @var string */
    public const HIGH = 'high';
    /** @var string */
    public const LOW = 'low';
    /** @var string */
    public const CORE = 'core';
    /**
     * Returns the default priority.
     *
     * @return string
     */
    public function default(): string
    {
        return self::DEFAULT;
    }
}
