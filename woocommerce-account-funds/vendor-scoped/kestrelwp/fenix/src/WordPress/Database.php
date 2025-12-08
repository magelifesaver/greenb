<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use wpdb;
/**
 * WordPress database class.
 *
 * @since 1.6.0
 */
class Database
{
    /** @var wpdb instance */
    protected wpdb $wpdb;
    /**
     * Constructs a new WordPress database instance.
     *
     * @since 1.6.0
     */
    protected function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb->hide_errors();
        // disable errors by default
    }
    /**
     * Returns the WordPress database instance.
     *
     * @since 1.6.0
     *
     * @return wpdb
     */
    public static function connect(): wpdb
    {
        return (new static())->wpdb;
        // @phpstan-ignore-line
    }
    /**
     * Performs a raw SQL query on the table and returns the results.
     *
     * @since 1.6.0
     *
     * @param string $query prepared SQL query to execute
     * @return array<mixed>
     */
    public static function query(string $query): array
    {
        return (array) static::connect()->get_results($query);
    }
    /**
     * Returns the WordPress database prefix.
     *
     * @since 1.6.0
     *
     * @param string|null $table optional table name to get the prefix for - if null, returns the default prefix
     * @return string
     */
    public static function prefix(?string $table = null): string
    {
        $prefix = static::connect()->prefix;
        if ($table !== null) {
            $table = Strings::string(trim($table));
            if ($table->starts_with($prefix)) {
                return $table->to_string();
            }
            return esc_sql($table->prepend($prefix)->to_string());
        }
        return $prefix;
    }
    /**
     * Returns the WordPress database charset.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function charset(): string
    {
        return static::connect()->charset;
    }
    /**
     * Returns the WordPress database collation.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function collation(): string
    {
        $instance = static::connect();
        if ($instance->has_cap('collation')) {
            return $instance->get_charset_collate();
        }
        return '';
    }
}
