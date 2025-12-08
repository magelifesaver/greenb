<?php

namespace Kestrel\Account_Funds\Scoped\Carbon\Doctrine;

use Kestrel\Account_Funds\Scoped\Carbon\Carbon;
use Kestrel\Account_Funds\Scoped\Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<Carbon> */
    use CarbonTypeConverter;
}
