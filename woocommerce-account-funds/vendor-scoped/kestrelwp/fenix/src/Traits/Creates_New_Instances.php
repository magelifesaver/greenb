<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
/**
 * Trait for objects that can create new instances from a static method.
 *
 * @TODO perhaps by version 2 this should be deprecated in favor of a factory pattern or a `make()` method since `create()` is a bit ambiguous
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
