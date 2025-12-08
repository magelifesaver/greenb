<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
/**
 * Trait for objects that can create new instances from a static method.
 *
 * @since 1.0.0
 *
 * @phpstan-consistent-constructor
 */
trait Creates_New_Instances
{
    /**
     * Creates a new instance of the class with given arguments.
     *
     * @since 1.0.0
     *
     * @param mixed ...$args optional arguments to pass to the constructor
     * @return $this
     */
    public static function create(...$args)
    {
        /** @phpstan-ignore-next-line */
        return new static(...$args);
    }
}
