<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database;
use RuntimeException;
use stdClass;
/**
 * Object representation of a custom WordPress database table.
 *
 * @since 1.6.0
 *
 * @phpstan-consistent-constructor
 */
class Table
{
    /** @var string table name */
    protected string $name;
    /** @var string|null primary key column name */
    protected ?string $primary_key;
    /**
     * Constructs a new table instance.
     *
     * @since 1.6.0
     *
     * @param string $name unprefixed table name
     * @param string|null $primary_key optional primary key column name, defaults to 'id'
     */
    protected function __construct(string $name, ?string $primary_key = 'id')
    {
        $this->name = $this->set_name($name)->get_name();
        $this->primary_key = trim(esc_sql($primary_key)) ?: null;
    }
    /**
     * Creates a new table instance.
     *
     * @since 1.6.0
     *
     * @param string $name unprefixed table name
     * @return Table
     */
    public static function name(string $name): Table
    {
        return new static($name);
    }
    /**
     * Sets the primary key column name for the table.
     *
     * @since 1.6.0
     *
     * @param string|null $primary_key
     * @return $this
     */
    public function set_primary_key(?string $primary_key): Table
    {
        $primary_key = $primary_key ? trim(esc_sql($primary_key)) : null;
        $this->primary_key = $primary_key ?: null;
        return $this;
    }
    /**
     * Returns the primary key column name.
     *
     * @since 1.6.0
     *
     * @return string|null
     */
    public function get_primary_key(): ?string
    {
        return $this->primary_key;
    }
    /**
     * Returns the table name (prefixed).
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Sets the table name.
     *
     * @since 1.6.0
     *
     * @param string $name
     * @return $this
     */
    public function set_name(string $name): Table
    {
        $this->name = esc_sql(Database::prefix($name));
        return $this;
    }
    /**
     * Determines if the table exists in the database.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public function exists(): bool
    {
        $result = Database::connect()->get_row("SHOW TABLES LIKE '{$this->name}'");
        return !empty($result);
    }
    /**
     * Creates the table in the database.
     *
     * @since 1.6.0
     *
     * @param string $schema SQL create table schema
     * @return void
     * @throws RuntimeException if the table creation fails
     */
    public function create(string $schema): void
    {
        $schema = trim($schema);
        if (empty($schema)) {
            return;
        }
        if (!function_exists('dbDelta')) {
            require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
            // @phpstan-ignore-line WordPress constant
        }
        $database = Database::connect();
        $name = $this->get_name();
        $collation = $database->has_cap('collation') ? $database->get_charset_collate() : '';
        $sql = "CREATE TABLE {$name} (" . "\n" . $schema . "\n" . ') ENGINE=INNODB ' . $collation . ';' . "\n";
        // phpcs:ignore
        $results = dbDelta($sql);
        $error = $database->last_error;
        if (empty($results) || !empty($error)) {
            if ($error) {
                throw new RuntimeException(esc_html(ucfirst($error)));
            } elseif ('' !== trim($name)) {
                throw new RuntimeException(esc_html(sprintf('Unknown error while creating table "%s".', $name)));
            } else {
                throw new RuntimeException('Could not create table without a name.');
            }
        }
    }
    /**
     * Inserts a new row into the table.
     *
     * @since 1.6.0
     *
     * @param array<string, array<mixed>|scalar> $values associative array of column names and values to insert
     * @param array<int, string> $format optional formats for the values
     * @return int|null ID of the inserted row or false if the insert failed
     */
    public function insert(array $values, array $format = []): ?int
    {
        $database = Database::connect();
        $result = $database->insert($this->name, $values, $format);
        if (empty($result)) {
            return null;
        }
        return $database->insert_id;
    }
    /**
     * Replaces a row in the table.
     *
     * This method is similar to insert, but it will replace an existing row if it matches the primary key.
     *
     * @since 1.6.0
     *
     * @param array<string, array<mixed>|scalar> $values associative array of column names and values to replace
     * @param array<int, string> $format optional formats for the values
     * @return int affected rows count
     */
    public function replace(array $values, array $format = []): int
    {
        return (int) Database::connect()->replace($this->name, $values, $format);
    }
    /**
     * Updates rows in the table.
     *
     * @since 1.6.0
     *
     * @param array<string, array<mixed>|scalar> $values associative array of column names and values to update
     * @param array<int, string> $format formats for the values
     * @param array<int|string, mixed> $where associative array of column names and values to match for update
     * @param array<int, string> $where_format optional formats for the where values
     * @return int affected rows count
     */
    public function update(array $values, array $format, array $where, array $where_format = []): int
    {
        return (int) Database::connect()->update($this->name, $values, $where, $format, $where_format);
    }
    /**
     * Deletes rows from the table.
     *
     * @since 1.6.0
     *
     * @param array<string, array<mixed>|scalar> $where associative array of column names and values to match for deletion
     * @param array<int, string> $where_format optional formats for the where values
     * @return int number of rows deleted
     */
    public function delete(array $where, array $where_format = []): int
    {
        return (int) Database::connect()->delete($this->name, $where, $where_format);
    }
    /**
     * Drops the table from the database.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public function drop(): bool
    {
        return (bool) Database::connect()->query("DROP TABLE IF EXISTS `{$this->name}`");
    }
    /**
     * Returns data for one row from the table.
     *
     * @since 1.6.0
     *
     * @param string $select_clauses prepared SQL clauses that will be appended to the SELECT statement, e.g. `"WHERE id = %d"`
     * @param mixed ...$args arguments to prepare the query with, e.g. `123`
     * @return stdClass|null
     */
    public function get_row(string $select_clauses, ...$args): ?object
    {
        if (empty($select_clauses)) {
            return null;
        }
        $database = Database::connect();
        $select_clauses = "SELECT * FROM {$this->name} " . $select_clauses . ' LIMIT 1';
        if (empty($args)) {
            $query = esc_sql($select_clauses);
        } else {
            $query = $database->prepare($select_clauses, ...$args);
        }
        $result = $database->get_row($query);
        // phpcs:ignore
        if (!$result) {
            return null;
        }
        return $result;
    }
    /**
     * Returns data for multiple rows from the table.
     *
     * @since 1.6.0
     *
     * @param string $select_clauses SQL query to execute, e.g. `"WHERE status = %s ORDER BY created_at DESC"`
     * @param mixed ...$args arguments to prepare the query with, e.g. `'active'`
     * @return array<int|string, object>
     */
    public function get_rows(string $select_clauses = '', ...$args): array
    {
        $database = Database::connect();
        $select_clauses = "SELECT * FROM {$this->name} " . $select_clauses;
        if (empty($args)) {
            $query = esc_sql($select_clauses);
        } else {
            $query = $database->prepare($select_clauses, ...$args);
        }
        // phpcs:ignore
        $results = $database->get_results($query, \OBJECT_K);
        // @phpstan-ignore-line WordPress constant
        return (array) $results;
    }
    /**
     * Counts the number of rows in the table based on a column and optional conditions.
     *
     * @since 1.7.1
     *
     * @param string $column column name to count, e.g. 'id'
     * @param string $select_clauses optional SQL clauses to filter the count, e.g. 'WHERE status = %s'
     * @param mixed ...$args arguments to prepare the query with (optional)
     * @return int number of rows counted
     */
    public function count_rows(string $column = 'id', string $select_clauses = '', ...$args): int
    {
        if (empty($column)) {
            return 0;
        }
        $database = Database::connect();
        if (empty($args)) {
            $select_clauses = esc_sql($select_clauses);
        } else {
            $select_clauses = $database->prepare($select_clauses, ...$args);
        }
        $result = $database->get_var("SELECT COUNT({$column}) FROM {$this->name} " . $select_clauses);
        // phpcs:ignore
        return (int) $result;
    }
    /**
     * Returns one value from the table based on a raw SQL query.
     *
     * @since 1.8.0
     *
     * @param string $query
     * @param int|null $limit
     * @return string|null
     */
    public function get_value(string $query, ?int $limit = 1): ?string
    {
        $query .= " FROM {$this->name}";
        if (is_int($limit) && $limit > 0) {
            $query .= " LIMIT {$limit}";
        }
        $result = Database::connect()->get_var($query);
        return null !== $result ? (string) $result : null;
        // phpcs:ignore
    }
}
