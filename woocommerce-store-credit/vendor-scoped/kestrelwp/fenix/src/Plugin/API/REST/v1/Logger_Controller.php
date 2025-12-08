<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST\v1;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Log_Level;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST\Controller;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\API\REST\Response_Error;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
/**
 * Logger controller.
 *
 * Allows the frontend to log messages to the log file
 *
 * @since 1.0.0
 */
final class Logger_Controller extends Controller
{
    /** @var string rest base */
    protected $rest_base = 'log';
    /**
     * Registers the route to post a log message.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route($this->get_namespace(), $this->build_route(), [['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'create_item_permissions_check'], 'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Logs a message.
     *
     * @since 1.0.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        $log_id = $request->get_param('id');
        $level = $request->get_param('level');
        $message = $request->get_param('message');
        $context = $request->get_param('context');
        if (!$level) {
            /* translators: Context: Type of the debug message. E.g. 'warning', 'alert', 'emergency', etc. */
            return $this->error_response(Response_Error::INVALID_PARAM, __('Log level is required.', self::plugin()->textdomain()));
        } elseif (!in_array($level, Log_Level::values(), \true)) {
            /* translators: Context: Type of the debug message. E.g. 'warning', 'alert', 'emergency', etc. */
            return $this->error_response(Response_Error::INVALID_PARAM, __('Invalid log level.', self::plugin()->textdomain()));
        } elseif (!$message || !is_string($message)) {
            /* translators: Context: Text content of the debug message is a required value when logging an item to the debug log */
            return $this->error_response(Response_Error::INVALID_PARAM, __('Log message is required.', self::plugin()->textdomain()));
        }
        Logger::$level($message, is_string($log_id) && !empty($log_id) ? $log_id : null, (array) $context);
        return rest_ensure_response(['level' => $level, 'message' => $message, 'context' => $context]);
    }
    /**
     * Checks whether the user has permissions to write to log.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<mixed>> $request request object
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::CREATABLE, 'manage_options', $request);
    }
    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @link https://json-schema.org/ JSON Schema
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_public_item_schema(): array
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => $this->rest_base, 'type' => 'object', 'properties' => ['id' => [
            'type' => ['string', 'null'],
            /* translators: Context: Unique identifier for the log (optional) */
            'description' => __('The log ID (optional).', self::plugin()->textdomain()),
            'default' => null,
            'required' => \false,
        ], 'level' => [
            'type' => 'string',
            /* translators: Context: Type of the debug message. E.g. 'warning', 'alert', 'emergency', etc. */
            'description' => __('The log level. ', self::plugin()->textdomain()),
            'enum' => Log_Level::values(),
            'required' => \true,
        ], 'message' => [
            'type' => 'string',
            /* translators: Context: Debug log message */
            'description' => __('The log message.', self::plugin()->textdomain()),
            'required' => \true,
        ], 'context' => [
            'type' => ['object', 'null'],
            /* translators: Context: Debug log context, e.g. additional data to be logged to the debug log */
            'description' => __('The log context.', self::plugin()->textdomain()),
        ]]];
        return $this->add_additional_fields_schema($schema);
    }
}
