<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App\Models;

defined('ABSPATH') or exit;

abstract class Model
{
    /**
     * Table name and output type
     *
     * @var string
     */
    const TABLE_NAME = '', OUTPUT_TYPE = OBJECT;

    /**
     * Create the table
     *
     * @return void
     */
    public abstract function create();

    /**
     * Alter the table
     *
     * @return void
     */
    public abstract function alter();

    /**
     * Drop the table
     *
     * @return void
     */
    public function drop() {
        self::execDBQuery("DROP IF EXISTS TABLE {table};");
    }

    /**
     * Get wpdb instance
     */
    public static function db()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Get table name (with prefix)
     */
    public static function getTableName()
    {
        $table_prefix = self::db()->prefix;
        return $table_prefix . static::TABLE_NAME;
    }

    /**
     * Get charset collate
     */
    public static function getCharsetCollate() {
        return self::db()->get_charset_collate();
    }

    /**
     * Execute an query (to modify table)
     *
     * @param string $query
     * @return int|bool
     */
    protected static function execQuery($query)
    {
        return self::db()->query(str_replace('{table}', self::getTableName(), $query));
    }

    /**
     * Execute a database query (to modify database)
     *
     * @param string $query
     * @return array
     */
    protected static function execDBQuery($query)
    {
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        $search = ['{table}', '{charset_collate}'];
        $replace = [self::getTableName(), self::getCharsetCollate()];
        return dbDelta(str_replace($search, $replace, $query));
    }

    /**
     * Get Row
     *
     * @param array|string $where
     * @param array|null $where_format
     * @param array|null $columns
     * @return array
     */
    public static function getRow($where, $where_format = null, $columns = null) {
        return self::db()->get_row(self::prepareSelectQuery($where, $where_format, $columns), static::OUTPUT_TYPE);
    }

    /**
     * Get Row by ID
     *
     * @param int $id
     * @param array|null $columns
     * @return array
     */
    public static function getRowById($id, $columns = null) {
        return self::getRow(['id' => $id], ['%d'], $columns);
    }

    /**
     * Get Rows
     *
     * @param array|string $where
     * @param array|null $where_format
     * @param array|null $columns
     * @param array|null $args
     * @return array
     */
    public static function getRows($where, $where_format = null, $columns = null, $args = null) {
        return self::db()->get_results(self::prepareSelectQuery($where, $where_format, $columns, $args), static::OUTPUT_TYPE);
    }

    /**
     * Get Results
     *
     * @param string $query
     * @return array
     */
    public static function getResults($query) {
        return self::db()->get_results(str_replace('{table}', self::getTableName(), $query), static::OUTPUT_TYPE);
    }

    /**
     * Prepare select query
     *
     * @param array $where
     * @param array|null $where_format
     * @param array|string|null $columns
     * @param array|null $args
     * @return string
     */
    protected static function prepareSelectQuery($where, $where_format, $columns, $args = null)
    {
        $table = self::getTableName();
        $fields = self::prepareFieldsQuery($columns);

        if (!empty($where)) {
            $where_query = self::prepareWhereQuery($where, $where_format);
            $query = "SELECT $fields FROM $table $where_query";
        } else {
            $query = "SELECT $fields FROM $table";
        }

        if (is_array($args)) {
            if (isset($args['order_by'])) {
                $order_by = $args['order_by'];
                $sort = 'ASC';
                if (isset($args['sort']) && strtoupper($args['sort']) == 'DESC') {
                    $sort = 'DESC';
                }
                $query .= " ORDER BY `$order_by` $sort";
            }

            if (isset($args['limit'])) {
                $limit = $args['limit'];
                $query .= " LIMIT $limit";
            }

            if (isset($args['offset'])) {
                $offset = $args['offset'];
                $query .= " OFFSET $offset";
            }
        }

        return $query . ';';
    }

    /**
     * Prepare fields query
     *
     * @param $columns string|array|null
     */
    protected static function prepareFieldsQuery($columns)
    {
        if (is_string($columns)) {
            $fields = $columns;
        } elseif (is_array($columns) && !empty($columns)) {
            $fields = implode(', ', array_map(function ($column) {
                return "`$column`";
            }, $columns));
        } else {
            $fields = '*';
        }
        return $fields;
    }

    /**
     * Prepare where query
     *
     * @param array $where
     * @param array|null $where_format
     * @return string
     */
    protected static function prepareWhereQuery($where, $where_format)
    {
        if (is_string($where)) {
            return $where;
        }

        $i = 0;
        $data = [];
        $values = [];
        $conditions = [];
        if (is_array($where) && !empty($where)) {
            foreach ($where as $field => $value) {
                if (isset($where_format) && isset($where_format[$i])) {
                    $format = $where_format[$i];
                    $i++;
                } else {
                    $format = "%s";
                }
                $data[$field]['value'] = $value;
                $data[$field]['format'] = $format;
            }
        }

        foreach ($data as $field => $value) {
            if (is_null($value['value'])) {
                $conditions[] = "`$field` IS NULL";
                continue;
            }
            $conditions[] = "$field = " . $value['format'];
            $values[] = $value['value'];
        }
        $conditions = implode(' AND ', $conditions);
        return self::db()->prepare("WHERE $conditions", $values);
    }

    /**
     * Inserts a row into the table
     *
     * @param array $data
     * @param array $format
     * @param bool $return_id
     * @return int|false
     */
    public static function insert($data, $format = null, $return_id = true) {
        $result = self::db()->insert(self::getTableName(), $data, $format);
        return $result && $return_id ? self::db()->insert_id : $result;
    }

    /**
     * Updates a row into the table
     *
     * @param array $data
     * @param array $where
     * @param array|string $format
     * @param array|string $where_format
     * @return int|false
     */
    public static function update($data, $where, $format = null, $where_format = null) {
        return self::db()->update(self::getTableName(), $data, $where, $format, $where_format);
    }

    /**
     * Updates a row into the table by ID
     *
     * @param int $id
     * @param array $data
     * @param array|string $format
     * @return int|false
     */
    public static function updateById($id, $data, $format = null) {
        return self::update($data, ['id' => $id], $format, ['%d']);
    }

    /**
     * Deletes a row into the table
     *
     * @param array $where
     * @param array|string $where_format
     * @return int|false
     */
    public static function delete($where, $where_format = null) {
        return self::db()->delete(self::getTableName(), $where, $where_format);
    }

    /**
     * Deletes a row into the table by ID
     *
     * @param int $id
     * @return int|false
     */
    public static function deleteById($id) {
        return self::delete(['id' => $id], ['%d']);
    }
}