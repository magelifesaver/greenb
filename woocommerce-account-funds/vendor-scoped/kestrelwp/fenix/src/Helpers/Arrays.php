<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Framework;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Query_Parameters;
use stdClass;
/**
 * Helper class for array operations.
 *
 * @since 1.0.0
 */
final class Arrays
{
    /** @var array<mixed> */
    private array $array;
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array<mixed> $array
     */
    private function __construct(array $array)
    {
        $this->array = $array;
    }
    /**
     * Initializes an array to perform operations on.
     *
     * @since 1.0.0
     *
     * @param array<mixed> $array
     * @return self
     */
    public static function array(array $array): self
    {
        return new self($array);
    }
    /**
     * Returns the array in context.
     *
     * @since 1.0.0
     *
     * @return array<mixed>
     */
    public function to_array(): array
    {
        return $this->array;
    }
    /**
     * Determines if an array is associative.
     *
     * @since 1.0.0
     *
     * @param array<mixed> $array
     * @return bool
     */
    public static function is_associative(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Converts an indexed array to an associative array with an optional key prefix.
     *
     * @since 1.4.0
     *
     * @param string $key_prefix
     * @return $this
     */
    public function associative(string $key_prefix = ''): self
    {
        $associative = [];
        foreach ($this->array as $key => $value) {
            $associative[$key_prefix . $key] = $value;
        }
        $this->array = $associative;
        return $this;
    }
    /**
     * Determines if an array is indexed.
     *
     * @since 1.0.0
     *
     * @param array<mixed> $array
     * @return bool
     */
    public static function is_indexed(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * Converts an associative array to an indexed array.
     *
     * @since 1.4.0
     *
     * @return $this
     */
    public function indexed(): self
    {
        $this->array = array_values($this->array);
        return $this;
    }
    /**
     * Returns the value from an associative array, directly or by using dot notation.
     *
     * Returns a default value it key is not found.
     *
     * This method also supports auto-expansion from flattened dot notation arrays.
     * If a key is not found directly or through nested navigation, the method will look for keys that start with the requested key followed by a dot, and expand them into a nested array structure.
     *
     * @since 1.0.0
     *
     * @param int|numeric-string|string|null $key when not provided, the entire array is returned
     * @param mixed $default_value returned if the provided key is not found
     * @return mixed
     */
    public function get($key = null, $default_value = null)
    {
        if (null === $key) {
            return $this->array;
        }
        // first check if key exists directly in the array
        if (array_key_exists($key, $this->array)) {
            return $this->array[$key];
        }
        if (is_numeric($key)) {
            // numeric keys are not processed for dot notation
            return $default_value;
        }
        // try to navigate through nested structure using dot notation
        $segments = explode('.', (string) $key);
        $value = $this->array;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                // navigation failed, try to expand from flattened dot notation
                return $this->get_expanded_from_flattened((string) $key, $default_value);
            }
            $value = $value[$segment];
        }
        return $value;
    }
    /**
     * Attempts to get a value from a flattened dot notation array by expanding child keys.
     *
     * Looks for array keys that start with the requested key followed by a dot,
     * extracts the remaining segments, and reconstructs them as a nested array.
     *
     * @since 1.8.0
     *
     * @param string $key the key to search for
     * @param mixed $default_value returned if no matching keys are found
     * @return mixed
     */
    private function get_expanded_from_flattened(string $key, $default_value)
    {
        if (is_numeric($key) || empty($key)) {
            return $default_value;
        }
        // look for keys that start with the dot notation pattern "{key}."
        $prefix = $key . '.';
        $prefix_length = strlen($prefix);
        $matches = [];
        foreach ($this->array as $array_key => $array_value) {
            if (is_string($array_key) && 0 === strpos($array_key, $prefix)) {
                // extract the remaining part after the prefix
                $remaining_key = substr($array_key, $prefix_length);
                $matches[$remaining_key] = $array_value;
            }
        }
        if (empty($matches)) {
            return $default_value;
        }
        // expand the matches from dot notation to nested array
        return self::array($matches)->expand_from_dot_notation()->to_array();
    }
    /**
     * Sets a value in the array.
     *
     * @since 1.0.0
     *
     * @param array<mixed>|int|string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value): self
    {
        if (is_array($key)) {
            $this->array = array_merge($this->array, $key);
        } else {
            $this->array[$key] = $value;
        }
        return $this;
    }
    /**
     * Removes one or more keys from the array.
     *
     * @since 1.0.0
     *
     * @param int|int[]|string|string[] $keys
     * @return self
     */
    public function remove($keys): self
    {
        foreach ((array) $keys as $key) {
            if (array_key_exists($key, $this->array)) {
                unset($this->array[$key]);
            }
        }
        return $this;
    }
    /**
     * Replaces a value in the array.
     *
     * @since 1.0.0
     *
     * @param int|string $key the array key to replace
     * @param mixed $value the value to replace with
     * @param bool $append when true, the value is added if the key does not exist
     * @return $this
     */
    public function replace($key, $value, bool $append = \false): self
    {
        if (array_key_exists($key, $this->array)) {
            $this->array[$key] = $value;
        } elseif ($append) {
            $this->set($key, $value);
        }
        return $this;
    }
    /**
     * Returns the array minus the specified keys.
     *
     * @since 1.0.0
     *
     * @param int|int[]|string|string[] $keys
     * @return array<mixed>
     */
    public function except($keys): array
    {
        return $this->remove($keys)->to_array();
    }
    /**
     * Returns only the specified keys from the array.
     *
     * @since 1.0.0
     *
     * @param int|int[]|string|string[] $keys
     * @return $this
     */
    public function only($keys): self
    {
        $keys = (array) $keys;
        $array = array_filter($this->array, function ($key) use ($keys) {
            return in_array($key, $keys, \true);
        }, \ARRAY_FILTER_USE_KEY);
        if (self::is_indexed($array)) {
            $array = array_values($array);
        }
        $this->array = $array;
        return $this;
    }
    /**
     * Filters the items in the array using a callback.
     *
     * @since 1.4.0
     *
     * @param callable $callback
     * @return $this
     */
    public function where(callable $callback): self
    {
        $array = array_filter($this->array, $callback);
        if (self::is_indexed($array)) {
            $array = array_values($array);
        }
        $this->array = $array;
        return $this;
    }
    /**
     * Filters items where the value is not null.
     *
     * @since 1.4.0
     *
     * @return $this
     */
    public function where_not_null(): self
    {
        return $this->where(function ($value) {
            return null !== $value;
        });
    }
    /**
     * Returns the first value from the array.
     *
     * @since 1.4.0
     *
     * @param callable|null $of optional callback to filter the first value
     * @param mixed $default default value to return if the array is empty
     * @return mixed
     */
    public function first(?callable $of = null, $default = null)
    {
        if (null === $of) {
            if (empty($this->array)) {
                return $default;
            }
            foreach ($this->array as $item) {
                return $item;
            }
        }
        foreach ($this->array as $key => $value) {
            if ($of($value, $key)) {
                return $value;
            }
        }
        return $default;
    }
    /**
     * Returns the last value from the array.
     *
     * @since 1.4.0
     *
     * @param callable|null $of optional callback to filter the last value
     * @param mixed $default default value to return if the array is empty
     * @return mixed
     */
    public function last(?callable $of = null, $default = null)
    {
        $reversed = array_reverse($this->array);
        $array = new self($reversed);
        return $array->first($of, $default);
    }
    /**
     * Discards duplicate values from the array.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function discard_duplicates(): self
    {
        $this->array = array_unique($this->array);
        return $this;
    }
    /**
     * Merges one or more arrays with the current array.
     *
     * @since 1.0.0
     *
     * @param array<int|string, mixed> ...$array
     * @return $this
     */
    public function merge(array ...$array): self
    {
        $this->array = array_merge($this->array, ...$array);
        return $this;
    }
    /**
     * Maps the array using a callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function map(callable $callback): self
    {
        $this->array = array_map($callback, $this->array);
        return $this;
    }
    /**
     * Inserts an array after a specified key.
     *
     * @since 1.0.0
     *
     * @param int|string $key key to insert after
     * @param array<int|string, mixed> $array array to insert in array
     * @return $this
     */
    public function insert_after($key, array $array): self
    {
        if (self::is_associative($this->array)) {
            $new_array = [];
            $keys = array_keys($this->array);
            foreach ($keys as $current_key) {
                $new_array[$current_key] = $this->array[$current_key];
                if ($current_key === $key) {
                    $new_array = array_merge($new_array, $array);
                }
            }
            $this->array = $new_array;
        } else {
            $keys = array_keys($this->array);
            $index = array_search($key, $keys, \true);
            if (\false !== $index) {
                array_splice($this->array, $index + 1, 0, $array);
            }
        }
        return $this;
    }
    /**
     * Inserts an array before a specified key.
     *
     * @since 1.0.0
     *
     * @param int|string $key key to insert before
     * @param array<int|string, mixed> $array array to insert in array
     * @return $this
     */
    public function insert_before($key, array $array): self
    {
        if (self::is_associative($this->array)) {
            $new_array = [];
            $keys = array_keys($this->array);
            foreach ($keys as $current_key) {
                if ($current_key === $key) {
                    $new_array = array_merge($new_array, $array);
                }
                $new_array[$current_key] = $this->array[$current_key];
            }
            $this->array = $new_array;
        } else {
            $keys = array_keys($this->array);
            $index = array_search($key, $keys, \true);
            if (\false !== $index) {
                array_splice($this->array, $index, 0, $array);
            }
        }
        return $this;
    }
    /**
     * Converts a multidimensional array to a flat array with dot notation keys.
     *
     * @NOTE This method will not convert indexed arrays to dot notation, even when they are nested inside an associative array value.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $array associative array used internally in recursions
     * @param string $prefix used internally in recursions
     * @return array<string, mixed>
     */
    public function flatten_to_dot_notation(?array $array = null, string $prefix = ''): array
    {
        if (null === $array) {
            $array = $this->array;
        }
        // skip keys whose name consists of a semver string to avoid breaking them
        if (Strings::is_semver(key($array))) {
            return $array;
        }
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && self::is_associative($value) && !Strings::is_semver(key($value))) {
                $nested = $this->flatten_to_dot_notation($value, $prefix . $key . '.');
                $result = array_merge($result, $nested);
            } else {
                // indexed arrays and other values are preserved
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
    /**
     * Expands a flat array with dot notation keys to a multidimensional array.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $array
     * @return self
     */
    public function expand_from_dot_notation(?array $array = null): self
    {
        if ($array === null) {
            $array = $this->array;
        }
        $this->array = $this->expand_from_dot_notation_recursively($array);
        return $this;
    }
    /**
     * Recursively expands an array with dot notation keys to a multidimensional array.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function expand_from_dot_notation_recursively(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->expand_from_dot_notation_recursively($value);
            }
            $pattern = '/\d+\.\d+\.\d+(?:[-+][A-Za-z0-9]+)*|[^.]+/';
            preg_match_all($pattern, (string) $key, $matches);
            $keys = $matches[0];
            $last_key = array_pop($keys);
            $pointer =& $result;
            foreach ($keys as $key_part) {
                // @phpstan-ignore-next-line
                if (!isset($pointer[$key_part]) || !is_array($pointer[$key_part])) {
                    $pointer[$key_part] = [];
                }
                $pointer =& $pointer[$key_part];
            }
            $pointer[$last_key] = $value;
        }
        return $result;
    }
    /**
     * Determines if an array contains one or more values.
     *
     * @since 1.0.0
     *
     * @param array<mixed>|mixed $value
     * @return bool
     */
    public function contains($value): bool
    {
        $search_values = array_unique((array) $value);
        $found_values = array_intersect($search_values, array_unique($this->array));
        return count($found_values) === count($search_values);
    }
    /**
     * Determines if an array contains one or more keys.
     *
     * @since 1.0.0
     *
     * @param array<mixed>|mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        if (is_numeric($key) || is_string($key)) {
            return array_key_exists($key, $this->array);
        } elseif (is_array($key)) {
            foreach ($key as $sub_key) {
                if (!$this->has($sub_key)) {
                    return \false;
                }
            }
            return \true;
        }
        return \false;
    }
    /**
     * Joins the array values into a string (returns helper object).
     *
     * @since 1.0.0
     *
     * @param string $glue
     * @return Strings
     */
    public function join(string $glue): Strings
    {
        return Strings::string(implode($glue, $this->array));
    }
    /**
     * Converts an array to a human-readable list.
     *
     * @since 1.0.0
     *
     * @param ","|"and"|"or" $conjunction
     * @param string|null $pattern custom pattern to use for the list
     * @return string
     */
    public function to_human_readable_list(string $conjunction = 'and', ?string $pattern = null): string
    {
        $array = array_filter($this->array, 'is_scalar');
        if (empty($array)) {
            return '';
        }
        if (count($array) === 1) {
            return current($array);
        }
        $last = array_pop($array);
        if (!$pattern) {
            switch ($conjunction) {
                case 'or':
                    /* translators: Placeholders: %1$s is a list of items, %2$s is the last item */
                    $pattern = _n('%1$s or %2$s', '%1$s, or %2$s', count($array), Framework::textdomain());
                    break;
                case 'and':
                    /* translators: Placeholders: %1$s is a comma separated list of items, %2$s is the last item */
                    $pattern = _n('%1$s and %2$s', '%1$s, and %2$s', count($array), Framework::textdomain());
                    break;
                default:
                    return implode(', ', $array) . ', ' . $last;
            }
        }
        return sprintf($pattern, implode(', ', $array), $last);
    }
    /**
     * Converts an array to a JSON string.
     *
     * @since 1.0.0
     *
     * @return string JSON
     */
    public function to_json(): string
    {
        return (string) wp_json_encode($this->array, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }
    /**
     * Converts an array to an object.
     *
     * @since 1.4.0
     *
     * @template T of object
     *
     * @param class-string<T> $class class name to instantiate, defaults to stdClass
     * @return T
     */
    public function to_object(string $class = stdClass::class): object
    {
        if ($class === stdClass::class) {
            return (object) $this->array;
        }
        return new $class($this->array);
    }
    /**
     * Converts an array to a query parameters object.
     *
     * @since 1.0.0
     *
     * @return Query_Parameters
     */
    public function to_query(): Query_Parameters
    {
        return Query_Parameters::from_array($this->array);
    }
}
