<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Arrayable;
defined('ABSPATH') or exit;
/**
 * Object representation of URL query parameters.
 *
 * @since 1.0.0
 */
final class Query_Parameters
{
    use Is_Arrayable;
    /** @var array<string, mixed> */
    protected array $parameters = [];
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }
    /**
     * Builds a new query parameters object from an array.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $parameters
     * @return self
     */
    public static function from_array(array $parameters): self
    {
        return new self($parameters);
    }
    /**
     * Builds a new query parameters object from a string.
     *
     * @since 1.0.0
     *
     * @param string $query
     * @return Query_Parameters
     */
    public static function from_string(string $query = ''): self
    {
        if ('' === $query) {
            return new self();
        }
        $parameters = [];
        parse_str($query, $parameters);
        $parameters = array_map(function ($param) {
            return '' !== $param ? $param : null;
        }, $parameters);
        return new self($parameters);
    }
    /**
     * Gets a query parameter by key.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Arrays::array($this->parameters)->get($key, $default);
    }
    /**
     * Determines if a query parameter exists.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }
    /**
     * Gets the count of the current query parameters.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }
    /**
     * Adds a query parameter.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function add(string $key, $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }
    /**
     * Adds many query parameters.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $parameters
     * @return $this
     */
    public function add_many(array $parameters): self
    {
        foreach ($parameters as $key => $value) {
            $this->add($key, $value);
        }
        return $this;
    }
    /**
     * Removes one or more query parameters.
     *
     * @since 1.0.0
     *
     * @param string|string[] $value
     * @return $this
     */
    public function remove($value): self
    {
        if (is_array($value)) {
            foreach ($value as $key) {
                unset($this->parameters[$key]);
            }
        } else {
            unset($this->parameters[$value]);
        }
        return $this;
    }
    /**
     * Gets all the query parameters as an array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return $this->parameters;
    }
    /**
     * Gets all the query parameters as a string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_string(): string
    {
        return trim(http_build_query($this->parameters, '', '&', \PHP_QUERY_RFC3986));
    }
}
