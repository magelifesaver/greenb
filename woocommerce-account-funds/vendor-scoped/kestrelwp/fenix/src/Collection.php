<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Arrayable;
use Traversable;
/**
 * A collection of items, typically {@see Model} instances.
 *
 * @since 1.1.0
 *
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 *
 * @method static Collection<TKey, TValue> create( TValue[] $items )
 * @method array<TKey, TValue> get_items()
 * @method Collection<TKey, TValue> set_items( TValue[] $items )
 * @method int get_total_items()
 * @method Collection<TKey, TValue> set_total_items( int $total_items )
 * @method int get_total_pages()
 * @method Collection<TKey, TValue> set_total_pages( int $total_pages )
 * @method int get_current_page()
 * @method Collection<TKey, TValue> set_current_page( int $current_page )
 * @method int get_items_per_page()
 * @method Collection<TKey, TValue> set_items_per_page( int $items_per_page )
 * @method bool get_pageable()
 * @method Collection<TKey, TValue> set_pageable( bool $pageable )
 */
final class Collection implements Countable, IteratorAggregate
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var array<int|string, TValue> */
    protected array $items;
    // @phpstan-ignore-line
    /** @var bool whether this collection is the result of a pageable query */
    protected bool $pageable;
    /** @var int */
    protected int $total_items;
    /** @var int */
    protected int $total_pages;
    /** @var int */
    protected int $current_page;
    /** @var int */
    protected int $items_per_page;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param array<TKey, TValue> $items
     */
    protected function __construct(array $items)
    {
        $this->items = $items;
        // default pagination values
        $this->pageable = \false;
        $this->total_items = count($items);
        $this->total_pages = 1;
        $this->current_page = 1;
        $this->items_per_page = $this->total_items;
    }
    /**
     * Checks if the collection originated from a pageable query.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_paged(): bool
    {
        return $this->pageable;
    }
    /**
     * Checks if the collection has any items.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function has_items(): bool
    {
        return !empty($this->items);
    }
    /**
     * Checks if the collection is empty.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_empty(): bool
    {
        return !$this->has_items();
    }
    /**
     * Checks if the collection is not empty.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_not_empty(): bool
    {
        return !$this->is_empty();
    }
    /**
     * Discards the values of the collection and retains the keys.
     *
     * @since 1.1.0
     *
     * @return Collection<int, TKey>
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }
    /**
     * Discards the keys of the collection.
     *
     * @since 1.1.0
     *
     * @return Collection<int, TValue>
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }
    /**
     * Returns one item from the collection, or null.
     *
     * @since 1.1.0
     *
     * @return TValue|null
     */
    public function one()
    {
        return reset($this->items) ?: null;
    }
    /**
     * Returns all items from the collection.
     *
     * @since 1.1.0
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }
    /**
     * Checks if the collection has an item with the specified key.
     *
     * @since 1.1.0
     *
     * @param TKey $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }
    /**
     * Gets an item from the collection by key.
     *
     * @since 1.1.0
     *
     * @param TKey $key
     * @param mixed|null $default optional default value to return if the key does not exist
     * @return TValue|null
     */
    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }
        return $this->items[$key];
    }
    /**
     * Adds an item to the collection.
     *
     * @since 1.1.0
     *
     * @param int|string|TKey $key
     * @param mixed|TValue $value
     * @return Collection<TKey, TValue>
     */
    public function put($key, $value): self
    {
        $this->items[$key] = $value;
        return new self($this->items);
    }
    /**
     * Removes an item from the collection.
     *
     * @since 1.1.0
     *
     * @param array<TKey>|TKey $keys
     * @return Collection<TKey, TValue>
     */
    public function remove($keys): self
    {
        foreach ((array) $keys as $key) {
            unset($this->items[$key]);
        }
        return new self($this->items);
    }
    /**
     * Merges an array of items into the collection.
     *
     * @since 1.1.0
     *
     * @param array<int|string, mixed> $items
     * @return Collection<TKey, TValue>
     */
    public function merge(array $items): self
    {
        return new self(array_merge($this->items, $items));
    }
    /**
     * Adds an array of items to the collection using the union operator.
     *
     * @since 1.1.0
     *
     * @param array<int|string, mixed> $items
     * @return Collection<TKey, TValue>
     */
    public function add(array $items): self
    {
        $this->items = $this->items + $items;
        return new self($this->items);
    }
    /**
     * Prepends an item at the start of the collection.
     *
     * @since 1.1.0
     *
     * @param int|string|TKey $key
     * @param mixed|TValue $value
     * @return Collection<TKey, TValue>
     */
    public function prepend($key, $value): self
    {
        /** @var array<TKey, TValue> $items */
        $items = [$key => $value] + $this->items;
        $this->items = $items;
        return new self($this->items);
    }
    /**
     * Appends an item to the end of the collection.
     *
     * @since 1.1.0
     *
     * @param int|string|TKey $key
     * @param mixed|TValue $value
     * @return Collection<TKey, TValue>
     */
    public function append($key, $value): self
    {
        /** @var array<TKey, TValue> $items */
        $items = $this->items + [$key => $value];
        $this->items = $items;
        return new self($this->items);
    }
    /**
     * Returns a new collection excluding the specified keys.
     *
     * @since 1.1.0
     *
     * @param int[]|string[]|TKey[] $keys
     * @return Collection<TKey, TValue>
     */
    public function except(array $keys): self
    {
        /** @var array<TKey, TValue> $items */
        $items = array_diff_key($this->items, array_flip($keys));
        $this->items = $items;
        return new self($this->items);
    }
    /**
     * Returns a new collection including only the specified keys.
     *
     * @since 1.1.0
     *
     * @param int[]|string[]|TKey[] $keys
     * @return Collection<TKey, TValue>
     */
    public function only(array $keys): self
    {
        /** @var array<TKey, TValue> $items */
        $items = array_intersect_key($this->items, array_flip($keys));
        return new self($items);
    }
    /**
     * Returns a new collection with only items that are numeric.
     *
     * @since 1.6.0
     *
     * @param array<int|string>|null $keys optional keys to filter by
     * @return Collection<TKey, numeric>
     */
    public function numeric(?array $keys = null): self
    {
        /** @var array<TKey, numeric> $items */
        $items = array_filter($this->items, function ($item, $key) use ($keys) {
            return is_numeric($item) && (null === $keys || in_array($key, $keys, \true));
        }, \ARRAY_FILTER_USE_BOTH);
        return new self($items);
    }
    /**
     * Maps each item in the collection to a new value.
     *
     * @since 1.1.0
     *
     * @template TNew
     *
     * @param callable(TValue, TKey) : TNew $callback
     * @return Collection<TKey, TNew>
     */
    public function map(callable $callback): self
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            $items[$key] = $callback($item, $key);
        }
        return new self($items);
    }
    /**
     * Filters the collection to only include items that pass the callback.
     *
     * The callback should return `true` to keep the item, and `false` to remove it.
     *
     * @since 1.1.0
     *
     * @param callable(TValue, TKey) : bool $callback
     * @return Collection<TKey, TValue>
     */
    public function filter(callable $callback): self
    {
        $items = array_filter($this->items, function ($item, $key) use ($callback) {
            return \true === $callback($item, $key);
        }, \ARRAY_FILTER_USE_BOTH);
        /** @var array<TKey, TValue> $items */
        return new self($items);
    }
    /**
     * Returns the first element in the collection where the callback conditions are met, or null.
     *
     * @since 1.1.0
     *
     * @param callable(TValue, TKey) : bool|null $callback
     * @return TValue|null
     */
    public function first(?callable $callback = null)
    {
        if (null === $callback) {
            return reset($this->items) ?: null;
        }
        foreach ($this->items as $key => $item) {
            if (\true === $callback($item, $key)) {
                return $item;
            }
        }
        return null;
    }
    /**
     * Returns the last element in the collection where the callback conditions are met, or null.
     *
     * @since 1.1.0
     *
     * @param callable(TValue, TKey) : bool|null $callback
     * @return TValue|null
     */
    public function last(?callable $callback = null)
    {
        if (null === $callback) {
            return end($this->items) ?: null;
        }
        $items = array_reverse($this->items, \true);
        foreach ($items as $key => $item) {
            if (\true === $callback($item, $key)) {
                return $item;
            }
        }
        return null;
    }
    /**
     * Gets the number of items in the collection.
     *
     * @since 1.1.0
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
    /**
     * Sums the values in the collection.
     *
     * @since 1.6.0
     *
     * @param int|int[]|string|string[]|null $key optional key(s) to sum by
     * @return float|int
     */
    public function sum($key = null)
    {
        return array_sum($this->numeric($key ? (array) $key : null)->get_items());
    }
    /**
     * Returns the maximum value in the collection.
     *
     * @since 1.6.0
     *
     * @param int|int[]|string|string[]|null $key optional key(s) to find the maximum by
     * @return float|int
     */
    public function max($key = null)
    {
        $items = $this->numeric($key ? (array) $key : null);
        return $items->is_empty() ? 0 : max($items->get_items());
    }
    /**
     * Returns the minimum value in the collection.
     *
     * @since 1.6.0
     *
     * @param int|int[]|string|string[]|null $key optional key(s) to find the minimum by
     * @return float|int
     */
    public function min($key = null)
    {
        $items = $this->numeric($key ? (array) $key : null);
        return $items->is_empty() ? 0 : min($items->get_items());
    }
    /**
     * Returns the average value in the collection.
     *
     * @since 1.6.0
     *
     * @param int|int[]|string|string[]|null $key optional key(s) to find the median by
     * @return float|int
     */
    public function average($key = null)
    {
        $items = $this->numeric($key ? (array) $key : null);
        if ($items->is_empty()) {
            return 0;
        }
        return $items->sum() / $items->count();
    }
    /**
     * Gets an iterator for the collection.
     *
     * @since 1.1.0
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
    /**
     * Converts the collection to a JSON string.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function to_json(): string
    {
        return Arrays::array($this->to_array())->to_json();
    }
    /**
     * Converts the collection to an array.
     *
     * @since 1.1.0
     *
     * @return array<TKey, array|mixed|scalar>
     */
    public function to_array(): array
    {
        $to_array = [];
        /** @see Is_Arrayable::to_array() */
        foreach ($this->items as $key => $value) {
            // @phpstan-ignore-next-line sanity check
            if (is_object($value) && is_callable([$value, 'to_array'])) {
                $to_array[$key] = $value->to_array();
            } elseif (is_array($value)) {
                $to_array[$key] = array_map(static function ($item) {
                    return is_object($item) && is_callable([$item, 'to_array']) ? $item->to_array() : $item;
                }, $value);
            } else {
                $to_array[$key] = $value;
            }
        }
        return $to_array;
    }
}
