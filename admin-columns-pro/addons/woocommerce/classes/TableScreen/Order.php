<?php

declare(strict_types=1);

namespace ACA\WC\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use AC\Type\Labels;
use AC\Type\TableId;
use ACA\WC\ListTable\Orders;
use ACA\WC\Type\OrderTableUrl;
use Automattic;

class Order extends TableScreen implements ListTable
{

    public function __construct()
    {
        parent::__construct(
            new TableId('wc_order'),
            'woocommerce_page_wc-orders',
            new Labels(
                __('Order', 'woocommerce'),
                __('Orders', 'woocommerce')
            ),
            new OrderTableUrl()
        );
    }

    public function list_table(): AC\ListTable
    {
        return new Orders(
            wc_get_container()->get(Automattic\WooCommerce\Internal\Admin\Orders\ListTable::class)
        );
    }

}