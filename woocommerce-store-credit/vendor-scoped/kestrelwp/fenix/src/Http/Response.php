<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use WP_Error;
use WP_REST_Response;
/**
 * HTTP response.
 *
 * @since 1.0.0
 *
 * @method int get_status()
 * @method string get_message()
 * @method array<string, mixed> get_headers()
 * @method string get_body()
 * @method array<mixed>|WP_Error get_raw_response_data()
 */
class Response
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var int response status code */
    protected int $status = 200;
    /** @var string optional response message */
    protected string $message = '';
    /** @var array<string, mixed>|null response headers */
    protected ?array $headers = [];
    /** @var string response body */
    protected string $body = '';
    /** @var array<int|string, mixed>|WP_Error raw data from {@see \wp_remote_request()} */
    protected $raw_response_data = [];
    /**
     * Response constructor.
     *
     * @since 1.0.0
     *
     * @param array<int|string, mixed>|WP_Error|null $response
     */
    public function __construct($response = null)
    {
        if (!$response) {
            return;
        }
        $this->set_raw_response_data($response);
    }
    /**
     * Sets the raw response data.
     *
     * @since 1.0.0
     *
     * @param array<int|string, mixed>|WP_Error $response
     * @return $this
     */
    protected function set_raw_response_data($response): Response
    {
        $this->raw_response_data = $response;
        // the following methods may be overridden by extending classes to set specific response properties
        $this->set_status();
        $this->set_message();
        $this->set_headers();
        $this->set_body();
        return $this;
    }
    /**
     * Sets the response status.
     *
     * @since 1.0.0
     *
     * @param int|null $status
     * @return $this
     */
    public function set_status(?int $status = null): Response
    {
        if (null === $status) {
            $status = wp_remote_retrieve_response_code($this->get_raw_response_data());
        }
        if (is_int($status)) {
            $this->status = $status;
        }
        return $this;
    }
    /**
     * Sets the response message.
     *
     * @since 1.0.0
     *
     * @param string|null $message
     * @return $this
     */
    public function set_message(?string $message = null): Response
    {
        if (null === $message) {
            /** @var mixed|string $message */
            $message = wp_remote_retrieve_response_message($this->get_raw_response_data());
        }
        if (is_string($message)) {
            $this->message = $message;
        }
        return $this;
    }
    /**
     * Sets the response headers.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $headers
     * @return $this
     */
    public function set_headers(?array $headers = null): Response
    {
        if (null === $headers) {
            $headers = wp_remote_retrieve_headers($this->get_raw_response_data());
            if (is_object($headers)) {
                $headers = $headers->getAll();
            }
        }
        // @phpstan-ignore-next-line in case the type has changed from above
        if (is_array($headers)) {
            $this->headers = $headers;
        }
        return $this;
    }
    /**
     * Sets the response body.
     *
     * @since 1.0.0
     *
     * @param string|null $body
     * @return $this
     */
    public function set_body(?string $body = null): Response
    {
        if (null === $body) {
            /** @var mixed|string $body */
            $body = wp_remote_retrieve_body($this->get_raw_response_data());
        }
        if (is_string($body)) {
            $this->body = $body;
        }
        return $this;
    }
    /**
     * Determines if the status is equal to the given code.
     *
     * @since 1.0.0
     *
     * @param int $status
     * @return bool
     */
    public function is_status(int $status): bool
    {
        return $status === $this->get_status();
    }
    /**
     * Determines if the response is an error.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_error(): bool
    {
        $status = $this->get_status();
        return $status < 200 || $status >= 400 || is_wp_error($this->get_raw_response_data());
        // @phpstan-ignore-line
    }
    /**
     * Determines if the response is successful.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_success(): bool
    {
        return !$this->is_error();
    }
    /**
     * Converts the response to a {@see WP_REST_Response} object.
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response
     */
    public function to_wordpress_response(): WP_REST_Response
    {
        return new WP_REST_Response($this->get_body(), $this->get_status(), $this->get_headers());
    }
    /**
     * Converts the response to a safe array.
     *
     * Requests extending this base response can override this method so that sensitive data can be stripped from the returned array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_sanitized_array(): array
    {
        return $this->to_array();
    }
    /**
     * Converts the response to a JSON string.
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
     * Converts the response to a safe JSON string.
     *
     * Requests extending this base response can override this method so that sensitive data can be stripped from the returned string.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_sanitized_string(): string
    {
        return Arrays::array($this->to_sanitized_array())->to_json();
    }
}
