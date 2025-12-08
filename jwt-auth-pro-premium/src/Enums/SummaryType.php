<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Enums;

enum SummaryType: int
{
    case TOKEN_STATS = 1;
    case ANALYTICS = 2;

    public static function fromString(string $type): self
    {
        return match (strtolower($type)) {
            'token_stats' => self::TOKEN_STATS,
            default => self::ANALYTICS,
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::TOKEN_STATS => 'token_stats',
            self::ANALYTICS => 'analytics',
        };
    }
}
