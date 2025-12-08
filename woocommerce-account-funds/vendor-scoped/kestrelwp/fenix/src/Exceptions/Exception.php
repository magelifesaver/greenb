<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Exceptions;

defined('ABSPATH') or exit;
use Exception as Base_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger\Log_Level;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
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
    /** @var bool whether the exception should be logged */
    protected bool $loggable = \false;
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
        if ($this->is_loggable()) {
            $this->log();
        }
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
     * Whether the exception should be logged when an instance is created.
     *
     * Implementations of this class can override this method to control whether the exception should be logged upon instantiation.
     *
     * @since 1.7.1
     *
     * @return bool
     */
    protected function is_loggable(): bool
    {
        return $this->loggable;
    }
    /**
     * Logs the exception.
     *
     * @since 1.0.0
     *
     * @param mixed|null $context
     * @return void
     */
    public function log($context = null): void
    {
        if (!$this->is_loggable()) {
            return;
        }
        $log_level = $this->get_level();
        if (in_array($log_level, Log_Level::values(), \true)) {
            if (null === $context) {
                $previous = $this->get_previous();
                $context = $previous ? [$previous->getMessage()] : [];
            } elseif (is_object($context)) {
                if (method_exists($context, 'to_array')) {
                    $context = $context->to_array();
                } elseif (method_exists($context, 'toArray')) {
                    $context = $context->toArray();
                } elseif (method_exists($context, 'to_string')) {
                    $context = [$context->to_string()];
                } elseif (method_exists($context, '__toString')) {
                    $context = [$context->__toString()];
                }
            } elseif (is_scalar($context)) {
                $context = (array) $context;
            }
            $context = is_array($context) ? $context : [];
            Logger::$log_level($this->get_message(), null, $context);
        }
    }
}
