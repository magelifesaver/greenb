<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Exceptions;

defined('ABSPATH') or exit;
use Exception as Base_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Log_Level;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Throwable;
/**
 * Framework base exception.
 *
 * Exceptions thrown by the framework and plugins implementing the framework should extend this class.
 *
 * @since 1.0.0
 */
class Exception extends Base_Exception
{
    use Creates_New_Instances;
    /** @var int HTTP code used in HTTP context - {@see Client_Error} and {@see Server_Error} */
    protected $code = Server_Error::INTERNAL_SERVER_ERROR;
    /** @var string {@see Log_Level} used for logged exceptions */
    protected string $level = Log_Level::ERROR;
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        // @TODO implement exception handler and revert to base handler in __destruct method
        parent::__construct($message, $this->get_code(), $previous);
    }
    /**
     * Gets the code for the exception.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function get_code(): int
    {
        return $this->code;
    }
    /**
     * Gets the level for the exception.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_level(): string
    {
        return $this->level;
    }
    /**
     * Gets the message for the exception.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_message(): string
    {
        return $this->getMessage();
    }
    /**
     * Gets the previous exception, if present.
     *
     * @since 1.0.0
     *
     * @return Throwable|null
     */
    public function get_previous(): ?Throwable
    {
        return $this->getPrevious();
    }
    /**
     * Logs the exception.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function log(): void
    {
        $log_level = $this->get_level();
        if (in_array($log_level, Log_Level::values(), \true)) {
            Logger::$log_level($this->get_message());
        }
    }
}
