<?php

namespace Kestrel\Store_Credit\Scoped\CommerceGuys\Addressing\Validator\Constraints;

use Kestrel\Store_Credit\Scoped\Symfony\Component\Validator\Constraint;
/**
 * @Annotation
 */
class CountryConstraint extends Constraint
{
    public $message = 'This value is not a valid country.';
}
