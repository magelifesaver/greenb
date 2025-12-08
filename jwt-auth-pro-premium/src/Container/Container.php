<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Container;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class Container
{
    /**
     * The container's bindings.
     *
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, Closure $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = $concrete;

        if ($shared) {
            $this->instances[$abstract] = null;
        }
    }

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, Closure $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve the given type from the container.
     *
     * @template T
     * @param class-string<T> $abstract
     * @return T
     *
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        return match (true) {
            isset($this->instances[$abstract]) => $this->instances[$abstract],
            isset($this->bindings[$abstract]) => $this->resolveBinding($abstract),
            default => $this->build($abstract),
        };
    }

    /**
     * Resolve a binding from the container.
     */
    private function resolveBinding(string $abstract): mixed
    {
        $concrete = $this->bindings[$abstract];
        $object = $concrete($this);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @throws ReflectionException
     */
    private function build(string $concrete): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = array_map(
            fn($parameter) => $this->resolveDependency($parameter),
            $constructor->getParameters()
        );

        return new $concrete(...$dependencies);
    }

    /**
     * Resolve a constructor parameter dependency.
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new InvalidArgumentException(
            "Unable to resolve dependency [{$parameter->getName()}]"
        );
    }

    /**
     * Determine if a given type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Get a shared instance of the given type.
     *
     * @throws ReflectionException
     */
    public function getInstance(string $abstract): mixed
    {
        if (!isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $this->make($abstract);
        }

        return $this->instances[$abstract];
    }
}
