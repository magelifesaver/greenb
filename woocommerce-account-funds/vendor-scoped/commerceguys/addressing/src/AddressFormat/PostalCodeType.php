<?php

namespace Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\AddressFormat;

use Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\AbstractEnum;
/**
 * Enumerates available postal code types.
 *
 * @codeCoverageIgnore
 */
final class PostalCodeType extends AbstractEnum
{
    const EIR = 'eircode';
    const PIN = 'pin';
    const POSTAL = 'postal';
    const ZIP = 'zip';
    public static function getDefault(): string
    {
        return static::POSTAL;
    }
}
