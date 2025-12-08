<?php

namespace UkrSolution\ProductLabelsPrinting;

class CodeValidatorEx extends \Melgrati\CodeValidator\CodeValidator
{
    public static function calculateEANCheckDigit($code) {
        return parent::calculateEANCheckDigit($code);
    }
}
