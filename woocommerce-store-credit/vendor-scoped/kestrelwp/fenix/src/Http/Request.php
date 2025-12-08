<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http;

defined('ABSPATH') or exit;
use Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Exceptions\Invalid_Request_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Exceptions\Request_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Exceptions\Response_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Url\Exceptions\Invalid_URL_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Url\Query_Parameters;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use WP_Http;
use WP_REST_Request;
/**
 * HTTP request.
 *
 * @since 1.0.0
 *
 * @method string get_url()
 * @method string get_path()
 * @method Query_Parameters|null get_query()
 * @method string get_method()
 * @method string get_http_version()
 * @method array<int|string, mixed> get_headers()
 * @method array<int|string, mixed>|null get_body()
 * @method string get_response()
 * @method bool get_blocking()
 * @method int get_allowed_redirects()
 * @method int get_timeout()
 * @method bool get_ssl_verify()
 * @method $this set_url( string $url )
 * @method $this set_path( string $path )
 * @method $this set_query(Query_Parameters|null $query)
 * @method $this set_method( string $method )
 * @method $this set_http_version( string $http_version )
 * @method $this set_headers( array $headers )
 * @method $this set_body( array|null $body )
 * @method $this set_response( string $response )
 * @method $this set_blocking( bool $blocking )
 * @method $this set_allowed_redirects( int $allowed_redirects )
 * @method $this set_timeout( int $timeout )
 * @method $this set_ssl_verify( bool $ssl_verify )
 * @method static Request GET( string $url, array $args = [] )
 * @method static Request POST( string $url, array $args = [] )
 * @method static Request PUT( string $url, array $args = [] )
 * @method static Request DELETE( string $url, array $args = [] )
 * @method static Request PATCH( string $url, array $args = [] )
 * @method static Request HEAD( string $url, array $args = [] )
 * @method static Request OPTIONS( string $url, array $args = [] )
 * @method static Request TRACE( string $url, array $args = [] )
 */
