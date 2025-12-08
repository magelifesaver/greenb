<?php

namespace Tmeister\JWTAuthPro\Database;

class MigrationManager
{
    private const MIGRATIONS = [
        '0.1.0' => Migrations\CreateAndSetupTokensTable::class,
    ];

    public function migrate(): void
    {
        $current_version = get_option('jwt_auth_pro_db_version', '0.0.0');

        foreach (self::MIGRATIONS as $version => $migration_class) {
            if (version_compare($current_version, $version, '<')) {
                $migration = new $migration_class();
                $migration->up();
            }
        }
    }

    public function rollback(): void
    {
        $current_version = get_option('jwt_auth_pro_db_version', '0.0.0');

        $migrations = array_reverse(self::MIGRATIONS);
        foreach ($migrations as $version => $migration_class) {
            if (version_compare($current_version, $version, '>=')) {
                $migration = new $migration_class();
                $migration->down();
            }
        }
    }
}
