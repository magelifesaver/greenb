<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Enums;

enum EventStatus: int
{
    case SUCCESS = 1;
    case FAILURE = 2;

    public static function fromString(string $status): self
    {
        return match (strtolower($status)) {
            'failure' => self::FAILURE,
            default => self::SUCCESS,
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::FAILURE => 'failure',
        };
    }
}
