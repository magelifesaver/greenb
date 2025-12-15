<?php

namespace ACA\WC\Export;

use ACA\WC\ListTable\Orders;
use ACP;
use Automattic;

class StrippedTableValue implements ACP\Export\Service
{

    protected $column;

    public function __construct(string $column_name)
    {
        $this->column = $column_name;
    }

    private function get_list_table(): Orders
    {
        return new Orders(
            wc_get_container()->get(Automattic\WooCommerce\Internal\Admin\Orders\ListTable::class)
        );
    }

    public function get_value($id): string
    {
        return strip_tags(
            $this->get_list_table()->render_cell($this->column, $id)
        );
    }

}