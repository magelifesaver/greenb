<?php

namespace Kestrel\Account_Funds\Scoped\Psr\Clock;

use DateTimeImmutable;
interface ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object
     */
    public function now(): DateTimeImmutable;
}
