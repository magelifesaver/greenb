<?php

namespace Tmeister\JWTAuthPro\Helpers;

class JWT extends \Firebase\JWT\JWT
{
    public static function getSupportedAlgorithms(): array
    {
        return array_keys(self::$supported_algs);
    }

    public static function isSupportedAlgorithm(string $algorithm): bool
    {
        return in_array($algorithm, self::getSupportedAlgorithms(), true);
    }
}