class Request
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var string base URL */
    protected string $url = '';
    /** @var string URL path */
    protected string $path = '';
    /** @var Query_Parameters|null optional query parameters */
    protected ?Query_Parameters $query = null;
    /** @var string HTTP method */
    protected string $method = Method::GET;
    /** @var string HTTP version to use */
    protected string $http_version = '1.1';
    /** @var array<string, mixed> HTTP headers */
    protected array $headers = [];
    /** @var array<int|string, mixed>|null HTTP body */
    protected ?array $body = null;
    /** @var class-string<Response> response class */
    protected string $response = Response::class;
    /** @var bool whether this is a blocking request */
    protected bool $blocking = \true;
    /** @var int number of allowed redirects to follow */
    protected int $allowed_redirects = 0;
    /** @var int timeout in seconds */
    protected int $timeout = 30;
    /** @var bool whether to verify SSL certificates */
    protected bool $ssl_verify = \true;
    /**
     * Request constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->set_properties($args);
    }
    /**
     * Adds a header to the request.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set_header(string $key, $value): Request
    {
        $this->headers[$key] = $value;
        return $this;
    }
    /**
     * Removes a header from the request.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @return $this
     */
    public function unset_header(string $key): Request
    {
        if (isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }
        return $this;
    }
    /**
     * Determines if the request has query parameters.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_query(): bool
    {
        return null !== $this->query && $this->query->count() > 0;
    }
    /**
     * Determines if the request is a blocking request.
     *
     * @see WP_Http::request()
     *
     * @return bool
     */
    public function is_blocking(): bool
    {
        return $this->get_blocking();
    }
    /**
     * Builds the request URL.
     *
     * @since 1.0.0
     *
     * @return string
     * @throws Invalid_URL_Exception
     */
    protected function build_request_url(): string
    {
        $url = Url::from_string(untrailingslashit($this->get_url()) . $this->get_path());
        if ($this->has_query()) {
            /** @var Query_Parameters $query */
            $query = $this->get_query();
            $url->add_query_parameters($query->to_array());
        }
        return $url->to_string();
    }
    /**
     * Validates the request.
     *
     * Requests extending this class can override this method to add custom validation logic.
     *
     * @since 1.0.0
     *
     * @return void
     * @throws Invalid_Request_Exception
     */
    public function validate(): void
    {
        if (!in_array($this->get_method(), Method::cases(), \true)) {
            // phpcs:ignore
            throw new Invalid_Request_Exception('Invalid request method.');
        }
    }
    /**
     * Sends the request.
     *
     * @since 1.0.0
     *
     * @return Response
     * @throws Invalid_Request_Exception
     * @throws Response_Exception
     */
    public function send(): Response
    {
        try {
            $url = $this->build_request_url();
        } catch (Invalid_URL_Exception $exception) {
            // phpcs:ignore
            throw new Invalid_Request_Exception(esc_html($exception->getMessage()), $exception);
            // phpcs:ignore
        }
        /** @var class-string<Response> $response */
        $response = $this->get_response();
        // @phpstan-ignore-next-line
        if (!class_exists($response) || !is_subclass_of($response, Response::class)) {
            // phpcs:ignore
            throw new Response_Exception(500, 'Invalid response class.');
        }
        // phpcs:ignore
        $this->validate();
        return $response::create(wp_safe_remote_request($url, ['httpversion' => $this->get_http_version(), 'method' => $this->get_method(), 'headers' => $this->get_headers(), 'body' => $this->get_body(), 'blocking' => $this->is_blocking(), 'redirection' => $this->get_allowed_redirects(), 'timeout' => $this->get_timeout(), 'sslverify' => $this->get_ssl_verify()]));
    }
    /**
     * Converts the request to a {@see WP_REST_Request} object.
     *
     * @since 1.0.0
     *
     * @return WP_REST_Request
     *
     * @phpstan-return WP_REST_Request<array<mixed>>
     */
    public function to_wordpress_request(): WP_REST_Request
    {
        return new WP_REST_Request($this->get_method(), untrailingslashit($this->get_url()) . $this->get_path(), $this->get_query()->to_array());
    }
    /**
     * Converts the request to a safe array.
     *
     * Requests extending this base request can override this method so that sensitive data can be stripped from the returned array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_sanitized_array(): array
    {
        $request_data = $this->to_array();
        if (isset($request_data['headers']['Authorization']) && is_string($request_data['headers']['Authorization'])) {
            $request_data['headers']['Authorization'] = str_repeat('*', strlen($request_data['headers']['Authorization']));
        }
        return $request_data;
    }
    /**
     * Converts the request to a JSON string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_string(): string
    {
        return Arrays::array($this->to_array())->to_json();
    }
    /**
     * Converts the request to a safe JSON string.
     *
     * Requests extending this base request can override this method so that sensitive data can be stripped from the returned string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_sanitized_string(): string
    {
        return Arrays::array($this->to_sanitized_array())->to_json();
    }
    /**
     * Maps HTTP methods to create instances of self with that method.
     *
     * For example, `Request::GET( $url, $args )` will return a new instance of `Request` with the method set to `GET`.
     *
     * @since 1.0.0
     *
     * @param string $name
     * @param array<mixed> $arguments
     * @return $this
     * @throws Error
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $parent = get_parent_class(static::class);
        if (in_array(strtoupper($name), Method::values(), \true)) {
            $url = $arguments[0] ?: '';
            $args = $arguments[1] ?: [];
            // @phpstan-ignore-next-line
            return new static(array_merge(['method' => strtoupper($name), 'url' => (string) $url], (array) $args));
        } elseif ($parent && is_callable([$parent, '__callStatic'])) {
            return $parent::__callStatic($name, $arguments);
        } elseif (method_exists(static::class, $name)) {
            // phpcs:ignore
            throw new Error('Call to private method ' . static::class . '::' . $name);
            // phpstan-ignore-line
        }
        // phpcs:ignore
        throw new Error('Call to undefined method ' . static::class . '::' . $name);
        // @phpstan-ignore-line
    }
}
