<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Represents the context of a metabox.
 *
 * @since 1.2.0
 */
class Metabox_Screen_Context
{
    use Is_Enum;
    /** @var string */
    public const NORMAL = 'normal';
    /** @var string */
    public const ADVANCED = 'advanced';
    /** @var string */
    public const SIDE = 'side';
    /**
     * Returns the default value for the enum.
     *
     * @return string
     */
    public static function default(): string
    {
        return self::ADVANCED;
    }
}
