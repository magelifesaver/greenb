<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Traits;

trait HasTableName
{
    protected function getTableName(string $table_name): string
    {
        return $this->wpdb->prefix . $table_name;
    }
}
