<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Exceptions\Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
use Throwable;
/**
 * Exception for settings.
 *
 * @since 1.1.0
 */
class Setting_Exception extends Exception
{
    /** @var string[] */
    protected array $messages = [];
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param string|string[] $message
     * @param Throwable|null $previous
     */
    public function __construct($message, ?Throwable $previous = null)
    {
        if ($previous && is_numeric($previous->getCode())) {
            $this->code = (int) $previous->getCode();
        } else {
            $this->code = Server_Error::INTERNAL_SERVER_ERROR;
        }
        if (is_array($message)) {
            $this->messages = array_filter(array_map('strval', $message));
        } else {
            $this->messages[] = strval($message);
        }
        parent::__construct($this->messages[0] ?: '', $previous);
    }
    /**
     * Returns all the exception messages.
     *
     * @since 1.8.0
     *
     * @return string[]
     */
    public function get_messages(): array
    {
        return $this->messages;
    }
}
