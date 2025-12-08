<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
use Exception;
use ReflectionClass;
use ReflectionProperty;
/**
 * A trait for objects that should be able to convert their properties to array.
 *
 * @since 1.0.0
 *
 * @property string[] $to_array_excluded_properties concrete classes can implement this property to exclude some of their members from the array conversion
 */
trait Is_Arrayable
{
    /** @var array<string, bool> which properties should be included in the conversion by access definition (by default private ones are excluded) */
    protected array $to_array_properties = ['private' => \false, 'protected' => \true, 'public' => \true];
    /** @var string[] list of properties that should not be converted to array regardless of access status (classes implementing this trait can set properties to exclude in their constructor) */
    protected array $to_array_excluded_properties = [];
    /** @var bool internal flag to prevent infinite recursive calls */
    private bool $to_array_recursion_lock = \false;
    /**
     * Converts all accessible properties to an associative array, recursively.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        if ($this->to_array_recursion_lock) {
            return [];
        }
        $this->to_array_recursion_lock = \true;
        $array = [];
        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            if ($this->is_property_accessible($property)) {
                $property_name = $property->getName();
                $getter_method = 'get_' . $property_name;
                // if a concrete accessor exists use that, or fallback to reflection value
                if (method_exists($this, $getter_method)) {
                    $value = $this->{$getter_method}();
                } else {
                    $value = $property->getValue($this);
                }
                // convert objects to arrays or scalar values if possible
                try {
                    if (is_object($value)) {
                        $value = $this->object_to_array_data($value);
                    } elseif (is_array($value)) {
                        array_walk($value, function (&$item) {
                            if (is_object($item)) {
                                $item = $this->object_to_array_data($item);
                            }
                        });
                    }
                } catch (Exception $exception) {
                    continue;
                    // skip this property if it cannot be converted
                }
                $array[$property->getName()] = $value;
            }
        }
        $this->to_array_recursion_lock = \false;
        // remove any excluded properties defined by the concrete class
        foreach ($this->to_array_excluded_properties as $property) {
            unset($array[$property]);
        }
        // this trait's properties + common properties from other traits that are not needed in the array
        unset($array['to_array_properties'], $array['to_array_excluded_properties'], $array['to_array_recursion_lock'], $array['plugin'], $array['instance']);
        return $array;
    }
    /**
     * Converts an object property to an array or a scalar value.
     *
     * @since 1.6.0
     *
     * @param object $item
     * @return mixed[]|scalar
     * @throws Exception if the object cannot be converted
     */
    private function object_to_array_data(object $item)
    {
        // typically, we would use the to_array() method with objects that use this trait, but we can experimentally support third party objects as well
        if (is_callable([$item, 'to_array'])) {
            return $item->to_array();
        } elseif (is_callable([$item, 'toArray'])) {
            return $item->toArray();
        } elseif (is_callable([$item, '__toArray'])) {
            return $item->__toArray();
        } elseif (is_callable([$item, 'to_string'])) {
            return $item->to_string();
        } elseif (is_callable([$item, 'toString'])) {
            return $item->toString();
        } elseif (is_callable([$item, '__toString'])) {
            return $item->__toString();
        }
        throw new Exception(esc_html(sprintf('Object %s cannot be prepared for array conversion.', get_class($item))));
    }
    /**
     * Checks if a property is accessible for array conversion.
     *
     * @since 1.0.0
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    private function is_property_accessible(ReflectionProperty $property): bool
    {
        $property->setAccessible(\true);
        if (!$property->isInitialized($this)) {
            return \false;
        }
        if ($this->to_array_properties['public'] && $property->isPublic()) {
            return \true;
        }
        if ($this->to_array_properties['protected'] && $property->isProtected()) {
            return \true;
        }
        if ($this->to_array_properties['private'] && $property->isPrivate()) {
            return \true;
        }
        return \false;
    }
}
