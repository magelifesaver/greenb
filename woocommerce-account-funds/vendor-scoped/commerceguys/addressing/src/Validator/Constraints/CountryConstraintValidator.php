<?php

namespace Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Validator\Constraints;

use Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Country\CountryRepository;
use Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Kestrel\Account_Funds\Scoped\Symfony\Component\Validator\Constraint;
use Kestrel\Account_Funds\Scoped\Symfony\Component\Validator\ConstraintValidator;
use Kestrel\Account_Funds\Scoped\Symfony\Component\Validator\Exception\UnexpectedTypeException;
class CountryConstraintValidator extends ConstraintValidator
{
    /**
     * The country repository.
     *
     * @var CountryRepositoryInterface
     */
    protected $countryRepository;
    public function __construct(CountryRepositoryInterface $countryRepository = null)
    {
        $this->countryRepository = $countryRepository ?: new CountryRepository();
    }
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }
        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }
        $countries = $this->countryRepository->getList();
        $value = (string) $value;
        if (!isset($countries[$value])) {
            $this->context->buildViolation($constraint->message)->setParameter('{{ value }}', $this->formatValue($value))->addViolation();
        }
    }
}
