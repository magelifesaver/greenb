<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Exceptions\Invalid_URL_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Exceptions\Invalid_URL_Scheme_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Query_Parameters;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Scheme;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Object representation of a URL.
 *
 * @since 1.0.0
 *
 * @method string get_scheme()
 * @method string get_host()
 * @method int|null get_port()
 * @method string get_path()
 * @method string get_fragment()
 */
final class Url
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var string */
    protected string $scheme = '';
    /** @var string */
    protected string $host = '';
    /** @var int|null */
    protected ?int $port = null;
    /** @var string */
    protected string $path = '/';
    /** @var string */
    protected string $fragment = '';
    /** @var Query_Parameters|null */
    protected ?Query_Parameters $query_parameters = null;
    /**
     * URL constructor.
     *
     * @since 1.0.0
     *
     * @param string|null $url
     * @throws Invalid_URL_Exception|Invalid_URL_Scheme_Exception
     */
    public function __construct(?string $url = null)
    {
        if (null === $url) {
            return;
        }
        $this->parse_url($url);
    }
    /**
     * Parses a URL string to class properties.
     *
     * @since 1.0.0
     *
     * @param string $url
     * @return void
     * @throws Invalid_URL_Exception
     */
    protected function parse_url(string $url): void
    {
        $parts = parse_url($url);
        // phpcs:ignore
        if (!$parts) {
            throw new Invalid_URL_Exception(esc_html(sprintf('Invalid URL: %s', $url)));
        }
        $scheme = $parts['scheme'] ?? '';
        $this->scheme = !empty($scheme) ? $this->sanitize_scheme($scheme) : '';
        $port = $parts['port'] ?? '';
        $this->port = is_numeric($port) ? (int) $port : null;
        $this->host = $parts['host'] ?? '';
        $this->path = $parts['path'] ?? '/';
        $this->query_parameters = Query_Parameters::from_string($parts['query'] ?? '');
        $this->fragment = $parts['fragment'] ?? '';
    }
    /**
     * Builds a new URL from a string.
     *
     * @since 1.0.0
     *
     * @param string $url
     * @return Url
     * @throws Invalid_URL_Exception|Invalid_URL_Scheme_Exception
     */
    public static function from_string(string $url): self
    {
        return new self($url);
    }
    /**
     * Sets and validates the URL scheme.
     *
     * @since 1.0.0
     *
     * @param string $value
     * @return $this
     * @throws Invalid_URL_Exception
     */
    public function set_scheme(string $value): self
    {
        $this->scheme = $this->sanitize_scheme($value);
        return $this;
    }
    /**
     * Sanitizes and validates a scheme.
     *
     * @since 1.0.0
     *
     * @param string $scheme
     * @return string
     * @throws Invalid_URL_Scheme_Exception
     */
    public function sanitize_scheme(string $scheme): string
    {
        $scheme = strtolower($scheme);
        if (!in_array($scheme, Scheme::cases(), \true)) {
            throw new Invalid_URL_Scheme_Exception(esc_html(sprintf('Invalid URL scheme: %s', $scheme)));
        }
        return $scheme;
    }
    /**
     * Gets the URL authority.
     *
     * The authority is the host and port.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_authority(): string
    {
        $authority = $this->host;
        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }
    /**
     * Gets a query parameter value by key.
     *
     * @since 1.0.0
     *
     * @param string $key query parameter key
     * @param mixed|null $default optional return value, defaults to null
     * @return mixed|null
     */
    public function get_query_parameter(string $key, $default = null)
    {
        return $this->query_parameters ? $this->query_parameters->get($key, $default) : $default;
    }
    /**
     * Gets the query parameters as a string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_query(): string
    {
        return $this->query_parameters ? $this->query_parameters->to_string() : '';
    }
    /**
     * Determines if the URL has query parameters set and are not empty.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_query_parameters(): bool
    {
        return null !== $this->query_parameters && '' !== $this->query_parameters->to_string();
    }
    /**
     * Add a query parameter.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function add_query_parameter(string $key, $value): self
    {
        if (!$this->query_parameters) {
            $this->query_parameters = new Query_Parameters();
        }
        $this->query_parameters->add($key, $value);
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
    public function add_query_parameters(array $parameters): self
    {
        if (!$this->query_parameters) {
            $this->query_parameters = new Query_Parameters();
        }
        $this->query_parameters->add_many($parameters);
        return $this;
    }
    /**
     * Removes a query parameter.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @return $this
     */
    public function remove_query_parameter(string $key): self
    {
        if (!$this->query_parameters) {
            return $this;
        }
        $this->query_parameters->remove($key);
        return $this;
    }
    /**
     * Removes query parameters.
     *
     * @since 1.0.0
     *
     * @param string[] $keys
     * @return $this
     */
    public function remove_query_parameters(array $keys): self
    {
        if (!$this->query_parameters) {
            return $this;
        }
        $this->query_parameters->remove($keys);
        return $this;
    }
    /**
     * Converts the URL object to a URL string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_string(): string
    {
        $url = '';
        if ('' !== $this->get_scheme()) {
            $url .= $this->get_scheme() . '://';
        }
        if ('' !== $this->get_authority()) {
            $url .= $this->get_authority();
        }
        if ('/' !== $this->get_path()) {
            $url .= $this->get_path();
        }
        if ('' !== $this->get_query()) {
            $url .= '?' . $this->get_query();
        }
        if ('' !== $this->get_fragment()) {
            $url .= '#' . $this->get_fragment();
        }
        return $url;
    }
}
