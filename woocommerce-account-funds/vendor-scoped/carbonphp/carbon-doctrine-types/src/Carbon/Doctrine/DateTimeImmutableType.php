<?php

namespace Kestrel\Account_Funds\Scoped\Carbon\Doctrine;

use Kestrel\Account_Funds\Scoped\Carbon\CarbonImmutable;
use Kestrel\Account_Funds\Scoped\Doctrine\DBAL\Types\VarDateTimeImmutableType;
class DateTimeImmutableType extends VarDateTimeImmutableType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<CarbonImmutable> */
    use CarbonTypeConverter;
    /**
     * @return class-string<CarbonImmutable>
     */
    protected function getCarbonClassName(): string
    {
        return CarbonImmutable::class;
    }
}
