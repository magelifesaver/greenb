<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
defined('ABSPATH') or exit;
/**
 * A trait for plugin classes that are handlers.
 *
 * @since 1.0.0
 */
trait Is_Handler
{
    use Has_Hidden_Callbacks;
    use Has_Plugin_Instance;
    use Is_Singleton;
    /**
     * Initializes the handler.
     *
     * @since 1.0.0
     *
     * @param mixed ...$args
     * @return static
     */
    public static function initialize(...$args)
    {
        return static::instance(...$args);
    }
}
