<?php

namespace Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Validator\Constraints;

use Kestrel\Account_Funds\Scoped\Symfony\Component\Validator\Constraint;
/**
 * @Annotation
 */
class CountryConstraint extends Constraint
{
    public $message = 'This value is not a valid country.';
}
