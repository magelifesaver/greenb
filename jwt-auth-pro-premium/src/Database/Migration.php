<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Database;

use Tmeister\JWTAuthPro\Traits\HasTableName;
use wpdb;

abstract class Migration
{
    use HasTableName;

    protected wpdb $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    abstract public function up(): void;

    abstract public function down(): void;
}
