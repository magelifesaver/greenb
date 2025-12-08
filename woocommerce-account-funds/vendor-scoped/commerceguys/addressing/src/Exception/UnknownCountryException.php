<?php

namespace Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Exception;

/**
 * This exception is thrown when an unknown country code is passed to the
 * CountryRepository.
 */
class UnknownCountryException extends \InvalidArgumentException implements ExceptionInterface
{
}
