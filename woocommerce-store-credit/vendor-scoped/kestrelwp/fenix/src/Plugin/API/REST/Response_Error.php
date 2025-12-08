<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Framework;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Named_Constructors;
use WP_REST_Server;
/**
 * WordPress REST API error codes.
 *
 * @since 1.0.0
 *
 * @method static Response_Error INVALID_REQUEST()
 * @method static Response_Error INVALID_PARAM()
 * @method static Response_Error FORBIDDEN()
 * @method static Response_Error UNAUTHORIZED()
 * @method static Response_Error NOT_FOUND()
 * @method static Response_Error CANNOT_CREATE()
 * @method static Response_Error CANNOT_EDIT()
 * @method static Response_Error CANNOT_DELETE()
 * @method static Response_Error CANNOT_READ()
 * @method static Response_Error SERVER_ERROR()
 */
final class Response_Error
{
    use Has_Named_Constructors;
    /** @var string invalid request generic message 400 */
    public const INVALID_REQUEST = 'rest_invalid_request';
    /** @var string invalid parameter 400 */
    public const INVALID_PARAM = 'rest_invalid_param';
    /** @var string permission error 401 */
    public const FORBIDDEN = 'rest_forbidden';
    /** @var string unauthorized 403 */
    public const UNAUTHORIZED = 'rest_unauthorized';
    /** @var string not found 404 error */
    public const NOT_FOUND = 'rest_not_found';
    /** @var string can't POST */
    public const CANNOT_CREATE = 'rest_cannot_create';
    /** @var string can't PUT/PATCH/UPDATE */
    public const CANNOT_EDIT = 'rest_cannot_edit';
    /** @var string can't DELETE */
    public const CANNOT_DELETE = 'rest_cannot_delete';
    /** @var string can't read (GET) */
    public const CANNOT_READ = 'rest_cannot_view';
    /** @var string generic server error 500 */
    public const SERVER_ERROR = 'rest_internal_server_error';
    /**
     * Returns the error code for the current instance.
     *
     * @return int
     */
    public function status_code(): int
    {
        return self::get_status_code($this->name());
    }
    /**
     * Returns the status message for the current instance.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function status_message(): string
    {
        return self::get_status_message($this->name());
    }
    /**
     * Gets an HTTP status code from an error code.
     *
     * @since 1.0.0
     *
     * @param string $error_code
     * @return int
     */
    public static function get_status_code(string $error_code): int
    {
        $codes = [self::INVALID_REQUEST => Client_Error::BAD_REQUEST, self::INVALID_PARAM => Client_Error::BAD_REQUEST, self::UNAUTHORIZED => Client_Error::UNAUTHORIZED, self::FORBIDDEN => Client_Error::FORBIDDEN, self::NOT_FOUND => Client_Error::NOT_FOUND, self::CANNOT_CREATE => Client_Error::METHOD_NOT_ALLOWED, self::CANNOT_READ => Client_Error::METHOD_NOT_ALLOWED, self::CANNOT_EDIT => Client_Error::METHOD_NOT_ALLOWED, self::CANNOT_DELETE => Client_Error::METHOD_NOT_ALLOWED, self::SERVER_ERROR => Server_Error::INTERNAL_SERVER_ERROR];
        return $codes[$error_code] ?? Server_Error::INTERNAL_SERVER_ERROR;
    }
    /**
     * Gets an error message from an error code.
     *
     * @since 1.0.0
     *
     * @param string $error_code
     * @return string
     */
    public static function get_status_message(string $error_code): string
    {
        $messages = [self::INVALID_REQUEST => __('Invalid request.', Framework::textdomain()), self::INVALID_PARAM => __('Invalid parameter.', Framework::textdomain()), self::UNAUTHORIZED => __('Unauthorized.', Framework::textdomain()), self::FORBIDDEN => __('Forbidden.', Framework::textdomain()), self::NOT_FOUND => __('Resource not found.', Framework::textdomain()), self::CANNOT_CREATE => __('Cannot create resource.', Framework::textdomain()), self::CANNOT_READ => __('Cannot read resource.', Framework::textdomain()), self::CANNOT_EDIT => __('Cannot edit resource.', Framework::textdomain()), self::CANNOT_DELETE => __('Cannot delete resource.', Framework::textdomain()), self::SERVER_ERROR => __('Internal server error.', Framework::textdomain())];
        return $messages[$error_code] ?? $messages[self::SERVER_ERROR];
    }
    /**
     * Gets the corresponding error code for a REST transport method.
     *
     * @since 1.0.0
     *
     * @param string $method
     * @return string
     */
    public static function for_method(string $method): string
    {
        $codes = [WP_REST_Server::CREATABLE => self::CANNOT_CREATE, WP_REST_Server::READABLE => self::CANNOT_READ, WP_REST_Server::EDITABLE => self::CANNOT_EDIT, WP_REST_Server::DELETABLE => self::CANNOT_DELETE];
        return $codes[$method] ?? self::SERVER_ERROR;
    }
}
