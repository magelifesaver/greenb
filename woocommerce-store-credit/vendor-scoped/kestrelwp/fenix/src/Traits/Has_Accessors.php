<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
use Error;
use ReflectionClass;
/**
 * A trait for objects that should have public accessors for their properties.
 *
 * @since 1.0.0
 */
trait Has_Accessors
{
    use Is_Arrayable;
    /** @var string[] memoized property names */
    private array $properties = [];
    /**
     * Gets all the properties of the current object using reflection.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    protected function get_properties(): array
    {
        if (empty($this->properties)) {
            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties();
            $this->properties = array_map(function ($property) {
                return $property->getName();
            }, $properties);
        }
        return $this->properties;
    }
    /**
     * Sets all class properties in bulk using the given data.
     *
     * @NOTE The values should be in the expected type or errors will occur.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $data array of property names and values
     * @return $this
     */
    public function set_properties(array $data)
    {
        foreach ($this->get_properties() as $property) {
            if (!array_key_exists($property, $data)) {
                continue;
            }
            // reserved property names to avoid recursions
            if (in_array($property, ['properties'], \true)) {
                continue;
            }
            $set_property = 'set_' . $property;
            if (method_exists($this, $set_property)) {
                $this->{$set_property}($data[$property]);
            } elseif (property_exists($this, $property)) {
                $this->{$property} = $data[$property];
            }
        }
        return $this;
    }
    /**
     * Magic method to get and set properties.
     *
     * @NOTE When invoking setters, the values should be in the expected type or type errors will occur.
     *
     * @since 1.0.0
     *
     * @param string $method
     * @param array<mixed> $arguments
     * @return $this|mixed
     * @throws Error if the method is inaccessible or undefined
     */
    public function __call(string $method, array $arguments)
    {
        // handle magic accessors
        if (preg_match('/^(get|set)_(.+)$/', $method, $matches)) {
            $action = $matches[1];
            $property = $matches[2];
            $accessor = $action . '_' . $property;
            if (!in_array($accessor, ['get_properties', 'set_properties'], \true)) {
                // use concrete method if it exists in the class
                if (method_exists($this, $accessor)) {
                    // @phpstan-ignore-next-line
                    return call_user_func_array([$this, $accessor], $arguments);
                }
                // fallback to get or set property directly if a concrete accessor does not exist
                if (property_exists($this, $property)) {
                    if ($action === 'get') {
                        return $this->{$property};
                    } elseif ($action === 'set') {
                        $this->{$property} = $arguments[0];
                        return $this;
                    }
                }
            }
        }
        /** @var class-string $parent */
        $parent = get_parent_class(static::class);
        // @phpstan-ignore-next-line
        if ($parent && is_callable([$parent, '__call'])) {
            // call other parent method if it exists
            return $parent::__call($method, $arguments);
            // @phpstan-ignore-line
        }
        // invoked method is inaccessible
        if (method_exists($this, $method)) {
            // phpcs:ignore
            throw new Error('Call to private method ' . static::class . '::' . $method);
            // @phpstan-ignore-line
        }
        // invoked method is undefined
        // phpcs:ignore
        throw new Error('Call to undefined method ' . static::class . '::' . $method);
        // @phpstan-ignore-line
    }
}
