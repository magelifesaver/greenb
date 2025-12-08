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

class RuleCollections extends Model
{
    /**
     * Table name and output type
     *
     * @var string
     */
    const TABLE_NAME = 'wdr_rule_collections', OUTPUT_TYPE = OBJECT;

    /**
     * Create the table
     *
     * @return void
     */
    public function create()
    {
        $query = "CREATE TABLE {table} (
		        `rule_id` int(11) NOT NULL,
                `collection_id` int(11) NOT NULL,
                UNIQUE KEY (`rule_id`, `collection_id`)
			) {charset_collate};";
        self::execDBQuery($query);
    }

    /**
     * Alter the table
     *
     * @return void
     */
    public function alter() {}
}